<?php
/**
 * File.php
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

use \InitPHP\Cache\Exception\CacheException;
use \InitPHP\Cache\Exception\InvalidArgumentException;

use const DIRECTORY_SEPARATOR;

use function ltrim;
use function rtrim;
use function file_get_contents;
use function file_put_contents;
use function chmod;
use function is_file;
use function is_dir;
use function rmdir;
use function unlink;
use function basename;
use function is_array;
use function is_string;
use function is_int;
use function is_float;
use function in_array;
use function glob;
use function time;
use function serialize;
use function unserialize;

class File extends \InitPHP\Cache\BaseHandler implements \InitPHP\Cache\CacheInterface
{

    /** @var array */
    protected $_HandlerOption = [
        'path'      => null,
        'mode'      => 0640,
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
        if(($data = $this->read($name)) === FALSE){
            return $this->reDefault($default);
        }
        if(isset($data['data'])){
            return $data['data'];
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
        $path = $this->realPath($name);
        $data = serialize([
            'time'  => time(),
            'ttl'   => $ttl,
            'data'  => $value
        ]);
        return $this->write($path, $data);
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
        $path = $this->realPath($name);
        if(!is_file($path)){
            return true;
        }
        return (@unlink($path)) !== FALSE;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->dirClear(null);
        return true;
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
        $read = $this->read($name);
        return $read !== FALSE;
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
        $name = $this->getOption('prefix', null) . $name;
        $this->validationName($name);
        $data = $this->read($name);
        if($data === FALSE || !isset($data['data'])){
            return 0;
        }
        if(!is_int($data['data']) && !is_float($data['data'])){
            return 0;
        }
        $data['data'] += $offset;
        $path = $this->realPath($name);
        if($this->write($path, serialize($data))){
            return $offset;
        }
        return 0;
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
        $name = $this->getOption('prefix', null) . $name;
        $this->validationName($name);
        $data = $this->read($name);
        if($data === FALSE || !isset($data['data'])){
            return 0;
        }
        if(!is_int($data['data']) && !is_float($data['data'])){
            return 0;
        }
        $data['data'] -= $offset;
        $path = $this->realPath($name);
        if($this->write($path, serialize($data))){
            return $offset;
        }
        return 0;
    }

    public function isSupported()
    {
        return true;
    }

    /**
     * @param string $path
     * @param string $content
     * @return bool
     */
    private function write($path, $content)
    {
        $write = @file_put_contents($path, $content);
        if($write !== FALSE){
            @chmod($path, $this->getOption('mode', 0640));
            return true;
        }
        return false;
    }

    /**
     * @param string $name
     * @return false|array
     * @throws InvalidArgumentException
     */
    private function read($name)
    {
        $path = $this->realPath($name);
        if(!is_file($path)){
            return false;
        }
        if(($read = @file_get_contents($path)) === FALSE){
            return false;
        }
        $data = @unserialize($read);
        if(!isset($data['ttl']) || !isset($data['time'])){
            return false;
        }
        if(($data['ttl'] > 0 && time() > $data['time'] + $data['ttl']) || !isset($data['data'])){
            $this->delete($name);
            return false;
        }
        return $data;
    }

    /**
     * @param string|null $path
     * @return void
     * @throws CacheException
     */
    private function dirClear($path)
    {
        if($path === null){
            $path = $this->getOption('path', null);
            if($path === null){
                throw new CacheException('The caching directory must be defined.');
            }
            $path = rtrim($path, '\\/')
                . DIRECTORY_SEPARATOR
                . $this->getOption('prefix', null)
                . '*';
            $files = glob($path);
        }elseif(!is_dir($path)){
            return;
        }else{
            $pattern = rtrim($path, '/\\')
                . DIRECTORY_SEPARATOR
                . $this->getOption('prefix', '')
                . '*';

            $files = glob($pattern);
        }
        if(!is_array($files)){
            return;
        }
        foreach ($files as $file) {
            if(is_dir($file)){
                $this->dirClear($path);
                @rmdir($path);
                continue;
            }
            $basename = basename($file);
            if(in_array($basename, ['.htaccess', 'index.htm', 'index.html', 'index.php', 'web.config'], true)){
                continue;
            }
            @unlink($file);
        }
    }

    /**
     * @param string $name
     * @return string
     * @throws CacheException
     */
    private function realPath($name)
    {
        $path = $this->getOption('path', null);
        if($path === null){
            throw new CacheException('The caching directory must be defined.');
        }
        return rtrim($path, '\\/')
            . DIRECTORY_SEPARATOR
            . ltrim($name, '\\/');
    }

}
