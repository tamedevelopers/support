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
        // if App is Core PHP
        if(!AppIsNotCorePHP()){
            $url = self::local();
        } else{
            // get path
            $url = env('APP_URL') ?? self::full();

            if(empty($url)){
                $url = str_replace(trim(self::getServerPath(), '/'), '', self::local());
            }
        }

        return trim($url, '\/');
    }

    /**
     * Get Server Name
     *
     * @return string|null
     */
    static public function server()
    {
        return $_SERVER['SERVER_NAME'] ?? null;
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
        return self::http() . self::host();
    }

    /**
     * Get URL Root Path
     *
     * @param string|null $path
     * @return string|null
     */
    static public function path($path = null)
    {
        // Parse the URL to get the path
        $parsedUrl = parse_url(self::full(), PHP_URL_PATH);

        // Check if the "path" key exists in the parsed URL
        $pathPart = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';

        if (!empty($path)) {
            $path = '/' . ltrim($path, '/');
            $path = self::replace($path);
        }
        
        return $pathPart . "{$path}";
    }

    /**
     * Create Local Url path
     * - [path without using framework]
     * 
     * @return array
     */
    static public function local()
    {
        return self::http() . self::server() . self::path();
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
