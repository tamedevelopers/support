<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Traits\ServerTrait;

class Asset{
    
    use ServerTrait;
    
    /**
     * Create assets Real path url
     * 
     * @param string $asset
     * - asset file e.g (style.css | js/main.js)
     * 
     * @param bool|null $cache
     * 
     * @return string
     */
    static public function asset(?string $asset = null, $cache = null)
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

        // trim
        $asset = trim((string) $asset, '/');

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
     * @return void
     */
    static public function config(?string $base_path = null, ?bool $cache = false) 
    {
        // if not defined
        if(!defined('ASSET_BASE_DIRECTORY')){
            // url helper class
            $urlFromhelper = UrlHelper::url();

            // if base path is set
            if(!empty($base_path)){

                // - Trim forward slash from left and right
                $base_path = trim($base_path, '/');

                // compile
                $urlFromhelper = "{$urlFromhelper}/{$base_path}";
            }

            define('ASSET_BASE_DIRECTORY', [
                'cache'     => $cache,
                'server'    => self::formatWithBaseDirectory($base_path),
                'domain'    => rtrim(
                    self::cleanServerPath($urlFromhelper), 
                    '/'
                ),
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
    static private function getFiletime(?string $file_path = null) 
    {
        return file_exists($file_path) 
                ? "?v=" . filemtime($file_path)
                : false;
    }
    
}