<?php

use mrwadson\cacher\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        Cache::options(['cache_dir' => __DIR__ . '/../cache_dir']);
        parent::__construct($name, $data, $dataName);
    }

    public function testCacheWriteAndRead()
    {
        Cache::write('cache_key', ['key1' => 'value1', 'key2' => 'value2'], 5); // cache lifetime 5 seconds
        $this->assertFileExists( __DIR__ . '/../cache_dir');

        $cache = Cache::read('cache_key');
        $this->assertArrayHasKey('key2', $cache);
    }

    public function testGetCacheExpiredTime()
    {
        Cache::write('cache_key_expired_time', ['key1' => 'value1', 'key2' => 'value2'], 5);
        $expiredTime = Cache::getExpiredTime('cache_key_expired_time1');

        $this->assertInternalType('int', $expiredTime);
    }

    public function testCallbackOnReadTheCache()
    {
        $data = Cache::read('cache_key', static function () {
            return ['key1' => 'value1'];
        });

        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('key1', $data);
    }
}
