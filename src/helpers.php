<?php 

if (! function_exists('Tame_isAppFramework')) {
    /**
     * Check if Application is not Core PHP
     * If running on other frameworks
     *
     * @return bool
     */
    function Tame_isAppFramework()
    {
        return (new \Tamedevelopers\Support\Tame)->isAppFramework();
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
        return new \Tamedevelopers\Support\Tame();
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
        return new \Tamedevelopers\Support\Mail();
    }
}

if (! function_exists('TameEnv')) {
    /**
     * Env Class
     * 
     * @param  mixed $path
     * @return \Tamedevelopers\Support\Env
     */
    function TameEnv($path = null)
    {
        return new \Tamedevelopers\Support\Env($path);
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
        return new \Tamedevelopers\Support\Cookie();
    }
}

if (! function_exists('TameTime')) {
    /**
     * Time Class
     * 
     * @param int|string|null $time
     * @param string|null $timezone
     * @return \Tamedevelopers\Support\Time
     */
    function TameTime($time = null, $timezone = null)
    {
        return new \Tamedevelopers\Support\Time($time, $timezone);
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
        return new \Tamedevelopers\Support\Str();
    }
}

if (! function_exists('TameSanitizer')) {
    /**
     * Tame Sanitizer
     * 
     * @return \Tamedevelopers\Support\TextSanitizer
     */
    function TameSanitizer()
    {
        return new \Tamedevelopers\Support\TextSanitizer();
    }
}

if (! function_exists('TameUtility')) {
    /**
     * Tame Utility
     * 
     * @param string|null $text
     * @return \Tamedevelopers\Support\Utility
     */
    function TameUtility($text = null)
    {
        return new \Tamedevelopers\Support\Utility($text);
    }
}

if (! function_exists('TameCountry')) {
    /**
     * Country Class
     * 
     * @return \Tamedevelopers\Support\Country
     */
    function TameCountry()
    {
        return new \Tamedevelopers\Support\Country();
    }
}

if (! function_exists('NumberToWords')) {
    /**
     * Number-to-words
     * 
     * @return \Tamedevelopers\Support\NumberToWords
     */
    function NumberToWords()
    {
        return new \Tamedevelopers\Support\NumberToWords();
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
        return new \Tamedevelopers\Support\PDF();
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
        return new \Tamedevelopers\Support\Zip();
    }
}

if (! function_exists('TameQR')) {
    /**
     * QRCode
     * @param string|null $path 
     * @return \Tamedevelopers\Support\QRCode
     */
    function TameQR(?string $path = null)
    {
        return new \Tamedevelopers\Support\QRCode($path);
    }
}

if (! function_exists('TameTotp')) {
    /**
     * Tame TOTP
     * 
     * @param int    $digits    Length of the OTP output code (default: 6).
     * @param int    $period    Time interval window in seconds (default: 30).
     * @param 'sha1'|'sha256'|'sha512' $algorithm HMAC hashing algorithm (default: 'sha1').
     * @return \Tamedevelopers\Support\TOTP
     */
    function TameTotp(int $digits = 6, int $period = 30, string $algorithm = 'sha1')
    {
        return new \Tamedevelopers\Support\TOTP($digits, $period, $algorithm);
    }
}

if (! function_exists('TameRecoveryKey')) {
    /**
     * Tame RecoveryKey
     * 
     * @return \Tamedevelopers\Support\RecoveryKey
     */
    function TameRecoveryKey(int $digits = 6, int $period = 30, string $algorithm = 'sha1')
    {
        return new \Tamedevelopers\Support\RecoveryKey($digits, $period, $algorithm);
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
        return new \Tamedevelopers\Support\Capsule\FileCache();
    }
}

if (! function_exists('TameFileBag')) {
    /**
     * Get instance of FileBag.
     * 
     * @param array<string, mixed>|null $collection
     * @return \Tamedevelopers\Support\Capsule\FileBag
     */
    function TameFileBag(?array $collection = null)
    {
        return new \Tamedevelopers\Support\Capsule\FileBag($collection);
    }
}

if (! function_exists('TameExchange')) {
    /**
     * Currency rates exchange
     * 
     * @return \Tamedevelopers\Support\Exchange
     */
    function TameExchange()
    {
        return new \Tamedevelopers\Support\Exchange();
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
        return new \Tamedevelopers\Support\Process\HttpRequest();
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
        return new \Tamedevelopers\Support\Process\HttpRequest();
    }
}

if (! function_exists('TameCollect')) {
    /**
     * Collection Class
     *
     * @param array|null $items 
     * @return \Tamedevelopers\Support\Collections\Collection|mixed
     */
    function TameCollect($items = [])
    {
        return new \Tamedevelopers\Support\Collections\Collection($items);
    }
}

if (! function_exists('tcollect')) {
    /**
     * Collection Class
     *
     * @param array|null $items =
     * @return \Tamedevelopers\Support\Collections\Collection|mixed
     */
    function tcollect($items = [])
    {
        return new \Tamedevelopers\Support\Collections\Collection($items);
    }
}

if (! function_exists('toptional')) {
    /**
     * Optional Class
     *
     * @param array|object|null $items 
     * @return \Tamedevelopers\Support\Collections\Collection|mixed
     */
    function toptional($items = [])
    {
        if(!is_array($items) && !is_null($items)){
            $items = (new \Tamedevelopers\Support\Server)->toArray($items);
        }

        return new \Tamedevelopers\Support\Collections\Collection($items);
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
        return new \Tamedevelopers\Support\Capsule\Manager();
    }
}

if (! $Tame_isAppFramework && ! function_exists('bcrypt')) {
     /**
     * Password Encrypter.
     * 
     * @param string $password 
     * @return string 
     */
    function bcrypt($password)
    {
        return (new \Tamedevelopers\Support\Hash)->make($password);
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
        return new \Tamedevelopers\Support\Server();
    }
}

