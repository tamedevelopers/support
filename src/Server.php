<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;


use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Traits\ServerTrait;
use Tamedevelopers\Support\Process\HttpRequest;
use Tamedevelopers\Support\Traits\ReusableTrait;
use Tamedevelopers\Support\Tame;

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
    public static function config($key, $default = null, string $base_folder = 'config')
    {
        // When running our custom CLI inside a framework (e.g., Laravel), ensure
        // the framework Application is registered to satisfy helpers like database_path().
        try {
            if (HttpRequest::runningInConsole()) {
                $tame = new Tame();

                if ($tame->isAppFramework()) {
                    $basePath = self::pathReplacer(self::formatWithBaseDirectory(), '\\');

                    // Laravel: register Application if not already set on the container
                    if($tame->isLaravel()){
                        self::requireFrameWorkBootstrap("{$basePath}/bootstrap/app.php");
                    } 
                    // CodeIgniter (assuming CI 3/4)
                    else if ($tame->isCodeIgniter()) {
                        self::requireFrameWorkBootstrap("{$basePath}/app/Config/Paths.php");
                    }
                    // CakePHP
                    elseif ($tame->isCakePhp()) {
                        self::requireFrameWorkBootstrap("{$basePath}/config/bootstrap.php");
                    }
                    // Symfony
                    elseif ($tame->isCakePhp()) {
                        self::requireFrameWorkBootstrap("{$basePath}/config/bootstrap.php");
                        self::requireFrameWorkBootstrap("{$basePath}/src/Kernel.php");
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore and fall back to normal file-based config loading
        }

        // Convert the key to an array
        $parts  = explode('.', $key);
        $config = [];

        // Get the file name
        $filePath = self::formatWithBaseDirectory("{$base_folder}/{$parts[0]}.php");

        // Check if the configuration file exists
        if (File::exists($filePath)) {
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
        if(!empty($config) && is_array($config) && is_array($default)){
            return array_merge($config, $default);
        }

        return $config ?? $default;
    }

    /**
     * Create Template File
     *
     * @param  array $array
     * @param  string|null $filename
     * - [base path will be automatically added]
     * 
     * @return void
     */
    public static function createTemplateFile(?array $array = [], ?string $filename = null)
    {
        // removing default base directory path if added by default
        $filename = Str::replace(self::formatWithBaseDirectory(), '', $filename);
        $filePath = Server::formatWithBaseDirectory($filename);

        // Generate PHP code
        $exported   = var_export($array, true);
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

        // Make directory
        $dirPath = dirname($filePath);
        File::makeDirectory($dirPath);

        // to avoid warning error
        // we check if path is a directory first before executing the code
        if(File::isDirectory($dirPath)){
            File::put($filePath, $phpCode);
        }
    }
    
    /**
     * Convert Value to an Array
     *
     * @param  mixed $value
     * @return array
     */
    public static function toArray($value)
    {
        // check value is a valid json data
        if (is_string($value)) {
            if(self::isValidJson($value)){
                return json_decode($value, true);
            }
        }

        // if not valid array, check if array is equal to one element
        if(!self::isNotValidArray($value) && count($value) === 1){
            if(!self::isNotValidArray($value[0] ?? $value)){
                return $value;
            }
        }

        return json_decode(
            json_encode($value), 
            true
        );
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
        if (self::isValidJson($value)) {
            return $value;
        }
    
        return json_encode($value);
    }

    /**
     * Check if data is not a valid array
     *
     * @param mixed $array
     * @return bool
     */
    private static function isNotValidArray(mixed $array = null)
    {
        // Return true if $array is not an array
        if (!is_array($array)) {
            return true;
        }

        // Check if $array contains any non-array values
        foreach ($array as $value) {
            if (!is_array($value)) {
                return true; // Return true if a non-array value is found
            }
        }

        // Return false if $array is a valid array
        return false;
    }


    /**
     * Check if data is valid JSON.
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

    /**
     * Require framework bootstrap file
     *
     * @param string $bootstrap
     * @return void
     */
    private static function requireFrameWorkBootstrap($bootstrap)
    {
        try {
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
            }
        } catch (\Throwable $th) {
            // Ignore continuous error
        }
    }
    
}