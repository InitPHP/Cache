<?php
/**
 * PDO.php
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

use function serialize;
use function unserialize;
use function time;
use function extension_loaded;
use function is_int;
use function is_float;
use function is_string;

class PDO extends \InitPHP\Cache\BaseHandler implements \InitPHP\Cache\CacheInterface
{
    /** @var array */
    protected $_HandlerOption = [
        'dsn'           => 'mysql:host=localhost;dbname=test',
        'username'      => null,
        'password'      => null,
        'charset'       => 'utf8mb4',
        'collation'     => 'utf8mb4_general_ci',
        'table'         => 'cache'
    ];

    /** @var \PDO */
    protected $pdo;

    public function __destruct()
    {
        if(isset($this->pdo)){
            $this->pdo = null;
            unset($this->pdo);
        }
    }

    /**
     * @return \PDO
     * @throws CacheException
     */
    protected function getPDO()
    {
        if(isset($this->pdo)){
            return $this->pdo;
        }
        try {
            $this->pdo = new \PDO($this->getOption('dsn'),
                $this->getOption('username'),
                $this->getOption('password'), [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            $this->pdo->exec("SET NAMES '" . $this->getOption('charset', 'utf8mb4') . "' COLLATE '" . $this->getOption('collation', 'utf8mb4_general_ci') . "'");
            $this->pdo->exec("SET CHARACTER SET '".$this->getOption('charset', 'utf8mb4')."'");
        }catch (\PDOException $e) {
            throw new CacheException('PDO Cache Connection Error : ' . $e->getMessage());
        }
        return $this->pdo;
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        if(!is_string($key)){
            throw new InvalidArgumentException('The requested cache name/key must be a string.');
        }
        if(($read = $this->read($key)) === FALSE){
            return $this->reDefault($default);
        }
        return $read['data'];
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
        $this->validationName($name, '{}()/\\@\'"');
        if(($ttl = $this->ttlCalc($ttl)) === FALSE){
            return false;
        }
        if($ttl !== null){
            $ttl += time();
        }
        $sql = 'INSERT INTO ' . $this->getOption("table") . ' ' . ($ttl === null ? '(name, ttl, data) VALUES (?, NULL, ?)' : '(name, ttl, data) VALUES (?, ?, ?)') . ';';
        $data = [$name];
        if($ttl !== null){
            $data[] = $ttl;
        }
        $data[] = serialize($value);
        try {
            $query = $this->getPDO()->prepare($sql);
            return (bool)$query->execute($data);
        }catch (\PDOException $e) {
            return false;
        }
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
        $this->validationName($name, '{}()/\\@\'"');
        $sql = 'DELETE FROM '.$this->getOption('table').' WHERE name=?;';
        try {
            $query = $this->getPDO()->prepare($sql);
            return (bool)$query->execute([$name]);
        }catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $prefix = $this->getOption('prefix');
        $table = $this->getOption('table');
        $sql = 'DELETE FROM ' . $table . ' '
            . ($prefix === NULL ? 'WHERE 1;' : 'WHERE name LIKE ?');
        try {
            $query = $this->getPDO()->prepare($sql);
            return (bool)($prefix === null ? $query->execute() : $query->execute([$prefix . '%']));
        }catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        if(!is_string($key)){
            throw new InvalidArgumentException('The requested cache name/key must be a string.');
        }
        return $this->read($key) !== FALSE;
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
        if(($read = $this->read($name)) === FALSE){
            return 0;
        }
        if(!isset($read['data']) || (!is_int($read['data']) && !is_float($read['data']))){
            return 0;
        }
        $read['data'] += $offset;
        if($this->update($name, $read['data']) === FALSE){
            return 0;
        }
        return $read['data'];
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
        if(($read = $this->read($name)) === FALSE){
            return 0;
        }
        if(!isset($read['data']) || (!is_int($read['data']) && !is_float($read['data']))){
            return 0;
        }
        $read['data'] -= $offset;
        if($this->update($name, $read['data']) === FALSE){
            return 0;
        }
        return $read['data'];
    }

    public function isSupported()
    {
        return extension_loaded('pdo');
    }

    /**
     * @param string $key
     * @return false|array
     * @throws InvalidArgumentException|CacheException
     */
    private function read($key)
    {
        $name = $this->getOption('prefix') . $key;
        $this->validationName($name, '{}()/\\@\'"');
        try {
            $query = $this->getPDO()->prepare('SELECT * FROM ' . $this->getOption('table') . ' WHERE name=?');
            if($query->execute([$name]) === FALSE){
                return false;
            }
            $res = $query->fetch(\PDO::FETCH_ASSOC);
            if(!empty($res['ttl']) && $res['ttl'] < time()){
                $this->delete($key);
                return false;
            }
            $res['data'] = unserialize($res['data']);
            return $res;
        }catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param mixed $data
     * @return bool
     * @throws CacheException
     */
    private function update($key, $data)
    {
        $name = $this->getOption('prefix') . $key;
        try {
            $query = $this->getPDO()->prepare('UPDATE ' . $this->getOption("table") . ' SET data=? WHERE name=?;');
            return (bool)$query->execute([serialize($data), $name]);
        }catch (\PDOException $e) {
            return false;
        }
    }

}