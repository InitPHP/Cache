<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests;

use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Exception\InvalidArgumentException;
use InvalidArgumentException as SplInvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheException as PsrCacheException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

final class ExceptionTest extends TestCase
{
    public function testCacheExceptionImplementsPsrCacheException(): void
    {
        self::assertInstanceOf(PsrCacheException::class, new CacheException('boom'));
    }

    public function testInvalidArgumentExceptionImplementsPsrInterface(): void
    {
        self::assertInstanceOf(PsrInvalidArgumentException::class, new InvalidArgumentException('bad'));
    }

    public function testInvalidArgumentExceptionExtendsSplException(): void
    {
        self::assertInstanceOf(SplInvalidArgumentException::class, new InvalidArgumentException('bad'));
    }

    public function testInvalidArgumentExceptionIsCatchableAsPsrCacheException(): void
    {
        // Psr\SimpleCache\InvalidArgumentException extends Psr\SimpleCache\CacheException.
        self::assertInstanceOf(PsrCacheException::class, new InvalidArgumentException('bad'));
    }
}
