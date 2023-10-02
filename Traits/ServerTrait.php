<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use ReflectionClass;
use Tamedevelopers\Support\Str;


trait ServerTrait{
    
    /**
     * server base dir
     * @var mixed
     */
    static protected $base_dir;


    /**
     * Define custom Server root path
     * 
     * @param string|null $path
     * 
     * @return string
     */
    static public function setDirectory($path = null)
    {
        // if base path was presented
        if(!empty($path)){
            self::$base_dir = $path;
        } else{
            // auto set the base dir property
            self::$base_dir = self::getDirectory(self::$base_dir);
        }
    }
    
    /**
     * get Directory
     * @param  string base directory path.
     * 
     * @return mixed
     */
    static public function getDirectory()
    {
        if(empty(self::$base_dir)){
            // get default project root path
            self::$base_dir = self::cleanServerPath( 
                self::serverRoot() 
            );
        }else{
            self::$base_dir = self::cleanServerPath(
                self::$base_dir
            );
        }
        
        return self::$base_dir;
    }

    /**
     * Get Server root
     * 
     * @return string
     */
    static private function serverRoot()
    {
        return self::getServers('server');
    }

    /**
     * Format path with Base Directory
     * 
     * @param string|null $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/server_path/path
     * 
     * @return string
     */
    static public function formatWithBaseDirectory($path = null)
    {
        $server = rtrim(
            self::getDirectory(),
            '/'
        );
        return self::pathReplacer(
            "{$server}/{$path}"
        );
    }

    /**
     * Format path with Domain Path
     * 
     * @param string|null $path
     * - [optional] You can pass a path to include with the domain link
     * - Final result: i.e https://domain.com/path
     * 
     * @return string
     */
    static public function formatWithDomainURI($path = null)
    {
        $server = rtrim(
            self::getServers('domain'),
            '/'
        );
        return self::pathReplacer(
            "{$server}/{$path}"
        );
    }

    /**
     * Get the base URL and domain information.
     *
     * @param string|null $mode 
     * - [optional] get direct info of data 
     * - server|domain|protocol
     * 
     * @return mixed
     * - An associative array containing\ server|domain|protocol
     */
    static public function getServers($mode = null)
    {
        // Only create Base path when `CONSTANT` is not defined
        // - The Constant holds the path setup information
        if(!defined('TAME_SERVER_CONNECT')){
            // create server path
            $serverPath = self::cleanServerPath(
                self::createAbsolutePath()
            );

            // Replace Document root inside server path
            $domainPath = self::createAbsoluteDomain($serverPath);

            // Data
            $data = [
                'server'    => $serverPath,
                'domain'    => $domainPath['domain'],
                'protocol'  => $domainPath['protocol'],
            ];

            /*
            |--------------------------------------------------------------------------
            | Storing data into a Global Constant 
            |--------------------------------------------------------------------------
            | We can now use on anywhere on our application 
            | Mostly to get our defined .env root Path
            */
            define('TAME_SERVER_CONNECT', $data);
        } else{
            // Data
            $serverData = TAME_SERVER_CONNECT;
            $data   = [
                'server'    => $serverData['server'],
                'domain'    => $serverData['domain'],
                'protocol'  => $serverData['protocol'],
            ];
        }

        return $data[$mode] ?? $data;
    }

    /**
     * cleanServerPath
     *
     * @param  string|null $path
     * @param  string $replacer
     * 
     * @return string
     */
    static public function cleanServerPath($path = null, $replacer = '/')
    {
        return rtrim(
            self::pathReplacer($path, $replacer), 
            '/'
        ) . '/';
    }

    /**
     * Replace path with given string
     * \ or /
     * 
     * @param string|null  $path
     * @param string  $replacer
     * 
     * @return string
     */
    static public function pathReplacer($path, $replacer = '/')
    {
        return str_replace(
            ['\\', '/'], 
            $replacer, 
            trim((string) $path)
        );
    }

    /**
     * Create Server Absolute Path
     * 
     * @return string
     */
    static private function createAbsolutePath()
    {
        // get direct root path
        $projectRootPath = self::getDirectRootPath();

        // if vendor is not present in the root directory, 
        // - Then we get path using `Vendor Autoload`
        if(!is_dir("{$projectRootPath}vendor")){
            $projectRootPath = self::getVendorRootPath();
        }

        return $projectRootPath;
    }

    /**
     * Create Server Absolute Path
     * @param string|null $serverPath 
     * 
     * @return array
     */
    static private function createAbsoluteDomain($serverPath = null)
    {
        // Determine the protocol (http or https)
        $protocol = Str::lower($_SERVER['HTTPS'] ?? null) !== 'off' 
                    ? 'https://' 
                    : 'http://';

        // The Document root path
        $docRoot = $_SERVER['DOCUMENT_ROOT'];

        // Get the server name (hostname)
        $serverName = $_SERVER['SERVER_NAME'] ?? null;

        // Replace Document root inside server path
        $domainPath = str_replace($docRoot, '', $serverPath);

        // trim(string, '/) - Trim forward slash from left and right
        // we using right trim only
        $domainPath = rtrim((string) $domainPath, '/');

        return [
            'domain'    => "{$protocol}{$serverName}{$domainPath}",
            'protocol'  => $protocol,
        ];
    }

    /**
     * Get Root path with vendor helper
     * 
     * @return string
     */
    static private function getVendorRootPath()
    {
        $reflection = new ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $vendorPath = dirname($reflection->getFileName(), 2);

        return dirname($vendorPath);
    }

    /**
     * Get root path with no helper
     * 
     * @return string
     */
    static private function getDirectRootPath()
    {
        $documentRoot   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $currentScript  = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);

        // setting default path to doc root
        $projectRootPath = $documentRoot;
        if (strpos($currentScript, $documentRoot) === 0) {
            $projectRootPath = substr($currentScript, strlen($documentRoot));
            $projectRootPath = trim($projectRootPath, '/');
            $projectRootPath = substr($projectRootPath, 0, (int) strpos($projectRootPath, '/'));
            $projectRootPath = $documentRoot . '/' . $projectRootPath;
            
            // if not directory then get the directory of the path link
            if (!is_dir($projectRootPath)) {
                $projectRootPath = dirname($projectRootPath);
            }
        }

        return $projectRootPath;
    }

}