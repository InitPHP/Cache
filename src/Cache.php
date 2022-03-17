<?php
/**
 * Cache.php
 *
 * This file is part of InitPHP.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    0.1
 * @link       https://www.muhammetsafak.com.tr
 */

namespace InitPHP\Cache;

use InitPHP\Cache\Exception\CacheException;

use function is_string;
use function class_exists;
use function is_array;

class Cache
{

    /**
     * @param string|object $handler
     * @param array $options
     * @return CacheInterface
     * @throws CacheException
     */
    public static function create($handler, $options = [])
    {
        if(!is_array($options)){
            throw new CacheException("\$options must be an associative array.");
        }
        if(is_string($handler) && class_exists($handler)){
            $handler = new $handler();
        }
        if($handler instanceof CacheInterface){
            return $handler->setOptions($options);
        }
        throw new CacheException('The handler must be an object or class that uses a \\InitPHP\\Cache\\CacheInterface interface.');
    }

}
