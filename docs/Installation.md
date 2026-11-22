# Installation

`initphp/cache` is distributed via
[Packagist](https://packagist.org/packages/initphp/cache). Its only runtime
dependency is the PSR-16 interface package; each backend may additionally need
its own PHP extension.

## Requirements

| Requirement | Notes |
| --- | --- |
| **PHP `>= 8.2`** | The code uses `declare(strict_types=1)`, `mixed`, and union types. |
| [`psr/simple-cache`](https://packagist.org/packages/psr/simple-cache) `^3.0` | Pulled in automatically by Composer. |
| Composer | For installation and autoloading. |

### Per-handler extensions

| Handler | Extension |
| --- | --- |
| [File](File-Handler.md) | none — PHP core only |
| [PDO](PDO-Handler.md) | `ext-pdo` (plus the matching driver, e.g. `pdo_mysql`, `pdo_sqlite`) |
| [Redis](Redis-Handler.md) | `ext-redis` (phpredis) |
| [Memcache(d)](Memcache-Handler.md) | `ext-memcached` (preferred) or `ext-memcache` |
| [WinCache](WinCache-Handler.md) | `ext-wincache` — _deprecated, Windows only_ |

> You only need the extension for the handler you actually use. The File handler
> works on any supported PHP install with no extra setup.

## Install

```bash
composer require initphp/cache
```

Composer registers the PSR-4 namespace `InitPHP\Cache\`, so everything is
available as soon as you require `vendor/autoload.php`.

## Verify the install

Drop this script into your project root as `check-cache.php`:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;

$dir = __DIR__ . '/var/cache';
@mkdir($dir, 0775, true);

$cache = Cache::create(File::class, ['path' => $dir]);

$cache->set('check', 'it works', 60);

echo $cache->get('check') === 'it works'
    ? "InitPHP Cache is working.\n"
    : "Something is wrong.\n";

$cache->delete('check');
```

```bash
php check-cache.php
# InitPHP Cache is working.
```

## Checking a handler at runtime

Every handler can tell you whether the current runtime supports it
(`isSupported()`), which is handy for picking a backend dynamically:

```php
use InitPHP\Cache\Handler\Redis;
use InitPHP\Cache\Handler\File;
use InitPHP\Cache\Cache;

$cache = (new Redis())->isSupported()
    ? Cache::create(Redis::class, ['host' => '127.0.0.1'])
    : Cache::create(File::class, ['path' => __DIR__ . '/var/cache']);
```

> Building an unsupported handler through the factory (e.g. `Redis` without
> `ext-redis`) throws a [`CacheException`](Error-Handling.md). The check above lets
> you fall back gracefully instead.

## Next steps

- [Quick Start](Quick-Start.md) — store and read your first values.
- [The Cache Factory](The-Cache-Factory.md) — everything `Cache::create()` does.
- [Configuration Options](Configuration-Options.md) — every option of every handler.
