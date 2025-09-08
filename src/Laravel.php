<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Tame;
use Illuminate\Support\Facades\Blade;


/**
 * Laravel Wrapper
 */ 
class Laravel{


    /**
     * Register a Blade directive
     *
     * @return void
     */
    public function cssDirective()
    {
        Blade::directive('css', function ($expression) {
            list($path, $class) = array_pad(explode(',', $expression, 2), 2, '');

            $path = str_replace(['"', "'"], '', $path);
            $class = str_replace(['"', "'"], '', $class);

            // fullpath
            $assets = tasset($path, true);

            return "<link rel='stylesheet' type='text/css' href='{$assets}'>";
        });
    }
    
    /**
     * Register a Blade directive
     *
     * @return void
     */
    public function jsDirective()
    {
        Blade::directive('js', function ($expression) {
            list($path, $class) = array_pad(explode(',', $expression, 2), 2, '');

            $path = str_replace(['"', "'"], '', $path);
            $class = str_replace(['"', "'"], '', $class);

            // fullpath
            $assets = tasset($path, true);

            return "<script src='{$assets}'></script>";
        });
    }
    
    /**
     * Register a Blade directive
     *
     * @return mixed
     */
    public function svgDirective()
    {
        Blade::directive('svg', function ($expression) {
            list($path, $class) = array_pad(explode(',', $expression, 2), 2, '');

            $path = str_replace(['"', "'"], '', $path);
            $class = str_replace(['"', "'"], '', $class);
            
            // fullpath
            $fullPath = public_path($path);

            // if file exists
            if(Tame::exists($fullPath)){
                
                $svg = new \DOMDocument();
                $svg->load($fullPath);

                // If a class is provided, add it to the SVG element
                if (!empty($class)) {
                    $svg->documentElement->setAttribute("class", $class);
                }

                $output = $svg->saveXML($svg->documentElement);

                // Return the modified SVG
                return $output;
            };
        });
    }

}