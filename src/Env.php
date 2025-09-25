<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Dotenv\Dotenv;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Constant;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Traits\ServerTrait;
use Tamedevelopers\Support\Process\HttpRequest;
use Tamedevelopers\Support\Traits\ReusableTrait;
use Tamedevelopers\Support\Collections\Collection;
use Tamedevelopers\Support\Capsule\CustomException;

class Env {
    
    use ServerTrait, ReusableTrait;

    /**
     * Handler to hold Symbolic Path
     * When we ->setDirectory
     * - We copy the extended `$base_dir` untouched to this property 
     * - Since Base Directory value should not be changed
     * - So this is a symbolic link to $base_dir and can be changed at any time
     * 
     * @var mixed
     */
    private static $sym_path;

    /**
     * Instance of self class
     * @var mixed
     */
    private static $class;

    /**
     * When .env file is loaded
     * @var bool
     */
    private static $envFileIsLoaded = false;

    /**
     * Define custom Server root path
     * 
     * @param string|null $path
     * @return void
     */
    public function __construct($path = null) 
    {
        // auto set the base dir property
        self::setDirectory($path);

        // add to global property
        self::$class = $this;

        // create public path
        self::$sym_path = self::$base_dir;
    }

    /**
     * Initialization of self class
     * @return void
     */
    private static function init() 
    {
        self::$class = new self();
    }

    /**
     * Define custom Directory path to .env file
     * By default we use your server root folder
     * 
     * @param string|null $path 
     * - [optional] Path to .env Folder Location
     * - Path to .env Folder\Not needed except called statically
     * 
     * @return array
     */
    public static function load($path = null)
    {
        self::createSymPath($path);

        try{
            // env class not exists
            if(!self::isDotEnvInstanceAvailable()){
                return [
                    'status'    => Constant::STATUS_404,
                    'message'   => sprintf(
                            "<<Error>> Required to use the `Env` class and helper (^5.4.1). \n%s", 
                            "run `composer require vlucas/phpdotenv`"),
                    'path'      => self::$sym_path,
                ];
            }
            
            $dotenv = Dotenv::createImmutable(self::$sym_path);
            $dotenv->load();

            self::$envFileIsLoaded = true;
            return [
                'status'    => Constant::STATUS_200,
                'message'   => ".env File Loaded Successfully",
                'path'      => self::$sym_path,
            ];
        } catch(Exception $e){
            return [
                'status'    => Constant::STATUS_404,
                'message'   => sprintf("<<Error>> Folder Seems not to be readable or not exists. \n%s", $e->getMessage()),
                'path'      => self::$sym_path,
            ];
        }
    }

    /**
     * Inherit the load() method and returns an error message 
     * if any or load environment variables
     * 
     * @param string|null $path Path to .env Folder\Not needed exept called statically
     * @return void
     */
    public static function loadOrFail($path = null)
    {
        $getStatus = self::load($path);
        if($getStatus['status'] !== Constant::STATUS_200){
            try {
                throw new CustomException(
                    "{$getStatus['message']} \n" . 
                    (new Exception)->getTraceAsString()
                );
            } catch (CustomException $e) {
                // Handle the exception silently (turn off error reporting)
                error_reporting(0);

                Manager::setHeaders(404, function() use($e){

                    // create error logger
                    self::bootLogger();

                    // Trigger a custom error
                    trigger_error($e->getMessage(), E_USER_ERROR);
                });
            }
        }
    }

    /**
     * Create .env file or Ignore
     * 
     * @return void
     */
    public static function createOrIgnore()
    {
        // file to .env
        $envPath = self::formatWithBaseDirectory('.env');

        // file env.example
        $envExamplePath = self::formatWithBaseDirectory('.env.example');

        // when system path is empty
        if(empty(self::$sym_path)){
            new self();
        }
        
        // only attempt to create file if direcotry if valid
        if(is_dir(self::$sym_path)){
            // if file doesn't exist and not a directory
            if(!Tame::exists($envPath)){
                
                // Write the contents to the new file
                File::put($envPath, self::envTxt());
            }

            // if file doesn't exist and not a directory
            if(!Tame::exists($envExamplePath)){
                
                // Write the contents to the new file
                File::put($envExamplePath, self::envTxt());
            }
        }
    }

