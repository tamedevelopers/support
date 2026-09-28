<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Closure;
use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Collections\Collection;

class Manager{
    
    /**
     * Regex pattern to target whitespace characters.
     */
    public static string $regex_whitespace = "/\s+/";

    /**
     * Regex pattern to target leading or trailing spaces/tabs from each line.
     */
    public static string $regex_lead_and_end = "/^[ \t]+|[ \t]+$/m";

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
     * Re-generate and persist a new APP_KEY in .env.
     */
    public static function regenerate(): string
    {
        $key = self::generate();

        Env::updateENV('APP_KEY', $key, false);

        return $key;
    }

    /**
     * Determine if debug mode is enabled.
     * 
     * @return bool
     */
    public static function AppDebug()
    {
        $value = $_ENV['APP_DEBUG'] 
            ?? getenv('APP_DEBUG') 
            ?? $_SERVER['APP_DEBUG'] 
            ?? true;

        return self::isEnvBool($value);
    }

    /**
     * Check if an environment variable value is boolean-like.
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
        return getenv($key) !== false || isset($_ENV[$key]) || isset($_SERVER[$key]);
    }

    /**
     * Set headers with response status code and execute an optional callback.
     *
     * @param  int $status
     * @param  Closure|null $closure
     * @param  bool $exit
     * @return void
     */
    public static function setHeaders($status = 404, $closure = null, bool $exit = true)
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        if(Tame::isClosure($closure)){
            $closure();
        }

        if ($exit) {
            exit(1);
        }
    }

    /**
     * Silently handles exceptions by disabling error reporting, setting a 404 response header,
     * and logging the error without exposing raw exception traces to the end user.
     *
     * @param \Throwable $throwable   The caught exception or error instance to handle.
     * @param  bool $exit
     * @param int        $error_level Optional PHP error level constant (defaults to E_USER_NOTICE).
     * 
     * @return void
     */
    public static function silentError($throwable, bool $exit = false, int $error_level = E_USER_NOTICE)
    {
        // Handle the exception silently (turn off error reporting)
        error_reporting(0);

        $description = '';

        if(!empty($throwable)){
            $collect = new Collection($throwable->getTrace());

            // Filter trace to include ONLY application files (strip out vendor noise)
            $appTrace = $collect
                ->filter(function ($item) {
                    return isset($item['file']) && str_contains($item['file'], 'app' . DIRECTORY_SEPARATOR);
                })
                ->take(5)
                ->map(function ($item, $index) {
                    // Make file path relative & concise (e.g. app\Services\Traits\OrderTrait.php:116)
                    $file       = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $item['file']);
                    $class      = isset($item['class']) ? class_basename($item['class']) . '->' : '';
                    $function   = $item['function'] ?? '';

                    return sprintf("#%d %s(%d): %s%s()", $index + 1, $file, $item['line'] ?? 0, $class, $function);
                })
                ->implode("\n");
            
            // Format a clean, human-readable error description
            $description = sprintf(
                "Error: %s\nLocation: %s (Line %d)\n\nApp Trace:\n%s",
                $throwable->getMessage(),
                str_replace(base_path() . DIRECTORY_SEPARATOR, '', $throwable->getFile()),
                $throwable->getLine(),
                $appTrace ?: "No app trace available."
            );
        }

        self::setHeaders(404, function() use($description, $error_level){
            Env::bootLogger();
            
            @trigger_error($description, $error_level);
        }, $exit);
    }

    /**
     * Collapse multiple consecutive whitespaces into a single space.
     * 
     * @param string $string
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
     * Remove leading and trailing spaces/tabs from each line in a string.
     * 
     * @param string $string
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
        $key = $_ENV['APP_KEY'] ?? getenv('APP_KEY') ?? '';
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

        if (!File::exists($path)) {
            return null;
        }

        $content = @File::get($path);

        return $content === false ? null : trim($content);
    }

    /**
     * Compute the fingerprint of a given key for integrity comparison.
     * - Uses sha256 over the full key string (including the base64: prefix)
     */
    protected static function fingerprint(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * Abort the request with HTTP 500 until a valid key is regenerated.
     * - Use Manager::regenerate() or the helper tmanager()->regenerate() to fix
     */
    protected static function denyUntilRegenerated(): void
    {
        self::setHeaders(500, function () {
            echo sprintf('Application key is missing or invalid. Please run %s to generate a new key.', "tmanager()->regenerate()");
        });
    }
}