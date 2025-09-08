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
     * - asset file e.g (style.css | js/main.js)
     * 
     * @param bool|null $cache
     * @param bool|null $path_type
     * 
     * @return string
     */
    public static function asset(?string $asset = null, $cache = null, $path_type = null)
    {
        // if coniguration has not been used in the global space
        // then we call to define paths for us
        if(!defined('ASSET_BASE_DIRECTORY')){
            self::config();
        }

        // asset path
        $assetPath = ASSET_BASE_DIRECTORY;

        // if asset method cache is not null
        // then we override the global configuration
        if(!is_bool($cache)){
            $cache = $assetPath['cache'];
        }

        // if asset method path_type is not null
        // then we override the global configuration
        if(!is_bool($path_type)){
            $path_type = $assetPath['path_type'];
        }

        // trim
        $asset = Str::trim($asset, '/');

        $file_domain = "{$assetPath['domain']}/{$asset}";

        // file server path
        $file_server = "{$assetPath['server']}/{$asset}";

        // append file update time
        $cacheTimeAppend = null;

        // cache allow from self method
        if($cache){
            if(!empty($asset)){
                $cacheTimeAppend = self::getFiletime($file_server) ?? null;
            }
        }

        // if `$path_type` is true, then we'll use relative path
        if($path_type){

            // replace domain path
            $domain = Str::replace($assetPath['removeDomain'], '', $file_domain);
            $domain = ltrim($domain, '/');

            return "/{$domain}{$cacheTimeAppend}";
        }

        // Using absolute path
        return "{$file_domain}{$cacheTimeAppend}";
    }
    
    /**
     * Configure Assets Default Directory
     * 
     * @param string $base_path
     * - [optional] Default is `base_directory/assets`
     * - If set and directory is not found, then we revert back to the default
     * 
     * @param bool $cache
     * - [optional] Default is true
     * - End point of link `?v=xxxxxxxx` is with cache of file time change
     * - This will automatically tells the broswer to fetch new file if the time change
     * - Time will only change if you make changes or modify the request file
     * 
     * @param string $path_type
     * -[optional] Default is false[Absolute Path] | true[Relative path]
     * 
     * @return void
     */
    public static function config(?string $base_path = null, ?bool $cache = false, $path_type = false) 
    {
        // if not defined
        if(!defined('ASSET_BASE_DIRECTORY')){
            // url helper class
            $urlFromhelper = HttpRequest::url();

            // if base path is set
            if(!empty($base_path)){

                // - Trim forward slash from left and right
                $base_path = Str::trim($base_path, '/');

                // base for url path
                $baseForUrlPath = $base_path;

                // check if accessed from default ip:address
                if(HttpRequest::isIpAccessedVia127Port()){
                    $baseForUrlPath = '';
                }

                // compile
                $urlFromhelper = "{$urlFromhelper}/{$baseForUrlPath}";
            }

            define('ASSET_BASE_DIRECTORY', [
                'cache'     => $cache,
                'path_type' => $path_type,
                'server'    => self::formatWithBaseDirectory($base_path),
                'domain'    => rtrim(
                    self::cleanServerPath($urlFromhelper), 
                    '/'
                ),
                'removeDomain' => HttpRequest::http() . HttpRequest::host()
            ]);
        }
    }
    
    /**
     * Get Last Modification of File
     * 
     * @param string $file_path
     * 
     * @return int|false
     */
    private static function getFiletime(?string $file_path = null) 
    {
        return file_exists($file_path) 
                ? "?v=" . filemtime($file_path)
                : false;
    }
    
}