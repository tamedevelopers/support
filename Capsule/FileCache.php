<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

class FileCache{    

    /**
     * cachePath
     *
     * @var mixed
     */
    static public $cachePath;
    

    /**
     * Set the cache storage path.
     *
     * @param string $path
     * @return void
     */
    static public function setCachePath(string $path = "cache"): void
    {
        // if \storage folder not found
        $path = storage_path($path);
        if (!is_dir($path)) {
            @mkdir($path, 0777);
        }

        self::$cachePath = rtrim($path, '/\\');
    }

    /**
     * Put an item in the cache with an optional expiration time.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $expirationTime Expiration time in seconds (null for no expiration)
     * @return void
     */
    static public function put(string $key, $value, ?int $expirationTime = 604800): void
    {
        $cachePath = self::getCachePath($key);

        $data = [
            'value' => $value,
            'expires_at' => $expirationTime !== null ? time() + $expirationTime : null,
        ];

        file_put_contents($cachePath, json_encode($data));
    }

    /**
     * Retrieve an item from the cache if it exists and has not expired.
     *
     * @param string $key
     * @return mixed|null
     */
    static public function get(string $key)
    {
        $cachePath = self::getCachePath($key);
        
        if (file_exists($cachePath)) {
            $data = json_decode(file_get_contents($cachePath), true);

            if (self::expired($key)) {
                self::forget($key);
                return null;
            }

            return $data['value'];
        }

        return null;
    }

    /**
     * Retrieve an item from the cache if it exists
     *
     * @param string $key
     * @return bool
     */
    static public function exists(string $key)
    {   
        $key = self::getCachePath($key);
        return file_exists( $key ) && !is_dir($key);
    }

    /**
     * Check if an item exists in the cache and has not expired.
     *
     * @param string $key
     * @return bool
     */
    static public function has(string $key): bool
    {
        $cachePath = self::getCachePath($key);

        if (file_exists($cachePath)) {
            $data = json_decode(file_get_contents($cachePath), true);

            return !self::expired($key);
        }

        return false;
    }

    /**
     * Check if the cached data associated with the key has expired.
     *
     * @param string $key
     * @return bool
     */
    static public function expired(string $key): bool
    {
        $cachePath = self::getCachePath($key);

        if (file_exists($cachePath)) {
            $data = json_decode(file_get_contents($cachePath), true);

            $expiresAt = $data['expires_at'] ?? null;

            return $expiresAt !== null && $expiresAt < time();
        }

        return false;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return void
     */
    static public function forget(string $key): void
    {
        $cachePath = self::getCachePath($key);

        if (file_exists($cachePath)) {
            unlink($cachePath);
        }
    }

    /**
     * Clear all items from the cache.
     *
     * @return void
     */
    static public function clear(): void
    {
        $files = glob(self::$cachePath . '/*.cache');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Get the cache file path for a given key.
     *
     * @param string $key
     * @return string
     */
    static protected function getCachePath(string $key): string
    {
        return self::$cachePath . '/' . md5($key) . '.cache';
    }
}
