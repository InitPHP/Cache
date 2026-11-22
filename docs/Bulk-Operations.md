# Bulk Operations

Three PSR-16 methods read, write or delete many items in one call. They are
convenience wrappers — each one loops over the single-item methods — but they
keep call sites tidy and accept any `iterable`.

```php
public function getMultiple(iterable $keys, mixed $default = null): iterable;
public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool;
public function deleteMultiple(iterable $keys): bool;
```

## `getMultiple()`

Returns a map keyed by the requested keys, with `$default` for every miss:

```php
$cache->set('a', 1);
$cache->set('b', 2);

$cache->getMultiple(['a', 'b', 'missing']);
// ['a' => 1, 'b' => 2, 'missing' => null]

$cache->getMultiple(['a', 'missing'], 0);
// ['a' => 1, 'missing' => 0]
```

## `setMultiple()`

Stores every pair, with one shared TTL (same rules as
[`set()`](Keys-TTL-and-Values.md#time-to-live-ttl)). Returns `true` only when
**every** write succeeded:

```php
$cache->setMultiple([
    'a' => 1,
    'b' => 2,
    'c' => 3,
], 600); // all three expire in 600 seconds → true
```

An empty input is a successful no-op:

```php
$cache->setMultiple([]); // true
```

## `deleteMultiple()`

Removes every key; returns `true` only when **every** delete succeeded:

```php
$cache->deleteMultiple(['a', 'b', 'c']); // true
$cache->deleteMultiple([]);              // true (nothing to do)
```

> Deleting a key that does not exist counts as success — the post-condition
> ("the key is gone") already holds.

## Any iterable works

PSR-16 requires `iterable`, so arrays, Generators and other `Traversable`s are
all accepted. This is handy for streaming a large key set without building an
array:

```php
function keysToWarm(): Generator
{
    yield 'home';
    yield 'about';
    yield 'pricing';
}

$pages = $cache->getMultiple(keysToWarm(), null);
```

For `setMultiple()` the iterable yields `key => value` pairs:

```php
function pairs(): Generator
{
    foreach (fetch_rows() as $row) {
        yield 'row_' . $row['id'] => $row;
    }
}

$cache->setMultiple(pairs(), 3600);
```

## Keys are still validated

Each key goes through the same validation as the single-item methods, so an
invalid key in the set throws an
[`InvalidArgumentException`](Error-Handling.md):

```php
$cache->getMultiple(['ok', 'bad:key']); // throws — ":" is reserved
```

> Because the methods iterate, a throw can happen part-way through. Validate
> untrusted keys up front (or hash them — see
> [Keys, TTL & Values](Keys-TTL-and-Values.md#practical-key-advice)) if you need
> all-or-nothing behaviour.

## Performance note

These helpers loop over the single-item operations rather than issuing one
batched backend command. They reduce code, not round-trips. For very large,
latency-sensitive batch loads against [Redis](Redis-Handler.md) or
[Memcache(d)](Memcache-Handler.md), a native multi-get may be faster — reach for the
underlying client in that hot path.

## Next steps

- [Keys, TTL & Values](Keys-TTL-and-Values.md) — per-item rules these inherit.
- [API Reference](API-Reference.md) — exact signatures and return types.
