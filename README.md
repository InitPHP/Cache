# InitPHP Cache

A lightweight [PSR-16 (Simple Cache)](https://www.php-fig.org/psr/psr-16/)
implementation with interchangeable handlers for the filesystem, PDO databases,
Redis, Memcache(d) and WinCache.

[![Designed & Maintained with Tan](https://muhammetsafak.com/badges/designed-maintained-with-tan.svg)](https://muhammetsafak.com/tan/)
[![CI](https://github.com/InitPHP/Cache/actions/workflows/ci.yml/badge.svg)](https://github.com/InitPHP/Cache/actions/workflows/ci.yml)
[![Latest Stable Version](http://poser.pugx.org/initphp/cache/v)](https://packagist.org/packages/initphp/cache) [![Total Downloads](http://poser.pugx.org/initphp/cache/downloads)](https://packagist.org/packages/initphp/cache) [![License](http://poser.pugx.org/initphp/cache/license)](https://packagist.org/packages/initphp/cache) [![PHP Version Require](http://poser.pugx.org/initphp/cache/require/php)](https://packagist.org/packages/initphp/cache)

## Requirements

- PHP 8.2 or higher
- [`psr/simple-cache`](https://packagist.org/packages/psr/simple-cache) `^3.0`

Each handler may need its own PHP extension:

| Handler | Class | Backend | Needs |
| ------- | ----- | ------- | ----- |
| File | `InitPHP\Cache\Handler\File` | Filesystem | — (core only) |
| PDO | `InitPHP\Cache\Handler\PDO` | SQL database (MySQL, SQLite, PostgreSQL…) | `ext-pdo` |
| Redis | `InitPHP\Cache\Handler\Redis` | Redis | `ext-redis` (phpredis) |
| Memcache | `InitPHP\Cache\Handler\Memcache` | Memcached | `ext-memcached` or `ext-memcache` |
| WinCache | `InitPHP\Cache\Handler\Wincache` | WinCache user cache | `ext-wincache` — _deprecated_ |

## Installation

```bash
composer require initphp/cache
```

## Quick start

```php
require 'vendor/autoload.php';

use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;

$cache = Cache::create(File::class, [
    'path' => __DIR__ . '/var/cache',
]);

// Read-through pattern: compute and store on a miss.
$posts = $cache->get('posts');
if ($posts === null) {
    $posts = [
        ['id' => 12, 'title' => 'Post 12'],
        ['id' => 15, 'title' => 'Post 15'],
    ];
    $cache->set('posts', $posts, 120); // cache for 120 seconds
}

print_r($posts);
```

`Cache::create()` returns a handler that implements
`InitPHP\Cache\CacheInterface`, which extends `Psr\SimpleCache\CacheInterface`.
You can equally type-hint and pass any PSR-16 cache around your application.

## Switching handlers

Only the factory call changes; the cache API is identical for every backend.

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\Redis;

$cache = Cache::create(Redis::class, [
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'database' => 0,
]);

$cache->set('user:42', ['name' => 'Jane'], 3600);
$cache->get('user:42');
```

## API at a glance

| Call | Returns | Purpose |
| ---- | ------- | ------- |
| `get(string $key, mixed $default = null)` | `mixed` | Read a value, or `$default` on a miss. |
| `set(string $key, mixed $value, null\|int\|DateInterval $ttl = null)` | `bool` | Store a value, optionally with a TTL. |
| `delete(string $key)` | `bool` | Remove one item. |
| `clear()` | `bool` | Remove every item this handler owns. |
| `has(string $key)` | `bool` | Whether a live item exists. |
| `getMultiple(iterable $keys, mixed $default = null)` | `iterable` | Read many items at once. |
| `setMultiple(iterable $values, null\|int\|DateInterval $ttl = null)` | `bool` | Store many items at once. |
| `deleteMultiple(iterable $keys)` | `bool` | Remove many items at once. |
| `increment(string $key, int $offset = 1)` | `int` | Add to a counter and return the new value. |
| `decrement(string $key, int $offset = 1)` | `int` | Subtract from a counter and return the new value. |

See [`docs/`](docs/Home.md) for the full guide, including per-handler
configuration and the exact semantics of TTLs, keys and counters.

## Notes

- **TTL:** `null` means "store with no expiry"; a positive integer or
  `DateInterval` sets a lifetime; a zero or negative TTL deletes the item.
- **Keys:** any non-empty string that does not contain the PSR-16 reserved
  characters `{}()/\@:`. An invalid key throws an
  [`InvalidArgumentException`](docs/Error-Handling.md).
- **Counters:** `increment()`/`decrement()` treat a missing item, or one that is not an
  `int` or `float`, as `0` and store the result without an expiry. They behave identically across
  every handler.
- **Values:** anything `serialize()` can handle round-trips exactly — including
  `null`, `false` and objects.

## Documentation

- [Home](docs/Home.md)
- [Installation](docs/Installation.md) · [Quick Start](docs/Quick-Start.md)
- [The Cache Factory](docs/The-Cache-Factory.md)
- [Keys, TTL & Values](docs/Keys-TTL-and-Values.md) ·
  [Counters](docs/Counters.md) ·
  [Bulk Operations](docs/Bulk-Operations.md)
- [Configuration Options](docs/Configuration-Options.md)
- Handlers: [File](docs/File-Handler.md) ·
  [PDO](docs/PDO-Handler.md) ·
  [Redis](docs/Redis-Handler.md) ·
  [Memcache(d)](docs/Memcache-Handler.md) ·
  [WinCache](docs/WinCache-Handler.md)
- [Error Handling](docs/Error-Handling.md) · [API Reference](docs/API-Reference.md)
- [Recipes](docs/Recipes.md) · [Custom Handlers](docs/Custom-Handlers.md) · [Testing](docs/Testing.md)
- [FAQ](docs/FAQ.md) · [Troubleshooting](docs/Troubleshooting.md)

## Contributing

Bug reports and pull requests are welcome. CI runs PHP-CS-Fixer, PHPStan (max
level) and PHPUnit across PHP 8.2–8.6, plus a Redis/Memcached integration job.
Run the same static bundle locally with:

```bash
composer ci
```

See the [changelog](CHANGELOG.md) for release history.

## Credits

- [Muhammet ŞAFAK](https://muhammetsafak.com) <<info@muhammetsafak.com>>

## License

Copyright &copy; 2022 InitPHP — released under the [MIT License](./LICENSE).
