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
     * Create SVG Directrives
     * 
     * @usage @svg('path_to_svg')
     * 
     * @return mixed
     */
    static public function svgDirective()
    {
        Tame::class_exists('Illuminate\Support\Facades\Blade', function(){

            // Register a Blade directive
            Blade::directive('svg', function ($expression) {
                // Parse the expression to get the path and classes
                list($path, $classes) = explode(',', $expression, 2);

                // path

                // Load the SVG file contents
                $svgContent = file_get_contents(public_path(trim($path, '"')));

                // Add classes to SVG
                $svg = simplexml_load_string($svgContent);
                if (!empty($classes)) {
                    $svg->addAttribute('class', trim($classes, '"'));
                }

                // Return the modified SVG
                return $svg->asXML();
            });
        });
    }

}