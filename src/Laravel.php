<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Tame;
use Illuminate\Support\Facades\Blade;


/**
 * Laravel Blade Directives Wrapper
 * Provides custom reusable Blade directives
 */
class Laravel{
    
    /**
     * Initialize blade facades
     */
    private static function initBladeFacades(): string
    {
        return "\Illuminate\Support\Facades\Blade";
    }

    /**
     * Register all directives at once
     * Useful for quick bootstrapping inside a ServiceProvider
     *
     * @return void
     */
    public static function registerDirectives()
    {
        self::cssDirective();
        self::jsDirective();
        self::svgDirective();
        self::assetDirective();
    }

    /**
     * Register the @css directive
     * Usage: @css('css/app.css')
     *
     * @return void
     */
    public static function cssDirective()
    {
        self::initBladeFacades()::directive('css', function ($expression) {
            return "<?php
                list(\$path, \$class) = array_pad(explode(',', {$expression}, 2), 2, '');
                \$path = str_replace(['\"', \"'\"], '', \$path);
                \$class = str_replace(['\"', \"'\"], '', \$class);
                \$assets = tasset(\$path, true, true);
                echo \"<link rel='stylesheet' type='text/css' href='\$assets'>\";
            ?>";
        });
    }
    
    /**
     * Register the @js directive
     * Usage: @js('js/app.js')
     *
     * @return void
     */
    public static function jsDirective()
    {
        self::initBladeFacades()::directive('js', function ($expression) {
            return "<?php
                list(\$path, \$class) = array_pad(explode(',', {$expression}, 2), 2, '');
                \$path = str_replace(['\"', \"'\"], '', \$path);
                \$class = str_replace(['\"', \"'\"], '', \$class);
                \$assets = tasset(\$path, true, true);
                echo \"<script src='\$assets'></script>\";
            ?>";
        });
    }
    
    /**
     * Register the @svg directive
     * Usage: @svg('images/icon.svg', 'w-6 h-6 text-gray-500')
     *
     * @return void
     */
    public static function svgDirective()
    {
        self::initBladeFacades()::directive('svg', function ($expression) {
            return "<?php
                list(\$path, \$class) = array_pad(explode(',', {$expression}, 2), 2, '');
                \$path = str_replace(['\"', \"'\"], '', \$path);
                \$class = str_replace(['\"', \"'\"], '', \$class);
                
                \$fullPath = \\Tamedevelopers\\Support\\Tame::stringReplacer(
                    str_replace(rtrim(domain('')), '', tasset(\$path, false, false))
                );

                libxml_use_internal_errors(true);
                if (\\Tamedevelopers\\Support\\Tame::exists(\$fullPath)) {
                    \$svg = new \\DOMDocument();
                    \$svg->load(\$fullPath);

                    if (!empty(\$class)) {
                        \$svg->documentElement->setAttribute('class', \$class);
                    }

                    echo \$svg->saveXML(\$svg->documentElement);
                }
                libxml_clear_errors();
            ?>";
        });
    }

    /**
     * Register the @asset directive
     * Allows passing extra params for cache-busting control
     * Usage: @asset('images/logo.png', true, true)
     *
     * @return void
     */
    public static function assetDirective(): void
    {
        self::initBladeFacades()::directive('asset', function ($expression) {
            return "<?php
                \$params = explode(',', {$expression});
                \$params = array_map(fn(\$p) => trim(\$p, '\"\\' '), \$params);

                // Extract parameters with defaults
                \$asset     = \$params[0] ?? '';
                \$cache     = isset(\$params[1]) ? filter_var(\$params[1], FILTER_VALIDATE_BOOLEAN) : true;
                \$path_type = isset(\$params[2]) ? filter_var(\$params[2], FILTER_VALIDATE_BOOLEAN) : true;

                echo tasset(\$asset, \$cache, \$path_type);
            ?>";
        });
    }

}