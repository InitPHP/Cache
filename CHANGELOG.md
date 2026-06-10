# Changelog

All notable changes to `initphp/cache` are documented here. The format is based
on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0]

The first stable release: a modern PHP 8 codebase, real bug fixes, full
PSR-16 compliance, a test suite, static analysis, CI and documentation. The
public surface (`Cache::create()` and the handler classes) is unchanged, but
several previously broken behaviours now work correctly.

### Requirements

- **Raised the minimum PHP version to 8.0** (was 5.6). The whole library now
  uses `declare(strict_types=1)` and native type declarations.
- **Requires `psr/simple-cache:^3.0`** (was `^1.0`), so every method now carries
  the fully-typed PSR-16 signatures.

### Fixed

- **The default `cache_` prefix was silently dropped.** The constructor
  overwrote the effective options with the handler defaults, discarding the
  base `prefix` default. Prefixes now survive and apply as documented.
- **`DateInterval` TTLs threw a `TypeError` on PHP 8.** TTL calculation called
  `DateInterval::format('U')`, which is not supported and yielded the literal
  string `"U"`. Intervals are now resolved with `DateTimeImmutable::add()`.
- **The File handler could not read items stored without a TTL.** The expiry
  check used `isset()`, which is `false` for a `null` TTL, so every "store
  forever" item read back as a miss. It now uses `array_key_exists()`.
- **The File handler never deleted expired files when a prefix was set.** The
  internal expiry cleanup re-applied the prefix, producing a wrong path. It now
  deletes by the resolved name.
- **`Redis::has()` always returned `true`.** `exists()` returns an integer and
  was compared with `!== false`; it is now compared with `> 0`.
- **The PDO handler could not overwrite an existing key.** `set()` only ran
  `INSERT`, so a second write to the same key hit the unique constraint and
  failed. It now upserts (delete + insert in a transaction).
- **The PDO handler only worked with MySQL.** `SET NAMES … COLLATE …`,
  `SET CHARACTER SET` and `WHERE 1` were MySQL-specific. Charset/collation tuning
  now runs only on MySQL, and the remaining SQL is portable, so SQLite and
  PostgreSQL work too.
- **`PDO::clear()` with the default `cache_` prefix could delete unrelated
  rows.** The trailing `_` was treated as a `LIKE` wildcard. Wildcards in the
  prefix are now escaped.
- **The PDO handler emitted warnings on every cache miss** by reading array
  offsets from a `false` result; a missing row is now handled explicitly.
- **The Memcache handler mangled array values in "raw" mode** and used a fragile
  flags-as-success hack. Values are now stored as a small serialised envelope
  that round-trips exactly.
- **`null`, `false` and other "falsy" values now round-trip** through the Redis
  and Memcache handlers instead of being reported as misses.
- **`increment()`/`decrement()` were inconsistent across handlers.** The File
  handler returned the offset instead of the new value; the Redis versions were
  incompatible with values written by `set()`; others created or skipped keys
  differently. They now share one contract (see below).

### Changed

- **One consistent counter contract.** `increment()`/`decrement()` are now
  implemented once in `BaseHandler` as a read-modify-write over `get()`/`set()`:
  a missing or non-numeric item counts as `0`, the new integer value is
  returned, and the result is stored without an expiry. This trades the native
  atomic counters of Redis/Memcache for behaviour that is identical everywhere
  and compatible with `set()`/`get()`.
- **Strict PSR-16 keys.** Keys are validated against the reserved set
  `{}()/\@:` and rejected when empty. Validation runs on the user key (not the
  prefixed name), and the previously per-handler reserved sets are unified.
- **`getMultiple()`, `setMultiple()` and `deleteMultiple()` accept any
  `iterable`** (Generators, `Traversable`…), as PSR-16 requires, rather than
  only arrays.
- **A zero or negative TTL deletes the item** and returns `true`, instead of
  failing the write.
- **Library exceptions are PSR-16 compliant.** Backend failures (e.g. a Redis
  connection error) now throw `InitPHP\Cache\Exception\CacheException`
  (which implements `Psr\SimpleCache\CacheException`) rather than a bare
  `RuntimeException`.
- **`get()` no longer invokes a callable default.** The default value is
  returned as-is, per PSR-16.
- The `Memcache` and `Wincache` handler `default_ttl` option now defaults to `0`
  ("no expiry") and is only used when `set()` is called without a TTL.
- PHPDoc, comments and documentation are now in English and match the actual
  behaviour.

### Deprecated

- **The `Wincache` handler is deprecated.** The WinCache extension is
  unmaintained and unavailable for PHP 8; prefer `File`, `Redis` or `Memcache`.

### Removed

- The unused `BaseHandler::options()` method and the non-standard callable
  default behaviour.

### Added

- A **PHPUnit** test suite built around a shared handler contract (run against
  the File, PDO and an in-memory handler, plus Redis/Memcached integration
  tests), **PHPStan** (max level) with extension stubs, a **PHP-CS-Fixer**
  configuration, a **GitHub Actions CI** workflow (PHP 8.0–8.4 plus a
  Redis/Memcached integration job) and a **`docs/`** directory.

[1.0.0]: https://github.com/InitPHP/Cache/releases/tag/1.0.0
