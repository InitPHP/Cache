<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests\Handler;

use InitPHP\Cache\CacheInterface;
use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Handler\File;
use InitPHP\Cache\Tests\AbstractCacheContractTestCase;

use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function serialize;
use function sys_get_temp_dir;
use function time;
use function uniqid;
use function unlink;

final class FileHandlerTest extends AbstractCacheContractTestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/initphp-cache-' . uniqid('', true);
        mkdir($this->dir, 0777, true);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->deleteTree($this->dir);
    }

    protected function createHandler(array $options = []): CacheInterface
    {
        return new File(['path' => $this->dir] + $options);
    }

    public function testMissingPathOptionThrowsOnSet(): void
    {
        $cache = new File();
        $this->expectException(CacheException::class);
        $cache->set('key', 'value');
    }

    public function testMissingPathOptionThrowsOnGet(): void
    {
        $cache = new File();
        $this->expectException(CacheException::class);
        $cache->get('key');
    }

    public function testExpiredFileIsTreatedAsMissAndDeleted(): void
    {
        $this->cache->set('e', 'value', 3600);
        $file = $this->dir . '/cache_e';
        // Backdate the file so its TTL has already elapsed.
        file_put_contents($file, serialize(['time' => time() - 7200, 'ttl' => 3600, 'data' => 'value']));

        self::assertNull($this->cache->get('e'));
        self::assertFileDoesNotExist($file);
    }

    public function testCorruptFileIsTreatedAsMiss(): void
    {
        file_put_contents($this->dir . '/cache_corrupt', 'this is not serialized php');
        self::assertNull($this->cache->get('corrupt'));
        self::assertFalse($this->cache->has('corrupt'));
    }

    public function testClearKeepsProtectedFiles(): void
    {
        $this->cache->set('a', 1);
        $htaccess = $this->dir . '/.htaccess';
        file_put_contents($htaccess, 'deny from all');

        self::assertTrue($this->cache->clear());
        self::assertFileExists($htaccess);
        self::assertFalse($this->cache->has('a'));
    }

    public function testClearOnlyRemovesItsOwnPrefix(): void
    {
        $this->cache->set('mine', 'v');
        $other = $this->createHandler(['prefix' => 'other_']);
        $other->set('theirs', 'v');

        $this->cache->clear();

        self::assertFalse($this->cache->has('mine'));
        self::assertTrue($other->has('theirs'));
    }

    public function testCustomPrefixIsApplied(): void
    {
        $cache = $this->createHandler(['prefix' => 'app_']);
        $cache->set('x', 1);
        self::assertFileExists($this->dir . '/app_x');
    }

    public function testClearWithEmptyPrefixStillKeepsProtectedFiles(): void
    {
        $cache = $this->createHandler(['prefix' => '']);
        $cache->set('item', 'v');
        $index = $this->dir . '/index.php';
        file_put_contents($index, '<?php');

        self::assertTrue($cache->clear());
        self::assertFileExists($index);
        self::assertFalse($cache->has('item'));
    }

    private function deleteTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->deleteTree($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
