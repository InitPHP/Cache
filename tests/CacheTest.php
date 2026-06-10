<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests;

use InitPHP\Cache\Cache;
use InitPHP\Cache\CacheInterface;
use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Tests\Fixtures\ArrayHandler;
use InitPHP\Cache\Tests\Fixtures\UnsupportedHandler;
use PHPUnit\Framework\TestCase;
use stdClass;

final class CacheTest extends TestCase
{
    public function testCreateFromClassStringReturnsConfiguredHandler(): void
    {
        $cache = Cache::create(ArrayHandler::class, ['prefix' => 'app_']);

        self::assertInstanceOf(CacheInterface::class, $cache);
        self::assertSame('app_', $cache->getOption('prefix'));
    }

    public function testCreateFromInstanceAppliesOptions(): void
    {
        $handler = new ArrayHandler();
        $cache = Cache::create($handler, ['prefix' => 'inst_']);

        self::assertSame($handler, $cache);
        self::assertSame('inst_', $cache->getOption('prefix'));
    }

    public function testCreateThrowsWhenClassDoesNotExist(): void
    {
        $this->expectException(CacheException::class);
        /** @phpstan-ignore argument.type */
        Cache::create('InitPHP\\Cache\\Handler\\DoesNotExist');
    }

    public function testCreateThrowsWhenClassDoesNotImplementInterface(): void
    {
        $this->expectException(CacheException::class);
        /** @phpstan-ignore argument.type */
        Cache::create(stdClass::class);
    }

    public function testCreateThrowsForUnsupportedHandler(): void
    {
        $this->expectException(CacheException::class);
        Cache::create(UnsupportedHandler::class);
    }
}
