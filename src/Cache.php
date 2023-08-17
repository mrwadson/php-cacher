<?php

namespace mrwadson\cacher;

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
        'cache_expire' => 3600, // in seconds = 1 hour
        'clear_cache_random' => false // clear cache in randomly period (see end function)
    ];

    private static $initiated = false;

    /**
     * Set options for cache
     *
     * @param array $options - array of the options
     *
     * @return void | array
     */
    public static function options($options)
    {
        if (!is_array($options)) {
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
    private static function init()
    {
        self::$options['cache_dir'] = self::$options['cache_dir'] ?: self::initiateDir();

        if (!is_dir($dir =  self::$options['cache_dir']) && !mkdir($dir) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        self::$options['cache_expire'] = (int)self::$options['cache_expire'];
        self::$options['clear_cache_random'] = (bool)self::$options['clear_cache_random'];
        if (!self::$initiated) {
            register_shutdown_function([__CLASS__, 'end']);
            self::$initiated = true;
        }
    }

    /**
     * Get initiate dir for setting as cache dir
     *
     * @return string
     */
    private static function initiateDir()
    {
        $stack = debug_backtrace();
        $firstFrame = $stack[count($stack) - 1];
        return dirname($firstFrame['file']) . '/cache';
    }

    /**
     * Read cache from the cache file by key
     *
     * @param string $key - file cache key
     *
     * @return mixed
     */
    public static function read($key)
    {
        if (!self::$initiated) {
            self::init();
        }
        if ($files = self::search($key)) {
            return json_decode(file_get_contents($files[0]), true);
        }

        return [];
    }

    /**
     * Write cache to the cache file by key
     *
     * @param string $key - file cache key
     * @param string | array $value - file cache key
     * @param int $expire - expire period in seconds
     *
     * @return void
     */
    public static function write($key, $value, $expire = 0)
    {
        self::delete($key);
        file_put_contents(self::$options['cache_dir'] . '/cache.' . self::clean($key) . '.' . (time() + ($expire ?: self::$options['cache_expire'])), json_encode($value));
    }

    /**
     * Search cache the cache file by key
     *
     * @param string $key - file cache key
     *
     * @return array
     */
    private static function search($key)
    {
        return glob(self::$options['cache_dir'] . '/cache.' . self::clean($key) . '.*');
    }

    /**
     * Delete the cache file by key
     *
     * @param string $key - file cache key
     *
     * @return void
     */
    public static function delete($key)
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
     * Clean the key from unsupported characters
     *
     * @param string $key - file cache key
     *
     * @return string
     */
    private static function clean($key)
    {
        return preg_replace('/[^A-Z0-9._-]/i', '', $key);
    }

    /**
     * Shutdown function - randomly started for clear all cache
     *
     * @return void
     */
    public static function end()
    {
        $files = glob(self::$options['cache_dir'] . '/cache.*');

        if ($files && (!self::$options['clear_cache_random'] || mt_rand(1, 100) === 1)) {
            foreach ($files as $file) {
                $time = substr(strrchr($file, '.'), 1);
                if ($time < time() && !@unlink($file)) {
                    clearstatcache(false, $file);
                }
            }
        }
    }
}