    /**
     * Turn off error reporting and log errors to a file
     * 
     * @param string $logFile The name of the file to log errors to
     * @return void
     */
    public static function bootLogger() 
    {
        // Directory path
        $dir = self::formatWithBaseDirectory('storage/logs/');

        // create custom file name
        $filename = "{$dir}orm.log";

        self::createDir_AndFiles($dir, $filename);

        // Determine the log message format
        $log_format = "[%s] %s in %s on line %d\n";

        $append     = true;
        $max_size   = 1024*1024;

        // Define the error level mapping
        $error_levels = self::error_levels();

        // If APP_DEBUG = false
        // Turn off error reporting for the application
        if(!self::isApplicationOnDebug()){
            // PHP 8+: E_STRICT is removed; suppress deprecations instead
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
            ini_set('display_errors', '0');
        }

        // Define the error handler function
        $error_handler = function($errno, $errstr, $errfile, $errline) use ($filename, $append, $max_size, $log_format, $error_levels) {
            // Construct the log message
            $error_level = isset($error_levels[$errno]) ? $error_levels[$errno] : 'Unknown Error';
            $log_message = sprintf($log_format, date('Y-m-d H:i:s'), $error_level . ': ' . $errstr, $errfile, $errline);

            // Write the log message to the file
            if ($append && file_exists($filename)) {
                $current_size = filesize($filename);
                if ($current_size > $max_size) {
                    File::put($filename, "{$log_message}");
                } else {
                    File::put($filename, "{$log_message}", FILE_APPEND);
                }
            } else {
                File::put($filename, $log_message);
            }

            // Let PHP handle the error in the normal way
            return false;
        };

        // Set the error handler function
        set_error_handler($error_handler);
    }

    /**
     * Get ENV (Enviroment) Data
     * - If .env was not used, 
     * - Then it will get all App Configuration Data as well
     * 
     * @param string|null $key - [optional] ENV KEY or APP Configuration Key
     * @param mixed $value - [optional] Default value if key not found
     * @return mixed
     */
    public static function env($key = null, $value = null)
    {
        // Convert all keys to lowercase
        $envData = array_change_key_case($_ENV, CASE_UPPER);

        // convert to upper-case
        $key = Str::upper(Str::trim($key));

        return $envData[$key] ?? $value;
    }

    /**
     * Update Environment path .env file
     * @param string|null $key \Environment key you want to update
     * @param string|bool|null $value \Value allocated to the key
     * @param bool $quote \Allow quotes around value
     * @param bool $space \Allow space between key and value
     * 
     * @return bool
     */
    public static function updateENV($key = null, $value = null, ?bool $quote = true, ?bool $space = false)
    {
        $path = self::formatWithBaseDirectory('.env');

        if (file_exists($path)) {

            // if isset
            if(Manager::isEnvSet($key)){
                
                // Read the contents of the .env file
                $lines = file($path);

                // Loop through the lines to find the variable
                foreach ($lines as &$line) {
                    // Check if the line contains the variable
                    if (strpos($line, $key) === 0) {

                        // get space seperator value
                        $separator = $space ? " = " : "=";

                        // check for boolean value
                        if(is_bool($value)){
                            // Update the value of the variable
                            $line = "{$key}=" . ($value ? 'true' : 'false') . PHP_EOL;
                        }else{
                            // check if quote is allowed
                            if($quote){
                                // Update the value of the variable with quotes
                                $line = "{$key}{$separator}\"{$value}\"" . PHP_EOL;
                            }else{
                                // Update the value of the variable without quotes
                                $line = "{$key}{$separator}{$value}" . PHP_EOL;
                            }
                        }
                        break;
                    }
                }

                // Write the updated contents back to the .env file
                File::put($path, implode('', $lines));

                return true;
            }
        }

        return false;
    }

    /**
     * Boot the ENV::BootLogger.
     * If the constant 'TAME_ENV_BOOTLOGER' is not defined, 
     * it defines it and starts the debugger automatically 
     * 
     * So that this is only called once in entire application life-cycle
     */
    public static function boot()
    {
        if(!defined('TAME_ENV_BOOTLOGER')){
            // start logger
            self::bootLogger();

            // Define boot logger as true
            define('TAME_ENV_BOOTLOGER', 1);
        } 
    }

    /**
     * If Dotenv SInstance Class Available
     * @return bool
     */
    public static function isDotEnvInstanceAvailable()
    {
        return class_exists('Dotenv\Dotenv');
    }

