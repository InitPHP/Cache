# InitPHP Cache — Documentation

`initphp/cache` is a [PSR-16](https://www.php-fig.org/psr/psr-16/) cache library
with interchangeable handlers. You configure a handler once through the
`Cache::create()` factory and then use the standard PSR-16 API everywhere.

## Contents

1. [Getting started](getting-started.md) — install, the factory, the read-through
   pattern.
2. [Configuration & options](configuration.md) — every option of every handler.
3. [PSR-16 behaviour & the handler API](psr-16.md) — keys, TTLs, bulk methods and
   the counter contract.
4. Handlers
   - [File](file-handler.md)
   - [PDO](pdo-handler.md)
   - [Redis](redis-handler.md)
   - [Memcache(d)](memcache-handler.md)
   - [WinCache](wincache-handler.md)
5. [Exceptions](exceptions.md)

## The 30-second version

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;

$cache = Cache::create(File::class, ['path' => __DIR__ . '/var/cache']);

$cache->set('key', 'value', 300); // store for 5 minutes
$cache->get('key');               // "value"
$cache->get('missing', 'default');// "default"
$cache->has('key');               // true
$cache->delete('key');            // true
```

Every handler implements `InitPHP\Cache\CacheInterface`, which extends
`Psr\SimpleCache\CacheInterface`. Code that depends on the PSR interface works
with any handler unchanged.
