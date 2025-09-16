<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Collections\Collection;


/**
 * @property array $providers
 * @property array $providersChildren
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
     * @param string $email
     * @param bool $lowercaseLocal
     * - If true, local part will be converted to lowercase. Default is false.
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

        // load children data
        self::loadProvidersChildren();

        if (isset(self::$providersChildren[$domain])) {
            $rules = self::$providersChildren[$domain];

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
     * 
     * @return bool
     */
    public static function equalsEmail(string $email, string $second_email, bool $lowercaseLocal = false)
    {
        return self::normalizeEmail($email, $lowercaseLocal) === self::normalizeEmail($second_email, $lowercaseLocal);
    }

    // --- Specific provider families ---
    public static function isGmail(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['gmail']));
    }

    public static function isOutlook(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['outlook']));
    }

    public static function isIcloud(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['icloud']));
    }

    public static function isFastmail(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['fastmail']));
    }

    public static function isProtonmail(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['protonmail']));
    }

    public static function isZoho(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['zohomail']));
    }

    public static function isYandex(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['yandex']));
    }

    public static function isGmx(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['gmx']));
    }

    public static function isMailCom(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['gmx']));
    }

    public static function isMailboxOrg(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['focused']));
    }

    public static function isPosteo(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['focused']));
    }

    public static function isRunbox(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['focused']));
    }

    public static function isStartmail(string $email): bool
    {
        self::loadProviders();
        return self::isDomainIn($email, array_keys(self::$providers['focused']));
    }

    /**
     * Load providers data child elements
     * @return void
     */
    protected static function loadProvidersChildren()
    {
        self::loadProviders();

        if (!empty(self::$providersChildren)) {
            return; // already loaded
        }

        $collection = new Collection(self::$providers);

        $data = [];
        $collection->each(function($item) use (&$data) {
            $data = array_merge($data, $item);
        });

        self::$providersChildren = $data;
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

    /**
     * Extract and normalize the domain part of an email.
     */
    protected static function extractDomain(?string $email): ?string
    {
        if (!is_string($email)) {
            return null;
        }
        $email = trim($email);
        $at = strrpos($email, '@');
        if ($at === false) {
            return null;
        }
        $domain = substr($email, $at + 1);
        if ($domain === '') {
            return null;
        }

        return mb_strtolower($domain);
    }

    /**
     * Check if email belongs to any of the given domains.
     *
     * @param string $email
     * @param string[] $domains
     */
    protected static function isDomainIn(string $email, array $domains): bool
    {
        $domain = self::extractDomain($email);
        if ($domain === null) {
            return false;
        }

        return in_array($domain, $domains, true);
    }

}