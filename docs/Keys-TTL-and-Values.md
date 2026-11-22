# Keys, TTL & Values

This page covers the three things you control on every write: the **key**, the
**value**, and the **time-to-live**. The rules are the same for every handler.

## Keys

A cache key must be a **non-empty string** that contains **none** of the
characters reserved by PSR-16:

```
{ } ( ) / \ @ :
```

Break either rule and the library throws an
[`InvalidArgumentException`](Error-Handling.md):

```php
$cache->get('user.42');   // fine
$cache->get('user_42');   // fine
$cache->get('user:42');   // throws — ":" is reserved
$cache->get('');          // throws — empty key
```

The reserved set is available as a constant:

```php
InitPHP\Cache\BaseHandler::RESERVED_CHARACTERS; // "{}()/\@:"
```

> **Why `/` and `\` are reserved matters for the File handler:** because they can
> never appear in a key, a key can never escape the cache directory. Keys are
> safe to build from user-controlled identifiers — but see
> [practical key advice](#practical-key-advice) below.

### The prefix

Every key is automatically prefixed with the `prefix` option (default `cache_`)
**after** validation, so the prefix itself is not subject to the reserved-char
rule. The prefix:

- keeps two applications that share one backend from reading each other's keys;
- scopes `clear()` — only items with the handler's prefix are removed (for the
  [File](File-Handler.md) and [PDO](PDO-Handler.md) handlers).

```php
$cache = Cache::create(File::class, ['path' => $dir, 'prefix' => 'app_']);
$cache->set('user', $u);   // physically stored as "app_user"
```

Set `prefix` to `''` to disable it.

### Practical key advice

- Keep keys reasonably short and ASCII. The [File](File-Handler.md) handler uses the
  prefixed key as a filename, so extremely long keys can hit filesystem limits.
- A common trick for arbitrary input is to hash it into a safe key:
  ```php
  $key = 'page_' . hash('xxh3', $url); // no reserved chars, bounded length
  $cache->set($key, $html, 600);
  ```

## Values

Any value PHP's `serialize()` can handle round-trips **exactly**: strings,
integers, floats, booleans, `null`, arrays and objects.

```php
$cache->set('int', 42);            $cache->get('int');    // 42      (int)
$cache->set('float', 3.14);        $cache->get('float');  // 3.14    (float)
$cache->set('bool', false);        $cache->get('bool');   // false   (bool)
$cache->set('list', [1, 2, 3]);    $cache->get('list');   // [1,2,3] (array)

$user = new stdClass();
$user->name = 'Jane';
$cache->set('user', $user);
$cache->get('user');               // stdClass { name: "Jane" }
```

> Objects must be serialisable. Closures, PDO connections, open resources and the
> like cannot be cached.

### `null` values

`null` is a perfectly valid value — it is stored and returned as `null`, not
treated as "absent":

```php
$cache->set('maybe', null);
$cache->has('maybe');             // true  — it really is stored
$cache->get('maybe', 'default');  // null  — NOT "default"
```

Because `get()` also returns its `$default` on a miss, you cannot tell a stored
`null` from a miss using `get()` alone. Use `has()` when that distinction
matters:

```php
if (!$cache->has('maybe')) {
    $cache->set('maybe', compute()); // only compute on a real miss
}
$value = $cache->get('maybe');
```

### The default is returned as-is

`get($key, $default)` returns `$default` verbatim on a miss. It is **never
called**, even if it is a callable — this matches PSR-16:

```php
$cache->get('missing', 'strlen'); // "strlen" (the string), not a function call
```

If you want lazy computation, use the `has()`/`get()` pattern above.

## Time-to-live (TTL)

`set()` and `setMultiple()` accept the TTL as the last argument. It can be `null`,
an integer number of seconds, or a `DateInterval`:

| TTL value | Effect |
| --- | --- |
| `null` (default) | Store with **no expiry** — kept until deleted or evicted. |
| positive `int` | Expire after that many **seconds**. |
| `DateInterval` | Expire after the interval, measured from **now**. |
| `0` or negative `int` | **Delete** the item; `set()` returns `true`. |

```php
$cache->set('a', 1);                            // forever
$cache->set('b', 1, 60);                        // 60 seconds
$cache->set('c', 1, new DateInterval('PT5M'));  // 5 minutes
$cache->set('d', 1, 0);                         // deletes "d", returns true
$cache->set('e', 1, -10);                       // same — already expired
```

### Where expiry is enforced

- The [File](File-Handler.md) and [PDO](PDO-Handler.md) handlers store the expiry
  alongside the value and drop the item on read once it has passed.
- The [Redis](Redis-Handler.md), [Memcache(d)](Memcache-Handler.md) and
  [WinCache](WinCache-Handler.md) handlers delegate expiry to the backend.

In all cases the observable behaviour is identical: an expired item reads back as
a miss.

> The Memcache and WinCache handlers also have a `default_ttl` option used **only
> when `set()` is called without a TTL** (`0` = no expiry). See their pages.

## Next steps

- [Counters](Counters.md) — `increment()` / `decrement()` semantics.
- [Bulk Operations](Bulk-Operations.md) — many keys at once.
- [Error Handling](Error-Handling.md) — what gets thrown, and when.
