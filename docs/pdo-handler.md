# PDO handler

`InitPHP\Cache\Handler\PDO` stores items in a single database table through PDO.
It is portable across MySQL, SQLite and PostgreSQL: MySQL-specific tuning runs
only on MySQL, and everything else uses plain SQL. Requires `ext-pdo`.

## Options

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `prefix` | `string` | `cache_` | Prepended to keys; also filters `clear()`. |
| `dsn` | `string` | `mysql:host=localhost;dbname=test` | PDO DSN. |
| `username` | `string\|null` | `null` | Connection user. |
| `password` | `string\|null` | `null` | Connection password. |
| `charset` | `string` | `utf8mb4` | MySQL only (`SET NAMES`). |
| `collation` | `string` | `utf8mb4_general_ci` | MySQL only. |
| `table` | `string` | `cache` | Table name; must match `[A-Za-z0-9_]+`. |

## Table schema

The table needs a unique `name` column, a nullable integer `ttl` (absolute
expiry timestamp) and a text `data` column.

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

SQLite (including a file or an in-memory database) works the same way:

```php
$cache = Cache::create(PDO::class, ['dsn' => 'sqlite:' . __DIR__ . '/cache.sqlite']);
```

## How it works

- `set()` upserts (delete + insert in a transaction), so writing the same key
  twice overwrites it.
- `ttl` is stored as an absolute expiry timestamp; an expired row is deleted on
  read and treated as a miss.
- `clear()` deletes rows whose `name` matches the prefix, escaping `LIKE`
  wildcards so a prefix such as `cache_` is matched literally.

## Errors

- A connection failure throws a [`CacheException`](exceptions.md).
- An invalid `table` option (anything outside `[A-Za-z0-9_]`) throws a
  `CacheException`.
- Query-level failures (for example, a missing table) cause the affected
  operation to return `false`/a miss rather than throw.
