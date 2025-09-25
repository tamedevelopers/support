<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Time;

final class Cookie{
    
    /** 
     * Site name 
     * @var string
     */
    protected static $name;
    
    /** 
     * Expire Cookie name
     * @var string
     */
    protected static $expireName;

    /** @var string Time Cookie name */
    protected static $timeName;

    /** 
     * Time format
     * @var string
     */
    protected static $timeFormat;

    /** 
     * Expire time format
     * @var mixed
     */
    protected static $expireFormat;

    /** 
     * Queued cookies
     * @var mixed
     */
    protected static $queued;
    
    /** 
     * Initialize site-specific cookie names and formats.
     */
    protected static function init(): static
    {
        self::$name         = Str::lower(Str::replace([' '], '', env('APP_NAME', '')));
        self::$timeName     = "__time_" . self::$name;
        self::$expireName   = "__expire_" . self::$name;
        self::$timeFormat   = Time::timestamp('next year', 'Y-m-d');
        self::$expireFormat = Time::timestamp('last year', 'Y-m-d');

        return new self();
    }

    /**
     * Create a cookie.
     *
     * @param mixed       $name     Cookie name.
     * @param mixed       $value    Cookie value.
     * @param int|string  $minutes  Expiration in minutes or strtotime string (0 = session).
     * @param string|null $path     Path where the cookie is available (default: '/').
     * @param string|null $domain   Domain for the cookie (e.g. '.example.com').
     * @param bool|null   $secure   Send only over HTTPS.
     * @param bool|null   $httponly Accessible only through HTTP (not JS).
     * @param bool|null   $force    Force setting even if headers are already sent.
     * 
     * @return void
     */
    public static function set($name, $value = null, $minutes = 0, $path = null, $domain = null, $secure = null, $httponly = null, $force = null)
    {
        // minutes
        $expires = self::minutesToExpire($minutes);

        // create default values
        [$path, $value, $domain, $secure, $httponly, $force] = self::getDefaultPathAndDomain(
            $path, $value, $domain, $secure, $httponly, $force
        );

        // Set the cookie if headers not sent or forced
        if (!headers_sent() || $force === true) {
            $name = self::getName($name);
            $value = self::getValue($value);

            self::normalizeCookie($name, $value, $expires, $path, $domain, $secure, $httponly);
        }
    }

    /**
     * Expire the given cookie.
     *
     * @param mixed $name
     * @return void
     */
    public static function forget($name)
    {
        self::set($name, null, -2628000); // 5 years ago
    }

    /**
     * Expire the given cookie.
     *
     * @param  mixed  $name
     * @return void
     */
    public static function expire($name)
    {
        self::forget($name);
    }

    /** 
     * Set Cookie Time
     */
    public static function setTime(): void
    {
        self::init()->set(self::$timeName, self::$timeFormat);
    }

    /** 
     * Set Cookie Expiration Time
     */
    public static function setExpire(): void
    {
        self::init()->set(self::$expireName, self::$expireFormat);
    }

    /** 
     * Get Time Data
     */
    public static function getTime(): mixed
    {
        return self::init()->get(self::$timeName);
    }

    /** 
     * Get Expire Time Data
     */
    public static function getExpire(): mixed
    {
        return self::init()->get(self::$expireName);
    }

    /** 
     * Cookie has name that exists
     * 
     * @param mixed $name
     * @return bool
     */
    public static function has($name = null)
    {
        return isset($_COOKIE[self::getName($name)]);
    }

    /** 
     * Get cookie 
     * 
     * @param mixed $name
     * @param mixed $default
     * @return mixed
     */
    public static function get($name = null, $default = null)
    {
        return $_COOKIE[self::getName($name)] ?? $default;
    }

    /** 
     * Get all cookie 
     * 
     * @param mixed $name
     * @return mixed
     */
    public static function all($name = null)
    {
        return self::get($name) ?? $_COOKIE;
    }

    /**
     * Get the cookie value and immediately delete it.
     * 
     * @param mixed $name
     * @param mixed $default
     * @return mixed
     */
    public static function pull($name, $default = null)
    {
        $value = self::get($name, $default);
        self::forget($name);
        return $value;
    }

