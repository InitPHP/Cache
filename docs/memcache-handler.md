# Memcache(d) handler

`InitPHP\Cache\Handler\Memcache` stores items in Memcached. It works with either
the modern [`Memcached`](https://www.php.net/manual/en/book.memcached.php)
extension (preferred) or the legacy `Memcache` extension; if both are present,
`Memcached` is used.

## Options

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `prefix` | `string` | `cache_` | Prepended to keys. |
| `host` | `string` | `127.0.0.1` | Server host. |
| `port` | `int` | `11211` | Server port. |
| `weight` | `int` | `1` | Server weight (Memcached only). |
| `default_ttl` | `int` | `0` | Expiry used when `set()` is called with no TTL. `0` means "no expiry". |

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

- Each value is stored as a small serialised envelope, so `null`, `false`,
  arrays and objects round-trip exactly.
- TTLs are delegated to the server. When `set()` is called without a TTL, the
  `default_ttl` option is used (`0` = no expiry).
- `clear()` calls `flush()`, which clears the **entire** Memcached instance —
  it is not limited by `prefix`.

## Errors

If neither extension is available, or the server cannot be reached, the handler
throws a [`CacheException`](exceptions.md).
