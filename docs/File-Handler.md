# File Handler

`InitPHP\Cache\Handler\File` stores each item as one PHP-serialised file named
`{prefix}{key}` inside a directory you choose. It needs **no extension** beyond
the PHP core, which makes it the simplest handler and a great default for local
development and small-to-medium caches.

## Options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `prefix` | `string` | `cache_` | Prepended to keys; also the `clear()` filter. |
| `path` | `string` | _(required)_ | Directory the cache files live in. |
| `mode` | `int\|null` | `0640` | `chmod()` mode applied to each file. `null` skips `chmod()`. |

## Usage

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;

$cache = Cache::create(File::class, [
    'path' => __DIR__ . '/var/cache',
    'mode' => 0640,
]);

$cache->set('settings', ['theme' => 'dark'], 3600);
$cache->get('settings');   // ['theme' => 'dark']
```

The `path` directory must **exist and be writable** before you use the handler.
A quick bootstrap:

```php
$dir = __DIR__ . '/var/cache';
if (!is_dir($dir)) {
    mkdir($dir, 0700, true);
}

$cache = Cache::create(File::class, ['path' => $dir]);
```

> **Security:** use a directory that belongs to the application (owned by the
> web/CLI user, mode `0700`), never `/tmp` or another location other users can
> write to. Cache files are PHP-`serialize()`d and read back with `unserialize()`,
> so a file planted by someone else becomes a deserialization input. Keep the
> directory outside the web root as well.

If `path` is missing or empty, any operation that touches the filesystem throws a
[`CacheException`](Error-Handling.md).

## How it works

- Each item is `serialize()`d together with its creation time and TTL, then
  written to `{path}/{prefix}{key}`.
- On read, an **expired file is deleted** and reported as a miss.
- A file that cannot be read or unserialised (corruption, a partial write) is
  also treated as a miss rather than an error.
- `clear()` removes only files matching `{prefix}*`, and **never deletes** the
  protective files `.htaccess`, `index.htm`, `index.html`, `index.php` or
  `web.config`.

```php
$cache->set('a', 1);
$cache->set('b', 2);
$cache->clear();          // removes app's "cache_a", "cache_b"; leaves index.php etc.
```

## Isolating caches with the prefix

Two handlers pointed at the same directory but with different prefixes don't see
or clear each other's items:

```php
$app   = Cache::create(File::class, ['path' => $dir, 'prefix' => 'app_']);
$views = Cache::create(File::class, ['path' => $dir, 'prefix' => 'view_']);

$app->set('x', 1);
$views->set('x', 2);

$app->clear();            // only "app_*" files removed
$views->get('x');         // 2 — untouched
```

## Security notes

- **Keep the cache directory out of the web root**, or protect it. The handler
  preserves the protective files above so directories that can't be moved (e.g.
  some shared hosts) can still be locked down with an `.htaccess` / `web.config`.
- Cache files are read with `unserialize()`. Point `path` only at a directory
  **your application controls** — never at attacker-influenced content, since a
  crafted file could instantiate objects on read.
- The default `mode` of `0640` keeps files readable only by the owner and group.
  Tighten to `0600` if the web server is the sole user.

## When to choose another handler

- A single flat directory with **very many** files can slow the filesystem;
  reach for [Redis](Redis-Handler.md) or [Memcache(d)](Memcache-Handler.md) for large,
  hot caches.
- For a **shared cache across multiple servers**, use a networked backend; the
  filesystem is per-host.

## Next steps

- [Configuration Options](Configuration-Options.md) — all handlers side by side.
- [Keys, TTL & Values](Keys-TTL-and-Values.md) — the rules for what you store.
- [Recipes](Recipes.md) — read-through, memoization, and more.
