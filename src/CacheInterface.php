<?php
/**
 * CacheInterface.php
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

interface CacheInterface extends \Psr\SimpleCache\CacheInterface
{

    /**
     * @param array $options
     * @return CacheInterface
     */
    public function setOptions($options = []);

    /**
     * @param string $name
     * @param int $offset
     * @return int
     * @throws \InitPHP\Cache\Exception\InvalidArgumentException
     */
    public function increment($name, $offset = 1);

    /**
     * @param string $name
     * @param int $offset
     * @return int
     * @throws \InitPHP\Cache\Exception\InvalidArgumentException
     */
    public function decrement($name, $offset = 1);

}
