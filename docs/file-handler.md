# File handler

`InitPHP\Cache\Handler\File` stores each item as one PHP-serialised file named
`{prefix}{key}` inside a directory you choose. It needs no extension beyond the
PHP core, which makes it a good default and ideal for development.

## Options

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `prefix` | `string` | `cache_` | Prepended to keys; also filters `clear()`. |
| `path` | `string` | _(required)_ | Directory the cache files live in. |
| `mode` | `int\|null` | `0640` | `chmod()` mode applied to each file. Set `null` to skip `chmod()`. |

## Usage

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;

$cache = Cache::create(File::class, [
    'path' => __DIR__ . '/var/cache',
    'mode' => 0640,
]);

$cache->set('settings', ['theme' => 'dark'], 3600);
$cache->get('settings');
```

The `path` directory must exist and be writable. If `path` is missing or empty,
operations that touch the filesystem throw a
[`CacheException`](exceptions.md).

## How it works

- Each item is `serialize()`d together with its creation time and TTL.
- On read, an expired file is deleted and treated as a miss.
- `clear()` removes only files matching `{prefix}*`, and never deletes the
  protective files `.htaccess`, `index.htm`, `index.html`, `index.php` or
  `web.config`.

## Notes

- **Keep the cache directory out of the web root**, or protect it (the cleared
  protective files above exist for directories that cannot be moved).
- Cache files are read with `unserialize()`. Point `path` only at a directory
  your application controls — never at attacker-influenced content.
- Very high item counts in a single flat directory can slow the filesystem; pick
  a backend like [Redis](redis-handler.md) for large, hot caches.
