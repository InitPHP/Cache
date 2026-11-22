# Troubleshooting

Symptoms, likely causes, and fixes. If none fit,
[open an issue](https://github.com/InitPHP/Cache/issues/new) with your PHP
version, handler and a minimal reproduction.

## `CacheException: … handler is not supported …`

**Cause:** you built a handler whose extension isn't loaded (e.g.
[Redis](Redis-Handler.md) without `ext-redis`, [Memcache](Memcache-Handler.md) without
`ext-memcached`/`ext-memcache`, or [WinCache](WinCache-Handler.md) on PHP 8).

**Fix:** install/enable the extension, or pre-check and fall back:

```php
$cache = (new Redis())->isSupported()
    ? Cache::create(Redis::class, [...])
    : Cache::create(File::class, ['path' => __DIR__ . '/var/cache']);
```

See [Installation](Installation.md#checking-a-handler-at-runtime).

## `CacheException: The File cache handler requires a … "path" option …`

**Cause:** the [File handler](File-Handler.md) was created without a `path` (or with
an empty one).

**Fix:** pass an existing, writable directory:

```php
$dir = __DIR__ . '/var/cache';
is_dir($dir) || mkdir($dir, 0775, true);
$cache = Cache::create(File::class, ['path' => $dir]);
```

## File handler: `set()` returns `true` but `get()` returns the default

**Likely causes:**

- **The directory isn't writable.** `set()` returns `false` when the write fails
  — check the return value and the directory permissions/owner.
- **You stored `null`.** A stored `null` is a real hit; `get()` still returns
  `null`. Use `has()` to confirm presence. See
  [Keys, TTL & Values](Keys-TTL-and-Values.md#null-values).
- **The TTL was `0` or negative**, which deletes the item.

## `InvalidArgumentException: The cache key … contains a reserved character`

**Cause:** the key contains one of `{}()/\@:` (often `:` from keys like
`user:42`) or is empty.

**Fix:** use `.`/`_`, or hash untrusted input:

```php
$key = 'user_' . $id;                 // or
$key = 'user_' . hash('xxh3', $raw);  // for arbitrary input
```

See [Keys, TTL & Values](Keys-TTL-and-Values.md#keys).

## PDO: every operation returns `false` / a miss

**Cause:** the cache table doesn't exist or its columns don't match. The PDO
handler returns `false` (rather than throwing) on query-level errors.

**Fix:** create the table with the correct schema for your driver — see
[PDO Handler → Create the table](PDO-Handler.md#create-the-table). To surface the
underlying error while debugging, query the table yourself with PDO in
`ERRMODE_EXCEPTION` and read the message.

## PDO: `CacheException: PDO cache connection error …`

**Cause:** the DSN, credentials or server are wrong/unreachable. Unlike query
errors, a **connection** failure throws.

**Fix:** verify the `dsn`, `username`, `password` and that the database is
reachable. Wrap construction in a `try/catch` to fall back. See
[Error Handling](Error-Handling.md#failing-soft-on-an-unavailable-backend).

## PDO: `clear()` removed more/fewer rows than expected

**Cause:** `clear()` deletes rows whose `name` matches the `prefix`. With an empty
prefix it clears the whole table; with a prefix it matches `prefix%` (wildcards in
the prefix are escaped, so `cache_` matches `cache_*` literally).

**Fix:** set a distinct `prefix` per application sharing the table.

## Redis/Memcached: `clear()` wiped data I didn't expect

**Cause:** `clear()` flushes the **whole** Redis database (`FLUSHDB`) or Memcached
instance (`flush()`), not just your prefix.

**Fix:** give the cache its own Redis `database` index, or a dedicated Memcached
instance. See [Redis](Redis-Handler.md#how-it-works) /
[Memcache(d)](Memcache-Handler.md#how-it-works).

## My counter "loses count" after a while

**Cause:** `increment()` stores **without** a TTL, so a counter you seeded with a
windowed `set($key, 0, $window)` loses its window on the first increment — or an
expiry elsewhere reset it.

**Fix:** manage the window with `set()` and a rotating key, as in the
[soft rate limiter](Counters.md#counters-and-ttl-a-soft-rate-limiter); or use a
native atomic counter for strict needs.

## Values come back changed (objects look "different")

**Cause:** values are stored via `serialize()`/`unserialize()`, so you get an
**equal** object back, not the *same* instance. Resources, closures and PDO
handles can't be cached at all.

**Fix:** compare with `==`/`assertEquals`, not `===`/`assertSame`, and cache plain
data rather than live objects.

## Static analysis can't find `\Redis` / `wincache_*`

**Cause:** you're analysing code that uses an extension you don't have installed.

**Fix:** install the extension in your analysis environment, or add stub files
(the package itself ships a small WinCache stub for exactly this — see its
`stubs/` directory and `phpstan.neon.dist`).

## Still stuck?

Collect your PHP version, the handler and options, and a minimal snippet, then
[open an issue](https://github.com/InitPHP/Cache/issues/new) or ask in
[Discussions](https://github.com/orgs/InitPHP/discussions).
