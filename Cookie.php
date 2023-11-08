<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

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
        self::$name         = strtolower(str_replace([' '], '', env('APP_NAME', '')));
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
    static public function set($name, $value = null, $minutes = 0, $path = null, $domain = null, $secure = null, $httponly = null, $force = null)
    {
        // minutes
        $minutes = self::minutesToExpire($minutes);

        // create default values
        [$path, $value, $domain, $secure, $httponly, $force] = self::getDefaultPathAndDomain($path, $value, $domain, $secure, $httponly, $force);

        // set cookie
        if ( !headers_sent() || $force === true) {
            @setcookie($name, $value, $minutes, $path, $domain, $secure, $httponly);
        }
    }

    /**
     * Expire the given cookie.
     *
     * @param  string  $name
     * @param  string|null  $path
     * @param  string  $domain
     * @return void
     */
    static public function forget($name, $path = null, $domain = null)
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
    static public function expire($name, $path = null, $domain = null)
    {
        self::forget($name, $path, $domain);
    }

    /** 
     * Set Cookie Time
     * 
     * @return void
     */
    static public function setTime()
    {
        self::init()
            ->set(self::$timeName, self::$timeFormat);
    }

    /** 
     * Set Cookie Expiration Time
     * 
     * @return void
     */
    static public function setExpire()
    {
        self::init()
            ->set(self::$expireName, self::$expireFormat);
    }

    /** 
     * Get Time Data
     * 
     * @return mixed
     */
    static public function getTime()
    {
        return self::init()->get(self::$timeName);
    }

    /** 
     * Get Expire Time Data
     * 
     * @return mixed
     */
    static public function getExpire()
    {
        return self::init()->get(self::$expireName);
    }

    /** 
     * Cookie has value  
     * @param string $name
     * - Cookie name
     * 
     * @return bool
     */
    static public function has($name = null)
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
    static public function get($name = null)
    {
        return self::has($name)
                ? $_COOKIE[(string) $name]
                : null;
    }

    /** 
     * Get all cookie 
     * @param string $name
     * 
     * [optional] Cookiename or return all cookies
     * 
     * @return mixed
     */
    static public function all($name = null)
    {
        return self::get($name) ?? $_COOKIE;
    }

    /** 
     * Set minutes
     * 
     * @param int|string $minutes
     * @return int
     */
    static private function minutesToExpire($minutes = 0)
    {
        // options
        if(empty($minutes)){
            $minutes = 0;
        } elseif(is_numeric($minutes)){
            $minutes = time() + (((int) $minutes) * 60);
        } else{
            $minutes = strtotime($minutes);
            if ($minutes === false || $minutes === -1) {
                // Invalid timestamp format, default to 0 (end of session)
                return 0;
            }
        }

        return (int) $minutes;
    }

    /**
     * Get the path and domain, or the default values.
     *
     * @param  string|null  $path
     * @param  string|null  $value
     * @param  string|null  $domain
     * @param  bool|null  $secure
     * @param  bool|null  $httponly
     * @param  bool|null  $force
     * @return array
     */
    static private function getDefaultPathAndDomain($path = null, $value = null, $domain = null, $secure = null, $httponly = null, $force = null)
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