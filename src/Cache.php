<?php

namespace mrwadson\cacher;

use Exception;
use RuntimeException;

/**
 * Simple PHP Cache class
 */
class Cache
{
    /**
     * Cache options
     *
     * @var string[]
     */
    private static $options = [
        'cache_dir' => null, // if null -> will change to "cache" dir related to the running script.
        'cache_expire' => 3600, // in seconds = 1 hour or -1 for lifetime cache
        'clear_cache_random' => false, // clear cache in randomly period (see "end" function)
        'never_clear_all_cache' => false, // never clear all cache files (at end script)
        'delete_cache_if_expired' => true // delete cache if it expired and never_clear_all_cache = true (on read cache)
    ];

    private static $initiated = false;

    /**
     * Set options for cache
     *
     * @param array $options array of the options
     *
     * @return void | array
     */
    public static function options(array $options)
    {
        if (!$options) {
            return self::$options;
        }
        self::$options = array_merge(self::$options, $options);
        self::init();
    }

    /**
     * Init options
     *
     * @return void
     */
    private static function init(): void
    {
        self::$options['cache_dir'] = self::$options['cache_dir'] ?: self::initiateDir();

        if (!is_dir($dir =  self::$options['cache_dir']) && !mkdir($dir) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        self::$options['cache_expire'] = (int)self::$options['cache_expire'];
        self::$options['clear_cache_random'] = (bool)self::$options['clear_cache_random'];
        self::$options['never_clear_all_cache'] = (bool)self::$options['never_clear_all_cache'];
        self::$options['delete_cache_if_expired'] = (bool)self::$options['delete_cache_if_expired'];

        if (!self::$initiated) {
            if (!self::$options['never_clear_all_cache']) {
                register_shutdown_function([__CLASS__, 'end']);
            }
            self::$initiated = true;
        }
    }

    /**
     * Get initiate dir for setting as cache dir
     *
     * @return string
     */
    private static function initiateDir(): string
    {
        $stack = debug_backtrace();
        $firstFrame = $stack[count($stack) - 1];
        return dirname($firstFrame['file']) . '/cache';
    }

    /**
     * Read cache from the file by key
     * And writes callback function resulted data to the cache file (if callback function is used)
     *
     * @param string $key file cache key
     * @param bool $unserializeOnRead need unserialize on read from cache
     * @param callable|null $callback callable function that return new data
     * @param bool $writeCallbackResult write data from callback result to cache
     * @param int|null $expire expire period in seconds if callable function used
     * @param bool $serializeOnWrite is need serialize value if write callback result
     *
     * @return mixed
     */
    public static function read(string $key, bool $unserializeOnRead = false, callable $callback = null, bool $writeCallbackResult = true, int $expire = null, bool $serializeOnWrite = false)
    {
        if (!self::$initiated) {
            self::init();
        }

        if (self::$options['never_clear_all_cache'] && self::$options['delete_cache_if_expired']) {
            self::deleteIfExpired($key);
        }

        if ($files = self::search($key)) {
            $data = json_decode(file_get_contents($files[0]), true);

            return $unserializeOnRead ? unserialize($data, ['allowed_classes' => true]) : $data;
        }

        if ($callback && $data = $callback()) {
            if ($writeCallbackResult) {
                self::write($key, $data, $expire, $serializeOnWrite);
            }
            return $data;
        }

        return null;
    }

    /**
     * Write cache to the file by key
     *
     * @param string $key file cache key
     * @param string | array $value file cache key
     * @param int|null $expire expire period in seconds
     * @param bool $serialize is need serialize value
     *
     * @return void
     */
    public static function write(string $key, $value, int $expire = null, bool $serialize = false): void
    {
        self::delete($key);

        $value = $serialize ? serialize($value) : $value;

        file_put_contents(self::$options['cache_dir'] . '/cache.' . self::clean($key) . '.' . self::getExpireTime($expire), json_encode($value));
    }

    /**
     * Check if cache expired by key
     *
     * @param string $key
     *
     * @return bool
     */
    public static function isExpired(string $key): bool
    {
        if ($expiredTime = self::getExpiredTime($key)) {
            return self::isTimeExpired($expiredTime);
        }

        return true;
    }

    /**
     * Delete the cache file by key
     *
     * @param string $key file cache key
     *
     * @return void
     */
    public static function delete(string $key): void
    {
        if (!self::$initiated) {
            self::init();
        }
        if ($files = self::search($key)) {
            foreach ($files as $file) {
                if (!@unlink($file)) {
                    clearstatcache(false, $file);
                }
            }
        }
    }

    /**
     * Clear cache by key if expired
     *
     * @param string $key
     *
     * @return void
     */
    public static function deleteIfExpired(string $key): void
    {
        if (self::isExpired($key)) {
            self::delete($key);
        }
    }

    /**
     * Get cache expired unix time by key
     *
     * @param string $key file cache key
     *
     * @return int|null
     */
    public static function getExpiredTime(string $key): ?int
    {
        if (!self::$initiated) {
            self::init();
        }
        if (($files = self::search($key)) && ($parts = explode('.', $files[0])) && $time = array_pop($parts)) {
            return (int)$time;
        }

        return null;
    }

    /**
     * Shutdown function for clear all cache
     *
     * @return void
     * @throws Exception
     */
    public static function end(): void
    {
        if (!self::$options['never_clear_all_cache']) {
            $files = glob(self::$options['cache_dir'] . '/cache.*');

            if ($files && (!self::$options['clear_cache_random'] || random_int(1, 100) === 1)) {
                foreach ($files as $file) {
                    $time = (int)substr(strrchr($file, '.'), 1);
                    if (self::isTimeExpired($time) && !@unlink($file)) {
                        clearstatcache(false, $file);
                    }
                }
            }
        }
    }

    /**
     * Search the cache files by key
     *
     * @param string $key file cache key
     *
     * @return array
     */
    private static function search(string $key): array
    {
        return glob(self::$options['cache_dir'] . '/cache.' . self::clean($key) . '.*');
    }

    /**
     * Get cache expire time
     *
     * @param int|null $expire seconds or -1 for lifetime cache
     *
     * @return mixed
     */
    private static function getExpireTime(int $expire = null)
    {
        if ($expire === -1) {
            return $expire;
        }

        if (is_null($expire) && self::$options['cache_expire'] === -1) {
            return self::$options['cache_expire'];
        }

        return (time() + (is_null($expire) ? self::$options['cache_expire'] : $expire));
    }

    /**
     * Check if time expired
     *
     * @param int $time
     *
     * @return bool
     */
    private static function isTimeExpired(int $time): bool
    {
        return $time !== -1 && $time < time();
    }

    /**
     * Clean the key from unsupported characters
     *
     * @param string $key file cache key
     *
     * @return string
     */
    private static function clean(string $key): string
    {
        return preg_replace('/[^A-Z0-9._-]/i', '', $key);
    }
}
