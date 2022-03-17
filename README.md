# InitPHP Cache

PSR-16 is a simple caching library that uses the Simple Cache interface.

[![Latest Stable Version](http://poser.pugx.org/initphp/cache/v)](https://packagist.org/packages/initphp/cache) [![Total Downloads](http://poser.pugx.org/initphp/cache/downloads)](https://packagist.org/packages/initphp/cache) [![Latest Unstable Version](http://poser.pugx.org/initphp/cache/v/unstable)](https://packagist.org/packages/initphp/cache) [![License](http://poser.pugx.org/initphp/cache/license)](https://packagist.org/packages/initphp/cache) [![PHP Version Require](http://poser.pugx.org/initphp/cache/require/php)](https://packagist.org/packages/initphp/cache)

## Requirements

- PHP 5.6 or higher
- [PSR-16 Simple Cache Interface](https://www.php-fig.org/psr/psr-16/)

Depending on the handler you will use, it may need PHP extensions.

- Redis
- PDO
- Wincache
- Memcache or Memcached

## Installation

```
composer require initphp/cache
```

## Usage

```php 
require_once "../vendor/autoload.php";
use \InitPHP\Cache\Cache;

$cache = Cache::create(\InitPHP\Cache\Handler\File::class, [
    'path'      => __DIR__ . '/Cache/';
]);

if(($posts = $cache->get('posts', null)) === null){
    $posts = [
        ['id' => '12', 'title' => 'Post 12 Title', 'content' => 'Post 12 Content'],
        ['id' => '15', 'title' => 'Post 15 Title', 'content' => 'Post 15 Content'],
        ['id' => '18', 'title' => 'Post 18 Title', 'content' => 'Post 18 Content']
    ];
    $cache->set('posts', $posts, 120);
}

echo '<pre>'; print_r($posts) echo '</pre>';
```

## Configuration and Options

### `\InitPHP\Cache\Handler\File::class`

```php 
$options = [
    'prefix'    => 'cache_',
    'mode'      => 0640,
];
```

### `\InitPHP\Cache\Handler\Memcache::class`

```php 
$options = [
    'prefix'    => 'cache_',
    'host'          => '127.0.0.1',
    'port'          => 11211,
    'weight'        => 1,
    'raw'           => false,
    'default_ttl'   => 60,
];
```

### `\InitPHP\Cache\Handler\PDO::class`

```php 
$options = [
    'prefix'    => 'cache_',
    'dsn'           => 'mysql:host=localhost;dbname=test',
    'username'      => null,
    'password'      => null,
    'charset'       => 'utf8mb4',
    'collation'     => 'utf8mb4_general_ci',
    'table'         => 'cache'
];
```

_Below is a sample cache table creation query for MySQL._

```sql 
CREATE TABLE `cache` (
    `name` VARCHAR(255) NOT NULL,
    `ttl` INT(11) NULL DEFAULT NULL,
    `data` TEXT NOT NULL,
    UNIQUE  (`name`)
) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
```

### `\InitPHP\Cache\Handler\Redis::class`

```php 
$options = [
    'prefix'    => 'cache_',
    'host'      => '127.0.0.1',
    'password'  => null,
    'port'      => 6379,
    'timeout'   => 0,
    'database'  => 0
];
```

### `\InitPHP\Cache\Handler\Wincache::class`

```php 
$options = [
    'prefix'    => 'cache_', // Cache Name Prefix
    'default_ttl'   => 60, // Used if ttl is NULL or not specified.
];
```

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 [MIT License](./LICENSE)