<?php

/**
 * Memcache.php
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
use Memcache as MemcacheExtension;
use Memcached;

use function class_exists;
use function serialize;
use function unserialize;

/**
 * Memcache(d) cache handler.
 *
 * Works with either the modern {@see Memcached} extension (preferred) or the
 * legacy {@see MemcacheExtension} extension. Values are stored as a small
 * serialised envelope so any serialisable value round-trips exactly.
 *
 * Options:
 *  - prefix      (string) Key prefix. Default "cache_".
 *  - host        (string) Server host. Default "127.0.0.1".
 *  - port        (int)    Server port. Default 11211.
 *  - weight      (int)    Server weight (Memcached). Default 1.
 *  - default_ttl (int)    Expiry, in seconds, used when set() is called without
 *                         a TTL. 0 means "no expiry". Default 0.
 */
class Memcache extends BaseHandler
{
    /**
     * @var array<string, mixed>
     */
    protected array $handlerOptions = [
        'host'        => '127.0.0.1',
        'port'        => 11211,
        'weight'      => 1,
        'default_ttl' => 0,
    ];

    protected MemcacheExtension|Memcached|null $memcache = null;

    public function __destruct()
    {
        if ($this->memcache instanceof Memcached) {
            $this->memcache->quit();
        } elseif ($this->memcache instanceof MemcacheExtension) {
            $this->memcache->close();
        }
        $this->memcache = null;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->getMemcache()->get($this->name($key));
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
        if ($seconds === null) {
            $seconds = $this->optionInt('default_ttl', 0);
        }
        $payload = serialize(['v' => $value]);
        $memcache = $this->getMemcache();

        if ($memcache instanceof Memcached) {
            return $memcache->set($name, $payload, $seconds);
        }

        return $memcache->set($name, $payload, 0, $seconds);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $memcache = $this->getMemcache();
        if ($memcache->delete($this->name($key))) {
            return true;
        }
        // A key that was already absent counts as a successful delete.
        return $memcache instanceof Memcached
            && $memcache->getResultCode() === Memcached::RES_NOTFOUND;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->getMemcache()->flush();
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return \is_string($this->getMemcache()->get($this->name($key)));
    }

    /**
     * @inheritDoc
     */
    public function isSupported(): bool
    {
        return \extension_loaded('memcached') || \extension_loaded('memcache');
    }

    /**
     * @return MemcacheExtension|Memcached A connected client.
     * @throws CacheException If no extension is available or the server cannot
     *                        be reached.
     */
    protected function getMemcache(): MemcacheExtension|Memcached
    {
        if ($this->memcache !== null) {
            return $this->memcache;
        }
        $host = $this->optionString('host', '127.0.0.1');
        $port = $this->optionInt('port', 11211);

        if (class_exists(Memcached::class)) {
            $memcached = new Memcached();
            $memcached->addServer($host, $port, $this->optionInt('weight', 1));
            $stats = $memcached->getStats();
            if (!\is_array($stats) || !isset($stats[$host . ':' . $port])) {
                throw new CacheException('Memcached connection failed.');
            }

            return $this->memcache = $memcached;
        }

        if (class_exists(MemcacheExtension::class)) {
            $memcache = new MemcacheExtension();
            if (@$memcache->connect($host, $port) === false) {
                throw new CacheException('Memcache connection failed.');
            }

            return $this->memcache = $memcache;
        }

        throw new CacheException('Neither the "memcached" nor the "memcache" extension is available.');
    }
}
