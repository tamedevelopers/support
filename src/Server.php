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
     * Base path
     *
     * @var mixed
     */
    private static $basePath = null;
    
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
                    self::$basePath = self::pathReplacer(self::formatWithBaseDirectory(), '\\');

                    // Register Application if not already set on the container
                    if($tame->isLaravel()){
                        self::bootstrapFile('bootstrap/app.php');
                    }  elseif ($tame->isCodeIgniter()) {
                        if (!self::bootstrapFile('app/Config/Paths.php')) {
                            self::bootstrapFile('application/config/config.php'); // CI3 fallback
                        }
                    } elseif ($tame->isCakePhp()) {
                        self::bootstrapFile('config/bootstrap.php');
                    } elseif ($tame->isSymfony()) {
                        self::bootstrapFile('config/bootstrap.php');
                        self::bootstrapFile('src/Kernel.php');
                    } elseif ($tame->isYii()) {
                        if (!self::bootstrapFile('config/web.php') && !self::bootstrapFile('config/console.php')) {
                            self::bootstrapFile('protected/config/main.php'); // Yii 1 fallback
                        }
                    } elseif ($tame->isSlim()) {
                        if (!self::bootstrapFile('config/bootstrap.php') && !self::bootstrapFile('src/app/app.php')) {
                            self::bootstrapFile('src/settings.php'); // Slim 3 skeleton fallback
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore and fall back to normal file-based config loading
        }

        // Setter Mode: Laravel-style config(['key' => 'value'])
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                // normalize and set using dot notation
                self::arrayDotSet($k, $v, $base_folder);
            }
            return true;
        }

        // Resolve File and Nested Path
        $parts = explode('.', $key);
        $file  = array_shift($parts);

        // Lazy Load: Only load the file if we haven't touched it yet
        if (!isset(self::$loadedConfigs[$file])) {
            $filePath = self::formatWithBaseDirectory("{$base_folder}/{$file}.php");

            if (File::exists($filePath)) {
                // require the php config file which returns an array
                $loaded = require $filePath;

                // ensure we have an array
                self::$loadedConfigs[$file] = is_array($loaded) ? $loaded : [];
            } else {
                // If the file doesn't exist, we still initialize it to allow on-the-fly sets
                self::$loadedConfigs[$file] = [];
            }
        }

        // Check for top-level overrides (exact key match)
        if (isset(self::$overrides[$key])) {
            return self::$overrides[$key];
        }

        // Traverse the nested structure
        $cursor = self::$loadedConfigs[$file];

        // if no nested parts, return entire file (or merged default)
        if (empty($parts)) {
            // Merge logic for entire file return
            if (is_array($cursor) && is_array($default) && !empty($default)) {
                return array_merge($cursor, $default);
            }

            return !empty($cursor) ? $cursor : $default;
        }

        foreach ($parts as $part) {
            if (is_array($cursor) && array_key_exists($part, $cursor)) {
                $cursor = $cursor[$part];
            } else {
                return $default;
            }
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
        // Maintain the flat override map for fast "exact-key" lookups
        self::$overrides[$key] = $value;
        
        // also merge into loadedConfigs so subsequent calls to config('file') reflect change
        $parts = explode('.', $key);
        $file  = array_shift($parts);

        // Ensure the base file is initialized in memory
        if (!isset(self::$loadedConfigs[$file])) {
            $filePath = self::formatWithBaseDirectory("{$base_folder}/{$file}.php");
            if (File::exists($filePath)) {
                $loaded = require $filePath;
                self::$loadedConfigs[$file] = is_array($loaded) ? $loaded : [];
            } else {
                self::$loadedConfigs[$file] = [];
            }
        }

        // Navigate to the specific nest level using a pointer
        $cursor =& self::$loadedConfigs[$file];

        foreach ($parts as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor =& $cursor[$segment];
        }

        // Update the value
        // array_replace_recursive ensures that if $value is an array, 
        // it doesn't wipe out existing sibling keys at that level.
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

        // directory path
        $dirPath = dirname($filePath);
        
        if(!File::isDirectory($dirPath)){
            File::makeDirectory($dirPath);
        }
        
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

        return json_decode(json_encode($value), true) ?? [];
    }

    /**
     * Convert Value to an Object
     *
     * @param  mixed $value
     * @return object
     */
    public static function toObject($value)
    {
        $encodedValue = json_encode(self::toArray($value));

        return json_decode($encodedValue, false);
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
    
        return (string) json_encode($value);
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
        if (!is_string($data) || trim($data) === '') {
            return false;
        }

        if (function_exists('json_validate')) {
            return json_validate($data);
        }
        
        json_decode($data);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Bootstrap framework file
     *
     * @param string $bootstrap
     * @return bool
     */
    private static function bootstrapFile($bootstrap)
    {
        try {
            $fullPath = self::$basePath . "/$bootstrap";

            $basePath = rtrim(self::$basePath ?? '', '/\\');
            $fullPath = $basePath . '/' . ltrim($bootstrap, '/\\');

            if (file_exists($fullPath)) {
                require_once $fullPath;
                return true;
            }
        } catch (\Throwable $th) {
            // Ignore continuous error
        }

        return false;
    }
    
}