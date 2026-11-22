# Custom Handlers

The five built-in handlers are not special — they extend
[`BaseHandler`](API-Reference.md#basehandler), which provides everything except the
storage primitives. Writing your own backend (APCu, DynamoDB, an in-memory store
for tests…) means implementing **six methods** and declaring your defaults.

## What `BaseHandler` gives you

You inherit, for free:

- option handling (`getOption`, `setOptions`, the `prefix` default, the
  `optionInt`/`optionFloat`/`optionString` helpers);
- key validation and prefixing via `$this->name($key)`;
- TTL normalisation via `$this->ttlToSeconds($ttl)`;
- the bulk methods `getMultiple` / `setMultiple` / `deleteMultiple`;
- the `increment()` / `decrement()` counters.

You implement the six abstract methods: `get`, `set`, `delete`, `clear`, `has`,
`isSupported`.

## A minimal in-memory handler

Dependency-free, complete, and handy for tests (see [Testing](Testing.md)):

```php
<?php

declare(strict_types=1);

namespace App\Cache;

use DateInterval;
use InitPHP\Cache\BaseHandler;

final class ArrayHandler extends BaseHandler
{
    /** @var array<string, array{expires: int|null, value: mixed}> */
    private array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $name = $this->name($key);                 // validates + prefixes
        if (!$this->alive($name)) {
            return $default;
        }
        return $this->store[$name]['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $name    = $this->name($key);
        $seconds = $this->ttlToSeconds($ttl);      // null | int (may be <= 0)

        if ($seconds !== null && $seconds <= 0) {
            unset($this->store[$name]);            // expired-on-write → delete
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
        return $this->alive($this->name($key));
    }

    public function isSupported(): bool
    {
        return true;
    }

    private function alive(string $name): bool
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
```

Use it like any built-in handler:

```php
use InitPHP\Cache\Cache;

$cache = Cache::create(App\Cache\ArrayHandler::class, ['prefix' => 'test_']);
$cache->set('k', 'v');
$cache->get('k'); // "v"
```

## A real-world example: APCu

Declare defaults in `$handlerOptions`, gate availability in `isSupported()`, and
lean on the helpers:

```php
<?php

declare(strict_types=1);

namespace App\Cache;

use DateInterval;
use InitPHP\Cache\BaseHandler;

final class ApcuHandler extends BaseHandler
{
    /** @var array<string, mixed> */
    protected array $handlerOptions = [
        'default_ttl' => 0, // 0 = no expiry, used when set() gets no TTL
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $value = apcu_fetch($this->name($key), $ok);
        return $ok ? $value : $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $name    = $this->name($key);
        $seconds = $this->ttlToSeconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            apcu_delete($name);                 // already-expired → delete
            return true;
        }
        if ($seconds === null) {
            $seconds = $this->optionInt('default_ttl', 0);
        }
        return apcu_store($name, $value, $seconds);
    }

    public function delete(string $key): bool
    {
        apcu_delete($this->name($key));         // a missing key still counts as deleted
        return true;
    }

    public function clear(): bool
    {
        return apcu_clear_cache(); // clears the whole APCu cache (not prefix-scoped)
    }

    public function has(string $key): bool
    {
        return apcu_exists($this->name($key));
    }

    public function isSupported(): bool
    {
        return extension_loaded('apcu') && (bool) ini_get('apc.enabled');
    }
}
```

## Rules to follow

To stay consistent with the built-in handlers and PSR-16:

1. **Always run keys through `$this->name($key)`** — it validates (throwing
   [`InvalidArgumentException`](Error-Handling.md) for bad keys) and applies the
   prefix.
2. **Normalise TTLs with `$this->ttlToSeconds($ttl)`** and treat a `<= 0` result
   as "delete the item, return `true`".
3. **`null` is a value, not a miss.** Make sure a stored `null` reads back as
   `null` and `has()` returns `true` for it. (A serialised envelope, as Redis and
   Memcache use, is the usual trick when the backend can't tell `false` from a
   miss.)
4. **Return `bool` from `set`/`delete`/`clear`**, and the value (or the caller's
   default) from `get`.
5. **Gate `isSupported()`** on whatever your backend needs; the
   [factory](The-Cache-Factory.md) calls it and throws a `CacheException` when it
   returns `false`.

Get those right and the inherited `increment()`, `decrement()` and the
`*Multiple()` methods just work.

## Next steps

- [Testing](Testing.md) — use a custom in-memory handler in your test suite.
- [API Reference](API-Reference.md#basehandler) — every `BaseHandler` helper.
