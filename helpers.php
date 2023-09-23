<?php 

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\PDF;
use Tamedevelopers\Support\Hash;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Asset;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\AutoloadRegister;
use Tamedevelopers\Support\Capsule\FileCache;


if (! function_exists('Tame')) {
    /**
     * Tame Object
     *
     * @return \Tamedevelopers\Support\Tame
     */
    function Tame()
    {
        return new Tame();
    }
}

if (! function_exists('PDF')) {
    /**
     * PDF Object
     *
     * @return \Tamedevelopers\Support\PDF
     */
    function PDF()
    {
        return new PDF();
    }
}

if (! function_exists('bcrypt')) {
     /**
     * Password Encrypter.
     * This function encrypts a password using bcrypt with a generated salt.
     *
     * @param string $password 
     * - The password to encrypt.
     * 
     * @return string 
     * - The encrypted password.
     */
    function bcrypt($password)
    {
        return Hash::make($password);
    }
}

if (! function_exists('FileCache')) {
    /**
     * File Cache Object
     *
     * @return \Tamedevelopers\Support\Capsule\FileCache
     */
    function FileCache()
    {
        return new FileCache();
    }
}

if (! function_exists('Server')) {
    /**
     * Server Object
     *
     * @return \Tamedevelopers\Support\Server
     */
    function server()
    {
        return new Server();
    }
}

if (! function_exists('autoload_register')) {
    /**
     * Autoload function to load class and files in a given folder
     *
     * @param string|array $baseDirectory 
     * - The directory path to load
     * - Do not include the root path, as The Application already have a copy of your path
     * - e.g 'classes' or ['app/main', 'includes']
     * 
     * @return \Tamedevelopers\Support\AutoloadRegister
     */
    function autoload_register(string|array $directory)
    {
        (new AutoloadRegister)->load($directory);
    }
}

if (! function_exists('config')) {
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
    function config(string $key, $default = null)
    {
        return (new Server)->config($key, $default);
    }
}

if (! function_exists('env')) {
    /**
     * Get ENV (Enviroment) Data
     * - If .env was not used, 
     * - Then it will get all App Configuration Data as well
     * 
     * @param string $key
     * - [optional] ENV KEY or APP Configuration Key
     * 
     * @param mixed $value
     * - [optional] Default value if key not found
     * 
     * @return mixed
     */
    function env(?string $key = null, mixed $value = null)
    {
        return Env::env($key, $value);
    }
}

if (! function_exists('env_update')) {
    /**
     * Update Environment [path .env] variables
     * 
     * @param string $key \Environment key you want to update
     * 
     * 
     * @param string|bool $value \Value of Variable to update
     * 
     * @param bool $allow_quote \Default is true
     * [optional] Allow quotes around values
     * 
     * @param bool $allow_space \Default is false
     * [optional] Allow space between key and value
     * 
     * @return bool
     */
    function env_update(?string $key = null, string|bool $value = null, ?bool $allow_quote = true, ?bool $allow_space = false)
    {
        return Env::updateENV($key, $value, $allow_quote, $allow_space);
    }
}

if (! function_exists('asset')) {
    /**
     * Create assets Real path url
     * 
     * @param string $asset
     * - asset file e.g (style.css | js/main.js)
     * 
     * @return string
     */
    function asset(?string $asset = null)
    {
        return Asset::asset($asset);
    }
}

if (! function_exists('asset_config')) {
    /**
     * Configure Assets Default Directory
     * 
     * @param string $base_path
     * - [optional] Default is `base_directory/assets`
     * - If set and directory is not found, then we revert back to the default
     * 
     * @param string $cache
     * - [optional] Default is true
     * - End point of link `?v=xxxxxxxx` is with cache of file time change
     * - This will automatically tells the broswer to fetch new file if the time change
     * - Time will only change if you make changes or modify the request file
     * 
     * @return void
     */
    function asset_config(?string $base_path = null, ?bool $cache = true)
    {
        Asset::config($base_path, $cache);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get Base Directory `Path`
     * @param string $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/server_path/path
     * 
     * @return string
     */
    function base_path(?string $path = null)
    {
        return server()->formatWithBaseDirectory($path);
    }
}

if (! function_exists('directory')) {
    /**
     * Get Base Directory `Path`
     * @param string $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/server_path/path
     * 
     * @return string
     */
    function directory(?string $path = null)
    {
        return base_path($path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get Storage Directory `Path`
     * @param string $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/storage/path
     * 
     * @return string
     */
    function storage_path(?string $path = null)
    {
        return base_path("storage/{$path}");
    }
}

if (! function_exists('public_path')) {
    /**
     * Get Public Directory `Path`
     * @param string $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/public/path
     * 
     * @return string
     */
    function public_path(?string $path = null)
    {
        return base_path("public/{$path}");
    }
}

if (! function_exists('app_path')) {
    /**
     * Get Storage Directory `Path`
     * @param string $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/app/path
     * 
     * @return string
     */
    function app_path(?string $path = null)
    {
        return base_path("app/{$path}");
    }
}

if (! function_exists('config_path')) {
    /**
     * Get Config Directory `Path`
     * @param string $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/server_path/path
     * 
     * @return string
     */
    function config_path(?string $path = null)
    {
        return base_path("config/{$path}");
    }
}

if (! function_exists('lang_path')) {
    /**
     * Get Config Directory `Path`
     * @param string $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/lang/path
     * 
     * @return string
     */
    function lang_path(?string $path = null)
    {
        return base_path("lang/{$path}");
    }
}

if (! function_exists('domain')) {
    /**
     * Get Domain `URL` URI
     * 
     * @param string $path
     * - [optional] You can pass a path to include with the domain link
     * - Final result: i.e https://domain.com/path
     * 
     * @return string
     */
    function domain(?string $path = null)
    {
        return server()->formatWithDomainURI($path);
    }
}


if (! function_exists('to_array')) {
    /**
     * Convert Value to an Array
     * 
     * @param  mixed $value
     * @return array
     */ 
    function to_array(mixed $value)
    {
        return server()->toArray($value);
    }
}

if (! function_exists('to_object')) {
    /**
     * Convert Value to an Object
     * 
     * @param  mixed $value
     * @return object
     */ 
    function to_object(mixed $value)
    {
        return server()->toObject($value);
    }
}

if (! function_exists('to_json')) {
    /**
     * Convert Value to Json Data
     * 
     * @param  mixed $value
     * @return string
     */ 
    function to_json($value)
    {
        return server()->toJson($value);
    }
}

if (! function_exists('dump')) {
    /**
     * Dump Data
     * @param mixed $data
     * 
     * @return void
     */ 
    function dump(...$data)
    {
        server()->dump($data);
    }
}

if (! function_exists('dd')) {
    /**
     * Dump and Data
     * @param mixed $data
     * 
     * @return void
     */ 
    function dd(...$data)
    {
        dump($data);
        exit(1);
    }
}
