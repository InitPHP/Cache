<?php

/**
 * Redis.php
 *
 * This file is part of InitPHP Cache.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    https://github.com/InitPHP/Cache/blob/main/LICENSE  MIT
 * @link       https://github.com/InitPHP/Cache
 */

declare(strict_types=1);

namespace InitPHP\Cache\Handler;

use DateInterval;
use InitPHP\Cache\BaseHandler;
use InitPHP\Cache\Exception\CacheException;
use Redis as RedisExtension;
use RedisException;

use function serialize;
use function unserialize;

/**
 * Redis cache handler backed by the phpredis ({@see RedisExtension}) extension.
 *
 * Every value is stored as a small serialised envelope, so any serialisable
 * value — including null, false and arrays — round-trips exactly. Expiry is
 * delegated to Redis via SETEX.
 *
 * Options:
 *  - prefix   (string)      Key prefix. Default "cache_".
 *  - host     (string)      Server host. Default "127.0.0.1".
 *  - port     (int)         Server port. Default 6379.
 *  - timeout  (int|float)   Connection timeout in seconds. Default 0.
 *  - password (string|null) AUTH password. Default null.
 *  - database (int|null)    Database index to SELECT. Default 0.
 */
class Redis extends BaseHandler
{
    /**
     * @var array<string, mixed>
     */
    protected array $handlerOptions = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ];

    protected ?RedisExtension $redis = null;

    public function __destruct()
    {
        if ($this->redis instanceof RedisExtension) {
            try {
                $this->redis->close();
            } catch (RedisException) {
                // The connection is being torn down anyway.
            }
            $this->redis = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->getRedis()->get($this->name($key));
        if (!\is_string($raw)) {
            return $default;
        }
        $data = @unserialize($raw);
        if (!\is_array($data) || !\array_key_exists('v', $data)) {
            return $default;
        }

        return $data['v'];
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $name = $this->name($key);
        $seconds = $this->ttlToSeconds($ttl);
        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }
        $payload = serialize(['v' => $value]);
        $redis = $this->getRedis();

        return $seconds === null
            ? $redis->set($name, $payload)
            : $redis->setex($name, $seconds, $payload);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $this->getRedis()->del($this->name($key));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->getRedis()->flushDB();
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->getRedis()->exists($this->name($key)) > 0;
    }

    /**
     * @inheritDoc
     */
    public function isSupported(): bool
    {
        return \extension_loaded('redis');
    }

    /**
     * @return RedisExtension A connected client.
     * @throws CacheException If the connection, authentication or database
     *                        selection fails.
     */
    protected function getRedis(): RedisExtension
    {
        if ($this->redis instanceof RedisExtension) {
            return $this->redis;
        }
        $redis = new RedisExtension();
        try {
            $connected = $redis->connect(
                $this->optionString('host', '127.0.0.1'),
                $this->optionInt('port', 6379),
                $this->optionFloat('timeout', 0.0)
            );
            if (!$connected) {
                throw new CacheException('Redis cache connection failed.');
            }
            $password = $this->getOption('password');
            if ($password !== null && !$redis->auth($this->optionString('password'))) {
                throw new CacheException('Redis cache authentication failed.');
            }
            $database = $this->getOption('database');
            if ($database !== null && !$redis->select($this->optionInt('database'))) {
                throw new CacheException('Redis cache database could not be selected.');
            }
        } catch (RedisException $e) {
            throw new CacheException('Redis cache error: ' . $e->getMessage(), 0, $e);
        }

        return $this->redis = $redis;
    }
}
