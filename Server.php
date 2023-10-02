<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;


use Tamedevelopers\Support\Traits\ServerTrait;
use Tamedevelopers\Support\Traits\ReusableTrait;

class Server{
    
    use ServerTrait, ReusableTrait;
    
    /**
     * Get the value of a configuration option.
     *
     * @param mixed $key 
     * The configuration key in dot notation (e.g., 'database.connections.mysql')
     * 
     * @param mixed $default 
     * [optional] The default value to return if the configuration option is not found
     * 
     * @param string $base_folder 
     * [optional] Custom base folder after the base_path()
     * - Default base for config() is 'config' folder.
     * 
     * @return mixed
     * The value of the configuration option, or null if it doesn't exist
     */
    static public function config($key, $default = null, string $base_folder = null)
    {
        // Convert the key to an array
        $parts = explode('.', $key);

        // Get the file name
        $filePath = base_path("{$base_folder}/{$parts[0]}.php");

        // Check if the configuration file exists
        if (file_exists($filePath)) {
            // Load the configuration array from the file
            $config = require($filePath);
        }

        // Remove the file name from the parts array
        unset($parts[0]);

        // Compile the configuration value
        foreach ($parts as $part) {
            if (isset($config[$part])) {
                $config = $config[$part];
            } else {
                $config = null;
            }
        }

        // if config not set
        if(!isset($config)){
            $config = null;
        }

        // try merging data if an array
        if(is_array($config) && is_array($default)){
            return array_merge($config, $default);
        }

        return $config ?? $default;
    }

    /**
     * Create Template File
     *
     * @param  array $data
     * @param  string|null $filename
     * @return void
     */
    static public function createTemplateFile(?array $data = [], ?string $filename = null)
    {
        // Get the file name
        $filePath = base_path($filename);

        // Generate PHP code
        $exported   = var_export($data, true);
        $string     = explode("\n", $exported);
        $string     = array_map('trim', $string);
        $string     = implode("\n    ", $string);
        $string     = ltrim($string, 'array (');
        $string     = rtrim($string, ')');
        $string     = trim($string);

        // Generate PHP code with specific formatting
        $phpCode = <<<PHP
        <?php

        return [

            /*
            |--------------------------------------------------------------------------
            | Template File Lines
            |--------------------------------------------------------------------------
            |
            | The following template lines are used during text formatting for various
            | messages that we need to display to the user. You are free to modify
            | these template lines according to your application's requirements.
            |
            */

            $string
        ];
        PHP;

        // to avoid warning error
        // we check if path is a directory first before executing the code
        if(is_dir(dirname($filePath))){
            file_put_contents($filePath, $phpCode);
        }
    }
    
    /**
     * Convert Value to an Array
     *
     * @param  mixed $value
     * @return array
     */
    static public function toArray($value)
    {
        if(self::isNotValidArray($value)){

            // check value is a valid json data
            if(self::isValidJson($value)){
                return json_decode($value, true);
            }

            return json_decode(json_encode($value), true);
        }

        return $value;
    }

    /**
     * Convert Value to an Object
     *
     * @param  mixed $value
     * @return object
     */
    static public function toObject($value)
    {
        return json_decode(
            json_encode( self::toArray($value) ), 
            false
        );
    }
    
    /**
     * Convert Value to Json Data
     *
     * @param  mixed $value
     * @return string
     */
    static public function toJson($value)
    {
        if(self::isValidJson($value)){
            return $value;
        }

        return json_encode($value);
    }

    /**
     * Check if data is not a valid array
     *
     * @param mixed $data
     * @return bool
     */
    static private function isNotValidArray(mixed $data = null)
    {
        if (!is_array($data)) {
            return true;
        }

        // array filter
        $filteredArray = array_filter($data, 'is_array');
    
        return count($filteredArray) === count($data);
    }

    /**
     * Check if data is valid JSON.
     *
     * @param mixed $data
     * @return bool
     */
    static private function isValidJson(mixed $data = null)
    {
        if(is_string($data)){
            json_decode($data);
            return json_last_error() === JSON_ERROR_NONE;
        }

        return false;
    }
    
}