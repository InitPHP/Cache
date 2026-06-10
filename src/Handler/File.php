<?php

/**
 * File.php
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

use function basename;
use function chmod;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_file;
use function rtrim;
use function serialize;
use function time;
use function unlink;
use function unserialize;

use const DIRECTORY_SEPARATOR;

/**
 * Filesystem cache handler.
 *
 * Each item is one PHP-serialised file named "{prefix}{key}" inside the
 * configured directory.
 *
 * Options:
 *  - prefix (string)      Key prefix and clear() glob filter. Default "cache_".
 *  - path   (string)      Directory the cache files live in. Required.
 *  - mode   (int|null)    chmod() mode applied to each file. Default 0640.
 */
class File extends BaseHandler
{
    /**
     * Files {@see clear()} never deletes even when they match the prefix glob.
     *
     * @var list<string>
     */
    private const PROTECTED_FILES = ['.htaccess', 'index.htm', 'index.html', 'index.php', 'web.config'];

    /**
     * @var array<string, mixed>
     */
    protected array $handlerOptions = [
        'path' => null,
        'mode' => 0640,
    ];

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->readByName($this->name($key));

        return $data === false ? $default : $data['data'];
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $name = $this->name($key);
        $seconds = $this->ttlToSeconds($ttl);
        if ($seconds !== null && $seconds <= 0) {
            return $this->deleteByName($name);
        }

        $payload = serialize([
            'time' => time(),
            'ttl'  => $seconds,
            'data' => $value,
        ]);

        return $this->write($this->pathByName($name), $payload);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return $this->deleteByName($this->name($key));
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $prefix = $this->getOption('prefix', '');
        $pattern = $this->directory() . DIRECTORY_SEPARATOR . (\is_string($prefix) ? $prefix : '') . '*';
        $files = glob($pattern);
        if ($files === false) {
            return true;
        }
        foreach ($files as $file) {
            if (!is_file($file) || \in_array(basename($file), self::PROTECTED_FILES, true)) {
                continue;
            }
            @unlink($file);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->readByName($this->name($key)) !== false;
    }

    /**
     * @inheritDoc
     */
    public function isSupported(): bool
    {
        return true;
    }

    /**
     * @param string $path
     * @param string $content
     * @return bool
     * @throws CacheException
     */
    private function write(string $path, string $content): bool
    {
        if (@file_put_contents($path, $content) === false) {
            return false;
        }
        $mode = $this->getOption('mode', 0640);
        if (\is_int($mode)) {
            @chmod($path, $mode);
        }

        return true;
    }

    /**
     * Reads and decodes a cache file, deleting it when it has expired.
     *
     * @param string $name Prefixed key.
     * @return array{time: int, ttl: int|null, data: mixed}|false False on a
     *         miss, an unreadable/corrupt file, or an expired item.
     * @throws CacheException
     */
    private function readByName(string $name): array|false
    {
        $path = $this->pathByName($name);
        if (!is_file($path)) {
            return false;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return false;
        }
        $data = @unserialize($raw);
        if (
            !\is_array($data)
            || !\array_key_exists('ttl', $data)
            || !\array_key_exists('time', $data)
            || !\array_key_exists('data', $data)
        ) {
            return false;
        }
        $time = $data['time'];
        $ttl = $data['ttl'];
        if ($ttl !== null && \is_int($time) && \is_int($ttl) && time() > ($time + $ttl)) {
            $this->deleteByName($name);

            return false;
        }

        /** @var array{time: int, ttl: int|null, data: mixed} $data */
        return $data;
    }

    /**
     * @param string $name Prefixed key.
     * @return bool
     * @throws CacheException
     */
    private function deleteByName(string $name): bool
    {
        $path = $this->pathByName($name);
        if (!is_file($path)) {
            return true;
        }

        return @unlink($path);
    }

    /**
     * @param string $name Prefixed key.
     * @return string Absolute path of the cache file.
     * @throws CacheException
     */
    private function pathByName(string $name): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * @return string The configured cache directory, without a trailing slash.
     * @throws CacheException If the "path" option is missing or empty.
     */
    private function directory(): string
    {
        $path = $this->getOption('path');
        if (!\is_string($path) || $path === '') {
            throw new CacheException('The File cache handler requires a non-empty "path" option pointing to a writable directory.');
        }

        return rtrim($path, '\\/');
    }
}