if (! function_exists('autoload_register')) {
    /**
     * Autoload function to load class and files in a given folder
     *
     * @param string|array $directory 
     * - The directory path to load
     * - Do not include the root path, as The Application already have a copy of your path
     * - e.g 'classes' or ['app/main', 'includes']
     * 
     * @return \Tamedevelopers\Support\AutoloadRegister
     */
    function autoload_register($directory)
    {
        (new \Tamedevelopers\Support\AutoloadRegister)->load($directory);
    }
}

if (! $Tame_isAppFramework && ! function_exists('config')) {
    /**
     * Get the value of a configuration option.
     *
     * @param mixed $key    Supports dot notation (e.g., 'database.connections.mysql')
     * @param mixed $default     The default value to return option is not found
     * @return mixed
     */
    function config($key, $default = null)
    {
        return (new \Tamedevelopers\Support\Server)->config($key, $default);
    }
}

if (! $Tame_isAppFramework && ! function_exists('env')) {
    /**
     * Get ENV (Enviroment) Data
     * 
     * @param string|null $key
     * @param mixed $default (optional) Default value if key not found
     * @return mixed
     */
    function env($key = null, $default = null)
    {
        return (new \Tamedevelopers\Support\Env)->env($key, $default);
    }
}

if (! function_exists('env_update')) {
    /**
     * Update Environment [path .env] variables
     * 
     * @param string|null $key \Environment key you want to update
     * @param string|bool|null $value \Value of Variable to update
     * @param bool $quote   Default is true
     * @param bool $space   Default is false Allow space between key and value
     * @return bool
     */
    function env_update($key = null, $value = null, ?bool $quote = true, ?bool $space = false)
    {
        return (new \Tamedevelopers\Support\Env)->updateENV(
            $key, 
            $value, 
            $quote, 
            $space
        );
    }
}

if (! function_exists('tview')) {
    /**
     * View Tenmplate Engine
     * 
     * @param string|null $viewPath The path to the view file.
     * @param array $data The data to be passed to the view.
     * @return Tamedevelopers\Support\View
     */
    function tview($viewPath = null, $data = [])
    {
        return new \Tamedevelopers\Support\View($viewPath, $data);
    }
}

if (! function_exists('tasset')) {
    /**
     * Create assets Real path url
     * 
     * @param string $asset
     * @param bool|null $cache
     * @param bool|null $type "absolute" | "relative" (default: false → absolute)
     * @return string
     */
    function tasset($asset, $cache = null, $type = null)
    {
        return (new \Tamedevelopers\Support\Asset)->asset($asset, $cache, $type);
    }
}

if (! function_exists('config_asset')) {
    /**
     * Configure Assets Default Directory
     * 
     * @param string|null $path
     * @param bool $cache       Whether to use cache-busting (default: true)
     * - End point of link `?v=xxxxxxxx` is with cache of file time chang
     * @param bool $type   "absolute" | "relative" (default: false → absolute)
     * @return void
     */
    function config_asset($path = null, $cache = false, $type = false)
    {
        (new \Tamedevelopers\Support\Asset)->config($path, $cache, $type);
    }
}

if (! function_exists('config_time')) {
    /**
     * Set the configuration options for text representations of time greeting()
     * 
     * @param array|null $options
     * @return void
     */
    function config_time(?array $options = [])
    {
        (new \Tamedevelopers\Support\Time)->config($options);
    }
}

if (! $Tame_isAppFramework && ! function_exists('__')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $key
     * @param  string|null  $locale
     * @param  string|null  $base_folder
     * @return string|array|null
     */
    function __($key = null, $locale = null, $base_folder = null)
    {
        if (is_null($key)) {
            return $key;
        }

        return (new \Tamedevelopers\Support\Translator)->trans($key, $locale, $base_folder);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get Base Directory `Path`
     * 
     * @param string|null $path
     * @return string
     */
    function base_path($path = null)
    {
        return (new \Tamedevelopers\Support\Server)->formatWithBaseDirectory($path);
    }
}

if (! function_exists('directory')) {
    /**
     * Get Base Directory `Path`
     * 
     * @param string|null $path
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
     * 
     * @param string|null $path
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
     * 
     * @param string|null $path
     * @return string
     */
    function public_path($path = null)
    {
        return base_path("public/{$path}");
    }
}

if (! function_exists('database_path')) {
    /**
     * Get Database Directory `Path`
     * 
     * @param string|null $path
     * @return string
     */
    function database_path($path = null)
    {
        return base_path("database/{$path}");
    }
}

if (! function_exists('app_path')) {
    /**
     * Get Storage Directory `Path`
     * 
     * @param string|null $path
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
     * 
     * @param string|null $path
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
     * 
     * @param string|null $path
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
     * @return string
     */
    function domain($path = null)
    {
        return (new \Tamedevelopers\Support\Server)->formatWithDomainURI($path);
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
        return (new \Tamedevelopers\Support\Server)->toArray($value);
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
        return (new \Tamedevelopers\Support\Server)->toObject($value);
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
        return (new \Tamedevelopers\Support\Server)->toJson($value);
    }
}

if (! $Tame_isAppFramework && ! function_exists('dump')) {
    /**
     * Dump Data
     * 
     * @param mixed $data
     * @return void
     */ 
    function dump(...$data)
    {
        (new \Tamedevelopers\Support\Server)->dump($data);
    }
}

if (! $Tame_isAppFramework && ! function_exists('dd')) {
    /**
     * Dump and Data
     * 
     * @param mixed $data
     * @return void
     */ 
    function dd(...$data)
    {
        dump($data);
        exit(1);
    }
}
