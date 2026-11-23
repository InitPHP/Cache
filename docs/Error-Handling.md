# Error Handling

The library throws exactly two exception types, both under
`InitPHP\Cache\Exception`. Each implements the matching PSR-16 interface, so you
can catch errors by the concrete class **or** through PSR.

| Class | Extends | Implements (PSR-16) | Thrown for |
| --- | --- | --- | --- |
| `CacheException` | `\Exception` | `Psr\SimpleCache\CacheException` | configuration / backend errors |
| `InvalidArgumentException` | `\InvalidArgumentException` | `Psr\SimpleCache\InvalidArgumentException` | invalid cache keys |

## `CacheException`

Thrown for problems that are **not** about a bad argument:

- a required extension is missing or disabled (building a handler);
- a backend connection / authentication / database selection fails
  ([Redis](Redis-Handler.md), [Memcache(d)](Memcache-Handler.md), [PDO](PDO-Handler.md));
- a required option is missing or invalid (the File [`path`](File-Handler.md), the
  PDO [`table`](PDO-Handler.md));
- the [factory](The-Cache-Factory.md) is given a class that does not exist or does
  not implement `CacheInterface`.

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\Redis;
use InitPHP\Cache\Exception\CacheException;

try {
    $cache = Cache::create(Redis::class, ['host' => 'redis.internal']);
    $cache->set('k', 'v');
} catch (CacheException $e) {
    // log and fall back to another handler
    error_log('Cache backend error: ' . $e->getMessage());
}
```

## `InvalidArgumentException`

Thrown when a cache key is **empty** or contains a character reserved by PSR-16
(`{}()/\@:`). It is raised by every key-taking method (`get`, `set`, `delete`,
`has`, the `*Multiple` methods, `increment`, `decrement`).

```php
use InitPHP\Cache\Exception\InvalidArgumentException;

try {
    $cache->get('bad:key'); // ":" is reserved
} catch (InvalidArgumentException $e) {
    // the key came from somewhere untrusted
}
```

See [Keys, TTL & Values](Keys-TTL-and-Values.md#keys) for the full key rules.

## One `catch` for everything

`Psr\SimpleCache\InvalidArgumentException` extends
`Psr\SimpleCache\CacheException`, so an invalid-key error is **also** a
`CacheException`. To handle any library error in one place, catch the PSR base:

```php
use Psr\SimpleCache\CacheException as PsrCacheException;

try {
    $cache->set($key, $value);
} catch (PsrCacheException $e) {
    // any InitPHP\Cache error — invalid key or backend failure
}
```

Or catch the concrete classes when you want to react differently:

```php
use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Exception\InvalidArgumentException;

try {
    $value = $cache->get($key);
} catch (InvalidArgumentException $e) {
    // 400-style: the caller supplied a bad key
} catch (CacheException $e) {
    // 500-style: a configuration or backend problem
}
```

## What does *not* throw

PSR-16 reserves exceptions for invalid arguments and genuine failures. Ordinary
runtime hiccups are reported through return values instead:

- A **cache miss** returns the `$default` (or `null`), not an exception.
- A failed **write/delete** generally returns `false`. For example, the
  [PDO handler](PDO-Handler.md#errors) returns `false` on a query-level error (such
  as a missing table) rather than throwing — only the **connection** failure
  throws.

This lets you treat the cache as best-effort:

```php
// A failed set() degrades gracefully — the value just isn't cached.
if (!$cache->set('report', $report, 3600)) {
    error_log('Could not cache the report; serving it uncached.');
}
```

## Failing soft on an unavailable backend

A common production pattern: never let a cache outage take down the request.
Build the handler defensively and fall back:

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\Redis;
use InitPHP\Cache\Handler\File;
use InitPHP\Cache\Exception\CacheException;

try {
    $cache = Cache::create(Redis::class, ['host' => 'redis.internal']);
    $cache->has('ping'); // force the lazy connection now, so we fail fast
} catch (CacheException $e) {
    // A directory that belongs to the application, not a shared one such as
    // sys_get_temp_dir(): the File handler unserialize()s what it finds there.
    $cache = Cache::create(File::class, ['path' => __DIR__ . '/var/cache']);
}
```

Do not point the `File` fallback at `/tmp` or any other directory other users can
write to. Another local user could pre-create it and plant a file in it, and
the handler would deserialize that file. Keep the directory owned by the
application user (mode `0700`); see [File Handler](File-Handler.md#usage).

## Next steps

- [Keys, TTL & Values](Keys-TTL-and-Values.md) — what makes a key invalid.
- [Troubleshooting](Troubleshooting.md) — symptoms, causes and fixes.
- [API Reference](API-Reference.md#exceptions) — the exception class definitions.
