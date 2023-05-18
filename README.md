# PHP Cacher

Ease to use simple file cacher written in PHP.

## Requirements

- PHP >= 5.6 (for wide using)

## Installation

Install through [composer](https://getcomposer.org/doc/00-intro.md).

```shell
composer install --no-dev # or without --no-dev flag if you need the tests
composer update mrwadson/cacher # or if already composer.lock file exists
```

To add as a VCS repository add following lines in your `composer.json` file:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mrwadson/cacher.git"
        }
    ],
    "require": {
        "mrwadson/cacher": "dev-master"
    }
}
```

## Usage

Just use the cacher in your code like this:

```php
<?php

use mrwadson\cacher;

require __DIR__ . '/vendor/autoload.php';

Cache::write('abc', ['key1' => 'value1']); // writes in "cache" dir (in current directory)

print_r(Cache::read('abc'));
```

Will output:

```php
Array
(
    [key1] => value1
)
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
Cache::write('abc', ['key1' => 'value1']); 

print_r(Cache::read('abc'));
```

Will output:

```php
Array
(
    [key1] => value1
)
```

## Tests

Running the tests:

```shell
composer test
```
