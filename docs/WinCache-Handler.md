# WinCache Handler

> ⚠️ **Deprecated.** The WinCache extension is no longer a supported
> Microsoft product and has **no stable release for PHP 8**; only an unsupported
> beta (2.0.0.9, best-effort maintenance) builds on PHP 8.1 and later. This
> handler is kept only for backward compatibility.
> On any modern stack use [File](File-Handler.md), [Redis](Redis-Handler.md) or
> [Memcache(d)](Memcache-Handler.md) instead.

`InitPHP\Cache\Handler\Wincache` stores items in the WinCache user cache
(`ext-wincache`, Windows only).

## Options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `prefix` | `string` | `cache_` | Prepended to keys. |
| `default_ttl` | `int` | `0` | Expiry used when `set()` is called **without** a TTL. `0` = no expiry. |

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
- When `set()` is called without a TTL, the `default_ttl` option is applied
  (`0` = no expiry).
- `has()` uses `wincache_ucache_exists()`.
- `clear()` calls **`wincache_ucache_clear()`**, which clears the **entire**
  user cache — it is not limited by `prefix`.

## Requirements

The handler is only supported when the extension is loaded **and** the user cache
is enabled:

```php
extension_loaded('wincache') && ini_get('wincache.ucenabled'); // must be true
```

Constructing the handler in an unsupported environment throws a
[`CacheException`](Error-Handling.md) — which is what you will get on any runtime
where the extension is not installed and enabled (there is no stable WinCache
release for PHP 8). Build a different handler instead.

## Next steps

- [File Handler](File-Handler.md) — the zero-dependency replacement.
- [Configuration Options](Configuration-Options.md) — all handlers side by side.
