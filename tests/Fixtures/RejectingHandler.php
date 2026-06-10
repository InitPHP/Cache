<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests\Fixtures;

use DateInterval;
use InitPHP\Cache\BaseHandler;

/**
 * A handler whose write operations always fail, used to verify that the bulk
 * methods aggregate per-item failures into a false result.
 */
final class RejectingHandler extends BaseHandler
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
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function isSupported(): bool
    {
        return true;
    }
}
