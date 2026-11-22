# InitPHP Cache — Wiki

Welcome to the official documentation for **`initphp/cache`** — a small,
[PSR-16 (Simple Cache)](https://www.php-fig.org/psr/psr-16/) caching library for
**PHP 8.2+** with interchangeable handlers for the filesystem, PDO databases,
Redis, Memcache(d) and WinCache.

```bash
composer require initphp/cache
```

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;

$cache = Cache::create(File::class, ['path' => __DIR__ . '/var/cache']);

$cache->set('user:42', ['name' => 'Jane'], 3600); // store for 1 hour
$cache->get('user:42');                            // ['name' => 'Jane']
$cache->get('missing', 'default');                 // "default"
$cache->has('user:42');                            // true
$cache->delete('user:42');                         // true
```

One factory call picks the backend; the **API is identical for every handler**,
so you can develop against the [File](File-Handler.md) handler and switch to
[Redis](Redis-Handler.md) in production by changing a single line.

## The package in one table

| Symbol | Role |
| --- | --- |
| [`Cache`](The-Cache-Factory.md) | Static factory — builds and configures a handler. The usual entry point. |
| [`CacheInterface`](API-Reference.md#cacheinterface) | The contract every handler fulfils; extends `Psr\SimpleCache\CacheInterface`. |
| [`BaseHandler`](Custom-Handlers.md) | Abstract base with the shared behaviour; extend it to write your own handler. |
| [`Handler\File`](File-Handler.md) · [`Handler\PDO`](PDO-Handler.md) · [`Handler\Redis`](Redis-Handler.md) · [`Handler\Memcache`](Memcache-Handler.md) · [`Handler\Wincache`](WinCache-Handler.md) | The five built-in handlers. |
| [`CacheException`](Error-Handling.md) / [`InvalidArgumentException`](Error-Handling.md) | The two exception types; both implement the matching PSR-16 interface. |

## Start here

- **New to the package?** Read [Installation](Installation.md), then
  [Quick Start](Quick-Start.md).
- **Choosing a backend?** Compare the [handlers](File-Handler.md) and their
  [configuration options](Configuration-Options.md).
- **Want the precise rules** for keys, TTLs and values?
  [Keys, TTL & Values](Keys-TTL-and-Values.md).
- **Building counters?** [Counters](Counters.md) explains `increment()` /
  `decrement()`.
- **Looking for a specific method?** The full [API Reference](API-Reference.md)
  lists every public member.
- **Writing your own backend?** [Custom Handlers](Custom-Handlers.md).

## The handlers at a glance

| Handler | Class | Backend | Needs | Best for |
| --- | --- | --- | --- | --- |
| [File](File-Handler.md) | `Handler\File` | Filesystem | core only | local dev, small/medium caches |
| [PDO](PDO-Handler.md) | `Handler\PDO` | SQL (MySQL, SQLite, PostgreSQL…) | `ext-pdo` | shared DB, no extra service |
| [Redis](Redis-Handler.md) | `Handler\Redis` | Redis | `ext-redis` | hot, distributed caches |
| [Memcache(d)](Memcache-Handler.md) | `Handler\Memcache` | Memcached | `ext-memcached` / `ext-memcache` | distributed, ephemeral caches |
| [WinCache](WinCache-Handler.md) | `Handler\Wincache` | WinCache user cache | `ext-wincache` | _deprecated — legacy Windows only_ |

## What you get

- **One PSR-16 API, five backends.** Every handler implements
  [`CacheInterface`](API-Reference.md#cacheinterface), so your code never depends
  on the concrete handler.
- **Exact value fidelity.** Anything `serialize()` accepts round-trips exactly —
  including `null`, `false`, arrays and objects. See
  [Keys, TTL & Values](Keys-TTL-and-Values.md).
- **Flexible TTLs.** `null` (forever), an integer of seconds, or a
  `DateInterval`; a zero/negative TTL deletes the item.
- **Consistent counters.** `increment()` / `decrement()` behave identically on
  every backend and return the new value. See [Counters](Counters.md).
- **Strict, predictable keys.** PSR-16 key validation with the reserved set
  `{}()/\@:`. See [Keys, TTL & Values](Keys-TTL-and-Values.md).
- **PSR-16 exceptions.** Every error implements `Psr\SimpleCache\CacheException`,
  so one `catch` covers the library. See [Error Handling](Error-Handling.md).
- **Extensible.** Add your own backend by extending
  [`BaseHandler`](Custom-Handlers.md).

## At a glance — behaviour

| You call | You get |
| --- | --- |
| `set('k', $v)` | stores `$v` with no expiry → `true` |
| `set('k', $v, 60)` | stores `$v` for 60 seconds → `true` |
| `set('k', $v, new DateInterval('PT5M'))` | stores `$v` for 5 minutes → `true` |
| `set('k', $v, 0)` _(or negative)_ | deletes `k` → `true` |
| `get('missing')` | `null` |
| `get('missing', 'x')` | `"x"` (returned as-is, never invoked) |
| `get('k')` after storing `null` | `null` (use `has()` to detect the miss) |
| `increment('n', 5)` on a missing key | `5` (new value) |
| `get('bad:key')` | throws [`InvalidArgumentException`](Error-Handling.md) (`:` is reserved) |

## Package metadata

- **License**: MIT
- **Minimum PHP**: 8.2
- **Runtime dependencies**: [`psr/simple-cache`](https://packagist.org/packages/psr/simple-cache) `^3.0`
- **Required extensions**: none for the [File](File-Handler.md) handler; one per backend otherwise
- **Packagist**: [`initphp/cache`](https://packagist.org/packages/initphp/cache)
- **Source**: [github.com/InitPHP/Cache](https://github.com/InitPHP/Cache)
- **Issues**: [github.com/InitPHP/Cache/issues](https://github.com/InitPHP/Cache/issues)
- **Discussions**: [github.com/orgs/InitPHP/discussions](https://github.com/orgs/InitPHP/discussions)

If something in this wiki is unclear, wrong, or out of date,
[open an issue](https://github.com/InitPHP/Cache/issues/new) — documentation
fixes are merged eagerly.