    /**
     * Checks if the specified environment variable has been set or started.
     * 
     * @param string $key The name of the environment variable to check. Defaults to 'APP_NAME'.
     * @return bool
     */
    public static function isEnvStarted($key = 'APP_NAME')
    {
        return (self::$envFileIsLoaded === true) || (Manager::isEnvSet($key) === true);
    }

    /**
     * Alias for `isEnvStarted()` method
     * @return bool
     */
    public static function isEnvFileLoaded()
    {
        return self::isEnvStarted();
    }

    /**
     * If Application debug mode is on or off
     * @return bool 
     */
    public static function isApplicationOnDebug() 
    {
        return Manager::isEnvBool(env('APP_DEBUG'));
    }    

    /**
     * Determines if the application is running in a given environment.
     * 
     * @param array|string $env     Environment(s) to check against (default: 'local')
     * @param bool         $strict  Whether to validate using server IP ranges instead of .env (default: false)
     * @return bool 
     */
    public static function environment($env = 'local', $strict = false)
    {
        [$envs, $current] = self::normalizeEnvs($env);

        // If environment checking should be strict - using ip check for IP range matches too!
        if($strict){
            // must pass strict validation AND match expected envs
            return self::validateStrict($current) && in_array($current, $envs, true);
        }
        
        // Final check: does current env match expected ones?
        return in_array($current, $envs, true);
    }

    /**
     * Create needed directory and files
     *
     * @param string|null $directory
     * @param string|null $filename
     * @return void
     */
    private static function createDir_AndFiles($directory = null,  $filename = null)
    {
        // if system path is null
        // calling the `new self()` will initalize the class and set the default path for us
        if(empty(self::$sym_path)){
            new self();
        }

        // if \storage folder not found
        if(!File::isDirectory(self::$sym_path. "storage")){
            File::makeDirectory(self::$sym_path. "storage", 0777);
        }

        // if \storage\logs\ folder not found
        if(!File::isDirectory($directory)){
            File::makeDirectory($directory, 0777);
        }

        // If the log file doesn't exist, create it
        if(!File::exists($filename)) {
            touch($filename);
            chmod($filename, 0777);
        }
    }

    /**
     * Create SymPath
     *
     * @param  string|null $path
     * @return void
     */
    private static function createSymPath($path = null)
    {
        // if sym_path is null
        if(is_null(self::$sym_path) || !(empty($path) && is_null($path))){
            
            // init entire class object
            self::init();

            if(!empty($path)){
                self::$class->getDirectory($path);
    
                // add to global property
                self::$sym_path = self::$class->cleanServerPath($path);
            }
        }
    }

    /**
     * Validate strict environment rules
     * 
     * @param string $current
     * @return bool
     */
    private static function validateStrict(string $current)
    {
        $isLocal = HttpRequest::isLocalIp();
        $localAliases = ['local', 'localhost', 'dev', 'development'];

        $productionAliases = [
            'prod', 'production', 'stage', 'staging',  'test', 'testing', 'preprod', 'pre-production',
            'live', 'online', 'public', 'remote', 'qa', 'uat', 'user-acceptance-testing', 'unknown'
        ];

        // If the current env is "local-like", require a local IP
        if (in_array($current, $localAliases, true) && ($isLocal === true)) {
            return $isLocal;
        }

        // Anything else (production/live/unknown) => must NOT be local
        return ! $isLocal && in_array($current, $productionAliases, true);
    }

    /**
     * Normalize environments.
     *
     * @param array|string $env
     * @return array [$envs, $current]
     */
    private static function normalizeEnvs($env)
    {
        $envs = array_map([Str::class, 'lower'], Str::flatten((array) $env));
        $current = Str::lower(env('APP_ENV', env('APP_ENVIRONMENT')));

        return [$envs, $current];
    }

    /**
     * GET Error Levels
     *
     * @return array 
     */
    private static function error_levels()
    {
        return array(
            E_ERROR             => 'Fatal Error',
            E_USER_ERROR        => 'User Error',
            E_PARSE             => 'Parse Error',
            E_WARNING           => 'Warning',
            E_USER_WARNING      => 'User Warning',
            E_NOTICE            => 'Notice',
            E_USER_NOTICE       => 'User Notice',
            // E_STRICT was removed in PHP 8+
            E_DEPRECATED        => 'Deprecated',
            E_USER_DEPRECATED   => 'User Deprecated',
        );
    }  

    /**
     * Sample copy of env file
     * 
     * @return string
     */
    private static function envTxt()
    {
        return (new Manager)->envDummy();
    }

}