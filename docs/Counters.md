# Counters

`increment()` and `decrement()` adjust an integer counter and return the **new
value**. They are an InitPHP extension on top of PSR-16 (declared on
[`CacheInterface`](API-Reference.md#cacheinterface)), and they behave **identically
on every handler**.

```php
public function increment(string $key, int $offset = 1): int;
public function decrement(string $key, int $offset = 1): int;
```

## Basic use

```php
$cache->increment('views');     // 1   (key was missing → starts at 0)
$cache->increment('views');     // 2
$cache->increment('views', 10); // 12
$cache->decrement('views', 5);  // 7
$cache->get('views');           // 7   — readable as a normal value
```

## The contract

The behaviour is the same for File, PDO, Redis, Memcache and WinCache:

- A **missing item, or one that is not an `int` or `float`,** is treated as `0`, so the result equals the
  offset:
  ```php
  $cache->increment('fresh', 5);     // 5  (was missing)

  $cache->set('label', 'hello');
  $cache->increment('label', 3);     // 3  (not an int or float → reset to 0, then +3)
  ```
- The **new integer value is returned**.
- The result is **stored without an expiry**. Incrementing a key that had a TTL
  drops that TTL.
- `decrement($key, $n)` is exactly `increment($key, -$n)`, so counters can go
  negative:
  ```php
  $cache->set('stock', 3);
  $cache->decrement('stock', 5);     // -2
  ```

## Not atomic across processes

These counters are implemented once in [`BaseHandler`](Custom-Handlers.md) as a
**read-modify-write** over `get()` / `set()`. That makes them uniform and
compatible with values written by `set()`, but it means they are **not atomic**
under concurrency: two processes incrementing the same key at the same instant
can race and lose an update.

For most caches (page-view counters, soft rate limits, metrics) that is fine. If
you need strict atomic counters under heavy concurrency, use your backend's
native facility directly — for example phpredis `INCRBY`:

```php
// Direct, atomic Redis counter (bypasses this library's envelope format)
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$redis->incrBy('atomic:counter', 1);
```

> Keep such native counters in their **own keyspace**. A value written with the
> library's `set()` is a serialised envelope, not a bare integer, so the two
> styles must not share a key.

## Counters and TTL: a soft rate limiter

Because `increment()` stores **without an expiry**, it is not a drop-in tool for
a time-windowed limit: the first increment after a `set($key, 0, $window)` would
drop the window. For a soft fixed-window limiter, manage the window yourself with
`get()` / `set()` and a key that rotates per window:

```php
function rateLimit(CacheInterface $cache, string $ip, int $max, int $window): bool
{
    // The key changes every $window seconds, so each window self-expires.
    $bucket = (int) floor(time() / $window);
    $key    = 'rate_' . hash('xxh3', $ip) . '_' . $bucket;

    $count = (int) $cache->get($key, 0) + 1;
    $cache->set($key, $count, $window); // re-set with the window TTL each hit

    return $count <= $max;
}

if (!rateLimit($cache, $clientIp, 100, 60)) {
    http_response_code(429);
    exit('Too Many Requests');
}
```

This stays correct with the library's semantics (the TTL lives on the `set()`,
not on `increment()`), though it shares the same non-atomic caveat — fine for a
soft limit. For a hard limit, use a native atomic counter with an expiry, e.g.
phpredis `INCR` + `EXPIRE`.

## Next steps

- [Keys, TTL & Values](Keys-TTL-and-Values.md) — how TTLs and values behave.
- [Recipes](Recipes.md) — more practical patterns.
- [API Reference](API-Reference.md#initphp-extensions) — the method signatures.
