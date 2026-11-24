# Recipes

Practical patterns built on the plain PSR-16 API. They work with **any**
[handler](File-Handler.md).

## A reusable `remember()` helper

The cache-aside / read-through pattern, factored into one helper. It uses
`has()` so a producer that legitimately returns `null` is still cached:

```php
use InitPHP\Cache\CacheInterface;

function remember(CacheInterface $cache, string $key, int $ttl, callable $producer): mixed
{
    if ($cache->has($key)) {
        return $cache->get($key);
    }
    $value = $producer();
    $cache->set($key, $value, $ttl);
    return $value;
}
```

```php
$settings = remember($cache, 'settings', 3600, function () use ($db) {
    return $db->query('SELECT * FROM settings')->fetchAll();
});
```

## Memoize an expensive computation

```php
$key = 'fib_' . $n;

$result = remember($cache, $key, 86400, fn () => fibonacci($n));
```

## Cache a database query

Build the key from whatever makes the query unique, and hash it to stay within
the [key rules](Keys-TTL-and-Values.md#keys):

```php
function cachedQuery(CacheInterface $cache, PDO $db, string $sql, array $params, int $ttl = 300): array
{
    $key = 'q_' . hash('xxh3', $sql . '|' . serialize($params));

    return remember($cache, $key, $ttl, function () use ($db, $sql, $params) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });
}
```

```php
$active = cachedQuery($cache, $db, 'SELECT * FROM users WHERE status = ?', ['active']);
```

Remember to **invalidate** when the underlying data changes:

```php
function updateUserStatus(CacheInterface $cache, PDO $db, int $id, string $status): void
{
    $db->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$status, $id]);
    $cache->delete('q_' . hash('xxh3', 'SELECT * FROM users WHERE status = ?|' . serialize(['active'])));
}
```

> Cache invalidation is the hard part. Keep keys derivable so you can delete the
> exact entries you wrote, or use short TTLs and accept brief staleness.

## Cache an HTTP/API response

```php
function fetchJson(CacheInterface $cache, string $url, int $ttl = 600): array
{
    return remember($cache, 'http_' . hash('xxh3', $url), $ttl, function () use ($url) {
        return json_decode(file_get_contents($url), true);
    });
}
```

## Fragment caching (rendered HTML)

A fragment that depends on the current user must be cached **per user**: put the
user's id in the key. A fixed key such as `frag_nav` would serve the first user's
menu, name and any tokens in it to everyone until the entry expires.

```php
function renderNav(CacheInterface $cache, ?int $userId, callable $render): string
{
    // $userId is null for a guest, who gets one shared "guest" fragment: the guest
    // fragment must not contain a CSRF token or other per-session data.
    return remember($cache, 'frag_nav_' . ($userId ?? 'guest'), 600, $render);
}

echo renderNav($cache, $user?->id, fn () => view('partials/nav', ['user' => $user]));
```

Only fragments that are identical for every visitor (a footer, a public
category list) may use a fixed key.

## Pick a backend per environment

Develop on the filesystem, run Redis in production — one place decides:

```php
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;
use InitPHP\Cache\Handler\Redis;

function makeCache(string $env): \InitPHP\Cache\CacheInterface
{
    return match ($env) {
        'production', 'staging' => Cache::create(Redis::class, [
            'host'     => getenv('REDIS_HOST') ?: '127.0.0.1',
            'database' => 1,
            'prefix'   => 'app_',
        ]),
        default => Cache::create(File::class, [
            'path'   => __DIR__ . '/var/cache',
            'prefix' => 'app_',
        ]),
    };
}

$cache = makeCache(getenv('APP_ENV') ?: 'local');
```

## Share one instance via a container

A cache is cheap to build but you usually want a **single** configured instance.
Register it once and inject the `CacheInterface`:

```php
// Pseudo-container registration
$container->singleton(CacheInterface::class, fn () => makeCache(getenv('APP_ENV')));

// Anywhere else — depend on the interface, not the handler
final class ReportService
{
    public function __construct(private CacheInterface $cache) {}

    public function daily(): array
    {
        return remember($this->cache, 'daily_report', 86400, fn () => $this->build());
    }
}
```

## A note on cache stampede

When a popular key expires, many concurrent requests can recompute it at once.
Simple mitigations:

- **Jitter the TTL** so keys don't all expire together:
  ```php
  $cache->set($key, $value, 3600 + random_int(0, 300));
  ```
- **Refresh slightly early** by storing your own "soft expiry" timestamp inside
  the value and recomputing in one request when it's near.
- For strict single-flight, guard the recompute with a short-lived lock key
  (`set('lock_'.$key, 1, 5)` and only the winner rebuilds).

For most apps, jittered TTLs are enough.

## Next steps

- [Counters](Counters.md) — view counters and soft rate limits.
- [Testing](Testing.md) — test code that depends on a cache.
- [Error Handling](Error-Handling.md) — degrade gracefully when a backend is down.
