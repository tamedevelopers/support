<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Capsule\File;

class Manager{
    
    /**
     * Remove all whitespace characters
     * @var string
     */
    public static $regex_whitespace = "/\s+/";

    /**
     * Remove leading or trailing spaces/tabs from each line
     * @var string
     */
    public static $regex_lead_and_end = "/^[ \t]+|[ \t]+$/m";

    /**
     * Sample copy of env file
     * 
     * @return string
     */
    public static function envDummy()
    {
        $key = self::generate();

        return preg_replace("/^[ \t]+|[ \t]+$/m", "", 'APP_NAME="ORM Database"
            APP_ENV=local
            APP_KEY='. $key .'
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
     * Generate an application key (Laravel-style).
     *
     * - 32 bytes of cryptographically secure random data
     * - Base64 encoded and prefixed with "base64:"
     */
    public static function generate(int $bytes = 32): string
    {
        $random = random_bytes($bytes);
        return 'base64:' . base64_encode($random);
    }
    
    /**
     * Ensures that the environment is started if it has not been initialized yet.
     *
     * Kept minimal for package usage to avoid runtime overhead.
     */
    public static function startEnvIFNotStarted(): void
    {
        if (!Env::isEnvStarted()) {
            Env::createOrIgnore();
            Env::load();
        }
    }

    /**
     * Re-generate and persist a new APP_KEY in .env (no quotes), then reload env.
     */
    public static function regenerate(): void
    {
        // generate new key
        $key = self::generate();

        // update env key
        Env::updateENV('APP_KEY', $key, false);

        // Update stored key reference for tamper detection
        // self::storeKeyFingerprint($key);
    }

    /**
     * App Debug
     * 
     * @return bool
     */
    public static function AppDebug()
    {
        return self::isEnvBool($_ENV['APP_DEBUG'] ?? true);
    }

    /**
     * Check if environment variable value is boolean-like.
     * 
     * @param mixed $value
     * @return bool
     */
    public static function isEnvBool($value)
    {
        if(is_string($value)){
            return in_array(Str::lower($value), ['true', '1', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    /**
     * Check if an environment variable is set.
     * 
     * @param string $key 
     * @return bool
     */
    public static function isEnvSet($key)
    {
        return getenv($key) !== false || isset($_ENV[$key]);
    }

    /**
     * Set headers with response code
     *
     * @param  mixed $status
     * @param  Closure|null $closure
     * @return void
     */
    public static function setHeaders($status = 404, $closure = null)
    {
        // Set HTTP response status code to 404
        @http_response_code($status);

        if(Tame::isClosure($closure)){
            $closure();
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
    public static function replaceWhiteSpace(?string $string = null)
    {
        return Str::trim(preg_replace(
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
    public static function replaceLeadEndSpace(?string $string = null)
    {   
        return preg_replace(self::$regex_lead_and_end, " ", $string);
    }

    /**
     * Ensure APP_KEY exists and matches stored fingerprint.
     * Note: kept protected for potential framework usage; not enforced at runtime in package.
     */
    protected static function ensureAppKeyOrFail(): void
    {
        $key = $_ENV['APP_KEY'] ?? '';
        if (!self::isValidAppKey($key)) {
            return; // no enforcement in package runtime
        }

        $fingerprint = self::readKeyFingerprint();
        if ($fingerprint === null) {
            self::storeKeyFingerprint($key);
            return;
        }

        if (!hash_equals($fingerprint, self::fingerprint($key))) {
            return; // no enforcement in package runtime
        }
    }

    /**
     * Validate APP_KEY format (Laravel style: base64: + 32 bytes encoded)
     */
    protected static function isValidAppKey(?string $key): bool
    {
        if (!is_string($key) || $key === '') {
            return false;
        }
        if (!str_starts_with($key, 'base64:')) {
            return false;
        }
        $raw = substr($key, 7);
        $decoded = base64_decode($raw, true);
        return $decoded !== false && strlen($decoded) === 32;
    }

    /**
     * Persist a fingerprint of the app key outside of .env to detect manual edits.
     */
    protected static function storeKeyFingerprint(string $key): void
    {
        $path = Env::formatWithBaseDirectory('storage/app_key');

        File::put($path, self::fingerprint($key));
    }

    /**
     * Read the stored app key fingerprint from storage/app_key.
     *
     * - Returns null if the fingerprint file does not exist yet (first boot)
     * - Returns the trimmed fingerprint string when present
     */
    protected static function readKeyFingerprint(): ?string
    {
        $path = Env::formatWithBaseDirectory('storage/app_key');
        if (!File::exists($path)) return null;
        $c = @File::get($path);
        return $c === false ? null : trim($c);
    }

    /**
     * Compute the fingerprint of a given key for integrity comparison.
     *
     * - Uses sha256 over the full key string (including the base64: prefix)
     */
    protected static function fingerprint(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * Abort the request with HTTP 500 until a valid key is regenerated.
     *
     * - Use Manager::regenerate() or the helper tmanager()->regenerate() to fix
     */
    protected static function denyUntilRegenerated(): void
    {
        self::setHeaders(500, function () {
            echo sprintf('Application key is missing or invalid. Please run %s to generate a new key.', "tmanager()->regenerate()");
        });
    }
}