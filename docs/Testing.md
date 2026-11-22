# Testing

When your code depends on a cache, you want tests that are **fast**,
**isolated** and **don't need a running Redis/Memcached**. Because everything is
behind [`CacheInterface`](API-Reference.md#cacheinterface), that's easy.

## Depend on the interface

Inject `CacheInterface`, never a concrete handler. Production wires a real
backend; tests pass a lightweight one.

```php
use InitPHP\Cache\CacheInterface;

final class PriceService
{
    public function __construct(private CacheInterface $cache) {}

    public function priceFor(string $sku): int
    {
        if ($this->cache->has("price_$sku")) {
            return $this->cache->get("price_$sku");
        }
        $price = $this->lookupExpensive($sku);
        $this->cache->set("price_$sku", $price, 60);
        return $price;
    }

    private function lookupExpensive(string $sku): int { /* ... */ }
}
```

## Option 1 — an in-memory handler (recommended)

Drop the dependency-free `ArrayHandler` from [Custom Handlers](Custom-Handlers.md)
into your test suite and use it directly. It is real (it goes through
[`BaseHandler`](API-Reference.md#basehandler), so keys, TTLs and counters behave
exactly as in production) but holds everything in a PHP array.

```php
use PHPUnit\Framework\TestCase;
use App\Cache\ArrayHandler; // see the "Custom Handlers" page

final class PriceServiceTest extends TestCase
{
    public function testCachesTheLookup(): void
    {
        $cache   = new ArrayHandler();
        $service = new PriceService($cache);

        $first  = $service->priceFor('ABC');
        $second = $service->priceFor('ABC');

        self::assertSame($first, $second);
        self::assertTrue($cache->has('cache_price_ABC')); // prefix is applied
    }
}
```

> The stored key is `cache_price_ABC` because the default `prefix` is `cache_`.
> Set `['prefix' => '']` on the handler if you'd rather assert on raw keys.

## Option 2 — the File handler in a temp directory

If you'd rather exercise a real backend end-to-end without extra services, point
the [File handler](File-Handler.md) at a throwaway directory:

```php
use PHPUnit\Framework\TestCase;
use InitPHP\Cache\Cache;
use InitPHP\Cache\Handler\File;
use InitPHP\Cache\CacheInterface;

final class PriceServiceFileTest extends TestCase
{
    private string $dir;
    private CacheInterface $cache;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/cache-test-' . uniqid();
        mkdir($this->dir);
        $this->cache = Cache::create(File::class, ['path' => $this->dir]);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    public function testRoundTrip(): void
    {
        $this->cache->set('k', 'v');
        self::assertSame('v', $this->cache->get('k'));
    }
}
```

## Option 3 — a mock

When you only need to assert that the cache was *used*, a mock from your test
framework is enough:

```php
$cache = $this->createMock(\InitPHP\Cache\CacheInterface::class);
$cache->expects($this->once())->method('set')->with('price_ABC', 42, 60);
$cache->method('has')->willReturn(false);

(new PriceService($cache))->priceFor('ABC');
```

Prefer the in-memory handler for behaviour tests (it actually stores and
expires); reach for a mock only to assert specific interactions.

## Testing expiry without `sleep()`

Real-time expiry is awkward to test. Two reliable approaches:

- Assert the **zero/negative-TTL = delete** behaviour, which is instant:
  ```php
  $cache->set('k', 'v');
  $cache->set('k', 'v', 0);          // deletes
  self::assertFalse($cache->has('k'));
  ```
- For the File handler, write the underlying file with a past timestamp to force
  expiry deterministically (this is exactly how the package tests its own File
  handler).

## How the package itself is tested

`initphp/cache` runs one shared **contract test** against an in-memory handler,
the File handler and the PDO handler (over SQLite), plus a **Redis/Memcached
integration** suite gated behind a `@group integration` marker so the default run
needs no services. It's a good model: test your glue against an in-memory handler
by default, and run the real backend only where it adds value.

## Next steps

- [Custom Handlers](Custom-Handlers.md) — the `ArrayHandler` used above.
- [Counters](Counters.md) / [Keys, TTL & Values](Keys-TTL-and-Values.md) — the exact
  behaviour your tests can rely on.
