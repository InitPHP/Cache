# FAQ

### Which handler should I use?

- **Local development / small sites:** [File](File-Handler.md) — zero setup.
- **You already run a database, no extra service wanted:** [PDO](PDO-Handler.md).
- **Hot, shared cache across servers:** [Redis](Redis-Handler.md) or
  [Memcache(d)](Memcache-Handler.md).
- **WinCache:** don't — it's [deprecated](WinCache-Handler.md) and unavailable on
  PHP 8.

See the comparison table on the [Home](Home.md#the-handlers-at-a-glance) page.

### Is this a PSR-16 cache?

Yes. Every handler implements `Psr\SimpleCache\CacheInterface` (via
[`CacheInterface`](API-Reference.md#cacheinterface)), so you can use it anywhere a
PSR-16 cache is expected, and the package `provide`s
`psr/simple-cache-implementation`.

### Can I store `null` / `false`?

Yes — any serialisable value round-trips exactly. Because `get()` also returns
its default on a miss, use `has()` to tell a stored `null` from a miss. See
[Keys, TTL & Values](Keys-TTL-and-Values.md#null-values).

### Why did my key throw an `InvalidArgumentException`?

The key was empty or contained a PSR-16 reserved character (`{}()/\@:`). Common
culprits are `:` (e.g. `user:42`) and `/`. Use `.` or `_` instead, or hash
untrusted input. See [Keys, TTL & Values](Keys-TTL-and-Values.md#keys).

### What does a TTL of `0` do?

It **deletes** the item (it's already expired), and `set()` returns `true`. Use
`null` for "no expiry". See [TTL](Keys-TTL-and-Values.md#time-to-live-ttl).

### Does `clear()` only remove my keys?

It depends on the backend:

- [File](File-Handler.md) and [PDO](PDO-Handler.md) clear only items with the handler's
  `prefix`.
- [Redis](Redis-Handler.md) runs `FLUSHDB` (the whole selected database) and
  [Memcache(d)](Memcache-Handler.md)/[WinCache](WinCache-Handler.md) flush the whole
  store.

For Redis, isolate with a dedicated `database`; for Memcached, a dedicated
instance.

### Are `increment()` / `decrement()` atomic?

No. They are uniform across handlers but implemented as read-modify-write, so
they can race under concurrency. For strict atomic counters, use the backend's
native command in its own keyspace. See [Counters](Counters.md#not-atomic-across-processes).

### Does increment keep the item's TTL?

No — `increment()`/`decrement()` store the new value **without** an expiry. If you
need a windowed counter, manage the TTL with `set()`. See the
[soft rate limiter](Counters.md#counters-and-ttl-a-soft-rate-limiter).

### Can I change options after creating the handler?

Yes: `setOptions(['key' => 'value'])` merges (case-insensitively) and returns the
handler. Read one back with `getOption('key', $default)`. See
[The Cache Factory](The-Cache-Factory.md#reading-and-changing-options-later).

### The PDO handler — does it create the table?

No. Create the table yourself with the schema for your database; the
[PDO Handler](PDO-Handler.md#create-the-table) page has ready-made `CREATE TABLE`
statements for MySQL, SQLite and PostgreSQL.

### Does it work without Redis/Memcached extensions?

The [File](File-Handler.md) handler needs nothing beyond PHP core, and
[PDO](PDO-Handler.md) needs only `ext-pdo`. The Redis and Memcache handlers need
their respective C extensions. Pre-check with `isSupported()` and fall back. See
[Installation](Installation.md#checking-a-handler-at-runtime).

### How do I cache in tests without a server?

Use a small in-memory handler (or the File handler with a temp dir). See
[Testing](Testing.md).

### Can I add my own backend?

Yes — extend [`BaseHandler`](API-Reference.md#basehandler) and implement six
methods. See [Custom Handlers](Custom-Handlers.md).

## Still stuck?

- [Troubleshooting](Troubleshooting.md) — symptoms and fixes.
- [Open an issue](https://github.com/InitPHP/Cache/issues/new).
