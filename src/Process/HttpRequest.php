<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Traits\ServerTrait;
use Tamedevelopers\Support\Process\Concerns\RequestInterface;

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
            'HTTP_CLIENT_IP',              // ← Spoofable, and rarely set by real proxies
            'HTTP_X_FORWARDED_FOR',        // ← Spoofable
            'HTTP_X_FORWARDED',            // ← Spoofable
            'HTTP_X_CLUSTER_CLIENT_IP',    // ← Spoofable
            'HTTP_FORWARDED_FOR',          // ← Spoofable
            'HTTP_FORWARDED',              // ← Spoofable
            'REMOTE_ADDR',                 // ← Only trustworthy one
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
     * 
     * @param string|null $url
     * @return string
     */
    public static function getHost($url = null)
    {
        return Tame::getHostFromUrl($url ?: self::full());
    }

    /**
     * Get Domain URL URI.
     * 
     * @param bool $withProtocol
     * @return string
     */
    public static function getDomain($withProtocol = true): string
    {
        if($withProtocol){
            return self::full();
        }

        return Str::replace(self::http(), '', self::getDomain());
    }

    /**
     * Get Cookie URL URI (without protocol).
     */
    public static function getCookieDomain(): string|null
    {
        $host = self::host();

        // Localhost and IPs must not set a Domain attribute.
        if (self::isIpAccessedViaLocalHost() || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        // Real domain: leading dot allows subdomains.
        return '.' . ltrim($host, '.');
    }

    /**
     * Get Base Path
     * 
     * @param string|null $path
     * @return string
     */
    public static function getBasePath($path = null): string
    {
        return self::path($path);
    }

    /**
     * Build the session config array for the current request.
     *
     * All customisation goes through the `$overrides` array. Recognised keys:
     *
     *   - 'secure'          bool         Force the `secure` flag.
     *                                    Default: auto-detect (HTTPS → true, HTTP → false).
     *   - 'allow_subdomain' bool         If true, cookie domain keeps its leading dot
     *                                    so subdomains inherit the cookie.
     *                                    If false, the dot is stripped (exact-host only).
     *                                    Default: true.
     *   - 'domain'          string|null  Override the resolved cookie domain.
     *   - 'path'            string       Override the resolved base path.
     *   - 'same_site'       string       'lax' | 'strict' | 'none'.
     *                                    Default: 'none' on HTTPS, 'lax' on HTTP.
     *
     * Any other key you pass is merged into the returned array untouched — useful
     * for 'lifetime', 'http_only', etc.
     *
     * @param  array  $overrides
     * @return array{
     *     domain:    string|null,
     *     path:      string,
     *     secure:    bool,
     *     same_site: string
     * }
     */
    public static function getSessionConfig($overrides = [])
    {
        // Secure flag
        $secure = array_key_exists('secure', $overrides)
            ? (bool) $overrides['secure']
            : self::isSecure();

        // Domain
        $allowSubdomain = array_key_exists('allow_subdomain', $overrides)
            ? (bool) $overrides['allow_subdomain']
            : true;

        $domain = array_key_exists('domain', $overrides)
            ? $overrides['domain']
            : self::getCookieDomain();

        if ($domain !== null && ! $allowSubdomain) {
            $domain = ltrim($domain, '.');
        }

        // SameSite
        $sameSite = array_key_exists('same_site', $overrides)
            ? $overrides['same_site']
            : ($secure ? 'none' : 'lax');

        // Auto-fix: SameSite=None requires Secure=true
        if ($sameSite === 'none' && ! $secure) {
            $sameSite = 'lax';
        }

        // Path
        $path = array_key_exists('path', $overrides)
            ? $overrides['path']
            : self::getBasePath();

        // Assemble, then merge remaining overrides
        return array_merge([
            'domain'    => $domain,
            'path'      => $path,
            'secure'    => $secure,
            'same_site' => $sameSite,
        ], $overrides);
    }

    /**
     * Apply the session config directly to Laravel's config repository.
     *
     * Example:
     *   HttpRequest::applySessionConfig();                       // defaults
     *   HttpRequest::applySessionConfig(['lifetime' => 120]);    // with override
     *
     * @param  array  $overrides
     * @return array  The config array that was applied
     */
    public static function applySessionConfig(array $overrides = [])
    {
        $session = self::getSessionConfig($overrides);

        config([
            'session.domain'    => $session['domain'],
            'session.path'      => $session['path'],
            'session.secure'    => $session['secure'],
            'session.same_site' => $session['same_site'],
        ]);

        return $session;
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
     * Check if the request use secure connection
     */
    public static function isSecure(): bool
    {
        return self::http() === 'https://';
    }

    /**
     * Check if the internet connection is available
     */
    public static function isInternet(): bool
    {
        return Tame::isInternetAvailable(null, 53, 2);
    }

    /**
     * Alias for `runningInConsole()` method
     */
    public static function isConsole(): bool
    {
        return self::runningInConsole();
    }

    /**
     * Check if the server is using a local/private IP.
     */
    public static function isLocalIp(): bool
    {
        // Strict mode: only verify if machine is local/private
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());

        // If we couldn't determine the IP, assume not local (safe default)
        if (empty($serverAddr) || $serverAddr === gethostname()) {
            return false;
        }

        // IPv6 loopback and link-local / unique-local ranges
        if (filter_var($serverAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip = strtolower($serverAddr);

            // ::1 loopback
            if ($ip === '::1') {
                return true;
            }

            // fc00::/7 (fc00–fdff) Unique Local Address
            if (preg_match('/^f[cd][0-9a-f]{2}:/', $ip)) {
                return true;
            }

            // fe80::/10 (fe80–febf) Link-Local
            if (preg_match('/^fe[89ab][0-9a-f]:/', $ip)) {
                return true;
            }

            return false;
        }

        // IPv4: 127.0.0.0/8, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
        if (filter_var($serverAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (str_starts_with($serverAddr, '127.') ||
                str_starts_with($serverAddr, '10.') ||
                str_starts_with($serverAddr, '192.168.')) {
                return true;
            }

            // 172.16.0.0 – 172.31.255.255
            if (preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $serverAddr)) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Is IP accessed via private LAN port in browser
     */
    public static function isIpAccessedViaPrivateLanPort(): bool
    {
        return self::isIpAccessedVia127Port();
    }

    /**
     * Is IP accessed via 127.0.0.1 port in browser
     */
    public static function isIpAccessedVia127Port(): bool
    {
        return Str::contains(self::host(), self::getRemoteAddr());
    }

    /**
     * Is IP accessed via localhost port in browser
     */ 
    public static function isIpAccessedViaLocalHost(): bool
    {
        return Str::contains(self::host(), ['localhost', '127.0.0.1']) ;
    }

    /**
     * Determine if the script is running in CLI mode.
     */
    public static function runningInConsole(): bool
    {
        return (php_sapi_name() === 'cli' || PHP_SAPI === 'cli');
    }

    /**
     * Get the remote address of the client making the request.
     */
    private static function getRemoteAddr(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Local Domain Path
     */
    private static function localDomainPath(): string
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
     */
    private static function getServerPath(): string
    {
        return self::cleanServerPath(
            self::createAbsolutePath()
        );
    }
}