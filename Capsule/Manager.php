<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;

class Manager{
    
    /**
     * Remove all whitespace characters
     * @var string
     */
    static public $regex_whitespace = "/\s+/";

    /**
     * Remove leading or trailing spaces/tabs from each line
     * @var string
     */
    static public $regex_lead_and_end = "/^[ \t]+|[ \t]+$/m";

    /**
     * Sample copy of env file
     * 
     * @return string
     */
    static public function envDummy()
    {
        return preg_replace("/^[ \t]+|[ \t]+$/m", "", 'APP_NAME="ORM Database"
            APP_ENV=local
            APP_KEY='. self::generate() .'
            APP_DEBUG=true
            SITE_EMAIL=
            
            DB_CONNECTION=mysql
            DB_HOST="127.0.0.1"
            DB_PORT=3306
            DB_USERNAME="root"
            DB_PASSWORD=
            DB_DATABASE=

            DB_CHARSET=utf8mb4
            DB_COLLATION=utf8mb4_general_ci

            MAIL_MAILER=smtp
            MAIL_HOST=
            MAIL_PORT=465
            MAIL_USERNAME=
            MAIL_PASSWORD=
            MAIL_ENCRYPTION=tls
            MAIL_FROM_ADDRESS="${MAIL_USERNAME}"
            MAIL_FROM_NAME="${APP_NAME}"

            AWS_ACCESS_KEY_ID=
            AWS_SECRET_ACCESS_KEY=
            AWS_DEFAULT_REGION=us-east-1
            AWS_BUCKET=
            AWS_URL=
            AWS_USE_PATH_STYLE_ENDPOINT=false
            
            CLOUDINARY_SECRET_KEY=
            CLOUDINARY_KEY=
            CLOUDINARY_NAME=
            CLOUDINARY_URL=
            CLOUDINARY_SECURE=false

            PUSHER_APP_ID=
            PUSHER_APP_KEY=
            PUSHER_APP_SECRET=
            PUSHER_HOST=
            PUSHER_PORT=443
            PUSHER_SCHEME=https
            PUSHER_APP_CLUSTER=mt1
        ');
    }

    /**
     * Generates an app KEY
     * 
     * @return string
     */
    static private function generate($length = 32)
    {
        $randomBytes = random_bytes($length);
        $appKey = 'base64:' . rtrim(strtr(base64_encode($randomBytes), '+/', '-_'), '=');
        $appKey = str_replace('+', '-', $appKey);
        $appKey = str_replace('/', '_', $appKey);

        // Generate a random position to insert '/'
        $randomPosition = random_int(0, strlen($appKey));
        $appKey         = substr_replace($appKey, '/', $randomPosition, 0);

        $appKey .= '=';

        return $appKey;
    }

    /**
     * Re-generate a new app KEY
     * 
     * @return void
     */
    static public function regenerate()
    {
        Env::updateENV('APP_KEY', self::generate(), false);
    }
    
    /**
     * App Debug
     * 
     * @return bool
     */
    static public function AppDebug()
    {
        return self::isEnvBool($_ENV['APP_DEBUG'] ?? true);
    }

    /**
     * Set env boolean value
     * @param string $value
     * 
     * @return mixed
     */
    static public function isEnvBool($value)
    {
        if(is_string($value)){
            return Str::lower($value) === 'true'
                    ? true
                    : false;
        }

        return $value;
    }

    /**
     * Check if environment key is set
     * @param string $key 
     * 
     * @return bool
     */
    static public function isEnvSet($key)
    {
        return isset($_ENV[$key]) ? true : false;
    }
    
    /**
     * Set headers with response code
     *
     * @param  mixed $status
     * @param  callable $function
     * @return void
     */
    static public function setHeaders($status = 404, callable $function = null)
    {
        // Set HTTP response status code to 404
        @http_response_code($status);

        if(is_callable($function)){
            $function();
        }

        // Exit with response 404
        exit(1);
    }

    /**
     * Remove whitespace from string
     * 
     * @param string $string
     * 
     * @return string
     */ 
    static public function replaceWhiteSpace(?string $string = null)
    {
        return trim(preg_replace(
            self::$regex_whitespace, 
            " ", 
            $string
        ));
    }

    /**
     * Remove leading and ending space from string
     * 
     * @param string $string
     * 
     * @return string
     */ 
    static public function replaceLeadEndSpace(?string $string = null)
    {   
        return preg_replace(self::$regex_lead_and_end, " ", $string);
    }

}