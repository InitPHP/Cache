<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests;

use ArrayIterator;
use DateInterval;
use InitPHP\Cache\CacheInterface;
use InitPHP\Cache\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

use function iterator_to_array;

/**
 * The behaviour every handler must satisfy.
 *
 * Concrete handler tests extend this and provide a working handler through
 * {@see createHandler()}; the whole contract then runs against the real backend.
 */
abstract class AbstractCacheContractTestCase extends TestCase
{
    protected CacheInterface $cache;

    /**
     * @param array<string, mixed> $options
     */
    abstract protected function createHandler(array $options = []): CacheInterface;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = $this->createHandler();
        $this->cache->clear();
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        parent::tearDown();
    }

    public function testGetReturnsDefaultOnMiss(): void
    {
        self::assertNull($this->cache->get('missing'));
        self::assertSame('fallback', $this->cache->get('missing', 'fallback'));
    }

    /**
     * @dataProvider scalarValueProvider
     */
    public function testStoresAndReturnsScalarWithExactType(mixed $value): void
    {
        self::assertTrue($this->cache->set('key', $value));
        self::assertSame($value, $this->cache->get('key', 'unexpected-default'));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function scalarValueProvider(): array
    {
        return [
            'string'       => ['hello world'],
            'empty string' => [''],
            'integer'      => [42],
            'zero'         => [0],
            'negative'     => [-17],
            'float'        => [3.14],
            'true'         => [true],
            'false'        => [false],
            'null'         => [null],
        ];
    }

    public function testStoresAndReturnsArray(): void
    {
        $value = ['a' => 1, 'b' => ['c' => 2], 'list' => [1, 2, 3]];
        self::assertTrue($this->cache->set('arr', $value));
        self::assertEquals($value, $this->cache->get('arr'));
    }

    public function testStoresAndReturnsObject(): void
    {
        $value = new stdClass();
        $value->name = 'InitPHP';
        $value->nested = (object) ['x' => 1];
        self::assertTrue($this->cache->set('obj', $value));
        self::assertEquals($value, $this->cache->get('obj'));
    }

    public function testHasReflectsPresence(): void
    {
        self::assertFalse($this->cache->has('flag'));
        $this->cache->set('flag', 'yes');
        self::assertTrue($this->cache->has('flag'));
    }

    public function testHasIsTrueForStoredNull(): void
    {
        $this->cache->set('nullable', null);
        self::assertTrue($this->cache->has('nullable'));
    }

    public function testDeleteRemovesKey(): void
    {
        $this->cache->set('temp', 'v');
        self::assertTrue($this->cache->delete('temp'));
        self::assertFalse($this->cache->has('temp'));
    }

    public function testDeleteMissingKeyReturnsTrue(): void
    {
        self::assertTrue($this->cache->delete('never-existed'));
    }

    public function testOverwriteExistingKey(): void
    {
        $this->cache->set('k', 'first');
        $this->cache->set('k', 'second');
        self::assertSame('second', $this->cache->get('k'));
    }

    public function testClearRemovesEverything(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);
        self::assertTrue($this->cache->clear());
        self::assertFalse($this->cache->has('a'));
        self::assertFalse($this->cache->has('b'));
    }

    public function testPositiveIntegerTtlIsAvailableImmediately(): void
    {
        self::assertTrue($this->cache->set('soon', 'v', 3600));
        self::assertSame('v', $this->cache->get('soon'));
    }

    public function testPositiveDateIntervalTtlIsAvailableImmediately(): void
    {
        self::assertTrue($this->cache->set('soon', 'v', new DateInterval('PT1H')));
        self::assertSame('v', $this->cache->get('soon'));
    }

    /**
     * @dataProvider expiredTtlProvider
     */
    public function testZeroOrNegativeTtlDeletesItem(int $ttl): void
    {
        $this->cache->set('victim', 'v');
        self::assertTrue($this->cache->set('victim', 'v', $ttl));
        self::assertFalse($this->cache->has('victim'));
        self::assertNull($this->cache->get('victim'));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function expiredTtlProvider(): array
    {
        return [
            'zero'     => [0],
            'negative' => [-10],
        ];
    }

    public function testIncrementCreatesCounterAndReturnsNewValue(): void
    {
        self::assertSame(5, $this->cache->increment('counter', 5));
        self::assertSame(8, $this->cache->increment('counter', 3));
        self::assertSame(8, $this->cache->get('counter'));
    }

    public function testIncrementDefaultsToOne(): void
    {
        self::assertSame(1, $this->cache->increment('hits'));
        self::assertSame(2, $this->cache->increment('hits'));
    }

    public function testDecrementReturnsNewValue(): void
    {
        $this->cache->set('stock', 10);
        self::assertSame(7, $this->cache->decrement('stock', 3));
        self::assertSame(7, $this->cache->get('stock'));
    }

    public function testIncrementTreatsNonNumericAsZero(): void
    {
        $this->cache->set('label', 'not-a-number');
        self::assertSame(4, $this->cache->increment('label', 4));
    }

    public function testGetMultipleReturnsValuesAndDefaults(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);

        $result = $this->cache->getMultiple(['a', 'b', 'missing'], 'def');

        self::assertSame(['a' => 1, 'b' => 2, 'missing' => 'def'], $this->normalize($result));
    }

    public function testGetMultipleAcceptsTraversable(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);

        $result = $this->cache->getMultiple(new ArrayIterator(['a', 'b']));

        self::assertSame(['a' => 1, 'b' => 2], $this->normalize($result));
    }

    public function testSetMultipleStoresAllValues(): void
    {
        self::assertTrue($this->cache->setMultiple(['x' => 10, 'y' => 20]));
        self::assertSame(10, $this->cache->get('x'));
        self::assertSame(20, $this->cache->get('y'));
    }

    public function testSetMultipleAcceptsTraversable(): void
    {
        self::assertTrue($this->cache->setMultiple(new ArrayIterator(['x' => 10, 'y' => 20])));
        self::assertSame(10, $this->cache->get('x'));
        self::assertSame(20, $this->cache->get('y'));
    }

    public function testSetMultipleEmptyReturnsTrue(): void
    {
        self::assertTrue($this->cache->setMultiple([]));
    }

    public function testDeleteMultipleRemovesAllKeys(): void
    {
        $this->cache->setMultiple(['x' => 1, 'y' => 2, 'z' => 3]);
        self::assertTrue($this->cache->deleteMultiple(['x', 'y']));
        self::assertFalse($this->cache->has('x'));
        self::assertFalse($this->cache->has('y'));
        self::assertTrue($this->cache->has('z'));
    }

    /**
     * @dataProvider reservedKeyProvider
     */
    public function testReservedCharacterInKeyThrows(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->get($key);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function reservedKeyProvider(): array
    {
        return [
            'brace open'   => ['na{me'],
            'brace close'  => ['na}me'],
            'paren open'   => ['na(me'],
            'paren close'  => ['na)me'],
            'slash'        => ['na/me'],
            'backslash'    => ['na\\me'],
            'at'           => ['na@me'],
            'colon'        => ['na:me'],
        ];
    }

    public function testEmptyKeyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->set('', 'v');
    }

    /**
     * Normalises an iterable result into a plain array for comparison.
     *
     * @param iterable<string, mixed> $result
     * @return array<string, mixed>
     */
    private function normalize(iterable $result): array
    {
        return \is_array($result) ? $result : iterator_to_array($result);
    }
}
