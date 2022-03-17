<?php
/**
 * Wincache.php
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

use InitPHP\Cache\Exception\InvalidArgumentException;

use function is_string;
use function is_int;
use function extension_loaded;
use function ini_get;
use function wincache_ucache_get;
use function wincache_ucache_set;
use function wincache_ucache_delete;
use function wincache_ucache_clear;
use function wincache_ucache_inc;
use function wincache_ucache_dec;

class Wincache extends \InitPHP\Cache\BaseHandler implements \InitPHP\Cache\CacheInterface
{
    /** @var array */
    protected $_HandlerOption = [
        'default_ttl'   => 60,
    ];

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
        $success = false;
        $data = wincache_ucache_get($name, $success);
        return $success ? $data : $this->reDefault($default);
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
        return (bool)wincache_ucache_set($name, $value, $ttl);
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
        return (bool)wincache_ucache_delete($name);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return (bool)wincache_ucache_clear();
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
        $success = false;
        $data = wincache_ucache_get($name, $success);
        return $success !== FALSE;
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
        $name = $this->getOption('prefix') . $name;
        $this->validationName($name);
        return (int)wincache_ucache_inc($name, $offset);
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
        $name = $this->getOption('prefix') . $name;
        $this->validationName($name);
        return (int)wincache_ucache_dec($name, $offset);
    }

    public function isSupported()
    {
        return (extension_loaded('wincache') && ini_get('wincache.ucenabled'));
    }

}
