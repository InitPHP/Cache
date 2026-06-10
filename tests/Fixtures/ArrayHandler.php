<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests\Fixtures;

use DateInterval;
use InitPHP\Cache\BaseHandler;

use function time;

/**
 * An in-memory cache handler used to exercise all of {@see BaseHandler}'s shared
 * behaviour (option handling, key validation, TTL normalisation, bulk methods
 * and counters) without touching the filesystem or any extension.
 */
final class ArrayHandler extends BaseHandler
{
    /**
     * @var array<string, array{expires: int|null, value: mixed}>
     */
    private array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $name = $this->name($key);
        if (!$this->isAlive($name)) {
            return $default;
        }

        return $this->store[$name]['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $name = $this->name($key);
        $seconds = $this->ttlToSeconds($ttl);
        if ($seconds !== null && $seconds <= 0) {
            unset($this->store[$name]);

            return true;
        }
        $this->store[$name] = [
            'expires' => $seconds === null ? null : time() + $seconds,
            'value'   => $value,
        ];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$this->name($key)]);

        return true;
    }

    public function clear(): bool
    {
        $this->store = [];

        return true;
    }

    public function has(string $key): bool
    {
        return $this->isAlive($this->name($key));
    }

    public function isSupported(): bool
    {
        return true;
    }

    private function isAlive(string $name): bool
    {
        if (!isset($this->store[$name])) {
            return false;
        }
        $expires = $this->store[$name]['expires'];
        if ($expires !== null && $expires < time()) {
            unset($this->store[$name]);

            return false;
        }

        return true;
    }
}
