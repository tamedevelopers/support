<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

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
    static protected $expire;

    /** 
     * Time Cookie name
     * @var string
     */
    static protected $time;
    
    /** 
     * Create Sitename From .env
     * - APP_NAME
     * 
     * @return void
     */
    static protected function init()
    {
        self::$name     = strtolower(str_replace([' '], '', $_ENV['APP_NAME']));
        self::$time     = "__time_" . self::$name;
        self::$expire   = "__expire_" . self::$name;
    }

    /** 
     * Create Cookie
     * @param string $name
     * - Cookie Name
     * 
     * @param string 
     * - Cookie Value
     * 
     * @param int|string|null $expires_or_options
     * [optional] The time the cookie expires. 
     * This is a Unix timestamp so is in number of seconds since the epoch. 
     * In other words, you'll most likely set this with the time function plus the number of seconds before you want it to expire. 
     * Or you might use mktime. time()+606024*30 will set the cookie to expire in 30 days. 
     * If set to 0, or omitted, the cookie will expire at the end of the session (when the browser closes).
     * 
     * @param string $path
     * [optional] The path on the server in which the cookie will be available on. 
     * If set to '/', the cookie will be available within the entire domain.
     * 
     * @param string $domain
     * [optional] The domain that the cookie is available. 
     * To make the cookie available on all subdomains of example.com then you'd set it to '.example.com'.
     * 
     * @param bool $secure
     * [optional] Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * 
     * @param bool $httponly 
     * [optional] When true the cookie will be made accessible only through the HTTP protocol. 
     * 
     * @return void
     * If output exists prior to calling this function, setcookie will fail and return false. 
     * If setcookie successfully runs, it will return true. 
     * This does not indicate whether the user accepted the cookie.
     */
    static public function set(
        ?string $name, 
        ?string $value, 
        int|string|null $expires_or_options = 0,
        ?string $path = '/', 
        ?string $domain = "", 
        ?bool $secure = false, 
        ?bool $httponly = false
    )
    {
        // options
        if(is_null($expires_or_options)){
            $expires_or_options = 0;
        }

        // expiration
        $expires = $expires_or_options;
        if (!is_int($expires)){
            if(is_null($expires)){
                $expires = strtotime('now +10 minutes');
            } else{
                $expires = strtotime($expires);
            }
        }

        // set cookie
        if (!headers_sent()) {
            setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
        }
    }

    /** 
     * Set Cookie Time
     * 
     * @return void
     */
    static public function setTime()
    {
        self::init();
        self::set(self::$time, date("Y-m-d", strtotime('next year')));
    }

    /** 
     * Set Cookie Expiration Time
     * 
     * @return void
     */
    static public function setExpire()
    {
        self::init();
        self::set(self::$expire, date("Y-m-d", strtotime('last year')));
    }

    /** 
     * Get Time Data
     * 
     * @return mixed
     */
    static public function getTime()
    {
        self::init();
        return self::get(self::$time);
    }

    /** 
     * Get Expire Time Data
     * 
     * @return mixed
     */
    static public function getExpire()
    {
        self::init();
        return self::get(self::$expire);
    }

    /** 
     * Cookie has value  
     * @param string $name
     * - Cookie name
     * 
     * @return bool
     */
    static public function has(?string $name = null)
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
    static public function get(?string $name = null)
    {
        return self::has($name) 
                ? $_COOKIE[$name]
                : null;
    }
    
}