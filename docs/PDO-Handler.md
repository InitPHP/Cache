# PDO Handler

`InitPHP\Cache\Handler\PDO` stores items in a single database table through PDO.
It is **portable** across MySQL, SQLite and PostgreSQL: MySQL-specific tuning runs
only on MySQL, and everything else is plain ANSI SQL. Requires `ext-pdo` plus the
driver for your database (`pdo_mysql`, `pdo_sqlite`, `pdo_pgsql`…).

## Options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `prefix` | `string` | `cache_` | Prepended to keys; also the `clear()` filter. |
| `dsn` | `string` | `mysql:host=localhost;dbname=test` | PDO connection DSN. |
| `username` | `string\|null` | `null` | Connection user. |
| `password` | `string\|null` | `null` | Connection password. |
| `charset` | `string` | `utf8mb4` | **MySQL only** (`SET NAMES`). |
| `collation` | `string` | `utf8mb4_general_ci` | **MySQL only**. |
| `table` | `string` | `cache` | Table name; must match `[A-Za-z0-9_]+`. |

## Create the table

The table needs a **unique** `name` column, a nullable integer `ttl` (the
absolute expiry timestamp) and a text `data` column.

**MySQL**

```sql
CREATE TABLE `cache` (
    `name` VARCHAR(255) NOT NULL,
    `ttl`  INT NULL DEFAULT NULL,
    `data` TEXT NOT NULL,
    UNIQUE (`name`)
) ENGINE = InnoDB CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
```

**SQLite**

```sql
CREATE TABLE cache (
    name VARCHAR(255) NOT NULL,
    ttl  INTEGER NULL DEFAULT NULL,
    data TEXT NOT NULL,
    UNIQUE (name)
);
```

**PostgreSQL**

```sql
CREATE TABLE cache (
    name VARCHAR(255) NOT NULL UNIQUE,
    ttl  INTEGER NULL,
    data TEXT NOT NULL
);
```

## Usage

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\PDO;

$cache = Cache::create(PDO::class, [
    'dsn'      => 'mysql:host=127.0.0.1;dbname=app',
    'username' => 'app',
    'password' => 'secret',
    'table'    => 'cache',
]);

$cache->set('feed', $items, 300);
$cache->get('feed');
```

SQLite — a file or an in-memory database — works the same way:

```php
$cache = Cache::create(PDO::class, [
    'dsn' => 'sqlite:' . __DIR__ . '/var/cache.sqlite',
]);
```

## How it works

- `set()` **upserts** (a `DELETE` + `INSERT` wrapped in a transaction), so
  writing the same key twice overwrites it cleanly.
- `ttl` is stored as an **absolute expiry timestamp**; an expired row is deleted
  on read and reported as a miss.
- `clear()` deletes rows whose `name` matches the prefix, **escaping `LIKE`
  wildcards** so a prefix such as `cache_` is matched literally (the `_` is not
  treated as a single-character wildcard).
- On MySQL the connection runs `SET NAMES … COLLATE …` from the `charset` /
  `collation` options; on other drivers those options are ignored.

## Errors

| Situation | Result |
| --- | --- |
| Connection cannot be established | throws [`CacheException`](Error-Handling.md) |
| `table` option contains anything but `[A-Za-z0-9_]` | throws `CacheException` |
| A query fails at runtime (e.g. the table is missing) | the call returns `false` / a miss — it does **not** throw |

```php
use InitPHP\Cache\Exception\CacheException;

try {
    $cache = Cache::create(PDO::class, ['dsn' => 'mysql:host=db;dbname=app']);
    $cache->get('x');
} catch (CacheException $e) {
    error_log('Cache DB unavailable: ' . $e->getMessage());
    // fall back to another handler, or proceed without caching
}
```

> The `table` name is validated (not parameterisable in SQL), so keep it a plain
> identifier. Everything that can be parameterised (keys, values, TTLs) uses
> prepared statements.

## When to choose another handler

- The PDO handler reuses an existing database — no extra service to run — which
  is its main appeal. For the **hottest** caches a dedicated in-memory store like
  [Redis](Redis-Handler.md) will usually be faster.
- Heavy cache churn adds write load to your database; size the table's storage
  and cleanup accordingly (expired rows are removed lazily, on read).

## Next steps

- [Configuration Options](Configuration-Options.md) — all handlers side by side.
- [Error Handling](Error-Handling.md) — what throws, what returns `false`.
- [Recipes](Recipes.md) — practical caching patterns.
