<?php

/**
 * Cache.php
 *
 * This file is part of InitPHP Cache.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    https://github.com/InitPHP/Cache/blob/main/LICENSE  MIT
 * @link       https://github.com/InitPHP/Cache
 */

declare(strict_types=1);

namespace InitPHP\Cache;

use InitPHP\Cache\Exception\CacheException;

use function class_exists;

/**
 * Factory that builds a configured cache handler.
 *
 * @see \InitPHP\Cache\Handler\File
 * @see \InitPHP\Cache\Handler\PDO
 * @see \InitPHP\Cache\Handler\Redis
 * @see \InitPHP\Cache\Handler\Memcache
 * @see \InitPHP\Cache\Handler\Wincache
 */
final class Cache
{
    /**
     * Creates a cache handler and applies the given options.
     *
     * @param class-string<CacheInterface>|CacheInterface $handler A handler
     *        class name to instantiate, or an already-built handler instance.
     * @param array<string, mixed> $options Handler options; keys are matched
     *        case-insensitively.
     * @return CacheInterface
     * @throws CacheException If the class does not exist or does not implement
     *                        {@see CacheInterface}, or if the handler's runtime
     *                        requirements are not met.
     */
    public static function create(string|CacheInterface $handler, array $options = []): CacheInterface
    {
        if (\is_string($handler)) {
            if (!class_exists($handler)) {
                throw new CacheException(\sprintf('The cache handler class "%s" does not exist.', $handler));
            }
            $handler = new $handler();
        }

        if ($handler instanceof CacheInterface) {
            return $handler->setOptions($options);
        }

        throw new CacheException(\sprintf(
            'The cache handler must implement %s.',
            CacheInterface::class
        ));
    }
}
