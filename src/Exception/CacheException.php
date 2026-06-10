<?php

/**
 * CacheException.php
 *
 * This file is part of InitPHP Cache.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    https://github.com/InitPHP/Cache/blob/main/LICENSE  MIT
 * @link       https://github.com/InitPHP/Cache
 */

declare(strict_types=1);

namespace InitPHP\Cache\Exception;

use Exception;
use Psr\SimpleCache\CacheException as PsrCacheException;

/**
 * Thrown for cache errors that are not caused by an invalid argument, such as a
 * missing configuration value or a backend connection failure.
 *
 * Implements {@see PsrCacheException} so that, per PSR-16, every exception the
 * library throws can be caught through the PSR interface.
 */
class CacheException extends Exception implements PsrCacheException
{
}
