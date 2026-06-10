<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests\Fixtures;

use DateInterval;
use InitPHP\Cache\BaseHandler;

/**
 * A no-op handler that exposes {@see BaseHandler}'s protected helpers so they
 * can be unit-tested directly.
 */
final class ProbeHandler extends BaseHandler
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return true;
    }

    public function delete(string $key): bool
    {
        return true;
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

    public function exposeName(string $key): string
    {
        return $this->name($key);
    }

    public function exposeTtlToSeconds(null|int|DateInterval $ttl): ?int
    {
        return $this->ttlToSeconds($ttl);
    }

    public function exposeOptionInt(string $key, int $default = 0): int
    {
        return $this->optionInt($key, $default);
    }

    public function exposeOptionFloat(string $key, float $default = 0.0): float
    {
        return $this->optionFloat($key, $default);
    }

    public function exposeOptionString(string $key, string $default = ''): string
    {
        return $this->optionString($key, $default);
    }
}
