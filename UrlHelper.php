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
        $baseURL    = self::getBaseURL();
        $scriptName = $_SERVER['SCRIPT_NAME'];

        // generate url
        $url = $baseURL['full_path'] . self::getURLPath($scriptName);

        // if App is Core PHP
        if(!AppIsNotCorePHP()){
            $url = self::localUrl($baseURL);
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
     * Get the URL path
     *
     * @param string $scriptName
     * @return string
     */
    static private function getURLPath($scriptName) 
    {
        $dir = str_replace(basename($scriptName), '', $scriptName);
        return $dir;
    }

    /**
     * Create Server Absolute Path
     * @param array $baseURL
     * 
     * @return array
     */
    static private function localUrl($baseURL)
    {
        // create server path
        $serverPath = Server::cleanServerPath(
            Server::createAbsolutePath()
        );

        // Get the server name (hostname)
        $serverName = $_SERVER['SERVER_NAME'] ?? null;

        // Replace Document root inside server path
        $domainPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $serverPath);

        // trim(string, '/) - Trim forward slash from left and right
        // we using right trim only
        $domainPath = rtrim((string) $domainPath, '\/');

        return "{$baseURL['http']}{$serverName}{$domainPath}";
    }

}
