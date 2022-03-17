<?php
/**
 * Memcache.php
 *
 * This file is part of InitPHP.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    0.1
 * @link       https://www.muhammetsafak.com.tr
 */

namespace InitPHP\Cache\Handler;

use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Exception\InvalidArgumentException;

use function class_exists;
use function is_string;
use function is_int;
use function is_array;
use function time;
use function extension_loaded;

class Memcache extends \InitPHP\Cache\BaseHandler implements \InitPHP\Cache\CacheInterface
{
    /** @var array */
    protected $_HandlerOption = [
        'host'          => '127.0.0.1',
        'port'          => 11211,
        'weight'        => 1,
        'raw'           => false,
        'default_ttl'   => 60,
    ];

    /** @var \Memcache|\Memcached */
    protected $memcache;

    public function __destruct()
    {
        if(isset($this->memcache)){
            if($this->memcache instanceof \Memcached){
                $this->memcache->quit();
            }elseif($this->memcache instanceof \Memcache){
                $this->memcache->close();
            }
            unset($this->memcache);
        }
    }

    protected function getMemcache()
    {
        if(isset($this->memcache)){
            return $this->memcache;
        }
        try {
            if(class_exists(\Memcache::class)){
                $this->memcache = new \Memcache();
                if($this->memcache->connect(
                        $this->getOption('host'),
                        $this->getOption('port')
                    ) === FALSE){
                    throw new \Exception('Memcache connection failed.');
                }
                $this->memcache->addServer(
                    $this->getOption('host'),
                    $this->getOption('port'),
                    true,
                    $this->getOption('weight')
                );
                return $this->memcache;
            }

            if(class_exists(\Memcached::class)){
                $this->memcache = new \Memcached();
                if($this->getOption('raw', false)){
                    $this->memcache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
                }
                $this->memcache->addServer(
                    $this->getOption('host'),
                    $this->getOption('port'),
                    $this->getOption('weight')
                );
                $stats = $this->memcache->getStats();

                $statKey = $this->getOption('host') . ':' . $this->getOption('port');
                if(!isset($stats[$statKey])){
                    throw new \Exception('Memcached connection failed.');
                }
                return $this->memcache;
            }

            throw new \Exception('Not supported Memcache or Memcahed extension.');
        }catch (\Exception $e) {
            throw new CacheException(__CLASS__ . ' : ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        if(!is_string($key)){
            throw new InvalidArgumentException('The requested cache name/key must be a string.');
        }
        $name = $this->getOption('prefix') . $key;
        $this->validationName($name);
        $memcache = $this->getMemcache();
        if($memcache instanceof \Memcache){
            $flags = false;
            $data = $memcache->get($name, $flags);
            if($flags === FALSE){
                return $this->reDefault($default);
            }
            return is_array($data) ? $data[0] : $data;
        }
        if($memcache instanceof \Memcached){
            $data = $memcache->get($name);
            if($memcache->getResultCode() === \Memcached::RES_NOTFOUND){
                return $this->reDefault($default);
            }
            return is_array($data) ? $data[0] : $data;
        }
        return $this->reDefault($default);
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        if(!is_string($key)){
            throw new InvalidArgumentException('The requested cache name/key must be a string.');
        }
        $name = $this->getOption('prefix') . $key;
        $this->validationName($name);
        if(($ttl = $this->ttlCalc($ttl)) === FALSE){
            return false;
        }
        if($ttl === null){
            $ttl = $this->getOption('default_ttl', 60);
        }
        if($this->getOption('raw') === FALSE){
            $value = [
                $value,
                time(),
                $ttl
            ];
        }
        $memcache = $this->getMemcache();
        if($memcache instanceof \Memcache){
            return (bool)$memcache->set($name, $value, 0, $ttl);
        }
        if($memcache instanceof \Memcached){
            return (bool)$memcache->set($name, $value, $ttl);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        if(!is_string($key)){
            throw new InvalidArgumentException('The requested cache name/key must be a string.');
        }
        $name = $this->getOption('prefix') . $key;
        $this->validationName($name);
        return (bool)$this->getMemcache()->delete($name);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return (bool)$this->getMemcache()->flush();
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        if(!is_string($key)){
            throw new InvalidArgumentException('The requested cache name/key must be a string.');
        }
        $name = $this->getOption('prefix') . $key;
        $this->validationName($name);
        $memcache = $this->getMemcache();
        if($memcache instanceof \Memcache){
            $flags = false;
            $data = $memcache->get($name, $flags);
            return $flags !== FALSE;
        }
        if($memcache instanceof \Memcached){
            $data = $memcache->get($name);
            return $memcache->getResultCode() === \Memcached::RES_SUCCESS;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function increment($name, $offset = 1)
    {
        if(!is_string($name)){
            throw new InvalidArgumentException('The requested cache name/key must be a string.');
        }
        if(!is_int($offset)){
            throw new InvalidArgumentException("\$offset must be an integer.");
        }
        if($this->getOption('raw', false) === FALSE){
            return 0;
        }
        $name = $this->getOption('prefix') . $name;
        $this->validationName($name);
        return (int)$this->getMemcache()->increment($name, $offset, $offset, $this->getOption('default_ttl', 60));
    }

    /**
     * @inheritDoc
     */
    public function decrement($name, $offset = 1)
    {
        if(!is_string($name)){
            throw new InvalidArgumentException('The requested cache name/key must be a string.');
        }
        if(!is_int($offset)){
            throw new InvalidArgumentException("\$offset must be an integer.");
        }
        if($this->getOption('raw', false) === FALSE){
            return 0;
        }
        $name = $this->getOption('prefix') . $name;
        $this->validationName($name);
        return (int)$this->getMemcache()->decrement($name, $offset, $offset, $this->getOption('default_ttl', 60));
    }

    public function isSupported()
    {
        return (extension_loaded('memcache') || extension_loaded('memcached'));
    }

}
