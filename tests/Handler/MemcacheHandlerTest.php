<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests\Handler;

use InitPHP\Cache\CacheInterface;
use InitPHP\Cache\Handler\Memcache as MemcacheHandler;
use InitPHP\Cache\Tests\AbstractCacheContractTestCase;
use Throwable;

use function getenv;

/**
 * Runs the full contract against a live Memcached server.
 *
 * @group integration
 */
final class MemcacheHandlerTest extends AbstractCacheContractTestCase
{
    protected function setUp(): void
    {
        if (!\extension_loaded('memcached') && !\extension_loaded('memcache')) {
            self::markTestSkipped('Neither the "memcached" nor the "memcache" extension is loaded.');
        }
        try {
            $this->createHandler()->clear();
        } catch (Throwable $e) {
            self::markTestSkipped('A Memcached server is not reachable: ' . $e->getMessage());
        }
        parent::setUp();
    }

    protected function createHandler(array $options = []): CacheInterface
    {
        $host = getenv('MEMCACHED_HOST');
        $port = getenv('MEMCACHED_PORT');

        return new MemcacheHandler([
            'host' => $host === false ? '127.0.0.1' : $host,
            'port' => $port === false ? 11211 : (int) $port,
        ] + $options);
    }
}
