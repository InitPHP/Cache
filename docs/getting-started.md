# Getting started

## Install

```bash
composer require initphp/cache
```

Requires PHP 8.0+. Install the extension your chosen handler needs
(`ext-pdo`, `ext-redis`, `ext-memcached`…); the File handler needs nothing
beyond the PHP core.

## Build a handler

Use the `Cache::create()` factory. Its first argument is a handler class name
(or an already-built handler instance); the second is an options array.

```php
require 'vendor/autoload.php';

use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;

$cache = Cache::create(File::class, [
    'path'   => __DIR__ . '/var/cache',
    'prefix' => 'app_',
]);
```

`create()` returns an object implementing `InitPHP\Cache\CacheInterface`. If the
class does not exist, does not implement the interface, or the runtime does not
support it (e.g. a missing extension), it throws an
[`InitPHP\Cache\Exception\CacheException`](exceptions.md).

You can also build a handler directly — the constructor takes the same options:

```php
use InitPHP\Cache\Handler\File;

$cache = new File(['path' => __DIR__ . '/var/cache']);
```

## Store and read

```php
$cache->set('greeting', 'Hello');      // no TTL → stored until deleted
$cache->set('token', 'abc', 3600);     // 3600 seconds

$cache->get('greeting');               // "Hello"
$cache->get('unknown');                // null (default)
$cache->get('unknown', 'fallback');    // "fallback"

$cache->has('greeting');               // true
$cache->delete('greeting');            // true
$cache->clear();                       // remove everything this handler owns
```

## The read-through pattern

The most common use: return a cached value, or compute and store it on a miss.

```php
$report = $cache->get('daily-report');

if ($report === null) {
    $report = build_expensive_report();   // your code
    $cache->set('daily-report', $report, 86400); // cache for a day
}

return $report;
```

> If `null` is a legitimate cached value for you, use `has()` to distinguish a
> miss from a stored `null`:
>
> ```php
> if (!$cache->has('daily-report')) {
>     $cache->set('daily-report', $report = build_expensive_report(), 86400);
> }
> $report = $cache->get('daily-report');
> ```

## Bulk operations

```php
$cache->setMultiple(['a' => 1, 'b' => 2, 'c' => 3], 600);

$cache->getMultiple(['a', 'b', 'missing'], 0);
// ['a' => 1, 'b' => 2, 'missing' => 0]

$cache->deleteMultiple(['a', 'b']);
```

`getMultiple()`, `setMultiple()` and `deleteMultiple()` accept any `iterable`,
so Generators and other `Traversable`s work too.

## Counters

```php
$cache->increment('views');        // 1
$cache->increment('views', 5);     // 6
$cache->decrement('views', 2);     // 4
$cache->get('views');              // 4
```

See [PSR-16 behaviour & the handler API](psr-16.md) for the exact rules around
keys, TTLs and counters, and [Configuration & options](configuration.md) for
each handler's settings.
