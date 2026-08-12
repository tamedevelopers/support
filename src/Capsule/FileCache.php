<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Closure;
use Tamedevelopers\Support\Time;
use Tamedevelopers\Support\Capsule\File;

class FileCache
{    
    /**
     * Cache storage path
     *
     * @var string|null
     */
    protected static ?string $cachePath = null;

    /**
     * Enable or disable serialization mode for complex objects.
     *
     * @var bool
     */
    protected static bool $serializeMode = false;

    /**
     * Set the cache storage path.
     *
     * @param string $path
     * @return void
     */
    public static function setCachePath(string $path = "cache"): void
    {
        $path = storage_path($path);
        File::makeDirectory($path, 0777);
        self::$cachePath = rtrim($path, '/\\');
    }

    /**
     * Ensure cache path is initialized.
     *
     * @return void
     */
    protected static function ensurePathInitialized(): void
    {
        if (!self::$cachePath) {
            self::setCachePath('cache');
        }
    }

    /**
     * Enable or disable serialization mode.
     *
     * @param bool $enable
     * @return static
     */
    public static function serializeMode(bool $enable = true)
    {
        self::$serializeMode = $enable;

        return new static();
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null|Time|\DateTimeInterface $ttl Expiration time in seconds (null for no expiration)
     * @return bool
     */
    public static function put(string $key, $value, $ttl = 604800): bool
    {
        self::ensurePathInitialized();

        $cachePath = self::getCachePath($key);

        // Only serialize if explicit serializeMode is enabled or if value is an Object/Resource
        $shouldSerialize = self::$serializeMode || is_object($value) || is_resource($value);
        $encodedValue = $shouldSerialize ? base64_encode(serialize($value)) : $value;

        $expiresAt = self::calculateExpiration($ttl);

        $data = [
            'serialized' => $shouldSerialize,
            'value'      => $encodedValue,
            'expires_at' => $expiresAt,
        ];

        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        // Atomic write
        $tempFile = $cachePath . '.' . uniqid('', true) . '.tmp';
        if (File::put($tempFile, $json) !== false) {
            return rename($tempFile, $cachePath);
        }

        return false;
    }

    /**
     * Retrieve an item from cache.
     *
     * @param string $key
     * @return mixed
     */
    public static function get(string $key, mixed $default = null)
    {
        self::ensurePathInitialized();

        $cachePath = self::getCachePath($key);

        if (!File::exists($cachePath)) {
            return $default;
        }

        if (self::expired($key)) {
            self::forget($key);
            return $default;
        }

        $content = File::get($cachePath);
        $data = json_decode((string) $content, true);

        if (!is_array($data) || !array_key_exists('value', $data)) {
            self::forget($key);
            return $default;
        }

        $value = $data['value'];

        if (!empty($data['serialized'])) {
            $value = unserialize(base64_decode($value));
        }

        return $value;
    }

    /**
     * Retrieve an item or compute and cache it.
     *
     * @param string $key
     * @param int|null|Time|\DateTimeInterface $ttl Expiration time in seconds (null for no expiration)
     * @param Closure|null $closure
     * @return mixed
     */
    public static function remember(string $key, $ttl = null, $closure = null)
    {
        // Support passing Closure as 2nd parameter: remember('key', fn() => ...)
        if ($ttl instanceof Closure) {
            $closure = $ttl;
            $ttl = 604800;
        }

        if (self::has($key)) {
            return self::get($key);
        }

        $value = $closure ? $closure() : null;

        self::put($key, $value, $ttl);

        return $value;
    }

    /**
     * Check if a cache file exists (ignoring expiration).
     *
     * @param string $key
     * @return bool
     */
    public static function exists(string $key): bool
    {
        self::ensurePathInitialized();
        return File::exists(self::getCachePath($key));
    }

    /**
     * Check if a cache key exists and has not expired.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::exists($key) && !self::expired($key);
    }

    /**
     * Determine if a cached item has expired.
     *
     * @param string $key
     * @return bool
     */
    public static function expired(string $key): bool
    {
        $cachePath = self::getCachePath($key);

        if (!File::exists($cachePath)) {
            return false;
        }

        $data = json_decode(File::get($cachePath), true);
        $expiresAt = $data['expires_at'] ?? null;

        return $expiresAt !== null && $expiresAt < time();
    }

    /**
     * Increment a numeric cache value.
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public static function increment(string $key, int $value = 1): int
    {
        $current = (int) self::get($key, 0);
        $new = $current + $value;
        self::put($key, $new);
        return $new;
    }

    /**
     * Decrement a numeric cache value.
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public static function decrement(string $key, int $value = 1): int
    {
        return self::increment($key, -$value);
    }

    /**
     * Remove a specific cache key.
     *
     * @param string $key
     * @return void
     */
    public static function forget(string $key): void
    {
        self::ensurePathInitialized();

        $cachePath = self::getCachePath($key);

        if (File::exists($cachePath)) {
            unlink($cachePath);
        }
    }

    /**
     * Clear all cache files.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::ensurePathInitialized();

        $files = glob(self::$cachePath . '/*.cache') ?: [];

        foreach ($files as $file) {
            if (File::exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Clear only expired cache files.
     *
     * @return void
     */
    public static function clearExpired(): void
    {
        self::ensurePathInitialized();

        $files = glob(self::$cachePath . '/*.cache') ?: [];

        foreach ($files as $file) {
            if (!File::exists($file)) continue;

            $data = json_decode((string) File::get($file), true);
            $expiresAt = $data['expires_at'] ?? null;

            if ($expiresAt !== null && $expiresAt < time()) {
                unlink($file);
            }
        }
    }

    /**
     * Occasionally clear expired cache automatically (Garbage Collection).
     *
     * @return void
     */
    public static function gc(): void
    {
        // 1 in 50 chance of triggering cleanup
        if (mt_rand(1, 50) === 1) {
            self::clearExpired();
        }
    }

    /**
     * Get all cache filenames.
     *
     * @return array
     */
    public static function all(): array
    {
        self::ensurePathInitialized();

        return array_map('basename', glob(self::$cachePath . '/*.cache') ?: []);
    }

    /**
     * Get cache stats: total count, expired count, and total size.
     *
     * @return array
     */
    public static function stats(): array
    {
        self::ensurePathInitialized();

        $files = glob(self::$cachePath . '/*.cache') ?: [];
        $count = count($files);
        $expired = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);
            $data = json_decode((string) File::get($file), true);
            if (($data['expires_at'] ?? 0) < time()) {
                $expired++;
            }
        }

        return [
            'count' => $count,
            'expired' => $expired,
            'total_size_kb' => round($totalSize / 1024, 2),
        ];
    }

    /**
     * Get the cache file path for a given key.
     *
     * @param string $key
     * @return string
     */
    protected static function getCachePath(string $key): string
    {
        self::ensurePathInitialized();
        return self::$cachePath . '/' . md5($key) . '.cache';
    }

    /**
     * Calculate absolute Unix timestamp for expiration.
     */
    protected static function calculateExpiration(mixed $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        // Handle Carbon, DateTime, and DateTimeImmutable objects directly
        if ($ttl instanceof \DateTimeInterface) {
            return $ttl->getTimestamp();
        }

        $seconds = match (true) {
            $ttl instanceof Time => $ttl->ttl,
            is_numeric($ttl) => (int) $ttl,
            default => 604800,
        };

        return time() + $seconds;
    }

}
