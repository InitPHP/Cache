# Redis handler

`InitPHP\Cache\Handler\Redis` stores items in Redis through the
[phpredis](https://github.com/phpredis/phpredis) extension (`ext-redis`).

## Options

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `prefix` | `string` | `cache_` | Prepended to keys. |
| `host` | `string` | `127.0.0.1` | Server host. |
| `port` | `int` | `6379` | Server port. |
| `timeout` | `int\|float` | `0` | Connection timeout in seconds (`0` = unlimited). |
| `password` | `string\|null` | `null` | `AUTH` password; skipped when `null`. |
| `database` | `int\|null` | `0` | Database index to `SELECT`. |

## Usage

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\Redis;

$cache = Cache::create(Redis::class, [
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'database' => 1,
    'password' => null,
]);

$cache->set('session:abc', $payload, 1800);
$cache->get('session:abc');
```

## How it works

- Each value is stored as a small serialised envelope, so `null`, `false`,
  arrays and objects round-trip exactly.
- TTLs are delegated to Redis (`SETEX`); items with no TTL never expire.
- `clear()` runs `FLUSHDB`, clearing the **whole selected database** regardless
  of `prefix`. Point the handler at a dedicated `database` if you need isolation.

## Errors

A failed connection, authentication or database selection throws a
[`CacheException`](exceptions.md) that wraps the underlying `RedisException`.

## Pure-PHP alternative

This handler requires the C extension. If you cannot install `ext-redis`, use a
PSR-16 adapter over a pure-PHP client (such as Predis) instead, or fall back to
the [PDO](pdo-handler.md) or [File](file-handler.md) handler.
