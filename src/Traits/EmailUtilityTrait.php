<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Capsule\File;


/**
 * @property array $providers
 */
trait EmailUtilityTrait{
    
    /**
     * Masks email characters in a string.
     *
     * @param string|null $email 
     * - The string to be masked.
     * 
     * @param int $length 
     * - The number of visible characters. Default is 4.
     * 
     * @param string $position 
     * - The position to apply the mask: 'left', 'middle' or 'center', 'right'. Default is 'right'.
     * 
     * @param string $mask 
     * - The character used for masking. Default is '*'.
     * 
     * @return string 
     * - The masked string.
     */
    public static function maskEmail($email = null, $length = 6, $position = 'right', $mask = '*')
    {
        $emalPosition = mb_strrpos($email, "@", 0, 'UTF-8');
        
        if ($emalPosition !== false) {
            [$local, $domain] = explode('@', $email, 2);
            $domain = "@{$domain}";
        } else {
            // Invalid email (no @ found)
            $local = $email;
            $domain = "@" . str_repeat($mask, 4);
        }

        return Str::mask($local, $length, $position, $mask) . $domain;
    }

    /**
     * Validate an email address.
     *
     * @param string|null $email 
     * - The email address to validate.
     *
     * @param bool $use_internet 
     * - By default is set to false, Which uses the checkdnsrr() and getmxrr()
     * To validate valid domain emails
     *
     * @param bool $server_verify 
     * - Verify Mail Server
     * 
     * @return bool 
     * - Whether the email address is valid (true) or not (false).
     */
    public static function validateEmail($email = null, $use_internet = false, $server_verify = false) 
    {
        return Tame::emailValidator($email, $use_internet, $server_verify);
    }

    /**
     * Normalize an email address using rules from `emailProviders` file.
     * 
     * @return string|null
     */
    public static function normalizeEmail(string $email, bool $lowercaseLocal = false)
    {
        $email = trim($email);
        if ($email === '' || strpos($email, '@') === false) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);
        $domain = mb_strtolower($domain);

        self::loadProviders();

        if (isset(self::$providers[$domain])) {
            $rules = self::$providers[$domain];

            if (!empty($rules['strip_plus'])) {
                $plusPos = strpos($local, '+');
                if ($plusPos !== false) {
                    $local = substr($local, 0, $plusPos);
                }
            }

            if (!empty($rules['strip_dots'])) {
                $local = str_replace('.', '', $local);
            }
        }

        if ($lowercaseLocal) {
            $local = mb_strtolower($local);
        }

        return $local . '@' . $domain;
    }

    /**
     * Compare two emails after normalization.
     *
     * @param string $email
     * - First email address to compare.
     *
     * @param string $second_email
     * - Second email address to compare.
     *
     * @param bool $lowercaseLocal
     * - If true, local part will be converted to lowercase. Default is false.
     * @return bool
     */
    public static function equalsEmail(string $email, string $second_email, bool $lowercaseLocal = false)
    {
        return self::normalizeEmail($email, $lowercaseLocal) === self::normalizeEmail($second_email, $lowercaseLocal);
    }

    /**
     * Load providers config from `emailProviders` file.
     * @return void
     */
    protected static function loadProviders()
    {
        if (!empty(self::$providers)) {
            return; // already loaded
        }

        $file = __DIR__ . '/../stubs/emailProviders.php';
        if (File::exists($file)) {
            self::$providers = require $file; // this directly returns the array
        }
    }
    
}