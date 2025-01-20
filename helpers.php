<?php 

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\PDF;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Zip;
use Tamedevelopers\Support\Hash;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Time;
use Tamedevelopers\Support\View;
use Tamedevelopers\Support\Asset;
use Tamedevelopers\Support\Cookie;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Country;
use Tamedevelopers\Support\UrlHelper;
use Tamedevelopers\Support\Translator;
use Tamedevelopers\Support\NumberToWords;
use Tamedevelopers\Support\AutoloadRegister;
use Tamedevelopers\Support\Capsule\FileCache;
use Tamedevelopers\Support\Collections\Collection;


if (! function_exists('AppIsNotCorePHP')) {
    /**
     * Check if Application is not Core PHP
     * If running on other frameworks
     *
     * @return bool
     */
    function AppIsNotCorePHP()
    {
        // using `get_declared_classes()` function will return all classes in your project
        // Check if any classe exist
        return Tame::checkAnyClassExists([
            '\Illuminate\Foundation\Application',
            // '\CI_Controller',
            // '\Cake\Controller\Controller',
            // '\Symfony\Component\HttpKernel\Kernel',
            // '\Symfony\Component\Routing\Annotation\Route',
        ]);
    }
}

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

if (! function_exists('TameCookie')) {
    /**
     * Cookie Object
     *
     * @return \Tamedevelopers\Support\Cookie
     */
    function TameCookie()
    {
        return new Cookie();
    }
}

if (! function_exists('TameTime')) {
    /**
     * Time Object
     * @param int|string|null $time
     * @param string|null $timezone
     * @return \Tamedevelopers\Support\Time
     */
    function TameTime($time = null, $timezone = null)
    {
        return new Time($time, $timezone);
    }
}


if (! function_exists('TameCollect')) {
    /**
     * Collection of data
     *
     * @param array $items 
     * 
     * @return \Tamedevelopers\Support\Collections\Collection
     */
    function TameCollect($items = [])
    {
        return new Collection($items);
    }
}

if (! function_exists('TameStr')) {
    /**
     * Tame Str
     * 
     * @return \Tamedevelopers\Support\Str
     */
    function TameStr()
    {
        return new Str();
    }
}

if (! function_exists('TameCountry')) {
    /**
     * Country Object
     * @return \Tamedevelopers\Support\Country
     */
    function TameCountry()
    {
        return new Country();
    }
}

if (! function_exists('NumberToWords')) {
    /**
     * NumberToWords Object
     * @return \Tamedevelopers\Support\NumberToWords
     */
    function NumberToWords()
    {
        return new NumberToWords();
    }
}

if (! function_exists('TamePDF')) {
    /**
     * PDF Object
     *
     * @return \Tamedevelopers\Support\PDF
     */
    function TamePDF()
    {
        return new PDF();
    }
}

if (! function_exists('TameZip')) {
    /**
     * Zip Object
     *
     * @return \Tamedevelopers\Support\Zip
     */
    function TameZip()
    {
        return new Zip();
    }
}

