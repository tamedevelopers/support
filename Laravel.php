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

                list($path, $class) = array_pad(explode(',', $expression, 2), 2, '');

                $path = str_replace(['"', "'"], '', $path);
                $class = str_replace(['"', "'"], '', $class);
                
                // fullpath
                $fullPath = public_path($path);

                // if file exists
                if(Tame::exists($fullPath)){
                    // Load the SVG file contents
                    $svgContent = file_get_contents($fullPath);

                    // Parse the SVG content into a SimpleXMLElement
                    $svg = simplexml_load_string($svgContent);

                    // If a class is provided, add it to the SVG element
                    if (!empty($class)) {
                        if (isset($svg['class'])) {
                            $svg['class'] .= ' ' . $class;
                        } else {
                            $svg->addAttribute('class', $class);
                        }
                    }

                    // Return the modified SVG
                    return $svg->asXML();
                };
            });
        });
    }

}