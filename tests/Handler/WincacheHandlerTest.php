<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests\Handler;

use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Handler\Wincache;
use PHPUnit\Framework\TestCase;

final class WincacheHandlerTest extends TestCase
{
    private function wincacheAvailable(): bool
    {
        return \extension_loaded('wincache') && (bool) \ini_get('wincache.ucenabled');
    }

    public function testConstructorThrowsWhenWinCacheIsUnavailable(): void
    {
        if ($this->wincacheAvailable()) {
            self::markTestSkipped('WinCache is available; the unsupported path cannot be exercised here.');
        }
        $this->expectException(CacheException::class);
        new Wincache();
    }

    public function testRoundTripWhenWinCacheIsAvailable(): void
    {
        if (!$this->wincacheAvailable()) {
            self::markTestSkipped('WinCache is not available on this platform.');
        }
        $cache = new Wincache();
        $cache->clear();
        self::assertTrue($cache->set('k', 'v'));
        self::assertSame('v', $cache->get('k'));
        self::assertTrue($cache->has('k'));
        self::assertTrue($cache->delete('k'));
        self::assertFalse($cache->has('k'));
    }
}
