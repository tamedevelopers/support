<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;


/**
 * @method mixed stringReplacer(string|null $path)
 * @method mixed resolvePrimaryMode(array $flags)
 */
trait TameCoreTrait{

    /**
     * Parse and retrieve details about the web server software environment.
     *
     * @param string|null $server Optional server software string (defaults to $_SERVER['SERVER_SOFTWARE'])
     * @return array{
     *     raw: string,
     *     name: string,
     *     version: string|null,
     *     os: string|null,
     *     modules: array<string, string>
     * } Array containing parsed server details and individual components.
     */
    public static function serverSoftware(?string $server = null): array
    {
        $raw = $server ?? ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');

        // Default structure
        $result = [
            'raw'           => $raw,
            'name'          => 'Unknown',
            'version'       => null,
            'sapi'          => php_sapi_name(),
            'os_detailed'   => PHP_OS,
            'php_version'   => PHP_VERSION,
            'os'            => [PHP_OS_FAMILY],
            'modules'       => [],
        ];

        if ($raw === 'Unknown' || empty(trim($raw))) {
            return $result;
        }

        // Split string into component segments by whitespace
        $parts = explode(' ', trim($raw));

        // First part is always primary server (e.g., "Apache/2.4.54")
        $primaryServer = array_shift($parts);
        $serverDetails = explode('/', $primaryServer, 2);

        $result['name']    = $serverDetails[0] ?? 'Unknown';
        $result['version'] = $serverDetails[1] ?? null;

        // Parse remaining tokens (e.g., "(Win64)", "OpenSSL/1.1.1p", "PHP/8.1.10")
        foreach ($parts as $part) {
            $part = trim($part);

            // Check for OS wrapper in parentheses like (Win64) or (Unix)
            if (str_starts_with($part, '(') && str_ends_with($part, ')')) {
                $result['os'][] = trim($part, '()');
                continue;
            }

            // Check for key/value modules separated by slash (e.g., OpenSSL/1.1.1p)
            if (str_contains($part, '/')) {
                [$moduleName, $moduleVersion] = explode('/', $part, 2);
                $result['modules'][$moduleName] = $moduleVersion;
            }
        }

        return $result;
    }

