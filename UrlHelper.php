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
        // get base url data
        $baseURL = self::getBaseURL();

        // if App is Core PHP
        if(!AppIsNotCorePHP()){
            $url = self::localUrl($baseURL);
        } else{
            // get path
            $url = env('APP_URL') ?? $baseURL['full_path'];

            if(empty($url)){
                $url = str_replace(trim(self::getServerPath(), '/'), '', self::localUrl($baseURL));
            }
        }

        return trim($url, '\/') . '/';
    }

    /**
     * Get the base URL
     *
     * @return array
     */
    static private function getBaseURL()
    {
        $http       = isset($_SERVER['HTTPS']) && Str::lower($_SERVER['HTTPS']) !== 'off' ? 'https://' : 'http://';
        $hostname   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        return [
            'http'      => $http,
            'hostname'  => $hostname,
            'full_path' => "{$http}{$hostname}",
        ];
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

    /**
     * Create Server Absolute Path
     * @param array $baseURL
     * 
     * @return array
     */
    static private function localUrl($baseURL)
    {
        // Get the server name (hostname)
        $serverName = $_SERVER['SERVER_NAME'] ?? null;

        // Replace Document root inside server path
        $domainPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', self::getServerPath());

        // trim(string, '/) - Trim forward slash from left and right
        // we using right trim only
        $domainPath = rtrim((string) $domainPath, '\/');

        return "{$baseURL['http']}{$serverName}{$domainPath}";
    }

}
