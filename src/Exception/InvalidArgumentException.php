<?php

/**
 * InvalidArgumentException.php
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

use InvalidArgumentException as PhpInvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

/**
 * Thrown when a cache key is invalid (empty or containing a character reserved
 * by PSR-16: {@see \InitPHP\Cache\BaseHandler::RESERVED_CHARACTERS}).
 *
 * Extends the SPL {@see PhpInvalidArgumentException} and implements the PSR-16
 * {@see PsrInvalidArgumentException}, so it can be caught either way.
 */
class InvalidArgumentException extends PhpInvalidArgumentException implements PsrInvalidArgumentException
{
}
