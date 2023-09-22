<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;


use Tamedevelopers\Support\Traits\ServerTrait;
use Tamedevelopers\Support\Traits\ReusableTrait;

final class Server{
    
    use ServerTrait, ReusableTrait;

    
    /**
     * Get the value of a configuration option.
     *
     * @param string $key 
     * The configuration key in dot notation (e.g., 'database.connections.mysql')
     * 
     * @param mixed $default 
     * [optional] The default value to return if the configuration option is not found
     * 
     * @return mixed
     * The value of the configuration option, or null if it doesn't exist
     */
    public static function config(string $key, $default = null)
    {
        // Convert the key to an array
        $parts = explode('.', $key);

        // Get the file name
        $filePath = base_path("config/{$parts[0]}.php");

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

        // try merging data if an array
        if(is_array($config) && is_array($default)){
            return array_merge($config, $default);
        }

        return $config ?? $default;
    }
    
    /**
     * Convert Value to an Array
     *
     * @param  mixed $value
     * @return array
     */
    public static function toArray($value)
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
    public static function toObject($value)
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
    public static function toJson($value)
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
    private static function isNotValidArray(mixed $data = null)
    {
        if (!is_array($data)) {
            return true;
        }

        // array filter
        $filteredArray = array_filter($data, 'is_array');
    
        return count($filteredArray) === count($data);
    }

    /**
     * Check if a string is valid JSON.
     *
     * @param mixed $data
     * @return bool
     */
    private static function isValidJson(mixed $data = null)
    {
        if(is_string($data)){
            json_decode($data);
            return json_last_error() === JSON_ERROR_NONE;
        }

        return false;
    }
    
}