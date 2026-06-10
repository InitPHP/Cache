# PSR-16 behaviour & the handler API

Every handler implements `InitPHP\Cache\CacheInterface`:

```php
namespace InitPHP\Cache;

interface CacheInterface extends \Psr\SimpleCache\CacheInterface
{
    public function setOptions(array $options = []): static;
    public function getOption(string $key, mixed $default = null): mixed;
    public function isSupported(): bool;
    public function increment(string $key, int $offset = 1): int;
    public function decrement(string $key, int $offset = 1): int;
}
```

The eight PSR-16 methods (`get`, `set`, `delete`, `clear`, `has`,
`getMultiple`, `setMultiple`, `deleteMultiple`) come from
`Psr\SimpleCache\CacheInterface`.

## Keys

A key is any **non-empty string** that does not contain a character reserved by
PSR-16:

```
{ } ( ) / \ @ :
```

An empty key or one containing a reserved character throws
`InitPHP\Cache\Exception\InvalidArgumentException` (which is both an SPL
`InvalidArgumentException` and a `Psr\SimpleCache\InvalidArgumentException`).

The configured `prefix` is prepended *after* validation, so the prefix itself is
not subject to the reserved-character rule.

```php
$cache->get('user.42');     // fine
$cache->get('user:42');     // throws — ":" is reserved
$cache->get('');            // throws — empty
```

## Values

Any value `serialize()` accepts round-trips exactly: strings, integers, floats,
booleans, `null`, arrays and objects.

```php
$cache->set('n', null);
$cache->has('n');               // true — null was really stored
$cache->get('n', 'default');    // null, not "default"
```

`get()` returns the `$default` argument **as-is** on a miss; it is never called,
even when it is a callable.

## TTL

`set()` and `setMultiple()` accept `null`, an integer number of seconds, or a
`DateInterval`:

| TTL | Effect |
| --- | ------ |
| `null` | Store with no expiry (until deleted or evicted). |
| positive `int` | Expire after that many seconds. |
| `DateInterval` | Expire after the interval, measured from now. |
| `0` or negative | Delete the item; `set()` returns `true`. |

```php
$cache->set('a', 1);                       // forever
$cache->set('b', 1, 60);                   // 60 seconds
$cache->set('c', 1, new DateInterval('PT5M')); // 5 minutes
$cache->set('d', 1, 0);                     // deletes "d"
```

> The File and PDO handlers store the expiry and enforce it on read. The Redis,
> Memcache and WinCache handlers delegate expiry to the backend.

## Bulk methods

`getMultiple()`, `setMultiple()` and `deleteMultiple()` accept any `iterable`
(arrays, Generators, `Traversable`s):

```php
function keys(): Generator {
    yield 'a';
    yield 'b';
}

$cache->getMultiple(keys(), 'default');
```

`getMultiple()` returns a map keyed by the requested keys, with `$default` for
every missing one. `setMultiple()` and `deleteMultiple()` return `true` only
when every individual operation succeeded.

## Counters

`increment()` and `decrement()` adjust an integer counter and return the **new
value**. They share one contract across every handler:

- a missing or non-numeric item is treated as `0`, so `increment('x', 5)`
  starts the counter at `5`;
- the result is stored **without an expiry**;
- the stored value is a normal integer, so `get()` reads it back.

```php
$cache->increment('hits');     // 1
$cache->increment('hits', 9);  // 10
$cache->decrement('hits', 4);  // 6
$cache->get('hits');           // 6
```

> These counters are read-modify-write operations built on `get()`/`set()`, so
> they are consistent everywhere but **not atomic** across concurrent processes.
> If you need atomic counters under high concurrency, use your backend's native
> facility directly.

## Capability check

`isSupported()` reports whether the current runtime can use a handler (for
example, whether the required extension is loaded). The constructor calls it and
throws a `CacheException` when a handler is unsupported, so you rarely need it
directly.
