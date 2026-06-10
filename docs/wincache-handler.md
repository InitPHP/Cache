# WinCache handler

> **Deprecated.** The WinCache extension is unmaintained and is not available
> for PHP 8. This handler is kept only for backward compatibility. On modern
> stacks use [File](file-handler.md), [Redis](redis-handler.md) or
> [Memcache(d)](memcache-handler.md) instead.

`InitPHP\Cache\Handler\Wincache` stores items in the WinCache user cache
(`ext-wincache`, Windows only).

## Options

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `prefix` | `string` | `cache_` | Prepended to keys. |
| `default_ttl` | `int` | `0` | Expiry used when `set()` is called with no TTL. `0` means "no expiry". |

## Usage

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\Wincache;

$cache = Cache::create(Wincache::class, [
    'default_ttl' => 300,
]);

$cache->set('key', 'value', 60);
$cache->get('key');
```

## How it works

- Values are stored with `wincache_ucache_set()`; TTLs are delegated to WinCache.
- `clear()` calls `wincache_ucache_clear()`, clearing the **entire** user cache.

## Errors

If the WinCache extension is not loaded, or `wincache.ucenabled` is off,
constructing the handler throws a [`CacheException`](exceptions.md).
