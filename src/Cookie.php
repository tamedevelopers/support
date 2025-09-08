<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Time;

class Cookie{
    
    /** 
     * Site name 
     * @var string
     */
    static protected $name;
    
    /** 
     * Expire Cookie name
     * @var string
     */
    static protected $expireName;

    /** 
     * Time Cookie name
     * @var string
     */
    static protected $timeName;

    /** 
     * Time format
     * @var string
     */
    static protected $timeFormat;

    /** 
     * Expire time format
     * @var mixed
     */
    static protected $expireFormat;
    
    /** 
     * Create Sitename From .env
     * 
     * @return $this
     */
    static protected function init()
    {
        self::$name         = Str::lower(Str::replace([' '], '', env('APP_NAME', '')));
        self::$timeName     = "__time_" . self::$name;
        self::$expireName   = "__expire_" . self::$name;
        self::$timeFormat   = Time::timestamp('next year', 'Y-m-d');
        self::$expireFormat = Time::timestamp('last year', 'Y-m-d');

        return new self();
    }

    /** 
     * Create Cookie
     * @param string $name
     * - Cookie Name
     * 
     * @param string|null $value
     * - Cookie Value
     * 
     * @param int|string $minutes
     * [optional] The time the cookie expires. 
     * This is a Unix timestamp so is in number of seconds since the epoch. 
     * In other words, you'll most likely set this with the time function plus the number of seconds before you want it to expire. 
     * Or you might use mktime. time()+606024*30 will set the cookie to expire in 30 days. 
     * If set to 0, or omitted, the cookie will expire at the end of the session (when the browser closes).
     * 
     * @param string|null $path
     * [optional] The path on the server in which the cookie will be available on. 
     * If set to '/', the cookie will be available within the entire domain.
     * 
     * @param string|null $domain
     * [optional] The domain that the cookie is available. 
     * To make the cookie available on all subdomains of example.com then you'd set it to '.example.com'.
     * 
     * @param bool|null $secure
     * [optional] Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * 
     * @param bool|null $httponly 
     * [optional] When true the cookie will be made accessible only through the HTTP protocol. 
     * 
     * @return void
     * If output exists prior to calling this function, setcookie will fail and return false. 
     * If setcookie successfully runs, it will return true. 
     * This does not indicate whether the user accepted the cookie.
     */
    public static function set($name, $value = null, $minutes = 0, $path = null, $domain = null, $secure = null, $httponly = null, $force = null)
    {
        // minutes
        $expires = self::minutesToExpire($minutes);

        // create default values
        [$path, $value, $domain, $secure, $httponly, $force] = self::getDefaultPathAndDomain(
            $path, $value, $domain, $secure, $httponly, $force
        );

        // Prefer new setcookie signature with array options (PHP 7.3+), fallback otherwise
        if (!headers_sent() || $force === true) {
            $options = [
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain ?: '',
                'secure'   => (bool) $secure,
                'httponly' => (bool) $httponly,
                'samesite' => 'Lax', // sensible default
            ];

            // Try modern signature; if it fails (older PHP), fallback to legacy
            try {
                if (PHP_VERSION_ID >= 70300) {
                    @setcookie($name, (string) $value, $options);
                } else {
                    @setcookie($name, (string) $value, (int) $expires, (string) $path, (string) $domain, (bool) $secure, (bool) $httponly);
                }
            } catch (\Throwable $e) {
                @setcookie($name, (string) $value, (int) $expires, (string) $path, (string) $domain, (bool) $secure, (bool) $httponly);
            }
        }
    }

    /**
     * Expire the given cookie.
     *
     * @param  string  $name
     * @param  string|null  $path
     * @param  string|null  $domain
     * @return void
     */
    public static function forget($name, $path = null, $domain = null)
    {
        self::set(
            name: $name,
            minutes: 'last year', 
            path: $path, 
            domain: $domain,
            force: true
        );
    }

    /**
     * Expire the given cookie.
     *
     * @param  string  $name
     * @param  string|null  $path
     * @param  string  $domain
     * @return void
     */
    public static function expire($name, $path = null, $domain = null)
    {
        self::forget($name, $path, $domain);
    }

    /** 
     * Set Cookie Time
     * 
     * @return void
     */
    public static function setTime()
    {
        self::init()->set(self::$timeName, self::$timeFormat);
    }

    /** 
     * Set Cookie Expiration Time
     * 
     * @return void
     */
    public static function setExpire()
    {
        self::init()->set(self::$expireName, self::$expireFormat);
    }

    /** 
     * Get Time Data
     * 
     * @return mixed
     */
    public static function getTime()
    {
        return self::init()->get(self::$timeName);
    }

    /** 
     * Get Expire Time Data
     * 
     * @return mixed
     */
    public static function getExpire()
    {
        return self::init()->get(self::$expireName);
    }

    /** 
     * Cookie has name that exists
     * 
     * @param string $name
     * - Cookie name
     * 
     * @return bool
     */
    public static function has($name = null)
    {
        return isset($_COOKIE[(string) $name]);
    }

    /** 
     * Get cookie 
     * @param string $name
     * - Cookie name
     * 
     * @return mixed
     */
    public static function get($name = null)
    {
        return self::has($name) ? $_COOKIE[(string) $name] : null;
    }

    /** 
     * Get all cookie 
     * @param string $name
     * 
     * [optional] Cookiename or return all cookies
     * 
     * @return mixed
     */
    public static function all($name = null)
    {
        return self::get($name) ?? $_COOKIE;
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