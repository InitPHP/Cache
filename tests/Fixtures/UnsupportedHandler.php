<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests\Fixtures;

use DateInterval;
use InitPHP\Cache\BaseHandler;

/**
 * A handler that reports itself as unsupported, used to verify that the
 * {@see BaseHandler} constructor rejects unsupported runtimes.
 */
final class UnsupportedHandler extends BaseHandler
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return false;
    }

    public function delete(string $key): bool
    {
        return false;
    }

    public function clear(): bool
    {
        return false;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function isSupported(): bool
    {
        return false;
    }
}