    /**
     * Detect the visitor's device mode from the User-Agent header.
     *
     * Mirrors the shape of serverSoftware() so both methods return
     * a structured array when no specific mode is requested.
     *
     * @param  'mobile'|'phone'|'tablet'|'desktop'|'bot'|'tv'|'console'|'wearable'|'embedded'|'unknown'|null $mode
     * @param  string|null $ua    Optional custom User-Agent (defaults to HTTP_USER_AGENT).
     * @return bool|array{
     *     raw: string,
     *     name: string,
     *     mode: string,
     *     mode_flags: array<string,bool>,
     *     platform: string,
     *     is_mobile: bool,
     *     is_tablet: bool,
     *     is_desktop: bool,
     *     is_bot: bool,
     * }
     */
    public static function osMode(?string $mode = null, ?string $ua = null)
    {
        $raw = $ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ua  = Str::lower(trim($raw));

        // ---------- bot ----------
        $botRegex = '/bot|crawl|spider|slurp|facebookexternalhit|ia_archiver|'
                . 'bingpreview|yandex|baiduspider|duckduckbot|semrush|ahrefs|mj12bot|'
                . 'petalbot|googlebot|bingbot|linkedinbot|twitterbot|whatsapp|telegrambot|'
                . 'discordbot|applebot|headlesschrome|phantomjs|selenium|puppeteer|'
                . 'python-requests|curl\/|wget\/|go-http-client|java\/|okhttp|axios/i';
        $isBot = $ua !== '' && preg_match($botRegex, $ua) === 1;

        // ---------- tablet (before mobile) ----------
        $isTablet = false;
        if (!$isBot) {
            if (preg_match('/ipad|tablet|kindle|silk|playbook|nexus (7|9|10)|sm-t\d+|gt-p\d+/i', $ua)) {
                $isTablet = true;
            } elseif (str_contains($ua, 'android') && !str_contains($ua, 'mobile')) {
                $isTablet = true;
            }
        }

        // ---------- mobile ----------
        $isMobile = false;
        if (!$isBot && !$isTablet) {
            $isMobile = preg_match(
                '/mobile|iphone|ipod|android.*mobile|windows phone|blackberry|bb10|'
                . 'opera mini|iemobile|webos|palm|symbian|nokia|sonyericsson|'
                . 'motorola|samsung|htc|lg-|zte-|huawei|xiaomi|redmi|oppo|vivo|'
                . 'oneplus|pixel \d|galaxy s|galaxy note/i',
                $ua
            ) === 1;
        }

        // ---------- tv / console / wearable ----------
        $isTv = !$isBot && preg_match(
            '/smart-tv|smarttv|googletv|appletv|hbbtv|pov_tv|netcast|viera|bravia|'
            . 'roku|firetv|fire tv|android tv|tizen|webos.*tv|crkey|chromecast/i', $ua
        ) === 1;

        $isConsole = !$isBot && preg_match(
            '/playstation|xbox|nintendo|switch/i', $ua
        ) === 1;

        $isWearable = !$isBot && preg_match(
            '/watch|wearable|smartwatch|applewatch|gear s|galaxy watch/i', $ua
        ) === 1;

        // ---------- embedded webview ----------
        $isEmbedded = !$isBot && (
            (str_contains($ua, 'iphone') && (str_contains($ua, 'fbav') || str_contains($ua, 'fban') || str_contains($ua, 'instagram')))
            || str_contains($ua, '; wv)')
            || str_contains($ua, 'webview')
            || preg_match('/fban|fbav|instagram|line\/|micromessenger|telegram|snapchat|tiktok|trill/i', $ua) === 1
        );

        // ---------- desktop fallback ----------
        $isDesktop = !$isBot && !$isMobile && !$isTablet && !$isTv && !$isConsole && !$isWearable;

        // ---------- unknown ----------
        $isUnknown = $ua === '';

        // ---------- flags map ----------
        $flags = [
            'mobile'   => $isMobile,
            'phone'    => $isMobile,
            'tablet'   => $isTablet,
            'desktop'  => $isDesktop,
            'bot'      => $isBot,
            'tv'       => $isTv,
            'console'  => $isConsole,
            'wearable' => $isWearable,
            'embedded' => $isEmbedded,
            'unknown'  => $isUnknown,
        ];

        // ---------- single-mode short-circuit ----------
        if ($mode !== null) {
            return $flags[Str::lower(trim($mode))] ?? false;
        }

        // ---------- 1. OS & Version ----------
        $osName = 'Unknown';
        $osVersion = null;
        
        $osMap = [
            'windows nt 10.0' => ['Windows', '10'],
            'windows nt 6.4'  => ['Windows', '10'],
            'windows nt 6.3'  => ['Windows', '8.1'],
            'windows nt 6.2'  => ['Windows', '8'],
            'windows nt 6.1'  => ['Windows', '7'],
            'windows nt 6.0'  => ['Windows', 'Vista'],
            'windows nt 5.1'  => ['Windows', 'XP'],
            'iphone os'       => ['iOS', null], // dynamic
            'ipad'            => ['iPadOS', null], 
            'mac os x'        => ['macOS', null], 
            'android'         => ['Android', null], // dynamic
            'cros'            => ['ChromeOS', null],
            'linux'           => ['Linux', null],
            'ubuntu'          => ['Ubuntu', null],
        ];
        
        foreach ($osMap as $needle => $info) {
            if (str_contains($ua, $needle)) {
                $osName = $info[0];
                $osVersion = $info[1];
                
                // dynamic version extraction
                if ($osVersion === null) {
                    if ($osName === 'iOS') {
                        preg_match('/iphone os ([0-9_]+)/i', $ua, $m);
                        $osVersion = isset($m[1]) ? str_replace('_', '.', $m[1]) : null;
                    } elseif ($osName === 'iPadOS') {
                        preg_match('/cpu os ([0-9_]+)/i', $ua, $m);
                        $osVersion = isset($m[1]) ? str_replace('_', '.', $m[1]) : null;
                    } elseif ($osName === 'macOS') {
                        preg_match('/mac os x ([0-9_]+)/i', $ua, $m);
                        $osVersion = isset($m[1]) ? str_replace('_', '.', $m[1]) : null;
                    } elseif ($osName === 'Android') {
                        preg_match('/android ([0-9.]+)/i', $ua, $m);
                        $osVersion = $m[1] ?? null;
                    } elseif ($osName === 'ChromeOS') {
                        preg_match('/cros [a-z0-9]+ ([0-9.]+)/i', $ua, $m);
                        $osVersion = $m[1] ?? null;
                    }
                }
                break;
            }
        }

        // ---------- 2. Browser & Version ----------
        $browserName = 'Unknown';
        $browserVersion = null;
        
        $browserMap = [
            'edg'            => 'Edge',
            'opr'            => 'Opera',
            'opera'          => 'Opera',
            'chrome'         => 'Chrome',
            'crios'          => 'Chrome', // iOS Chrome
            'firefox'        => 'Firefox',
            'fxios'          => 'Firefox', // iOS Firefox
            'safari'         => 'Safari',
            'msie'           => 'Internet Explorer',
            'trident'        => 'Internet Explorer',
        ];

        foreach ($browserMap as $needle => $name) {
            if (str_contains($ua, $needle)) {
                $browserName = $name;
                // Extract version (e.g. chrome/156.0.0.0)
                if (preg_match('/' . preg_quote($needle, '/') . '\/([0-9.]+)/i', $ua, $m)) {
                    $browserVersion = $m[1];
                }
                break;
            }
        }

        // ---------- 3. Device Model & Brand ----------
        $deviceBrand = null;
        $deviceModel = null;

        // Samsung specific parse (e.g. SM-S908B -> Galaxy S22 Ultra)
        if (preg_match('/samsung|sm-|gt-/i', $ua)) {
            $deviceBrand = 'Samsung';
            if (preg_match('/(sm-[a-z0-9]+|gt-[a-z0-9]+)/i', $ua, $m)) {
                $deviceModel = strtoupper($m[1]);
            }
        } 
        // Huawei
        elseif (preg_match('/huawei/i', $ua)) {
            $deviceBrand = 'Huawei';
            if (preg_match('/(?:huawei|hm) ([a-z0-9\-]+)/i', $ua, $m)) {
                $deviceModel = strtoupper($m[1]);
            }
        }
        // Google Pixel
        elseif (preg_match('/pixel/i', $ua)) {
            $deviceBrand = 'Google';
            if (preg_match('/(pixel [a-z0-9 ]+)/i', $ua, $m)) {
                $deviceModel = ucwords($m[1]);
            }
        }
        // iPhone / iPad
        elseif (preg_match('/iphone|ipad/i', $ua)) {
            $deviceBrand = 'Apple';
            $deviceModel = str_contains($ua, 'iphone') ? 'iPhone' : 'iPad';
        }
        // Xiaomi
        elseif (preg_match('/xiaomi|redmi|mi /i', $ua)) {
            $deviceBrand = 'Xiaomi';
            if (preg_match('/(?:xiaomi|redmi|mi) ([a-z0-9 ]+)(?=build|\))/i', $ua, $m)) {
                $deviceModel = ucwords(trim($m[1]));
            }
        }

        // ---------- Structured return ----------
        return [
            'raw'             => $raw,
            'name'            => $osName,
            'mode'            => self::resolvePrimaryMode($flags),
            'mode_flags'      => $flags,
            'platform'        => $osName,
            'os_name'         => $osName,
            'os_version'      => $osVersion,
            'browser_name'    => $browserName,
            'browser_version' => $browserVersion,
            'device_brand'    => $deviceBrand,
            'device_model'    => $deviceModel,
            'is_mobile'       => $isMobile,
            'is_tablet'       => $isTablet,
            'is_desktop'      => $isDesktop,
            'is_bot'          => $isBot,
        ];
    }

