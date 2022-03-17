<?php
/**
 * Redis.php
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

use function extension_loaded;
use function is_string;
use function is_int;
use function serialize;
use function time;
use function gettype;
use function unserialize;

class Redis extends \InitPHP\Cache\BaseHandler implements \InitPHP\Cache\CacheInterface
{

    protected $_HandlerOption = [
        'host'      => '127.0.0.1',
        'password'  => null,
        'port'      => 6379,
        'timeout'   => 0,
        'database'  => 0,
    ];

    /** @var \Redis */
    protected $redis;

    public function __destruct()
    {
        if(isset($this->redis)){
            unset($this->redis);
        }
    }

    /**
     * @return \Redis
     */
    public function getRedis()
    {
        if(isset($this->redis)){
            return $this->redis;
        }
        $this->redis = new \Redis();
        try {
            if(!$this->redis->connect($this->getOption('host'), $this->getOption('port'), $this->getOption('timeout'))){
                throw new \Exception('Redis Cache connection failed.');
            }
            $password = $this->getOption('password', null);
            if($password !== null && !$this->redis->auth($password)){
                throw new \Exception('Redis Cache authentication failed.');
            }
            $database = $this->getOption('database', null);
            if($database !== null && !$this->redis->select($database)){
                throw new \Exception('Redis Cache : The database could not be selected.');
            }
        }catch (\RedisException $e) {
            $error = 'A redis exception is caught : ' . $e->getMessage();
            throw new \RuntimeException($error);
        }catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
        return $this->redis;
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
        if(($data = $this->getRedis()->get($name)) !== FALSE){
            $data = unserialize($data);
            if(isset($data['__cache_type'], $data['__cache_value'])){
                return $data['__cache_value'];
            }
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
        if(($ttl = $this->ttlCalc($ttl)) === FALSE){
            return false;
        }
        $name = $this->getOption('prefix') . $key;
        $this->validationName($name);
        $type = gettype($value);
        switch($type){
            case 'array':
            case 'object':
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
            case 'NULL':
                break;
            case 'resource':
            default:
                return false;
        }
        if(!($this->getRedis()->set($name, serialize(['__cache_type' => $type, '__cache_value' => $value])))){
            return false;
        }
        if($ttl !== null){
            $ttl = time() + $ttl;
            $this->getRedis()->expireAt($name, $ttl);
        }
        return true;
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
        return $this->getRedis()->del($name) === 1;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return $this->getRedis()->flushDB();
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
        return ($this->getRedis()->exists($name) !== FALSE);
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
        return $this->getRedis()->incrBy($name, $offset);
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
        return $this->getRedis()->incrBy($name, -($offset));
    }

    public function isSupported()
    {
        return extension_loaded('redis');
    }

}
