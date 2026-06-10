<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests;

use DateInterval;
use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Exception\InvalidArgumentException;
use InitPHP\Cache\Tests\Fixtures\ArrayHandler;
use InitPHP\Cache\Tests\Fixtures\ProbeHandler;
use InitPHP\Cache\Tests\Fixtures\RejectingHandler;
use InitPHP\Cache\Tests\Fixtures\UnsupportedHandler;
use PHPUnit\Framework\TestCase;

final class BaseHandlerTest extends TestCase
{
    public function testPrefixDefaultsToCachePrefix(): void
    {
        self::assertSame('cache_', (new ArrayHandler())->getOption('prefix'));
    }

    public function testConstructorMergesUserOptionsCaseInsensitively(): void
    {
        $handler = new ProbeHandler(['PREFIX' => 'X_']);
        self::assertSame('X_', $handler->getOption('prefix'));
    }

    public function testSetOptionsMergesCaseInsensitivelyAndReturnsStatic(): void
    {
        $handler = new ArrayHandler();
        $returned = $handler->setOptions(['Prefix' => 'merged_']);

        self::assertSame($handler, $returned);
        self::assertSame('merged_', $handler->getOption('prefix'));
    }

    public function testGetOptionReturnsDefaultWhenAbsent(): void
    {
        self::assertSame('fallback', (new ArrayHandler())->getOption('nope', 'fallback'));
    }

    public function testNamePrependsPrefixAndValidates(): void
    {
        self::assertSame('cache_user', (new ProbeHandler())->exposeName('user'));
    }

    public function testNameThrowsOnEmptyKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ProbeHandler())->exposeName('');
    }

    public function testNameThrowsOnReservedCharacter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ProbeHandler())->exposeName('a:b');
    }

    public function testTtlToSecondsReturnsNullForNull(): void
    {
        self::assertNull((new ProbeHandler())->exposeTtlToSeconds(null));
    }

    public function testTtlToSecondsPassesThroughIntegers(): void
    {
        self::assertSame(120, (new ProbeHandler())->exposeTtlToSeconds(120));
        self::assertSame(-5, (new ProbeHandler())->exposeTtlToSeconds(-5));
    }

    public function testTtlToSecondsResolvesDateInterval(): void
    {
        $seconds = (new ProbeHandler())->exposeTtlToSeconds(new DateInterval('PT60S'));

        self::assertNotNull($seconds);
        self::assertGreaterThanOrEqual(59, $seconds);
        self::assertLessThanOrEqual(60, $seconds);
    }

    public function testOptionIntCoercesNumericAndFallsBack(): void
    {
        $handler = new ProbeHandler(['a' => '15', 'b' => 'nope']);
        self::assertSame(15, $handler->exposeOptionInt('a'));
        self::assertSame(7, $handler->exposeOptionInt('b', 7));
        self::assertSame(3, $handler->exposeOptionInt('missing', 3));
    }

    public function testOptionFloatCoercesNumericAndFallsBack(): void
    {
        $handler = new ProbeHandler(['a' => '1.5']);
        self::assertSame(1.5, $handler->exposeOptionFloat('a'));
        self::assertSame(2.0, $handler->exposeOptionFloat('missing', 2.0));
    }

    public function testOptionStringCoercesScalarAndFallsBackForArrays(): void
    {
        $handler = new ProbeHandler(['a' => 42, 'b' => ['x']]);
        self::assertSame('42', $handler->exposeOptionString('a'));
        self::assertSame('def', $handler->exposeOptionString('b', 'def'));
    }

    public function testUnsupportedHandlerConstructorThrows(): void
    {
        $this->expectException(CacheException::class);
        new UnsupportedHandler();
    }

    public function testSetMultipleReturnsFalseWhenAnItemFails(): void
    {
        self::assertFalse((new RejectingHandler())->setMultiple(['a' => 1, 'b' => 2]));
    }

    public function testDeleteMultipleReturnsFalseWhenAnItemFails(): void
    {
        self::assertFalse((new RejectingHandler())->deleteMultiple(['a', 'b']));
    }
}
