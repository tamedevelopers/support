<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Server;

class UrlHelper {

    /**
     * Get the URL
     * 
     * @return string
     */
    public static function url() 
    {
        // Prefer APP_URL from environment without instantiating Env to avoid recursion
        $url = Env::env('APP_URL') ?? self::full();

        return Str::trim($url, '\/');
    }

    /**
     * Get Server Path
     *
     * @return string|null
     */
    public static function server()
    {
        return self::getServerPath();
    }

    /**
     * Get Request Url
     *
     * @return string|null
     */
    public static function request()
    {
        $request = $_SERVER['REQUEST_URI'] ?? null;

        return Str::replace(self::path(), '', $request);
    }

    /**
     * Get Referral Url
     *
     * @return string|null
     */
    public static function referral()
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Get URL HTTP
     *
     * @return string|null
     */
    public static function http()
    {
        return isset($_SERVER['HTTPS']) && Str::lower($_SERVER['HTTPS']) !== 'off' ? 'https://' : 'http://';
    }

    /**
     * Get URL Host
     *
     * @return string|null
     */
    public static function host()
    {
        return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    }

    /**
     * Get URL Fullpath
     *
     * @return string|null
     */
    public static function full()
    {
        return self::http() . self::host() . self::path();
    }

    /**
     * Get URL Root Path
     *
     * @param string|null $path
     * @return string|null
     */
    public static function path($path = null)
    {
        if (!empty($path)) {
            $path = ltrim($path, '/');
            $path = self::replace($path);
        }
        
        return self::localDomainPath() . "{$path}";
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
