# Memcache(d) Handler

`InitPHP\Cache\Handler\Memcache` stores items in Memcached. It works with either
the modern [`Memcached`](https://www.php.net/manual/en/book.memcached.php)
extension (**preferred**) or the legacy `Memcache` extension; if both are
installed, `Memcached` is used.

## Options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `prefix` | `string` | `cache_` | Prepended to keys. |
| `host` | `string` | `127.0.0.1` | Server host. |
| `port` | `int` | `11211` | Server port. |
| `weight` | `int` | `1` | Server weight (Memcached only). |
| `default_ttl` | `int` | `0` | Expiry used when `set()` is called **without** a TTL. `0` = no expiry. |

## Usage

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\Memcache;

$cache = Cache::create(Memcache::class, [
    'host' => '127.0.0.1',
    'port' => 11211,
]);

$cache->set('fragment:nav', $html, 600);
$cache->get('fragment:nav');
```

## How it works

- Each value is stored as a small **serialised envelope**, so `null`, `false`,
  arrays and objects round-trip exactly.
- TTLs are delegated to the server. When `set()` is called **without** a TTL, the
  `default_ttl` option is applied (`0` means "no expiry").
- `has()` reports whether the key returns a stored value.
- `clear()` calls **`flush()`**, which clears the **entire Memcached instance** —
  it is **not** limited by `prefix`.

```php
$cache->set('a', 1);            // explicit no-TTL → uses default_ttl (0 = forever)
$cache->set('b', 2, 60);        // 60-second TTL
$cache->clear();                // flushes the whole server, not just "cache_*"
```

> **`clear()` is global.** Because Memcached has no key enumeration, `flush()`
> wipes everything on the server, including keys written by other apps that share
> it. Run a dedicated Memcached instance for a cache you intend to `clear()`.

## The `default_ttl` option

`default_ttl` only matters for `set()` calls that omit the TTL argument. An
explicit TTL always wins, and an explicit `0`/negative still deletes the item:

```php
$cache = Cache::create(Memcache::class, ['default_ttl' => 300]);

$cache->set('a', 1);      // no TTL given → expires in 300s (default_ttl)
$cache->set('b', 2, 60);  // explicit → 60s
$cache->set('c', 3, 0);   // explicit 0 → deletes "c"
```

## Connection and requirements

The handler connects on first use and throws a
[`CacheException`](Error-Handling.md) when neither extension is available or the
server cannot be reached:

```php
extension_loaded('memcached') || extension_loaded('memcache'); // true when usable
```

```php
use InitPHP\Cache\Exception\CacheException;

try {
    $cache = Cache::create(Memcache::class, ['host' => 'memcached.internal']);
    $cache->set('k', 'v');
} catch (CacheException $e) {
    error_log('Memcached unavailable: ' . $e->getMessage());
}
```

## Next steps

- [Configuration Options](Configuration-Options.md) — all handlers side by side.
- [Keys, TTL & Values](Keys-TTL-and-Values.md) — `default_ttl` vs an explicit TTL.
- [Error Handling](Error-Handling.md) — connection failures and exceptions.