    /**
     * Parse a human-formatted amount string into a numeric value.
     *
     * Accepts strings containing currency symbols, letters, spaces, non-breaking
     * spaces, grouping separators ('.' or ','), and an optional leading sign
     * ('-', '−' U+2212, or '+'). Only the first group of digits is considered;
     * everything that is not a digit, '.', ',' is discarded.
     *
     * The last '.' or ',' in the cleaned string is treated as the decimal
     * marker; earlier occurrences are treated as group separators. A trailing
     * three-digit group (e.g. "1.234.567" or "32.999") is interpreted as a
     * grouping separator rather than decimals.
     *
     * Returns 0 for non-string input, empty strings, or strings with no digits.
     *
     * @param mixed $value The raw value to parse (expected to be a string).
     * @return float The parsed amount, or 0.0 when no numeric value is found.
     */
    public static function amount($value) 
    {
        // accept only text
        if (!is_string($value)) {
            return 0.0;
        }

        $s = Str::trim($value);
        if ($s === '') {
            return 0.0;
        }

        // sign
        $negative = (bool) preg_match('/^[-\x{2212}]/u', $s);
        $s = preg_replace('/^[-\x{2212}+]/u', '', $s);

        // keep only digits, '.' and ','; drop currency symbols, letters, spaces, NBSP…
        $s = preg_replace('/[^\d.,]/', '', $s);
        if ($s === '') {
            return 0.0;
        }

        // last '.' or ',' is the decimal marker; earlier ones are group separators
        $lastDot   = strrpos($s, '.');
        $lastComma = strrpos($s, ',');
        $decPos    = max($lastDot === false ? -1 : $lastDot, $lastComma === false ? -1 : $lastComma);

        if ($decPos === -1) {
            $intPart = $s;
            $decPart = '';
        } else {
            $intPart = preg_replace('/[.,]/', '', substr($s, 0, $decPos));
            $decPart = preg_replace('/[.,]/', '', substr($s, $decPos + 1));
            // "1.234.567" / "32.999" / "7,500" → the 3-digit tail was a group, not decimals
            if (strlen($decPart) === 3 && strlen($intPart) > 0) {
                $intPart .= $decPart;
                $decPart = '';
            }
        }

        $intPart = preg_replace('/^0+(?=\d)/', '', $intPart);
        if ($intPart === '') {
            $intPart = '0';
        }

        $normalized = ($negative ? '-' : '') . $intPart . ($decPart !== '' ? '.' . $decPart : '');

        return (float) $normalized;
    }

