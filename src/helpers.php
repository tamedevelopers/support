<?php 

use Tamedevelopers\Support\Collections\Collection;
use Tamedevelopers\Support\Process\HttpRequest;
use Tamedevelopers\Support\Capsule\FileCache;
use Tamedevelopers\Support\AutoloadRegister;
use Tamedevelopers\Support\Capsule\FileBag;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\NumberToWords;
use Tamedevelopers\Support\TextSanitizer;
use Tamedevelopers\Support\RecoveryKey;
use Tamedevelopers\Support\Translator;
use Tamedevelopers\Support\Exchange;
use Tamedevelopers\Support\Country;
use Tamedevelopers\Support\Utility;
use Tamedevelopers\Support\Cookie;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\QRCode;
use Tamedevelopers\Support\Asset;
use Tamedevelopers\Support\Mail;
use Tamedevelopers\Support\Hash;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Time;
use Tamedevelopers\Support\TOTP;
use Tamedevelopers\Support\View;
use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\PDF;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Zip;

if (! function_exists('Tame_isAppFramework')) {
    /**
     * Check if application is running inside a framework.
     */
    function Tame_isAppFramework(): bool
    {
        return Tame::isAppFramework();
    }
}

/**
 * Cache framework status check to prevent repeated executions.
 */
$Tame_isAppFramework = function_exists('Tame_isAppFramework') ? Tame_isAppFramework() : false;


if (! function_exists('Tame')) {
    /**
     * Get Tame instance.
     */
    function Tame(): Tame
    {
        return new Tame();
    }
}

if (! function_exists('TameMail')) {
    /**
     * Get Mailer instance.
     */
    function TameMail(): Mail
    {
        return new Mail();
    }
}

if (! function_exists('TameEnv')) {
    /**
     * Get Env instance.
     * 
     * @param string|null $path
     */
    function TameEnv(?string $path = null): Env
    {
        return new Env($path);
    }
}

if (! function_exists('TameCookie')) {
    /**
     * Get Cookie instance.
     */
    function TameCookie(): Cookie
    {
        return new Cookie();
    }
}

if (! function_exists('TameTime')) {
    /**
     * Get Time instance.
     *
     * @param int|string|null $time
     * @param string|null $timezone
     */
    function TameTime($time = null, ?string $timezone = null): Time
    {
        return new Time($time, $timezone);
    }
}

if (! function_exists('TameStr')) {
    /**
     * Get Str instance.
     */
    function TameStr(): Str
    {
        return new Str();
    }
}

if (! function_exists('TameSanitizer')) {
    /**
     * Get TextSanitizer instance.
     */
    function TameSanitizer(): TextSanitizer
    {
        return new TextSanitizer();
    }
}

if (! function_exists('TameUtility')) {
    /**
     * Get Utility instance.
     * 
     * @param string|null $text
     */
    function TameUtility(?string $text = null): Utility
    {
        return new Utility($text);
    }
}

if (! function_exists('TameCountry')) {
    /**
     * Get Country instance.
     */
    function TameCountry(): Country
    {
        return new Country();
    }
}

if (! function_exists('NumberToWords')) {
    /**
     * Get NumberToWords instance.
     */
    function NumberToWords(): NumberToWords
    {
        return new NumberToWords();
    }
}

if (! function_exists('TamePDF')) {
    /**
     * Get PDF instance.
     */
    function TamePDF(): PDF
    {
        return new PDF();
    }
}

if (! function_exists('TameZip')) {
    /**
     * Get Zip instance.
     * 
     * @param string|null $sourcePath The source path
     * @param string|null $archivePath The path to the archive file
     */
    function TameZip(?string $sourcePath = null, ?string $archivePath = null): Zip
    {
        return new Zip($sourcePath, $archivePath);
    }
}

if (! function_exists('TameQR')) {
    /**
     * Get QRCode instance.

     * @param string|null $path
     */
    function TameQR(?string $path = null): QRCode
    {
        return new QRCode($path);
    }
}

