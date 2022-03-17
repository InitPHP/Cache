<?php
/**
 * BaseHandler.php
 *
 * This file is part of InitPHP.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    0.1
 * @link       https://www.muhammetsafak.com.tr
 */

namespace InitPHP\Cache;

use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Exception\InvalidArgumentException;

use const CASE_LOWER;

use function array_merge;
use function array_change_key_case;
use function is_callable;
use function call_user_func_array;
use function is_int;
use function time;

abstract class BaseHandler implements CacheInterface
{

    protected $_Options = [
        'prefix'        => 'cache_',
    ];

    public function __construct(array $options = [])
    {
        if($this->isSupported() === FALSE){
            throw new CacheException('In order to use this caching method, the necessary plugins must be installed/active.');
        }
        $this->_Options = array_merge(array_change_key_case($this->_HandlerOption, CASE_LOWER), array_change_key_case($options, CASE_LOWER));
    }

    public function setOptions($options = [])
    {
        if(!empty($options)){
            $this->_Options = array_merge($this->_Options, array_change_key_case($options, CASE_LOWER));
        }
        return $this;
    }

    public function getOption($key, $default = null)
    {
        $key = strtolower($key);
        return isset($this->_Options[$key]) ? $this->_Options[$key] : $default;
    }

    public function options(array $options = [])
    {
        return empty($options) ? $this->_Options : array_merge($this->_Options, array_change_key_case($options, CASE_LOWER));
    }

    /**
     * @inheritDoc
     */
    abstract public function get($key, $default = null);

    /**
     * @inheritDoc
     */
    abstract public function set($key, $value, $ttl = null);

    /**
     * @inheritDoc
     */
    abstract public function delete($key);

    /**
     * @inheritDoc
     */
    abstract public function clear();

    /**
     * @inheritDoc
     */
    abstract public function has($key);

    /**
     * @inheritDoc
     */
    abstract public function increment($name, $offset = 1);

    /**
     * @inheritDoc
     */
    abstract public function decrement($name, $offset = 1);

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        if(!is_array($keys)){
            throw new InvalidArgumentException("\$keys must be an array.");
        }
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        if(!is_array($values)){
            throw new InvalidArgumentException("\$values must be an array.");
        }
        if(!empty($values)){
            foreach ($values as $key => $data) {
                $this->set($key, $data, $ttl);
            }
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        if(!is_array($keys)){
            throw new InvalidArgumentException("\$keys must be an array.");
        }
        if(!empty($keys)){
            foreach ($keys as $key) {
                $this->delete($key);
            }
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    abstract public function isSupported();

    /**
     * @param string $name
     * @param string $chars
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validationName($name, $chars = '{}()/\\@:')
    {
        if(strpbrk($name, $chars) !== FALSE){
            throw new InvalidArgumentException('Cache name cannot contain "' . $chars . '" characters.');
        }
    }

    /**
     * @param null|int|\DateInterval $ttl
     * @return null|int|false
     */
    protected function ttlCalc($ttl = null)
    {
        if($ttl === null){
            return $ttl;
        }
        if($ttl instanceof \DateInterval){
            $ttl = $ttl->format('U') - time();
        }
        if(!is_int($ttl)){
            throw new InvalidArgumentException("\$ttl can be an integer, NULL, or a \DateInterval object.");
        }
        if($ttl < 0){
            return false;
        }
        return $ttl;
    }

    protected function reDefault($default = null)
    {
        return is_callable($default) ? call_user_func_array($default, []) : $default;
    }

}