    /**
     * Get platform svg icon set
     * 
     * @param 'facebook'|'messenger'|'instagram'|'linkedin'|'whatsapp'|'youtube'|'spotify'|'google'|'telegram'|
     * 'wechat'|'discord'|'snapchat'|'tiktok'|'playstore'|'appstore'|'windows'|'linux'|'android'|'mobile'|'phone'|
     * 'unknown'|'mac'|'macintosh'|'ios'|'iphone'|'c'|'os x'|string|null $platform
     * 
     * @param string|null|'macos'|'os x'|'ios' $os_name
     * @return string
     */
    public static function platformIcon($platform = null, $os_name = null)
    {
        $platform = Str::lower(basename($platform));
        $os_name = Str::lower($os_name);

        // set path
        $path = self::stringReplacer( __DIR__  . DIRECTORY_SEPARATOR);

        // Create items data set
        $dataSet = [
            'facebook'  => "{$path}icons/platform/facebook.svg",
            'messenger' => "{$path}icons/platform/messenger.svg",
            'instagram' => "{$path}icons/platform/instagram.svg",
            'linkedin'  => "{$path}icons/platform/linkedin.svg",
            'whatsapp'  => "{$path}icons/platform/whatsapp.svg",
            'youtube'   => "{$path}icons/platform/youtube.svg",
            'spotify'   => "{$path}icons/platform/spotify.svg",
            'google'    => "{$path}icons/platform/google.svg",
            'telegram'  => "{$path}icons/platform/telegram.svg",
            'wechat'    => "{$path}icons/platform/wechat.svg",
            'discord'   => "{$path}icons/platform/discord.svg",
            'snapchat'  => "{$path}icons/platform/snapchat.svg",
            'tiktok'    => "{$path}icons/platform/tiktok.svg",
            'playstore' => "{$path}icons/platform/playstore.svg",
            'appstore'  => "{$path}icons/platform/appstore.svg",
            'windows'   => "{$path}icons/platform/windows.svg",
            'linux'     => "{$path}icons/platform/linux.svg",
            'mac'       => "{$path}icons/platform/mac.svg",
            'iphone'    => "{$path}icons/platform/iphone.svg",
            'android'   => "{$path}icons/platform/android.svg",
            'mobile'    => "{$path}icons/platform/mobile.svg",
            'phone'     => "{$path}icons/platform/phone.svg",
            'unknown'   => "{$path}icons/platform/unknown.svg",
        ];

        // check for extra validations
        if (in_array($platform, ['macintosh', 'c', 'os x']) || in_array($os_name, ['macos', 'os x'])) {
            $platform = 'mac';
        } elseif (in_array($platform, ['iphone', 'ios']) || in_array($os_name, ['ios'])) {
            $platform = 'iphone';
        } elseif (in_array($platform, ['androidos']) || in_array($os_name, ['androidos'])) {
            $platform = 'android';
        } elseif (in_array($platform, ['play-store', 'googleplay', 'google play'])) {
            $platform = 'playstore';
        } elseif (in_array($platform, ['app-store', 'apple-store', 'apple store'])) {
            $platform = 'appstore';
        }

        return self::stringReplacer($dataSet[$platform] ?? $dataSet['unknown']);
    }