if (! function_exists('TameTotp')) {
    /**
     * Get TOTP instance.
     *
     * @param int $digits Length of the OTP output code (default: 6).
     * @param int $period Interval window in seconds (default: 30).
     * @param 'sha1'|'sha256'|'sha512' $algorithm HMAC hashing algorithm (default: 'sha1').
     * @return TOTP
     */
    function TameTotp(int $digits = 6, int $period = 30, string $algorithm = 'sha1')
    {
        return new TOTP($digits, $period, $algorithm);
    }
}

if (! function_exists('TameRecoveryKey')) {
    /**
     * Get RecoveryKey instance.
     */
    function TameRecoveryKey(): RecoveryKey
    {
        return new RecoveryKey();
    }
}

if (! function_exists('FileCache')) {
    /**
     * Get FileCache instance.
     */
    function FileCache(): FileCache
    {
        return new FileCache();
    }
}

if (! function_exists('TameFileBag')) {
    /**
     * Get FileBag instance.
     *
     * @param array<string, mixed>|null $collection
     */
    function TameFileBag(?array $collection = null): FileBag
    {
        return new FileBag($collection);
    }
}

if (! function_exists('TameExchange')) {
    /**
     * Get Exchange instance.
     */
    function TameExchange(): Exchange
    {
        return new Exchange();
    }
}

if (! function_exists('urlHelper')) {
    /**
     * Native HTTP Request accessor.
     */
    function urlHelper(): HttpRequest
    {
        return new HttpRequest();
    }
}

// Lightweight accessors (do not conflict with frameworks)
if (! function_exists('TameRequest')) {
    /**
     * Native HTTP Request accessor.
     */
    function TameRequest(): HttpRequest
    {
        return new HttpRequest();
    }
}

if (! function_exists('TameCollect')) {
    /**
     * Get Collection instance.
     *
     * @param mixed $items
     */
    function TameCollect($items = []): Collection
    {
        return new Collection($items);
    }
}

if (! function_exists('tcollect')) {
    /**
     * Get Collection instance.
     *
     * @param mixed $items
     */
    function tcollect($items = []): Collection
    {
        return new Collection($items);
    }
}

if (! function_exists('toptional')) {
    /**
     * Get Collection wrapped optionally.
     *
     * @param mixed $items
     * @return Collection|mixed
     */
    function toptional($items = [])
    {
        if(!is_array($items) && !is_null($items)){
            $items = Server::toArray($items);
        }

        return new Collection($items);
    }
}

if (! function_exists('tmanager')) {
    /**
     * Get Manager instance.
     */
    function tmanager(): Manager
    {
        return new Manager();
    }
}

if (! $Tame_isAppFramework && ! function_exists('bcrypt')) {
     /**
     * Password Encrypter.
     * 
     * @param string $password 
     */
    function bcrypt($password): string
    {
        return Hash::make($password);
    }
}

if (! function_exists('server')) {
    /**
     * Get Server instance.
     */
    function server(): Server
    {
        return new Server();
    }
}

if (! function_exists('autoload_register')) {
    /**
     * Autoload function to load classes and files in a given directory.
     *
     * @param string|array $directory
     */
    function autoload_register($directory): void
    {
        AutoloadRegister::load($directory);
    }
}

if (! $Tame_isAppFramework && ! function_exists('config')) {
    /**
     * Get configuration option value.
     *
     * @param mixed $key  Supports dot notation (e.g., 'database.connections.mysql')
     * @param mixed $default
     * @return mixed
     */
    function config($key, $default = null)
    {
        return Server::config($key, $default);
    }
}

if (! $Tame_isAppFramework && ! function_exists('env')) {
    /**
     * Get Environment value.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function env($key = null, $default = null)
    {
        return Env::env($key, $default);
    }
}

if (! function_exists('env_update')) {
    /**
     * Update Environment variables.
     * 
     * @param string|null $key
     * @param mixed $value
     * @param bool|null $quote   (Default: true)
     * @param bool|null $space   (Default: false) Allow space between key and value
     * @return bool
     */
    function env_update($key = null, $value = null, ?bool $quote = true, ?bool $space = false)
    {
        return Env::updateENV($key, $value, $quote, $space);
    }
}