    /**
     * Set a short-lived cookie for the next request only.
     * Typically used for flash messages.
     *
     * @param mixed $name
     * @param mixed $value
     */
    public static function flash($name, $value): void
    {
        self::set($name, $value, '+1 minute');
    }

    /**
     * Queue a cookie to be set later.
     * Useful in response middleware before headers are sent.
     *
     * @param mixed $name
     * @param mixed $value
     * @param int|string $minutes
     * @param string|null $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool|null $httponly
     * @param bool|null   $force    Force setting even if headers are already sent.
     * @return void
     */
    public static function queue($name, $value, $minutes = 0, $path = null, $domain = null, $secure = null, $httponly = null, $force = null)
    {
        self::$queued[] = compact(
            'name', 'value', 'minutes', 'path', 'domain', 'secure', 'httponly', 'force'
        );
    }

    /**
     * Apply all queued cookies by sending them to the client,
     * then clear the queue.
     */
    public static function setQueue(): void
    {
        foreach (self::$queued as $cookie) {
            self::set(
                $cookie['name'],
                $cookie['value'],
                $cookie['minutes'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly'],
                $cookie['force']
            );
        }
        self::$queued = [];
    }

    /**
     * Flush (clear) all cookies.
     * Removes everything in $_COOKIE by expiring them.
     */
    public static function flush(): void
    {
        foreach ($_COOKIE as $name => $value) {
            self::forget($name);
        }
    }

    /** 
     * Normalize a value into a string. 
     * 
     * @param mixed $name
     * @return string 
     */
    private static function getName($name = null)
    {
        if (is_array($name)) {
            return (string) (Str::head($name) ?? '');
        }

        if (is_object($name)) { 
            $array = Server::toArray($name); 
            return (string) (Str::last($array) ?? ''); 
        }

        return (string) $name;
    }
    
    /**
     *  Normalize a value into a string.
     *
     * @param  mixed $value
     * @return string
     */
    private static function getValue($value = null)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) $value;
    }

    /** 
     * Set minutes
     * 
     * @param int|string $minutes
     * @return int
     */
    private static function minutesToExpire($minutes = 0)
    {
        if (empty($minutes)) {
            return 0;
        }
        if (is_numeric($minutes)) {
            return time() + (((int) $minutes) * 60);
        }
        
        $ts = strtotime((string) $minutes);
        if ($ts === false || $ts === -1) {
            // Invalid timestamp format, default to 0 (end of session)
            return 0;
        }
        return (int) $ts;
    }
    
    /**
     * Normalize Cookie
     *
     * @param  mixed $name
     * @param  mixed $value
     * @param  mixed $expires
     * @param  mixed $path
     * @param  mixed $domain
     * @param  mixed $secure
     * @param  mixed $httponly
     * @return void
     */
    private static function normalizeCookie($name, $value, $expires, $path, $domain, $secure, $httponly)
    {
        [$expires, $path, $domain, $secure, $httponly] = [
            (int) $expires, (string) $path, (string) $domain, (bool) $secure, (bool) $httponly
        ];

        // PHP 7.3+ supports array options
        if (PHP_VERSION_ID >= 70300) {
            @setcookie($name, $value, [
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => 'Lax', // sensible default
            ]);
        } else {
            // fails (older PHP), fallback to legacy signature
            @setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
        }
    }

    /**
     * Get default path/domain and flags.
     *
     * @param  string|null  $path
     * @param  string|null  $value
     * @param  string|null  $domain
     * @param  bool|null  $secure
     * @param  bool|null  $httponly
     * @param  bool|null  $force
     * @return array
     */
    private static function getDefaultPathAndDomain($path = null, $value = null, $domain = null, $secure = null, $httponly = null, $force = null)
    {
        if(is_null($secure)){
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        }

        return [
            !empty($path) ? $path : '/', 
            !empty($value) ? $value : '', 
            !empty($domain) ? $domain : '', 
            is_bool($secure) ? $secure : false, 
            is_bool($httponly) ? $httponly : false,
            is_bool($force) ? $force : false,
        ];
    }
    
}