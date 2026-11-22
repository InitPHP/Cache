# API Reference

Every public member of `initphp/cache`. For narrative explanations, follow the
links into the concept pages.

---

## `Cache` (factory)

`InitPHP\Cache\Cache` — see [The Cache Factory](The-Cache-Factory.md).

### `create()`

```php
public static function create(
    string|CacheInterface $handler,
    array $options = []
): CacheInterface
```

Builds (or accepts) a handler, applies `$options`, and returns it.

- `$handler` — a handler **class name** to instantiate, or an already-built
  handler **instance**.
- `$options` — handler options, keys matched case-insensitively.
- **Throws** [`CacheException`](#cacheexception) if the class does not exist, does
  not implement `CacheInterface`, or the runtime does not support it.

---

## `CacheInterface`

`InitPHP\Cache\CacheInterface extends Psr\SimpleCache\CacheInterface`.

The eight PSR-16 methods plus five InitPHP extensions. Every handler implements
this interface.

### PSR-16 methods

```php
public function get(string $key, mixed $default = null): mixed;
```
Return the value for `$key`, or `$default` (returned as-is) on a miss.
**Throws** [`InvalidArgumentException`](#invalidargumentexception) for an invalid
key. See [Keys, TTL & Values](Keys-TTL-and-Values.md).

```php
public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;
```
Store `$value`. `$ttl`: `null` = forever, seconds, or a `DateInterval`; `0`/
negative deletes the item. Returns `true` on success. See
[TTL](Keys-TTL-and-Values.md#time-to-live-ttl).

```php
public function delete(string $key): bool;
```
Remove one item. Deleting a missing key still succeeds.

```php
public function clear(): bool;
```
Remove every item this handler owns. Scope differs per handler — File/PDO honour
the `prefix`; Redis/Memcache/WinCache flush the whole store. See each handler
page.

```php
public function has(string $key): bool;
```
Whether a live (non-expired) item exists. Use this to distinguish a stored `null`
from a miss.

```php
public function getMultiple(iterable $keys, mixed $default = null): iterable;
```
Map of `key => value`, with `$default` for misses. See
[Bulk Operations](Bulk-Operations.md).

```php
public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool;
```
Store many `key => value` pairs with one TTL. `true` only if all succeed.

```php
public function deleteMultiple(iterable $keys): bool;
```
Delete many keys. `true` only if all succeed.

### InitPHP extensions

```php
public function setOptions(array $options = []): static;
```
Merge options (case-insensitively) into the handler; returns `$this`.

```php
public function getOption(string $key, mixed $default = null): mixed;
```
Read one option (case-insensitive lookup), or `$default` when unset.

```php
public function isSupported(): bool;
```
Whether the current runtime can use this handler (e.g. the extension is loaded).

<h3 id="increment"><code>increment()</code> / <code>decrement()</code></h3>

```php
public function increment(string $key, int $offset = 1): int;
public function decrement(string $key, int $offset = 1): int;
```
Adjust an integer counter and return the **new value**. A missing item, or one that is not an int or float,
counts as `0`; the result is stored without an expiry. Identical on every
handler, **not** atomic across processes. See [Counters](Counters.md).

---

## `BaseHandler`

`InitPHP\Cache\BaseHandler implements CacheInterface` — the abstract base every
built-in handler extends. Relevant when [writing your own handler](Custom-Handlers.md).

### Constant

```php
public const RESERVED_CHARACTERS = '{}()/\@:';
```
The PSR-16 characters a key may not contain.

### Methods a subclass must implement

`get()`, `set()`, `delete()`, `clear()`, `has()`, `isSupported()` — the
storage-specific primitives. (`increment()`, `decrement()`, the `*Multiple()`
methods, options handling and key/TTL helpers are provided.)

### Protected helpers for subclasses

```php
protected function name(string $key): string;
```
Validate `$key` and return it prefixed with the `prefix` option — the physical
storage key. Throws [`InvalidArgumentException`](#invalidargumentexception) for an
invalid key.

```php
protected function validateKey(string $key): string;
```
Validate a key without prefixing it (returns it unchanged, or throws).

```php
protected function ttlToSeconds(null|int|DateInterval $ttl): ?int;
```
Normalise a PSR-16 TTL to seconds (`null` = no expiry). A `DateInterval` is
resolved from now; the result may be zero/negative ("already expired").

```php
protected function optionInt(string $key, int $default = 0): int;
protected function optionFloat(string $key, float $default = 0.0): float;
protected function optionString(string $key, string $default = ''): string;
```
Read an option coerced to a scalar type, falling back to `$default` when the
stored value is not coercible.

---

## Exceptions

`InitPHP\Cache\Exception\*` — see [Error Handling](Error-Handling.md).

### `CacheException`

```php
class CacheException
    extends \Exception
    implements \Psr\SimpleCache\CacheException
```
Configuration and backend errors (missing extension, connection failure, missing
File `path`, invalid PDO `table`, bad factory class).

### `InvalidArgumentException`

```php
class InvalidArgumentException
    extends \InvalidArgumentException
    implements \Psr\SimpleCache\InvalidArgumentException
```
An invalid cache key (empty, or containing a reserved character). Because the PSR
interface extends `Psr\SimpleCache\CacheException`, this is also catchable as a
`CacheException`.

## Next steps

- [Error Handling](Error-Handling.md) — the exception model in depth.
- [Custom Handlers](Custom-Handlers.md) — using the `BaseHandler` helpers above.
