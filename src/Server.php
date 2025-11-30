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
     * In-memory loaded config files (filename => array)
     *
     * @var array
     */
    private static array $loadedConfigs = [];

    /**
     * Runtime overrides (dot.notation.key => mixed)
     *
     * @var array
     */
    private static array $overrides = [];
    
    /**
     * Get the value of a configuration option.
     * 
     * * Usage:
     *  - Server::config('app.name'); // get
     *  - Server::config('app', ['name' => 'MyApp']); // returns merged or default if not found
     *  - Server::config(['app.name' => 'MyApp', 'session.driver' => 'database']); // set overrides
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
                    elseif ($tame->isSymfony()) {
                        self::requireFrameWorkBootstrap("{$basePath}/config/bootstrap.php");
                        self::requireFrameWorkBootstrap("{$basePath}/src/Kernel.php");
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore and fall back to normal file-based config loading
        }

        // If $key is an array => setter mode (Laravel-style)
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                // normalize and set using dot notation
                self::arrayDotSet($k, $v, $base_folder);
            }
            return true;
        }

        // If exact override exists, return it immediately
        if (isset(self::$overrides[$key])) {
            return self::$overrides[$key];
        }

        // split key into file and nested parts
        $parts = explode('.', $key);
        $file  = $parts[0] ?? '';
        unset($parts[0]);

        // load file into memory if not loaded
        if (!isset(self::$loadedConfigs[$file])) {
            $filePath = self::formatWithBaseDirectory("{$base_folder}/{$file}.php");

            if (File::exists($filePath)) {
                // require the php config file which returns an array
                $loaded = require $filePath;

                // ensure we have an array
                self::$loadedConfigs[$file] = is_array($loaded) ? $loaded : [];
            } else {
                self::$loadedConfigs[$file] = [];
            }
        }

        $config = self::$loadedConfigs[$file];

        // if no nested parts, return entire file (or merged default)
        if (empty($parts)) {
            // if default is array and config is array, merge and return
            if (is_array($config) && is_array($default) && !empty($default)) {
                return array_merge($config, $default);
            }

            return $config ?: ($default ?? null);
        }

        // reconstruct full dotted nested key string for override checks
        $nestedKey = $file . '.' . implode('.', $parts);

        // if override exists for nested key, return it
        if (isset(self::$overrides[$nestedKey])) {
            return self::$overrides[$nestedKey];
        }

        // traverse nested parts
        $cursor = $config;
        foreach ($parts as $part) {
            if (is_array($cursor) && array_key_exists($part, $cursor)) {
                $cursor = $cursor[$part];
            } else {
                // not found => return default
                return $default;
            }
        }

        // if cursor is array and default is array => merge (behaviour you had previously)
        if (is_array($cursor) && is_array($default) && !empty($default)) {
            return array_merge($cursor, $default);
        }

        return $cursor;
    }

    /**
     * Set a nested dot-notated key into the overrides and into loadedConfigs (merge with existing)
     *
     * @param string $key dot notation e.g. 'session.driver'
     * @param mixed $value
     * @param string $base_folder base config folder (keeps parity with loader)
     * @return void
     */
    protected static function arrayDotSet(string $key, $value, string $base_folder = 'config'): void
    {
        // set override store (full key)
        self::$overrides[$key] = $value;

        // also merge into loadedConfigs so subsequent calls to config('file') reflect change
        $parts = explode('.', $key);
        $file  = array_shift($parts);

        // ensure file loaded
        if (!isset(self::$loadedConfigs[$file])) {
            $filePath = self::formatWithBaseDirectory("{$base_folder}/{$file}.php");
            if (File::exists($filePath)) {
                $loaded = require $filePath;
                self::$loadedConfigs[$file] = is_array($loaded) ? $loaded : [];
            } else {
                self::$loadedConfigs[$file] = [];
            }
        }

        // merge the nested value into loadedConfigs[$file]
        $cursor =& self::$loadedConfigs[$file];

        while (count($parts) > 0) {
            $segment = array_shift($parts);
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                // if next level either not exist or not array, replace with array to allow merging
                $cursor[$segment] = [];
            }
            $cursor =& $cursor[$segment];
        }

        // if both cursor and value are arrays, merge, else set
        if (is_array($cursor) && is_array($value)) {
            $cursor = array_replace_recursive($cursor, $value);
        } else {
            $cursor = $value;
        }
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