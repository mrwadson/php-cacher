<?php

use mrwadson\cacher\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function __construct($name = null)
    {
        Cache::options(['cache_dir' => __DIR__ . '/../cache_dir']);
        parent::__construct($name);
    }

    public function testCacheWriteAndRead(): void
    {
        Cache::write('cache_key', ['key1' => 'value1', 'key2' => 'value2'], 5); // cache lifetime 5 seconds
        $this->assertFileExists(__DIR__ . '/../cache_dir');

        $cache = Cache::read('cache_key');
        $this->assertArrayHasKey('key2', $cache);
    }

    public function testGetCacheExpiredTime(): void
    {
        Cache::write('cache_key_expired_time1', ['key1' => 'value1', 'key2' => 'value2'], 5);
        $expiredTime = Cache::getExpiredTime('cache_key_expired_time1');
        $this->assertIsInt($expiredTime);
    }

    public function testCallbackOnReadTheCache(): void
    {
        $data = Cache::read('cache_key', static function () {
            return ['key1' => 'value1'];
        });

        $this->assertArrayHasKey('key1', $data);
    }
}
