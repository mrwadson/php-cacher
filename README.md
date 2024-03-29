# PHP Cacher

Ease to use simple file cacher written in PHP. Write and read your various data in easy way.

## Requirements

- PHP >= 7.1

## Installation

Install through [composer](https://getcomposer.org/doc/00-intro.md).

```shell
composer install --no-dev # or without --no-dev flag if you need the tests
composer update mrwadson/php-cacher # or if already composer.lock file exists
```

To add as a VCS repository add following lines in your `composer.json` file:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mrwadson/php-cacher.git"
        }
    ],
    "require": {
        "mrwadson/php-cacher": "dev-master"
    }
}
```

## Usage

Just use the cacher in your code like this:

```php
<?php

use mrwadson\cacher;

require __DIR__ . '/vendor/autoload.php';

Cache::write('cache_key', ['key1' => 'value1']); // writes in "cache" dir (in current directory)

print_r(Cache::read('cache_key'));

// Array
// (
//     [key1] => value1
// )
```

Or set options for cacher if you need:

```php
<?php

use mrwadson\cacher;

require __DIR__ . '/vendor/autoload.php';

Cache::options([
    'cache_dir' => null, // if null -> by default "cache" dir (in executed script directory)
    'cache_expire' => 300, // set cache expire in 300 seconds = 5 minutes
]); 
Cache::write('cache_key', ['key1' => 'value1']); 

print_r(Cache::read('cache_key'));

// Array
// (
//     [key1] => value1
// )
```

Get the cache expired time by key:

```php
$expiredTime = Cache::getExpiredTime('cache_key');
echo $expiredTime; // cache filename by example: cache.cache_key.1695904404

// 1695904404
```

Make callback function call if data does not exist on read:

```php
<?php

use mrwadson\cacher;

require __DIR__ . '/vendor/autoload.php';

$data = Cache::read('cache_key', function () {
    return ['key1' => 'value1']
});

print_r(Cache::read('cache_key'));

// Array
// (
//     [key1] => value1
// )
```

## Tests

Running the tests (PHPUnit):

```shell
composer test
```
