<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Server;

class UrlHelper {

    /**
     * Get the URL
     * 
     * @return string
     */
    static public function url() 
    {
        // create from .env APP_URL or Default path
        $url = env('APP_URL') ?? self::full();

        return trim($url, '\/');
    }

    /**
     * Get Server Path
     *
     * @return string|null
     */
    static public function server()
    {
        return self::getServerPath();
    }

    /**
     * Get Request Url
     *
     * @return string|null
     */
    static public function request()
    {
        $request = $_SERVER['REQUEST_URI'] ?? null;

        return str_replace(self::path(), '', $request);
    }

    /**
     * Get Referral Url
     *
     * @return string|null
     */
    static public function referral()
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Get URL HTTP
     *
     * @return string|null
     */
    static public function http()
    {
        return isset($_SERVER['HTTPS']) && Str::lower($_SERVER['HTTPS']) !== 'off' ? 'https://' : 'http://';
    }

    /**
     * Get URL Host
     *
     * @return string|null
     */
    static public function host()
    {
        return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    }

    /**
     * Get URL Fullpath
     *
     * @return string|null
     */
    static public function full()
    {
        return self::http() . self::host() . self::path();
    }

    /**
     * Get URL Root Path
     *
     * @param string|null $path
     * @return string|null
     */
    static public function path($path = null)
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
    static public function isIpAccessedVia127Port()
    {
        return Str::contains($_SERVER['REMOTE_ADDR'], self::host());
    }

    /**
     * Local Domain Path
     * 
     * @return array
     */
    static private function localDomainPath()
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
    static private function replace($path = null) 
    {
        return Server::pathReplacer($path);
    }

    /**
     * Get server path
     * @return string
     */
    static private function getServerPath() 
    {
        return Server::cleanServerPath(
            Server::createAbsolutePath()
        );
    }

}