    /**
     * Get path to payment svg icon
     * 
     * @param 'add-money'|'alipay'|'bank'|'cc'|'credit-card'|'discover'|'faster-pay'|'groupbuy'|'maestro'|'mastercard'|
     * 'pay'|'payme'|'payment-card'|'payment-wallet'|'paypal'|'stripe-circle'|'tripe-sqaure'|'stripe'|'visa'|string|null $payment
     * 
     * @return string
     */
    public static function paymentIcon($payment = null)
    {
        // platform to lower
        $payment = Str::lower(basename($payment));

        // set path
        $path = self::stringReplacer( __DIR__  . DIRECTORY_SEPARATOR);

        // Create items data set
        $dataSet = [
            'add-money'     => "{$path}icons/payment/add-money.svg",
            'alipay'        => "{$path}icons/payment/alipay.svg",
            'bank'          => "{$path}icons/payment/bank.svg",
            'cc'            => "{$path}icons/payment/cc.svg",
            'credit-card'   => "{$path}icons/payment/credit-card.svg",
            'discover'      => "{$path}icons/payment/discover.svg",
            'faster-pay'    => "{$path}icons/payment/faster-pay.svg",
            'groupbuy'      => "{$path}icons/payment/groupbuy.svg",
            'maestro'       => "{$path}icons/payment/maestro.svg",
            'mastercard'    => "{$path}icons/payment/mastercard.svg",
            'pay'           => "{$path}icons/payment/pay.svg",
            'payme'         => "{$path}icons/payment/payme.svg",
            'payment-card'  => "{$path}icons/payment/payment-card.svg",
            'payment-wallet'=> "{$path}icons/payment/payment-wallet.svg",
            'paypal'        => "{$path}icons/payment/paypal.svg",
            'stripe-circle' => "{$path}icons/payment/stripe-circle.svg",
            'stripe-sqaure' => "{$path}icons/payment/stripe-sqaure.svg",
            'stripe'        => "{$path}icons/payment/stripe.svg",
            'visa'          => "{$path}icons/payment/cc.svg",
        ];  

        return self::stringReplacer($dataSet[$payment] ?? $dataSet['cc']);
    }
    
}