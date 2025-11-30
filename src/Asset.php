<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\ServerTrait;
use Tamedevelopers\Support\Process\HttpRequest;

class Asset{
    
    use ServerTrait;
    
    /**
     * Create assets Real path url
     * 
     * @param string $asset
     * @param bool|null $cache
     * @param bool|null $type "absolute" | "relative" (default: false → absolute)
     */
    public static function asset($asset = null, $cache = null, $type = null): string
    {
        // if coniguration has not been used in the global space
        // then we call to define paths for us
        if(!defined('ASSET_BASE_DIRECTORY')){
            self::config();
        }

        // asset path
        $assetConfig = ASSET_BASE_DIRECTORY;

        // Only override global config, when <cache> it's not boolean
        if(!is_bool($cache)){
            $cache = $assetConfig['cache'];
        }

        // Only override global config, when <type> it's not boolean
        if(!is_bool($type)){
            $type = $assetConfig['type'];
        }

        $configPath = $assetConfig['path'];
        if(!empty($configPath)){
            $configPath = "/$configPath";
        }

        // trim
        $asset = Str::trim($asset, '/');

        $file_domain = "{$assetConfig['domain']}{$configPath}/{$asset}";

        // file server path
        $file_server = "{$assetConfig['server']}{$configPath}/{$asset}";

        // append file update time
        $cacheTimeAppend = null;

        // cache allow from self method
        if($cache && !empty($asset)){
            $cacheTimeAppend = self::getFiletime($file_server) ?? null;
        }
        
        // Using <relative path> when true
        if($type === true){
            // replace domain path
            $domain = Str::replace($assetConfig['removeDomain'], '', $file_domain);
            $domain = ltrim($domain, '/');

            return "/{$domain}{$cacheTimeAppend}";
        }

        // Using <absolute path>
        return "{$file_domain}{$cacheTimeAppend}";
    }
    
    /**
     * Configure Assets Default Directory
     * 
     * @param string|null $path
     * @param bool $cache       Whether to use cache-busting (default: true)
     * - End point of link `?v=xxxxxxxx` is with cache of file time chang
     * @param bool $type   "absolute" | "relative" (default: false → absolute)
     */
    public static function config($path = null, $cache = false, $type = false): void
    {
        // if not defined
        if(!defined('ASSET_BASE_DIRECTORY')){

            // http
            $http = HttpRequest::http();
            $host = HttpRequest::host();

            // url helper class
            $url = HttpRequest::url();

            // clean http from url
            $urlFromhelper = Str::replace($http, '', $url);

            // if base path is set
            if(!empty($path)){
                // - Trim forward slash from left and right
                $path = Str::trim($path, '/');
                $path = Str::trim($path, DIRECTORY_SEPARATOR);

                // base for url path
                $baseForUrlPath = $path;

                // check if accessed from default ip:address
                if(HttpRequest::isIpAccessedViaPrivateLanPort()){
                    $baseForUrlPath = '';
                }

                // compile
                $urlFromhelper = "{$urlFromhelper}/{$baseForUrlPath}";
            }

            $domain = rtrim(self::cleanServerPath("{$http}{$urlFromhelper}"), '/');
            $domain = rtrim($domain, DIRECTORY_SEPARATOR);

            define('ASSET_BASE_DIRECTORY', [
                'cache'     => $cache,
                'type'      => $type,
                'path'      => $path,
                'server'    => self::formatWithBaseDirectory($path),
                'domain'    => $domain,
                'removeDomain' => "{$http}{$host}"
            ]);
        }
    }
    
    /**
     * Get Last Modification of File
     * 
     * @param string $file_path
     * @return int|false
     */
    private static function getFiletime(?string $file_path = null) 
    {
        return file_exists($file_path) 
                ? "?v=" . filemtime($file_path)
                : false;
    }
    
}