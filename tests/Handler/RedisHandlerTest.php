<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests\Handler;

use InitPHP\Cache\CacheInterface;
use InitPHP\Cache\Handler\Redis as RedisHandler;
use InitPHP\Cache\Tests\AbstractCacheContractTestCase;
use Throwable;

use function getenv;

/**
 * Runs the full contract against a live Redis server.
 *
 * @group integration
 */
final class RedisHandlerTest extends AbstractCacheContractTestCase
{
    protected function setUp(): void
    {
        if (!\extension_loaded('redis')) {
            self::markTestSkipped('The "redis" extension is not loaded.');
        }
        try {
            $this->createHandler()->clear();
        } catch (Throwable $e) {
            self::markTestSkipped('A Redis server is not reachable: ' . $e->getMessage());
        }
        parent::setUp();
    }

    protected function createHandler(array $options = []): CacheInterface
    {
        $host = getenv('REDIS_HOST');
        $port = getenv('REDIS_PORT');

        return new RedisHandler([
            'host'     => $host === false ? '127.0.0.1' : $host,
            'port'     => $port === false ? 6379 : (int) $port,
            'database' => 15,
        ] + $options);
    }
}
