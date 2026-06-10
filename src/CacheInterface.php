<?php

/**
 * CacheInterface.php
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

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

/**
 * The contract every InitPHP cache handler fulfils.
 *
 * It extends the PSR-16 {@see PsrCacheInterface} and adds the option API used by
 * the {@see Cache} factory plus atomic-style integer counters.
 */
interface CacheInterface extends PsrCacheInterface
{
    /**
     * Merges the given options into the handler's effective configuration.
     *
     * Keys are matched case-insensitively; an empty array is a no-op.
     *
     * @param array<string, mixed> $options
     * @return static
     */
    public function setOptions(array $options = []): static;

    /**
     * Returns a single configuration value, or $default when it is not set.
     *
     * @param string $key Looked up case-insensitively.
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $key, mixed $default = null): mixed;

    /**
     * Reports whether the runtime can use this handler (e.g. the required
     * extension is loaded).
     *
     * @return bool
     */
    public function isSupported(): bool;

    /**
     * Increases a numeric cache item by $offset and returns the new value.
     *
     * A missing or non-numeric item is treated as 0, so the result equals
     * $offset. The updated value is stored without an expiry time.
     *
     * @param string $key
     * @param int $offset
     * @return int The value after incrementing.
     * @throws Exception\InvalidArgumentException If $key is not a legal key.
     */
    public function increment(string $key, int $offset = 1): int;

    /**
     * Decreases a numeric cache item by $offset and returns the new value.
     *
     * A missing or non-numeric item is treated as 0, so the result equals
     * -$offset. The updated value is stored without an expiry time.
     *
     * @param string $key
     * @param int $offset
     * @return int The value after decrementing.
     * @throws Exception\InvalidArgumentException If $key is not a legal key.
     */
    public function decrement(string $key, int $offset = 1): int;
}
