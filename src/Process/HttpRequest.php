<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use Tamedevelopers\Support\Traits\ServerTrait;
use Tamedevelopers\Support\Collections\Collection;
use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Process\Concerns\RequestInterface;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;

/**
 * Native PHP request implementation for RequestInterface.
 */
class HttpRequest implements RequestInterface
{
    use ServerTrait;

    /** @inheritDoc */
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** @inheritDoc */
    public static function url() : string
    {
        // If we are in the browser, the browser's URL is the "Truth"
        if (!self::runningInConsole()) {
            return self::full();
        }

        // Fallback to Env for CLI/Cron jobs where there is no browser request
        $url = Env::env('APP_URL') ?? self::full();

        return Str::trim($url, '\/');
    }

    /** @inheritDoc */
    public static function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /** @inheritDoc */
    public static function path($path = null): string
    {
        $basePath = self::localDomainPath();
    
        // Ensure base path is at least a /
        $basePath = '/' . trim($basePath, '/');

        if (!empty($path)) {
            $path = ltrim($path, '/');

            // If we aren't at root, add a separator
            $glue = $basePath === '/' ? '' : '/';

            return $basePath . $glue . self::replace($path);
        }
        
        return $basePath;
    }

    /** @inheritDoc */
    public static function http(): string
    {
        // Check for standard HTTPS
        if (isset($_SERVER['HTTPS']) && Str::lower($_SERVER['HTTPS']) !== 'off') {
            return 'https://';
        }

        // Check for Proxy-forwarded SSL (Common in Nginx/Cloudflare/Heroku)
        $forwarded = self::header('X-Forwarded-Proto');
        if ($forwarded === 'https') {
            return 'https://';
        }
        
        return 'http://';
    }

