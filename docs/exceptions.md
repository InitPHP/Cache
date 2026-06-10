# Exceptions

The library throws two exception types, both under
`InitPHP\Cache\Exception`. Each implements the matching PSR-16 interface, so you
can catch library errors either by the concrete class or through PSR.

## `CacheException`

```php
InitPHP\Cache\Exception\CacheException
    extends \Exception
    implements \Psr\SimpleCache\CacheException
```

Thrown for errors that are not about an invalid argument, such as:

- a required handler extension is missing or disabled
  (`Cache::create()` / the constructor);
- a backend connection, authentication or database selection fails
  (Redis, Memcache, PDO);
- a required option is missing or invalid (the File `path`, the PDO `table`);
- the factory is given a class that does not exist or does not implement
  `CacheInterface`.

## `InvalidArgumentException`

```php
InitPHP\Cache\Exception\InvalidArgumentException
    extends \InvalidArgumentException
    implements \Psr\SimpleCache\InvalidArgumentException
```

Thrown when a cache key is invalid — empty, or containing one of the PSR-16
reserved characters `{}()/\@:`.

Because `Psr\SimpleCache\InvalidArgumentException` extends
`Psr\SimpleCache\CacheException`, an invalid-key error is also catchable as a
`CacheException`.

## Catching

```php
use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Exception\InvalidArgumentException;

try {
    $cache->get($userSuppliedKey);
} catch (InvalidArgumentException $e) {
    // The key was empty or contained a reserved character.
} catch (CacheException $e) {
    // A configuration or backend error.
}
```

Or catch everything the library can throw through the PSR interface:

```php
use Psr\SimpleCache\CacheException as PsrCacheException;

try {
    $cache->set($key, $value);
} catch (PsrCacheException $e) {
    // Any InitPHP\Cache error (invalid key or otherwise).
}
```

> Note: PSR-16 read/write methods return `false` (or a default) for ordinary
> backend hiccups rather than throwing. Exceptions are reserved for invalid
> arguments and genuine configuration/connection failures.
