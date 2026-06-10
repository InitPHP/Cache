# Configuration & options

Options are passed to `Cache::create()` (or a handler constructor) as an
associative array. **Keys are matched case-insensitively** — `prefix`, `Prefix`
and `PREFIX` are the same option.

You can also merge options into an existing handler with `setOptions()`, and
read one back with `getOption()`:

```php
$cache = Cache::create(File::class, ['path' => '/tmp/cache']);
$cache->setOptions(['prefix' => 'app_']);
$cache->getOption('prefix');        // "app_"
$cache->getOption('missing', '-');  // "-"
```

## Shared option

| Option | Type | Default | Applies to |
| ------ | ---- | ------- | ---------- |
| `prefix` | `string` | `cache_` | every handler |

`prefix` is prepended to each key before it reaches the backend, and scopes
`clear()` so one application's cache does not wipe another's that shares the same
store. Set it to `''` to disable prefixing.

## File

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `path` | `string` | _(required)_ | Directory the cache files live in. |
| `mode` | `int\|null` | `0640` | `chmod()` mode applied to each file. |

See [File handler](file-handler.md).

## PDO

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `dsn` | `string` | `mysql:host=localhost;dbname=test` | PDO DSN. |
| `username` | `string\|null` | `null` | Connection user. |
| `password` | `string\|null` | `null` | Connection password. |
| `charset` | `string` | `utf8mb4` | MySQL only. |
| `collation` | `string` | `utf8mb4_general_ci` | MySQL only. |
| `table` | `string` | `cache` | Table name (`[A-Za-z0-9_]+`). |

See [PDO handler](pdo-handler.md).

## Redis

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `host` | `string` | `127.0.0.1` | Server host. |
| `port` | `int` | `6379` | Server port. |
| `timeout` | `int\|float` | `0` | Connection timeout (seconds). |
| `password` | `string\|null` | `null` | `AUTH` password. |
| `database` | `int\|null` | `0` | Database index to `SELECT`. |

See [Redis handler](redis-handler.md).

## Memcache

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `host` | `string` | `127.0.0.1` | Server host. |
| `port` | `int` | `11211` | Server port. |
| `weight` | `int` | `1` | Server weight (Memcached). |
| `default_ttl` | `int` | `0` | Expiry used when `set()` gets no TTL. `0` = no expiry. |

See [Memcache handler](memcache-handler.md).

## WinCache

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `default_ttl` | `int` | `0` | Expiry used when `set()` gets no TTL. `0` = no expiry. |

See [WinCache handler](wincache-handler.md).
