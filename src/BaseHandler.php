<?php

/**
 * BaseHandler.php
 *
 * This file is part of InitPHP Cache.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    https://github.com/InitPHP/Cache/blob/main/LICENSE  MIT
 * @link       https://github.com/InitPHP/Cache
 */

declare(strict_types=1);

namespace InitPHP\Cache;

use DateInterval;
use DateTimeImmutable;
use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Exception\InvalidArgumentException;

use function array_change_key_case;
use function array_merge;
use function is_numeric;
use function strpbrk;
use function strtolower;
use function time;

/**
 * Shared behaviour for every cache handler.
 *
 * Concrete handlers implement the storage-specific primitives ({@see get()},
 * {@see set()}, {@see delete()}, {@see clear()}, {@see has()} and
 * {@see isSupported()}); this base class provides option handling, PSR-16 key
 * validation, TTL normalisation, the bulk methods and integer counters.
 */
abstract class BaseHandler implements CacheInterface
{
    /**
     * Characters reserved by PSR-16 that must never appear in a cache key.
     *
     * @var string
     */
    public const RESERVED_CHARACTERS = '{}()/\@:';

    /**
     * Library-wide default options, applied before any handler defaults.
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_OPTIONS = [
        'prefix' => 'cache_',
    ];

    /**
     * Handler-specific default options. Concrete handlers override this.
     *
     * @var array<string, mixed>
     */
    protected array $handlerOptions = [];

    /**
     * The effective options after merging defaults with user input.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * @param array<string, mixed> $options Handler options; keys are matched
     *                                       case-insensitively.
     * @throws CacheException If the handler is not supported in this runtime.
     */
    public function __construct(array $options = [])
    {
        if (!$this->isSupported()) {
            throw new CacheException(\sprintf(
                'The "%s" cache handler is not supported in this environment; the required extension is missing or disabled.',
                static::class
            ));
        }
        $this->options = array_merge(
            self::DEFAULT_OPTIONS,
            array_change_key_case($this->handlerOptions),
            array_change_key_case($options)
        );
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options = []): static
    {
        if ($options !== []) {
            $this->options = array_merge($this->options, array_change_key_case($options));
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[strtolower($key)] ?? $default;
    }

    /**
     * Returns an option coerced to int, or $default when it is not numeric.
     */
    protected function optionInt(string $key, int $default = 0): int
    {
        $value = $this->getOption($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Returns an option coerced to float, or $default when it is not numeric.
     */
    protected function optionFloat(string $key, float $default = 0.0): float
    {
        $value = $this->getOption($key);

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * Returns an option coerced to string, or $default when it is not scalar.
     */
    protected function optionString(string $key, string $default = ''): string
    {
        $value = $this->getOption($key);

        return \is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @inheritDoc
     */
    abstract public function get(string $key, mixed $default = null): mixed;

    /**
     * @inheritDoc
     */
    abstract public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;

    /**
     * @inheritDoc
     */
    abstract public function delete(string $key): bool;

    /**
     * @inheritDoc
     */
    abstract public function clear(): bool;

    /**
     * @inheritDoc
     */
    abstract public function has(string $key): bool;

    /**
     * @inheritDoc
     */
    abstract public function isSupported(): bool;

    /**
     * @inheritDoc
     */
    public function increment(string $key, int $offset = 1): int
    {
        $current = $this->get($key);
        $base = (\is_int($current) || \is_float($current)) ? $current : 0;
        $new = (int) ($base + $offset);
        $this->set($key, $new);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $offset = 1): int
    {
        return $this->increment($key, -$offset);
    }

    /**
     * @inheritDoc
     *
     * @param iterable<string> $keys
     * @return array<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }

        return $data;
    }

    /**
     * @inheritDoc
     *
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $result = true;
        foreach ($values as $key => $value) {
            if (!$this->set((string) $key, $value, $ttl)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $result = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Validates a user-supplied cache key against the PSR-16 rules.
     *
     * @param string $key
     * @return string The validated key, unchanged.
     * @throws InvalidArgumentException If the key is empty or reserved.
     */
    protected function validateKey(string $key): string
    {
        if ($key === '') {
            throw new InvalidArgumentException('The cache key must be a non-empty string.');
        }
        if (strpbrk($key, self::RESERVED_CHARACTERS) !== false) {
            throw new InvalidArgumentException(\sprintf(
                'The cache key "%s" contains a reserved character. The following are reserved: %s',
                $key,
                self::RESERVED_CHARACTERS
            ));
        }

        return $key;
    }

    /**
     * Validates $key and returns it prefixed with the configured "prefix"
     * option, ready to be used as the physical storage key.
     *
     * @param string $key
     * @return string
     * @throws InvalidArgumentException If the key is empty or reserved.
     */
    protected function name(string $key): string
    {
        $prefix = $this->getOption('prefix', '');

        return (\is_string($prefix) ? $prefix : '') . $this->validateKey($key);
    }

    /**
     * Normalises a PSR-16 TTL to a number of seconds (or null for "no expiry").
     *
     * A {@see DateInterval} is resolved relative to the current time. The result
     * may be zero or negative, which callers treat as "already expired".
     *
     * @param null|int|DateInterval $ttl
     * @return int|null
     */
    protected function ttlToSeconds(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }
        if ($ttl instanceof DateInterval) {
            return (new DateTimeImmutable())->add($ttl)->getTimestamp() - time();
        }

        return $ttl;
    }
}
