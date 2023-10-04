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
     * @param string $assetDir
     * - Default is `assets` folder
     * 
     * @return string
     */
    static public function asset(?string $asset = null)
    {
        // if coniguration has not been used in the global space
        // then we call to define paths for us
        if(!defined('ASSET_BASE_DIRECTORY')){
            self::config();
        }

        // asset path
        $assetPath = ASSET_BASE_DIRECTORY;

        // trim
        $asset = trim((string) $asset, '/');

        $file_domain = "{$assetPath['domain']}/{$asset}";

        // file server path
        $file_server = "{$assetPath['server']}/{$asset}";

        // cache
        $cache = $assetPath['cache'] ? self::getFiletime($file_server) : null;

        return "{$file_domain}{$cache}";
    }
    
    /**
     * Configure Assets Default Directory
     * 
     * @param string $base_path
     * - [optional] Default is `base_directory/assets`
     * - If set and directory is not found, then we revert back to the default
     * 
     * @param string $cache
     * - [optional] Default is true
     * - End point of link `?v=xxxxxxxx` is with cache of file time change
     * - This will automatically tells the broswer to fetch new file if the time change
     * - Time will only change if you make changes or modify the request file
     * 
     * @return void
     */
    static public function config(?string $base_path = null, ?bool $cache = true) 
    {
        // severs
        $server = self::getServers();

        // set default
        if(empty($base_path)){
            $base_path = "assets";
        }

        // if not defined
        if(!defined('ASSET_BASE_DIRECTORY')){
            // - Trim forward slash from left and right
            $base_path = trim($base_path, '/');

            define('ASSET_BASE_DIRECTORY', [
                'cache'     => $cache,
                'server'    => self::formatWithBaseDirectory($base_path),
                'domain'    => rtrim(
                    self::cleanServerPath("{$server['domain']}{$base_path}"), 
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