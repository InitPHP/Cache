# InitPHP Cache

A lightweight [PSR-16 (Simple Cache)](https://www.php-fig.org/psr/psr-16/)
implementation with interchangeable handlers for the filesystem, PDO databases,
Redis, Memcache(d) and WinCache.

[![CI](https://github.com/InitPHP/Cache/actions/workflows/ci.yml/badge.svg)](https://github.com/InitPHP/Cache/actions/workflows/ci.yml)
[![Latest Stable Version](http://poser.pugx.org/initphp/cache/v)](https://packagist.org/packages/initphp/cache) [![Total Downloads](http://poser.pugx.org/initphp/cache/downloads)](https://packagist.org/packages/initphp/cache) [![License](http://poser.pugx.org/initphp/cache/license)](https://packagist.org/packages/initphp/cache) [![PHP Version Require](http://poser.pugx.org/initphp/cache/require/php)](https://packagist.org/packages/initphp/cache)

## Requirements

- PHP 8.0 or higher
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

See [`docs/`](docs/README.md) for the full guide, including per-handler
configuration and the exact semantics of TTLs, keys and counters.

## Notes

- **TTL:** `null` means "store with no expiry"; a positive integer or
  `DateInterval` sets a lifetime; a zero or negative TTL deletes the item.
- **Keys:** any non-empty string that does not contain the PSR-16 reserved
  characters `{}()/\@:`. An invalid key throws an
  [`InvalidArgumentException`](docs/exceptions.md).
- **Counters:** `increment()`/`decrement()` treat a missing or non-numeric item
  as `0` and store the result without an expiry. They behave identically across
  every handler.
- **Values:** anything `serialize()` can handle round-trips exactly — including
  `null`, `false` and objects.

## Documentation

- [Getting started](docs/getting-started.md)
- [Configuration & options](docs/configuration.md)
- [PSR-16 behaviour & the handler API](docs/psr-16.md)
- Handlers: [File](docs/file-handler.md) ·
  [PDO](docs/pdo-handler.md) ·
  [Redis](docs/redis-handler.md) ·
  [Memcache(d)](docs/memcache-handler.md) ·
  [WinCache](docs/wincache-handler.md)
- [Exceptions](docs/exceptions.md)

## Contributing

Bug reports and pull requests are welcome. CI runs PHP-CS-Fixer, PHPStan (max
level) and PHPUnit across PHP 8.0–8.4, plus a Redis/Memcached integration job.
Run the same static bundle locally with:

```bash
composer ci
```

See the [changelog](CHANGELOG.md) for release history.

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 InitPHP — released under the [MIT License](./LICENSE).