    /** @inheritDoc */
    public static function host(): string
    {
        //  Try Forwarded Host (Advanced/Proxy)
        if ($host = self::header('X-Forwarded-Host')) {
            return $host;
        }

        // Try Standard Host
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    /** @inheritDoc */
    public static function full(): string
    {
        $path = self::path();

        // Ensure we don't return "//" for root
        $formattedPath = ($path === '/') ? '' : '/' . ltrim($path, '/');

        return self::http() . self::host() . $formattedPath;
    }

    /** @inheritDoc */
    public static function query($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /** @inheritDoc */
    public static function post($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /** @inheritDoc */
    public static function input($key = null, $default = null)
    {
        if ($key === null) {
            return array_merge($_GET, $_POST);
        }
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /** @inheritDoc */
    public static function header(string $key, $default = null)
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        if (isset($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }
        $fallbacks = [
            'CONTENT_TYPE' => 'content-type',
            'CONTENT_LENGTH' => 'content-length',
        ];
        foreach ($fallbacks as $srv => $hdr) {
            if (strtolower($key) === $hdr && isset($_SERVER[$srv])) {
                return $_SERVER[$srv];
            }
        }
        return $default;
    }

    /** @inheritDoc */
    public static function headers(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }
        return $headers;
    }

    /** @inheritDoc */
    public static function cookie(string $key, $default = null)
    {
        return $_COOKIE[$key] ?? $default;
    }

    /** @inheritDoc */
    public static function cookies(): array
    {
        return (array) ($_COOKIE ?? []);
    }

    /** @inheritDoc */
    public static function ip(): ?string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $ipList = explode(',', (string) $_SERVER[$k]);
                $ip = trim($ipList[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return null;
    }

    /** @inheritDoc */
    public static function isAjax(): bool
    {
        return strtolower((string) (self::header('X-Requested-With') ?? '')) === 'xmlhttprequest';
    }

    /** @inheritDoc */
    public static function server(): string
    {
        return self::getServerPath();
    }

    /** @inheritDoc */
    public static function request(): string
    {
        return Str::replace(self::path(), '', ($_SERVER['REQUEST_URI'] ?? ''));
    }

    /** @inheritDoc */
    public static function referral(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Get Host from URL
     * - Parse URL and reliably extract the host, sanitizing protocol typos.
     *
     * @param string $url
     * @return string
     */
    public static function getHost($url)
    {
        return Tame::getHostFromUrl($url);
    }

    /**
     * Check if a given URL is reachable
     *
     * @param string $url
     * @return bool
     */
    public static function urlExist($url)
    {
        return Tame::urlExist($url);
    }

    /**
     * Check if the internet connection is available
     *
     * @return bool
     */
    public static function isInternet()
    {
        return Tame::isInternetAvailable(null, 53, 2);
    }

    /**
     * Alias for `runningInConsole()` method
     *
     * @return bool
     */
    public static function isConsole()
    {
        return self::runningInConsole();
    }

    /**
     * Check if the server is using a local/private IP.
     *
     * @return bool
     */
    public static function isLocalIp()
    {
        // Strict mode: only verify if machine is local/private
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());

        // Check if it's loopback or private LAN range
        // 127.* loopback IPv4
        // ::1 loopback IPv6
        // 10.*, 172.16.*, 192.168.* → private LAN ranges
        // localhost - explicit hostname, which some setups resolve instead of raw IP
        $localRanges = ['127.', '::1', '10.', '172.16.', 'localhost'];

        // return TRUE if the current server address starts with any local prefix
        return (new Collection($localRanges))->startsWith($serverAddr);
    }

    /**
     * Is IP accessed via private LAN port in browser
     * 
     * @return bool
     */
    public static function isIpAccessedViaPrivateLanPort()
    {
        return self::isIpAccessedVia127Port();
    }

    /**
     * Is IP accessed via 127.0.0.1 port in browser
     * 
     * @return bool
     */
    public static function isIpAccessedVia127Port()
    {
        return Str::contains(
            $_SERVER['REMOTE_ADDR'] ?? '', 
            self::host()
        );
    }

    /**
     * Is IP accessed via localhost port in browser
     *
     * @return bool
     */ 
    public static function isIpAccessedViaLocalHost()
    {
        return Str::contains(
            $_SERVER['REMOTE_ADDR'] ?? '',
            'localhost'
        );
    }

    /**
     * Determine if the script is running in CLI mode.
     *
     * @return bool
     */
    public static function runningInConsole()
    {
        return (php_sapi_name() === 'cli' || PHP_SAPI === 'cli');
    }

    /**
     * Local Domain Path
     * 
     * @return string
     */
    private static function localDomainPath()
    {

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $root = self::pathReplacer($_SERVER['DOCUMENT_ROOT']);
        $absolutePath = self::pathReplacer(self::createAbsolutePath());

        // Normalize and get the physical directory
        $path = str_replace($root, '', $absolutePath);
        $path = trim($path, '/');

        // 2. The "1% Fix": Verify the path actually exists in the URI
        // If the URI is /blog/posts and path is /var/www/html, the path is invalid for the URL.
        if (!empty($path) && strpos($uri, '/' . $path) !== 0) {
            // If not found at the start of the URI, we might be in a root rewrite scenario.
            // We try to see if the script name (minus index.php) matches the start of URI.
            $path = ''; 
        }

        // 3. Fallback for Front Controllers (e.g., Laravel-style /public/index.php)
        // If path is empty, we check if we are running in a known app framework structure
        if (empty($path) && (new Tame)->isAppFramework()) {
            // Logic to detect if we're inside a 'public' folder but accessed via root
            return '/';
        }

        return empty($path) ? '/' : $path;
    }

    /**
     * Get server path
     * 
     * @param string|null $path
     * @return string
     */
    private static function replace($path = null) 
    {
        return self::pathReplacer($path);
    }

    /**
     * Get server path
     * @return string
     */
    private static function getServerPath() 
    {
        return self::cleanServerPath(
            self::createAbsolutePath()
        );
    }
}