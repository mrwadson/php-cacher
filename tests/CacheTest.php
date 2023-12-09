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
        Cache::write(__METHOD__, ['key1' => 'value1', 'key2' => 'value2'], 5); // cache lifetime 5 seconds
        $this->assertFileExists(__DIR__ . '/../cache_dir');

        $cache = Cache::read(__METHOD__);
        $this->assertArrayHasKey('key2', $cache);
    }

    public function testGetCacheIsExpired(): void
    {
        Cache::write(__METHOD__, ['key1' => 'value1', 'key2' => 'value2'], 3);
        sleep(5);
        $this->assertTrue(Cache::isExpired(__METHOD__));
    }

    public function testCallbackOnReadTheCache(): void
    {
        $data = Cache::read(__METHOD__, false, static function () {
            return ['key1' => 'value1'];
        });

        $this->assertArrayHasKey('key1', $data);
    }

    public function testReadAndWriteCacheWithCallback(): void
    {
        $cachedClassData = Cache::read(__METHOD__, true, static function () {
            $class = new StdClass();
            $class->key1 = 'value1';
            $class->key2 = 555;
            $class->key3 = false;

            return $class;
        }, true, null, true);

        $this->assertObjectHasAttribute('key3', $cachedClassData);
    }
}
