<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests\Handler;

use InitPHP\Cache\CacheInterface;
use InitPHP\Cache\Exception\CacheException;
use InitPHP\Cache\Handler\PDO as PdoHandler;
use InitPHP\Cache\Tests\AbstractCacheContractTestCase;
use PDO;

use function is_file;
use function serialize;
use function sys_get_temp_dir;
use function tempnam;
use function time;
use function unlink;

final class PdoHandlerTest extends AbstractCacheContractTestCase
{
    private string $dbFile;
    private PDO $raw;

    protected function setUp(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'initphp-cache-pdo-');
        self::assertIsString($file);
        $this->dbFile = $file;
        $this->raw = new PDO('sqlite:' . $this->dbFile);
        $this->raw->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->raw->exec(
            'CREATE TABLE cache (name VARCHAR(255) NOT NULL, ttl INTEGER NULL DEFAULT NULL, data TEXT NOT NULL, UNIQUE(name))'
        );
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (isset($this->dbFile) && is_file($this->dbFile)) {
            @unlink($this->dbFile);
        }
    }

    protected function createHandler(array $options = []): CacheInterface
    {
        return new PdoHandler(['dsn' => 'sqlite:' . $this->dbFile] + $options);
    }

    public function testInvalidTableNameThrows(): void
    {
        $cache = $this->createHandler(['table' => 'bad table; DROP']);
        $this->expectException(CacheException::class);
        $cache->set('k', 'v');
    }

    public function testConnectionErrorThrowsCacheException(): void
    {
        $cache = new PdoHandler(['dsn' => 'unknown-driver:host=nope']);
        $this->expectException(CacheException::class);
        $cache->get('k');
    }

    public function testExpiredRowIsTreatedAsMissAndDeleted(): void
    {
        $this->raw->exec(
            "INSERT INTO cache (name, ttl, data) VALUES ('cache_old', " . (time() - 60) . ", '" . serialize('v') . "')"
        );

        self::assertNull($this->cache->get('old'));

        $count = $this->raw->query("SELECT COUNT(*) FROM cache WHERE name = 'cache_old'");
        self::assertNotFalse($count);
        self::assertSame(0, (int) $count->fetchColumn());
    }

    public function testOperationsAreSafeWhenTableIsMissing(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'initphp-cache-notable-');
        self::assertIsString($file);
        try {
            $cache = $this->createHandler();
            // Point at a fresh database that has no cache table.
            $cache->setOptions(['dsn' => 'sqlite:' . $file]);

            self::assertNull($cache->get('x'));
            self::assertFalse($cache->has('x'));
            self::assertFalse($cache->set('x', 'v'));
            self::assertFalse($cache->delete('x'));
            self::assertFalse($cache->clear());
        } finally {
            @unlink($file);
        }
    }

    public function testClearEscapesUnderscoreWildcardInPrefix(): void
    {
        // The default prefix is "cache_"; the trailing underscore must be
        // matched literally and not as a single-character LIKE wildcard.
        $this->cache->set('foo', 'mine');
        $this->raw->exec("INSERT INTO cache (name, ttl, data) VALUES ('cacheXfoo', NULL, '" . serialize('other') . "')");

        self::assertTrue($this->cache->clear());

        $survivor = $this->raw->query("SELECT COUNT(*) FROM cache WHERE name = 'cacheXfoo'");
        self::assertNotFalse($survivor);
        self::assertSame(1, (int) $survivor->fetchColumn(), 'A row not matching the literal prefix must survive clear().');
    }
}
