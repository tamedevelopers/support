<?php 

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\PDF;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Zip;
use Tamedevelopers\Support\Hash;
use Tamedevelopers\Support\Mail;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Time;
use Tamedevelopers\Support\View;
use Tamedevelopers\Support\Asset;
use Tamedevelopers\Support\Cookie;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Country;
use Tamedevelopers\Support\Translator;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\NumberToWords;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Process\Session;
use Tamedevelopers\Support\AutoloadRegister;
use Tamedevelopers\Support\Capsule\FileCache;
use Tamedevelopers\Support\Process\HttpRequest;
use Tamedevelopers\Support\Collections\Collection;


if (! function_exists('Tame_isAppFramework')) {
    /**
     * Check if Application is not Core PHP
     * If running on other frameworks
     *
     * @return bool
     */
    function Tame_isAppFramework()
    {
        return (new Tame)->isAppFramework();
    }
}

/**
 * Helps without calling the method multiple times
 */
$Tame_isAppFramework = function_exists('Tame_isAppFramework') ? Tame_isAppFramework() : false;


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

if (! function_exists('TameMail')) {
    /**
     * Mailer Object
     *
     * @return \Tamedevelopers\Support\Mail
     */
    function TameMail()
    {
        return new Mail();
    }
}

if (! function_exists('TameEnv')) {
    /**
     * Env Class
     * @param  mixed $path
     * @return \Tamedevelopers\Support\Env
     */
    function TameEnv($path = null)
    {
        return new Env($path);
    }
}

if (! function_exists('TameFile')) {
    /**
     * File Class
     * @return \Tamedevelopers\Support\Capsule\File
     */
    function TameFile()
    {
        return new File();
    }
}

if (! function_exists('TameCookie')) {
    /**
     * Cookie Class
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
     * Time Class
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
     * Collection Class
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

if (! function_exists('tcollect')) {
    /**
     * Collection Class
     *
     * @param array $items 
     * 
     * @return \Tamedevelopers\Support\Collections\Collection
     */
    function tcollect($items = [])
    {
        return new Collection($items);
    }
}

if (! function_exists('tmanager')) {
    /**
     * Manager Class
     * 
     * @return \Tamedevelopers\Support\Capsule\Manager
     */
    function tmanager()
    {
        return new Manager();
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
     * Country Class
     * @return \Tamedevelopers\Support\Country
     */
    function TameCountry()
    {
        return new Country();
    }
}

if (! function_exists('NumberToWords')) {
    /**
     * NumberToWords Class
     * @return \Tamedevelopers\Support\NumberToWords
     */
    function NumberToWords()
    {
        return new NumberToWords();
    }
}

if (! function_exists('TamePDF')) {
    /**
     * PDF Class
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
     * Zip Class
     *
     * @return \Tamedevelopers\Support\Zip
     */
    function TameZip()
    {
        return new Zip();
    }
}

if (! $Tame_isAppFramework && ! function_exists('bcrypt')) {
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

if (! function_exists('urlHelper')) {
    /**
     * Get URL Helper
     * 
     * @return \Tamedevelopers\Support\Process\HttpRequest
     */
    function urlHelper()
    {
        return new HttpRequest();
    }
}

// Lightweight accessors (do not conflict with frameworks)
if (! function_exists('TameRequest')) {
    /**
     * Native HTTP Request accessor
     * @return \Tamedevelopers\Support\Process\HttpRequest
     */
    function TameRequest()
    {
        return new HttpRequest();
    }
}

if (! function_exists('TameSession')) {
    /**
     * Native Session accessor
     * @return \Tamedevelopers\Support\Process\Session
     */
    function TameSession()
    {
        $s = new Session();
        $s->start();
        return $s;
    }
}

if (! $Tame_isAppFramework && ! function_exists('config')) {
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

if (! $Tame_isAppFramework && ! function_exists('env')) {
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
     * @param string|null $viewPath The path to the view file.
     * @param array $data The data to be passed to the view.
     * 
     * @return Tamedevelopers\Support\View
     */
    function tview($viewPath = null, $data = [])
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
     * @param bool|null $path_type
     * -[optional] Default is true (Absolute Path)|Else -- false is (Relative path)
     * 
     * @return string
     */
    function tasset($asset = null, $cache = null, $path_type = null)
    {
        return Asset::asset($asset, $cache, $path_type);
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
     * @param bool $cache
     * - [optional] Default is false
     * - End point of link `?v=xxxxxxxx` is with cache of file time change
     * - This will automatically tells the broswer to fetch new file if the time change
     * - Time will only change if you make changes or modify the request file
     * 
     * @param bool $path_type
     * -[optional] Default is false[Absolute Path] | true[Relative path]
     * 
     * @return void
     */
    function config_asset($base_path = null, ?bool $cache = false, ?bool $path_type = false)
    {
        Asset::config($base_path, $cache, $path_type);
    }
}

if (! function_exists('config_time')) {
    /**
     * Set the configuration options for text representations of time greeting()
     * @param array|null $options
     * 
     * @return void
     */
    function config_time(?array $options = [])
    {
        (new Time)->config($options);
    }
}

if (! $Tame_isAppFramework && ! function_exists('__')) {
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

if (! $Tame_isAppFramework && ! function_exists('dump')) {
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

if (! $Tame_isAppFramework && ! function_exists('dd')) {
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
