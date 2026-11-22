# The Cache Factory

`InitPHP\Cache\Cache` is a tiny factory with a single static method. It builds a
handler, checks the runtime supports it, applies your options, and hands you back
a [`CacheInterface`](API-Reference.md#cacheinterface).

```php
namespace InitPHP\Cache;

final class Cache
{
    public static function create(
        string|CacheInterface $handler,
        array $options = []
    ): CacheInterface;
}
```

## Create from a class name

The usual form — pass a handler class and its options:

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;

$cache = Cache::create(File::class, [
    'path'   => __DIR__ . '/var/cache',
    'prefix' => 'app_',
]);
```

The class is instantiated for you, so it must be one of the built-in handlers or
your own class that implements [`CacheInterface`](API-Reference.md#cacheinterface)
and has a no-argument-compatible constructor (extending
[`BaseHandler`](Custom-Handlers.md) gives you exactly that).

## Create from an instance

If you have already built a handler, pass it straight through. `create()` simply
applies the options and returns it:

```php
$handler = new File(['path' => __DIR__ . '/var/cache']);

$cache = Cache::create($handler, ['prefix' => 'app_']);
// $cache === $handler
```

## You don't have to use the factory

The factory is convenience, not ceremony. Every handler's constructor accepts the
same options array, so this is equivalent to the first example:

```php
use InitPHP\Cache\Handler\File;

$cache = new File([
    'path'   => __DIR__ . '/var/cache',
    'prefix' => 'app_',
]);
```

The factory earns its keep when the handler class is dynamic (e.g. read from
config) and when you want a single place that validates the class.

## Options are case-insensitive

Option keys are normalised to lower case, so these are all the same option:

```php
Cache::create(File::class, ['PATH' => '/tmp/cache']);
Cache::create(File::class, ['Path' => '/tmp/cache']);
Cache::create(File::class, ['path' => '/tmp/cache']);
```

Options you don't set fall back to the handler's defaults (and the shared
`prefix` default of `cache_`). See [Configuration Options](Configuration-Options.md)
for every default.

## Reading and changing options later

Handlers expose two helpers from [`CacheInterface`](API-Reference.md#cacheinterface):

```php
$cache->getOption('prefix');          // "app_"
$cache->getOption('missing', '-');    // "-"  (default when unset)

$cache->setOptions(['prefix' => 'v2_']); // merge more options in; returns $this
```

`setOptions()` merges (case-insensitively) over the current options and returns
the handler, so it chains:

```php
$cache = (new Redis())->setOptions(['host' => 'cache.internal', 'database' => 2]);
```

## When `create()` throws

`create()` throws a [`CacheException`](Error-Handling.md) in three cases:

```php
use InitPHP\Cache\Exception\CacheException;

try {
    // 1. The class does not exist
    Cache::create('App\\Cache\\Nope');

    // 2. The class exists but is not a CacheInterface
    Cache::create(\stdClass::class);

    // 3. The handler's runtime requirement is missing
    //    (e.g. Redis without ext-redis)
    Cache::create(\InitPHP\Cache\Handler\Redis::class);
} catch (CacheException $e) {
    // log / fall back to another handler
}
```

The third case happens inside the handler constructor, which calls
`isSupported()` and rejects an unsupported environment. You can pre-check with
`(new Redis())->isSupported()` — see [Installation](Installation.md#checking-a-handler-at-runtime).

## Next steps

- [Keys, TTL & Values](Keys-TTL-and-Values.md) — the rules for what you store.
- [Configuration Options](Configuration-Options.md) — every option of every handler.
- [Custom Handlers](Custom-Handlers.md) — pass your own class to `create()`.
