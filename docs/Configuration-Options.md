# Configuration Options

Options are passed to [`Cache::create()`](The-Cache-Factory.md) (or a handler
constructor) as an associative array. **Keys are matched case-insensitively**, so
`prefix`, `Prefix` and `PREFIX` are the same option. Anything you omit falls back
to the default.

```php
$cache = Cache::create(File::class, [
    'path'   => __DIR__ . '/var/cache',
    'prefix' => 'app_',
]);

$cache->getOption('prefix');         // "app_"
$cache->getOption('mode', 0640);     // 0640  (default returned when unset)
$cache->setOptions(['prefix' => 'v2_']); // merge more in; returns $this
```

## Shared option — every handler

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `prefix` | `string` | `cache_` | Prepended to every key after validation; scopes `clear()` on the File and PDO handlers. Set to `''` to disable. |

## [File](File-Handler.md)

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `path` | `string` | _(required)_ | Directory the cache files live in. Must exist and be writable. |
| `mode` | `int\|null` | `0640` | `chmod()` mode per file; `null` skips `chmod()`. |

```php
Cache::create(File::class, [
    'path'   => __DIR__ . '/var/cache',
    'mode'   => 0600,
    'prefix' => 'app_',
]);
```

## [PDO](PDO-Handler.md)

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `dsn` | `string` | `mysql:host=localhost;dbname=test` | PDO connection DSN. |
| `username` | `string\|null` | `null` | Connection user. |
| `password` | `string\|null` | `null` | Connection password. |
| `charset` | `string` | `utf8mb4` | MySQL only (`SET NAMES`). |
| `collation` | `string` | `utf8mb4_general_ci` | MySQL only. |
| `table` | `string` | `cache` | Table name; must match `[A-Za-z0-9_]+`. |

```php
Cache::create(PDO::class, [
    'dsn'      => 'pgsql:host=127.0.0.1;dbname=app',
    'username' => 'app',
    'password' => 'secret',
    'table'    => 'app_cache',
]);
```

## [Redis](Redis-Handler.md)

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `host` | `string` | `127.0.0.1` | Server host. |
| `port` | `int` | `6379` | Server port. |
| `timeout` | `int\|float` | `0` | Connection timeout in seconds (`0` = unlimited). |
| `password` | `string\|null` | `null` | `AUTH` password; skipped when `null`. |
| `database` | `int\|null` | `0` | Database index to `SELECT`. |

```php
Cache::create(Redis::class, [
    'host'     => 'redis.internal',
    'port'     => 6379,
    'database' => 3,
    'timeout'  => 1.5,
]);
```

## [Memcache(d)](Memcache-Handler.md)

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `host` | `string` | `127.0.0.1` | Server host. |
| `port` | `int` | `11211` | Server port. |
| `weight` | `int` | `1` | Server weight (Memcached only). |
| `default_ttl` | `int` | `0` | TTL used when `set()` gets no TTL. `0` = no expiry. |

```php
Cache::create(Memcache::class, [
    'host'        => 'memcached.internal',
    'port'        => 11211,
    'default_ttl' => 300,
]);
```

## [WinCache](WinCache-Handler.md) — _deprecated_

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `default_ttl` | `int` | `0` | TTL used when `set()` gets no TTL. `0` = no expiry. |

## Notes on types

- Numeric options are coerced leniently: a numeric string like `'6379'` is
  accepted for an integer option. A non-numeric value falls back to the default.
- `path` and the PDO `table` are validated and will throw a
  [`CacheException`](Error-Handling.md) if missing/invalid — see each handler page.

## Next steps

- [The Cache Factory](The-Cache-Factory.md) — how options are applied and merged.
- The individual handler pages for the meaning and edge cases of each option.