if (! AppIsNotCorePHP() && ! function_exists('bcrypt')) {
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

if (! function_exists('server')) {
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

if (! AppIsNotCorePHP() && ! function_exists('config')) {
    /**
     * Get the value of a configuration option.
     *
     * @param mixed $key 
     * The configuration key in dot notation (e.g., 'database.connections.mysql')
     * 
     * @param mixed $default 
     * [optional] The default value to return if the configuration option is not found
     * 
     * @return mixed
     * The value of the configuration option, or null if it doesn't exist
     */
    function config($key, $default = null)
    {
        return server()->config($key, $default);
    }
}

if (! AppIsNotCorePHP() && ! function_exists('env')) {
    /**
     * Get ENV (Enviroment) Data
     * - If .env was not used, 
     * - Then it will get all App Configuration Data as well
     * 
     * @param string|null $key
     * - [optional] ENV KEY or APP Configuration Key
     * 
     * @param mixed $value
     * - [optional] Default value if key not found
     * 
     * @return mixed
     */
    function env($key = null, $value = null)
    {
        return Env::env($key, $value);
    }
}

if (! function_exists('env_update')) {
    /**
     * Update Environment [path .env] variables
     * 
     * @param string|null $key \Environment key you want to update
     * 
     * @param string|bool|null $value \Value of Variable to update
     * 
     * @param bool $quote \Default is true
     * [optional] Allow quotes around values
     * 
     * @param bool $space \Default is false
     * [optional] Allow space between key and value
     * 
     * @return bool
     */
    function env_update($key = null, $value = null, ?bool $quote = true, ?bool $space = false)
    {
        return Env::updateENV($key, $value, $quote, $space);
    }
}

if (! function_exists('tview')) {
    /**
     * View Tenmplate Engine
     * 
     * @param string $viewPath The path to the view file.
     * @param array $data The data to be passed to the view.
     * 
     * @return Tamedevelopers\Support\View
     */
    function tview($viewPath, $data = [])
    {
        return new View($viewPath, $data);
    }
}

if (! function_exists('tasset')) {
    /**
     * Create assets Real path url
     * 
     * @param string $asset
     * - asset file e.g (style.css | js/main.js)
     * 
     * @param bool|null $cache
     * 
     * @return string
     */
    function tasset($asset = null, $cache = null)
    {
        return Asset::asset($asset, $cache);
    }
}

if (! function_exists('config_asset')) {
    /**
     * Configure Assets Default Directory
     * 
     * @param string|null $base_path
     * - [optional] Default is `base_directory/assets`
     * - If set and directory is not found, then we revert back to the default
     * 
     * @param string $cache
     * - [optional] Default is false
     * - End point of link `?v=xxxxxxxx` is with cache of file time change
     * - This will automatically tells the broswer to fetch new file if the time change
     * - Time will only change if you make changes or modify the request file
     * 
     * @return void
     */
    function config_asset($base_path = null, ?bool $cache = false)
    {
        Asset::config($base_path, $cache);
    }
}

if (! AppIsNotCorePHP() && ! function_exists('__')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $key
     * @param  string|null  $locale
     * @param  string|null  $base_folder
     * 
     * @return string|array|null
     */
    function __($key = null, $locale = null, $base_folder = null)
    {
        if (is_null($key)) {
            return $key;
        }

        return Translator::trans($key, $locale, $base_folder);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get Base Directory `Path`
     * @param string|null $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/server_path/path
     * 
     * @return string
     */
    function base_path($path = null)
    {
        return server()->formatWithBaseDirectory($path);
    }
}

if (! function_exists('directory')) {
    /**
     * Get Base Directory `Path`
     * @param string|null $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/server_path/path
     * 
     * @return string
     */
    function directory($path = null)
    {
        return base_path($path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get Storage Directory `Path`
     * @param string|null $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/storage/path
     * 
     * @return string
     */
    function storage_path($path = null)
    {
        return base_path("storage/{$path}");
    }
}

if (! function_exists('public_path')) {
    /**
     * Get Public Directory `Path`
     * @param string|null $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/public/path
     * 
     * @return string
     */
    function public_path($path = null)
    {
        return base_path("public/{$path}");
    }
}

if (! function_exists('app_path')) {
    /**
     * Get Storage Directory `Path`
     * @param string|null $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/app/path
     * 
     * @return string
     */
    function app_path($path = null)
    {
        return base_path("app/{$path}");
    }
}

if (! function_exists('config_path')) {
    /**
     * Get Config Directory `Path`
     * @param string|null $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/server_path/path
     * 
     * @return string
     */
    function config_path($path = null)
    {
        return base_path("config/{$path}");
    }
}

if (! function_exists('lang_path')) {
    /**
     * Get Config Directory `Path`
     * @param string|null $path
     * - [optional] You can pass a path to include with the base directory
     * - Final result: i.e C:/lang/path
     * 
     * @return string
     */
    function lang_path($path = null)
    {
        return base_path("lang/{$path}");
    }
}

if (! function_exists('domain')) {
    /**
     * Get Domain `URL` URI
     * 
     * @param string|null $path
     * - [optional] You can pass a path to include with the domain link
     * - Final result: i.e https://domain.com/path
     * 
     * @return string
     */
    function domain($path = null)
    {
        return server()->formatWithDomainURI($path);
    }
}

if (! function_exists('urlHelper')) {
    /**
     * Get URL Helper
     * 
     * @return \Tamedevelopers\Support\UrlHelper
     */
    function urlHelper()
    {
        return new UrlHelper();
    }
}


if (! function_exists('to_array')) {
    /**
     * Convert Value to an Array
     * 
     * @param  mixed $value
     * @return array
     */ 
    function to_array($value)
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
    function to_object($value)
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