if (! function_exists('tview')) {
    /**
     * View Template Engine.
     * 
     * @param string|null $viewPath
     * @param array $data
     */
    function tview($viewPath = null, $data = []): View
    {
        return new View($viewPath, $data);
    }
}

if (! function_exists('tasset')) {
    /**
     * Get asset URL.
     * 
     * @param string $asset
     * @param bool $cache   Whether to use cache-busting (default: null)
     * @param 'absolute'|'relative'|bool|null $type ( default: null)
     */
    function tasset($asset, $cache = null, $type = null): string
    {
        return Asset::asset($asset, $cache, $type);
    }
}

if (! function_exists('config_asset')) {
    /**
     * Configure asset options.
     * 
     * @param string|null $path
     * @param bool $cache   Whether to use cache-busting (default: false)
     * @param 'absolute'|'relative'|bool|null $type (default: false)
     */
    function config_asset($path = null, $cache = false, $type = false): void
    {
        Asset::config($path, $cache, $type);
    }
}

if (! function_exists('config_time')) {
    /**
     * Configure time options.
     * 
     * @param array|null $options
     */
    function config_time(?array $options = []): void
    {
        Time::config($options);
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

        return Translator::trans($key, $locale, $base_folder);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get Base Directory Path.
     * 
     * @param string|null $path
     */
    function base_path($path = null): string
    {
        return Server::formatWithBaseDirectory($path);
    }
}

if (! function_exists('directory')) {
    /**
     * Get Base Directory Path.
     * 
     * @param string|null $path
     */
    function directory($path = null): string
    {
        return base_path($path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get Storage Directory Path.
     * 
     * @param string|null $path
     */
    function storage_path($path = null): string
    {
        return base_path("storage/{$path}");
    }
}

if (! function_exists('public_path')) {
    /**
     * Get Public Directory Path.
     * 
     * @param string|null $path
     */
    function public_path($path = null): string
    {
        return base_path("public/{$path}");
    }
}

if (! function_exists('database_path')) {
    /**
     * Get Database Directory Path.
     * 
     * @param string|null $path
     */
    function database_path($path = null): string
    {
        return base_path("database/{$path}");
    }
}

if (! function_exists('app_path')) {
    /**
     * Get App Directory Path.
     * 
     * @param string|null $path
     */
    function app_path($path = null): string
    {
        return base_path("app/{$path}");
    }
}

if (! function_exists('config_path')) {
    /**
     * Get Config Directory Path.
     * 
     * @param string|null $path
     */
    function config_path($path = null): string
    {
        return base_path("config/{$path}");
    }
}

if (! function_exists('lang_path')) {
    /**
     * Get Language Directory Path.
     * 
     * @param string|null $path
     */
    function lang_path($path = null): string
    {
        return base_path("lang/{$path}");
    }
}

if (! function_exists('domain')) {
    /**
     * Get Domain URL URI.
     * 
     * @param string|null $path
     */
    function domain($path = null): string
    {
        return Server::formatWithDomainURI($path);
    }
}

if (! function_exists('to_array')) {
    /**
     * Convert Value to Array
     * 
     * @param  mixed $value
     */ 
    function to_array($value): array
    {
        return Server::toArray($value);
    }
}

if (! function_exists('to_object')) {
    /**
     * Convert Value to Object
     * 
     * @param  mixed $value
     */ 
    function to_object($value): object
    {
        return Server::toObject($value);
    }
}

if (! function_exists('to_json')) {
    /**
     * Convert value to JSON string.
     * 
     * @param  mixed $value
     */ 
    function to_json($value): string
    {
        return Server::toJson($value);
    }
}

if (! $Tame_isAppFramework && ! function_exists('dump')) {
    /**
     * Dump data.
     *
     * @param mixed ...$data
     */ 
    function dump(...$data): void
    {
        Server::dump($data);
    }
}

if (! $Tame_isAppFramework && ! function_exists('dd')) {
    /**
     * Dump data and die.
     * 
     * @param mixed ...$data
     */ 
    function dd(...$data): void
    {
        dump($data);
        exit(1);
    }
}
