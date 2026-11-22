# Quick Start

Five minutes from `composer require` to a working cache.

## 1. Build a handler

Use the [`Cache::create()`](The-Cache-Factory.md) factory. Its first argument is a
handler class; the second is an options array.

```php
<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;

$cache = Cache::create(File::class, [
    'path' => __DIR__ . '/var/cache',   // the directory must exist and be writable
]);
```

`create()` returns an object implementing
[`CacheInterface`](API-Reference.md#cacheinterface), which extends
`Psr\SimpleCache\CacheInterface`. You can type-hint either interface anywhere in
your app.

## 2. Store and read

```php
$cache->set('greeting', 'Hello');     // no TTL → kept until deleted
$cache->set('token', 'abc123', 3600); // expires in 3600 seconds

$cache->get('greeting');              // "Hello"
$cache->get('unknown');               // null   (default)
$cache->get('unknown', 'fallback');   // "fallback"

$cache->has('greeting');              // true
$cache->delete('greeting');           // true
$cache->clear();                      // wipe everything this handler owns
```

Values keep their type and structure — strings, ints, floats, booleans, `null`,
arrays and objects all round-trip exactly:

```php
$cache->set('config', ['debug' => true, 'level' => 5]);
$cache->get('config');                // ['debug' => true, 'level' => 5]
```

## 3. The read-through pattern

The most common use of a cache: return the stored value, or compute and store it
on a miss.

```php
$report = $cache->get('daily-report');

if ($report === null) {
    $report = build_expensive_report();        // your slow code
    $cache->set('daily-report', $report, 86400); // cache for a day
}

return $report;
```

> If `null` is a legitimate value for you, use `has()` to tell a miss from a
> stored `null` — see [Keys, TTL & Values](Keys-TTL-and-Values.md#null-values).

## 4. Switch the backend in one line

Because every handler shares the same API, moving from the filesystem to Redis is
a one-line change:

```php
use InitPHP\Cache\Handler\Redis;

$cache = Cache::create(Redis::class, [
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'database' => 0,
]);

// ...the rest of your code is unchanged.
$cache->set('user:42', ['name' => 'Jane'], 3600);
```

See the [handler pages](File-Handler.md) for each backend's options.

## 5. Bulk operations and counters

```php
// Many at once (accepts arrays or any iterable)
$cache->setMultiple(['a' => 1, 'b' => 2, 'c' => 3], 600);
$cache->getMultiple(['a', 'b', 'missing'], 0); // ['a'=>1, 'b'=>2, 'missing'=>0]
$cache->deleteMultiple(['a', 'b']);

// Counters (return the new value; not atomic)
$cache->increment('views');     // 1
$cache->increment('views', 5);  // 6
$cache->decrement('views', 2);  // 4
```

See [Bulk Operations](Bulk-Operations.md) and [Counters](Counters.md).

## What just happened

1. `create()` instantiated the handler, verified the runtime supports it, and
   merged your options over the defaults.
2. `set()` serialised the value and wrote it to the backend, with the TTL you
   gave (or none).
3. `get()` read it back, returned your `$default` on a miss, and honoured the
   expiry.

## Things worth knowing immediately

- **Keys are validated.** A key must be a non-empty string with none of the
  reserved characters `{}()/\@:`; otherwise an
  [`InvalidArgumentException`](Error-Handling.md) is thrown. See
  [Keys, TTL & Values](Keys-TTL-and-Values.md#keys).
- **A zero or negative TTL deletes the item** (and returns `true`).
- **The default key prefix is `cache_`.** It scopes `clear()` so caches sharing
  one backend don't wipe each other. Change it with the `prefix` option.
- **`get()` never calls your default.** A callable default is returned as a plain
  value, per PSR-16.

## Next steps

- [The Cache Factory](The-Cache-Factory.md) — instances, options, error cases.
- [Keys, TTL & Values](Keys-TTL-and-Values.md) — the full rules.
- [Recipes](Recipes.md) — read-through, memoization, rate limiting, and more.
- [Configuration Options](Configuration-Options.md) — every option, every handler.
