<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Capsule\File;

/**
 * Utility for fetching, caching, and checking disposable email domains.
 *
 * Caching strategy:
 * - First attempt to read a gzip-compressed JSON cache: storage/cache/disposable_domains.json.gz
 * - If missing or expired, fetch the latest domains.json from upstream, normalize, compress and store.
 * - TTL (in days) can be configured via DISPOSABLE_DOMAINS_TTL_DAYS (default: 7 days).
 * - Upstream URL can be overridden via DISPOSABLE_DOMAINS_URL.
 */
trait DisposableEmailUtilityTrait
{
    /** @var string Default upstream JSON URL */
    private static string $REMOTE_JSON_URL = 'https://disposable.github.io/disposable-email-domains/domains.json';

    /** @var array<string>|null In-memory normalized domain list (lowercased, unique, sorted) */
    private static ?array $disposableDomains = null;

    /** @var array<string,bool>|null Fast lookup index (built from $disposableDomains) */
    private static ?array $domainsIndex = null;

    /** @var int Days for cached expiration */
    private static ?int $cachedExpireDay = 7;

    /**
     * Check if an email address belongs to a disposable provider.
     */
    public static function isDisposableEmail(string $email): bool
    {
        $email = Str::lower($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = substr(strrchr($email, '@') ?: '', 1);
        return self::isDisposableDomain($domain);
    }

    /**
     * Check if a domain (or any of its parent suffixes) is disposable.
     */
    public static function isDisposableDomain(string $domain): bool
    {
        $domain = Str::lower($domain);
        if ($domain === '') {
            return false;
        }

        $domains = self::domains();

        // Build or reuse index for O(1) lookups
        if (self::$domainsIndex === null) {
            self::$domainsIndex = array_fill_keys($domains, true);
        }

        // Exact domain match
        if (isset(self::$domainsIndex[$domain])) {
            return true;
        }

        // Check parent suffixes (e.g., sub.mail.temp-mail.org -> temp-mail.org)
        $parts = explode('.', $domain);
        array_shift($parts); // remove leftmost label
        while (count($parts) >= 2) {
            $candidate = implode('.', $parts);
            if (isset(self::$domainsIndex[$candidate])) {
                return true;
            }
            array_shift($parts);
        }

        return false;
    }

    /**
     * Get the cached/remote list of disposable domains.
     * Set $forceRefresh to true to ignore cache and fetch latest immediately.
     *
     * @return array<int,string>
     */
    public static function domains(bool $forceRefresh = false): array
    {
        if (!$forceRefresh && is_array(self::$disposableDomains) && self::$disposableDomains !== []) {
            return self::$disposableDomains;
        }

        $data = $forceRefresh ? null : self::loadFromCache();
        if ($data === null) {
            $data = self::fetchAndCache();
        }

        self::$disposableDomains = $data;
        self::$domainsIndex = null; // reset index so it's rebuilt lazily
        return self::$disposableDomains;
    }

    /**
     * Force-refresh the cache from the upstream source and return the domains.
     */
    public static function refreshCache(): array
    {
        return self::domains(true);
    }

    /**
     * Cache metadata for debugging/monitoring purposes.
     */
    public static function cacheInfo(): array
    {
        $file = self::cacheFile();
        $ttl = self::cacheTtlSeconds();
        $exists = File::exists($file);
        $last = $exists ? (int) File::lastModified($file) : null;

        return [
            'path' => $file,
            'exists' => $exists,
            'last_modified' => $last,
            'expires_at' => $last ? $last + $ttl : null,
            'ttl_seconds' => $ttl,
        ];
    }

    // ========== Internals ==========

    /**
     * Fetch domains.json, normalize, write cache (gzip), and return the list.
     *
     * @return array<int,string>
     */
    private static function fetchAndCache(): array
    {
        $url = Env::env('DISPOSABLE_DOMAINS_URL', self::$REMOTE_JSON_URL);
        $json = File::get($url);

        // Fallback to existing cache when fetch fails
        if ($json === false || $json === '') {
            return self::loadFromCache() ?? [];
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return self::loadFromCache() ?? [];
        }

        $domains = self::normalizeDomainsArray($data);
        self::writeCache($domains);
        return $domains;
    }

    /**
     * Try to load domains from the local gzip cache if present and not expired.
     *
     * @return array<int,string>|null Null when cache is missing/expired/unreadable
     */
    private static function loadFromCache(): ?array
    {
        $file = self::cacheFile();
        $ttl = self::cacheTtlSeconds();

        if (!File::exists($file)) {
            return null;
        }

        $modified = (int) File::lastModified($file);
        if ($modified <= 0 || (time() - $modified) > $ttl) {
            return null; // expired
        }

        $raw = File::get($file);
        if ($raw === false || $raw === '') {
            return null;
        }

        // Prefer gzip; fall back to plain JSON if not compressed
        $json = function_exists('gzdecode') ? @gzdecode($raw) : null;
        if ($json === false || $json === null) {
            $json = $raw; // assume plain JSON
        }

        $data = json_decode((string) $json, true);
        if (!is_array($data)) {
            return null;
        }

        return self::normalizeDomainsArray($data);
    }

    /**
     * Normalize domain array: lower-case, trim, unique, sorted.
     *
     * @param array<int,mixed> $data
     * @return array<int,string>
     */
    private static function normalizeDomainsArray(array $data): array
    {
        $set = [];
        foreach ($data as $d) {
            if (!is_string($d)) {
                continue;
            }
            $d = Str::lower($d);
            if ($d !== '') {
                $set[$d] = true;
            }
        }
        $domains = array_keys($set);
        sort($domains, SORT_STRING);
        return $domains;
    }

    /**
     * Write gzip-compressed JSON cache to disk.
     */
    private static function writeCache(array $domains): void
    {
        $dir = self::cacheDir();
        File::makeDirectory($dir);

        if (!File::isDirectory($dir)) {
            return; // cannot write
        }

        $payload = json_encode(array_values($domains), JSON_UNESCAPED_SLASHES);
        $compressed = function_exists('gzencode') ? gzencode((string) $payload, 9) : $payload;
        File::put(self::cacheFile(), (string) $compressed);
    }

    private static function cacheDir(): string
    {
        return Server::formatWithBaseDirectory('storage/cache');
    }

    private static function cacheFile(): string
    {
        return rtrim(self::cacheDir(), '/') . '/disposable_domains.json.gz';
    }

    private static function cacheTtlSeconds(): int
    {
        $days = (int) Env::env('DISPOSABLE_DOMAINS_TTL_DAYS', self::$cachedExpireDay);
        $days = $days > 0 ? $days : self::$cachedExpireDay;
        return $days * 86400;
    }
}