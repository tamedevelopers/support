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
        // if configuration has not been used in the global space
        // then we call to define paths for us
        if(!defined('ASSET_BASE_DIRECTORY')){
            self::config();
        }

        // asset path
        $assetConfig = ASSET_BASE_DIRECTORY;
        $cache = is_bool($cache) ? $cache : $assetConfig['cache'];
        $type  = is_bool($type)  ? $type  : $assetConfig['type'];

        // Build the internal path segment
        // If $path is 'assets' and $asset is 'css/style.css', result is 'assets/css/style.css'
        $subPath = Str::trim($assetConfig['path'], '/');
        $asset   = Str::trim($asset, '/');
        $fullPathSegment = Str::trim("{$subPath}/{$asset}", '/');

        // Final URL and Server Path
        $file_domain = $assetConfig['domain'] . '/' . $fullPathSegment;
        $file_server = $assetConfig['server'] . '/' . $fullPathSegment;

        // Default to Absolute URL
        $finalUrl = $file_domain;
        
        // Handle Relative Path conversion
        if($type === true){
            // Strip the protocol and host (e.g., http://localhost)
            $relative = Str::replace($assetConfig['removeDomain'], '', $file_domain);

            // if the replacement didn't actually happen.
            $relative = '/' . ltrim($relative, '/');

            // Check for "://" to see if the replacement failed (meaning the host is still there)
            if (str_contains($relative, '://')) {
                $finalUrl = $file_domain;
            } else {
                $finalUrl = $relative;
            }
        }

        // Always calculate and append Cache Time
        $cacheTimeAppend = ($cache && !empty($asset)) 
            ? self::getFiletime($file_server) 
            : '';

        // Using <absolute path>
        return $finalUrl . $cacheTimeAppend;
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

            $url    = HttpRequest::url();
            $http   = HttpRequest::http();
            $host   = HttpRequest::host();
            $baseProtocolHost = rtrim("{$http}{$host}", '/');

            // Clean the user-provided asset sub-path (e.g., 'public' or 'assets')
            $path = !empty($path) ? Str::trim($path, '/\\') : '';

            define('ASSET_BASE_DIRECTORY', [
                'cache'     => $cache,
                'type'      => $type,
                'path'      => $path,
                'server'    => self::formatWithBaseDirectory(),
                'domain'    => rtrim($url, '/'),
                'removeDomain' => $baseProtocolHost
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