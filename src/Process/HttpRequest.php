<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Process\Concerns\RequestInterface;

/**
 * Native PHP request implementation for RequestInterface.
 */
class HttpRequest implements RequestInterface
{
    /** @inheritDoc */
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** @inheritDoc */
    public static function url() : string
    {
        // Prefer APP_URL from environment without instantiating Env to avoid recursion
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
        if (!empty($path)) {
            $path = ltrim($path, '/');
            $path = self::replace($path);
        }
        
        return self::localDomainPath() . "{$path}";
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

    /** @inheritDoc */
    public static function http(): string
    {
        return isset($_SERVER['HTTPS']) && Str::lower($_SERVER['HTTPS']) !== 'off' 
            ? 'https://' : 'http://';
    }

    /** @inheritDoc */
    public static function host(): string
    {
        return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    }

    /** @inheritDoc */
    public static function full(): string
    {
        return self::http() . self::host() . self::path();
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
     * Local Domain Path
     * 
     * @return array
     */
    private static function localDomainPath()
    {
        $domainPath = str_replace(
            $_SERVER['DOCUMENT_ROOT'], 
            '', 
            self::getServerPath()
        );

        return self::isIpAccessedVia127Port() ? '/' : $domainPath;
    }

    /**
     * Get server path
     * 
     * @param string|null $path
     * @return string
     */
    private static function replace($path = null) 
    {
        return Server::pathReplacer($path);
    }

    /**
     * Get server path
     * @return string
     */
    private static function getServerPath() 
    {
        return Server::cleanServerPath(
            Server::createAbsolutePath()
        );
    }
}