# Redis Handler

`InitPHP\Cache\Handler\Redis` stores items in Redis through the
[phpredis](https://github.com/phpredis/phpredis) extension (`ext-redis`). It is a
good choice for hot, distributed caches shared across many servers.

## Options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
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

- Each value is stored as a small **serialised envelope**, so `null`, `false`,
  arrays and objects round-trip exactly (a bare Redis string could not tell a
  stored `false` from a miss — the envelope fixes that).
- TTLs are delegated to Redis with `SETEX`; an item with no TTL never expires.
- `has()` uses `EXISTS`.
- `clear()` runs **`FLUSHDB`**, which clears the **whole selected database**,
  regardless of `prefix`.

> **Isolate with `database`, not just `prefix`.** Because `clear()` flushes the
> entire database, point the handler at a dedicated database index if other data
> shares the server:
>
> ```php
> $cache = Cache::create(Redis::class, ['database' => 3]);
> $cache->clear(); // flushes only database 3
> ```

## Connection lifecycle

The connection is opened lazily on first use and reused for the handler's
lifetime. A failed **connection**, **authentication** (`AUTH`) or **database
selection** (`SELECT`) throws a [`CacheException`](Error-Handling.md) that wraps the
underlying `RedisException`:

```php
use InitPHP\Cache\Exception\CacheException;

try {
    $cache = Cache::create(Redis::class, ['host' => 'redis.internal']);
    $cache->set('k', 'v');
} catch (CacheException $e) {
    error_log('Redis unavailable: ' . $e->getMessage());
}
```

## Requirements

This handler needs the **phpredis C extension** (`ext-redis`). Check it with:

```php
extension_loaded('redis'); // true when available
```

If you cannot install the extension, use a PSR-16 adapter over a pure-PHP client
(such as Predis), or fall back to the [PDO](PDO-Handler.md) or [File](File-Handler.md)
handler.

## Atomic counters

This library's [`increment()` / `decrement()`](Counters.md) are uniform across
handlers and therefore **not** atomic. For a strict atomic Redis counter, call
phpredis directly in its own keyspace — see [Counters](Counters.md#not-atomic-across-processes).

## Next steps

- [Configuration Options](Configuration-Options.md) — all handlers side by side.
- [Counters](Counters.md) — the counter contract and the atomic alternative.
- [Error Handling](Error-Handling.md) — connection failures and exceptions.
