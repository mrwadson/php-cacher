<?php

use App\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function testWrightCache()
    {
        Cache::options(['cache_dir' => __DIR__ . '/../cache_dir']);
        Cache::write('key1', ['key1' => 'value1', 'key2' => 'value2'], 5); // cache lifetime 5 seconds

        $cache = Cache::read('key1');
        $this->assertArrayHasKey('key2', $cache);
    }
}
