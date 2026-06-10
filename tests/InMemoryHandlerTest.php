<?php

declare(strict_types=1);

namespace InitPHP\Cache\Tests;

use InitPHP\Cache\CacheInterface;
use InitPHP\Cache\Tests\Fixtures\ArrayHandler;

/**
 * Runs the full handler contract against the in-memory {@see ArrayHandler},
 * which exercises every shared {@see \InitPHP\Cache\BaseHandler} code path.
 */
final class InMemoryHandlerTest extends AbstractCacheContractTestCase
{
    protected function createHandler(array $options = []): CacheInterface
    {
        return new ArrayHandler($options);
    }
}
