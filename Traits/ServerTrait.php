<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use ReflectionClass;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\UrlHelper;


trait ServerTrait{
    
    /**
     * Base directory of the application (normalized with trailing slash).
     * @var string
     */
    static protected $base_dir = null;

    /**
     * Lightweight in-memory cache for server and domain values.
     * @var array|null
     */
    protected static $servers_cache = null;


    /**
     * Define custom Server root path.
     * 
     * @param string|null $path
     * @return void
     */
    public static function setDirectory($path = null)
    {
        if (!empty($path)) {
            self::$base_dir = self::cleanServerPath($path);
            return;
        }

        if (empty(self::$base_dir)) {
            self::$base_dir = self::cleanServerPath(self::serverRoot());
        }
    }
    
    /**
     * Get normalized base directory with trailing slash.
     * 
     * @return string
     */
    public static function getDirectory()
    {
        if (empty(self::$base_dir)) {
            self::$base_dir = self::cleanServerPath(self::serverRoot());
        } else {
            self::$base_dir = self::cleanServerPath(self::$base_dir);
        }
        
        return self::$base_dir;
    }

    /**
     * Compute server root path
     * 
     * @return string
     */
    private static function serverRoot()
    {
        return self::getServers('server');
    }

    /**
     * Format path with Base Directory.
     * 
     * @param string|null $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/server_path/path
     * 
     * @return string
     */
    public static function formatWithBaseDirectory($path = null)
    {
        $base = rtrim(self::getDirectory(), '/');
        $suffix = ltrim((string) $path, '/');

        return self::pathReplacer($base . ($suffix !== '' ? "/{$suffix}" : ''));
    }

    /**
     * Format path with Domain URI.
     * 
     * @param string|null $path
     * - [optional] You can pass a path to include with the domain link
     * - Final result: i.e https://domain.com/path
     * 
     * @return string
     */
    public static function formatWithDomainURI($path = null)
    {
        $domain = rtrim((string) self::getServers('domain'), '/');
        $suffix = ltrim((string) $path, '/');

        return self::pathReplacer($domain . ($suffix !== '' ? "/{$suffix}" : ''));
    }

    /**
     * Get the base URL and domain information.
     *
     * @param string|null $mode 
     * - [optional] get direct info of data 
     * - server|domain
     * 
     * @return mixed
     * Returns array ['server' => ..., 'domain' => ...] or a specific key.
     */
    public static function getServers($mode = null)
    {
        // Use cached value when available
        if (self::$servers_cache !== null) {
            $data = self::$servers_cache;
        } else {
            if (!defined('TAME_SERVER_CONNECT')) {
                $serverPath = self::cleanServerPath(self::createAbsolutePath());

                $data = [
                    'server' => $serverPath,
                    'domain' => UrlHelper::url(),
                ];

                // Backward-compat: expose as constant once
                define('TAME_SERVER_CONNECT', $data);
            } else {
                $serverData = TAME_SERVER_CONNECT;
                $data = [
                    'server' => $serverData['server'],
                    'domain' => $serverData['domain'],
                ];
            }

            self::$servers_cache = $data;
        }

        return $mode ? ($data[$mode] ?? null) : $data;
    }

    /**
     * Normalize server paths and ensure trailing slash.
     *
     * @param  string|null $path
     * @param  string $replacer
     * 
     * @return string
     */
    public static function cleanServerPath($path = null, $replacer = '/')
    {
        return rtrim(
            self::pathReplacer($path, $replacer), 
            '/'
        ) . '/';
    }

    /**
     * Replace path separators with given string.
     * 
     * @param string|null  $path
     * @param string  $replacer
     * 
     * @return string
     */
    public static function pathReplacer($path = null, $replacer = '/')
    {
        return str_replace(
            ['\\', '/'], 
            $replacer, 
            Str::trim($path)
        );
    }

    /**
     * Create Server Absolute Path
     * 
     * @return string
     */
    public static function createAbsolutePath()
    {
        // get direct root path
        $projectRootPath = self::getDirectRootPath();

        // if vendor is not present in the root directory, 
        // - Then we get path using `Vendor Autoload`
        if(!is_dir($projectRootPath . '/vendor')){
            $projectRootPath = self::getVendorRootPath();
        }

        return $projectRootPath;
    }

    /**
     * Get Root path using composer vendor helper.
     * Supports web SAPI and CLI (falls back to getcwd()).
     * @return string
     */
    private static function getVendorRootPath()
    {
        $reflection = new ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $vendorPath = dirname($reflection->getFileName(), 2);

        return dirname($vendorPath);
    }

    /**
     * Get root path without helpers (fast path).
     * 
     * @return string
     */
    private static function getDirectRootPath()
    {
        $documentRoot   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $currentScript  = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);

        // CLI/unknown SAPI fallback
        if ($documentRoot === '' || $currentScript === '') {
            $cwd = getcwd() ?: '';
            return $cwd !== '' ? str_replace('\\', '/', $cwd) : '/';
        }

        // Adjust when running under frameworks (e.g., Laravel public/index.php)
        if((new Tame)->isAppFramework()){
            $path = 'public/index.php';

            if(strpos($currentScript, $path) !== false){
                $currentScript = Str::replace($path, '', $currentScript);
            }
        }

        // setting default path to doc root
        $projectRootPath = $documentRoot;

        if (strpos($currentScript, $documentRoot) === 0) {
            $relative = Str::trim(substr($currentScript, strlen($documentRoot)), '/');
            $firstSegment = $relative !== '' ? substr($relative, 0, (int) strpos($relative . '/', '/')) : '';
            $projectRootPath = rtrim($documentRoot . '/' . $firstSegment, '/');

            if (!is_dir($projectRootPath)) {
                $projectRootPath = dirname($projectRootPath);
            }
        }

        return $projectRootPath;
    }

}