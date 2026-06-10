<?php

/**
 * Wincache.php
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

use function wincache_ucache_clear;
use function wincache_ucache_delete;
use function wincache_ucache_exists;
use function wincache_ucache_get;
use function wincache_ucache_set;

/**
 * WinCache user-cache handler (Windows + the WinCache extension only).
 *
 * @deprecated The WinCache extension is unmaintained and not available for
 *             PHP 8. The handler is kept for backward compatibility; prefer
 *             {@see File}, {@see Redis} or {@see Memcache} on modern stacks.
 *
 * Options:
 *  - prefix      (string) Key prefix. Default "cache_".
 *  - default_ttl (int)    Expiry, in seconds, used when set() is called without
 *                         a TTL. 0 means "no expiry". Default 0.
 */
class Wincache extends BaseHandler
{
    /**
     * @var array<string, mixed>
     */
    protected array $handlerOptions = [
        'default_ttl' => 0,
    ];

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $success = false;
        $value = wincache_ucache_get($this->name($key), $success);

        return $success ? $value : $default;
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

        return wincache_ucache_set($name, $value, $seconds);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $name = $this->name($key);
        if (!wincache_ucache_exists($name)) {
            return true;
        }

        return wincache_ucache_delete($name);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return wincache_ucache_clear();
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return wincache_ucache_exists($this->name($key));
    }

    /**
     * @inheritDoc
     */
    public function isSupported(): bool
    {
        return \extension_loaded('wincache') && (bool) \ini_get('wincache.ucenabled');
    }
}
