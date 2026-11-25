<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

trait GeoIPTrait
{
    /**
     * Detect Continent and Continent Code from IANA Timezone
     * 
     * @param string|null $timezone
     * @return array
     */
    private static function parseTimezoneContinent($timezone = null)
    {
        if (!$timezone || !is_string($timezone)) {
            return [
                "continent"       => null,
                "continent_code"  => null
            ];
        }

        // Extract the first segment before "/"
        $parts = explode("/", $timezone);
        $prefix = $parts[0];

        $continent = 'continent';
        $continent_code = 'continent_code';

        // IANA prefix mapping
        $continentMap = [
            "Africa"      => [$continent => "Africa",      $continent_code => "AF"],
            "Europe"      => [$continent => "Europe",      $continent_code => "EU"],
            "Asia"        => [$continent => "Asia",        $continent_code => "AS"],
            "America"     => [$continent => "America",     $continent_code => "NA"], // NA for entire America region
            "Australia"   => [$continent => "Oceania",     $continent_code => "OC"], // Australia → Oceania
            "Pacific"     => [$continent => "Oceania",     $continent_code => "OC"], // Pacific islands → Oceania
            "Indian"      => [$continent => "Asia",        $continent_code => "AS"], // Indian Ocean region is Asian
            "Atlantic"    => [$continent => "Europe",      $continent_code => "EU"], // Used for EU territories
            "Arctic"      => [$continent => "Europe",      $continent_code => "EU"],
            "Antarctica"  => [$continent => "Antarctica",  $continent_code => "AN"],
            "UTC"         => [$continent => "Global",      $continent_code => "GL"],
        ];

        return $continentMap[$prefix] ?? [
            "continent"       => null,
            "continent_code"  => null
        ];
    }

    /** 
     * Get IP Address 
     * 
     * @return string
     */
    static protected function getIpAddress()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_TRUE_CLIENT_IP',    // Cloudflare enterprise
            'HTTP_X_FORWARDED_FOR',   // Proxies
            'HTTP_X_REAL_IP',         // Nginx
            'HTTP_CLIENT_IP',         // Shared internet
        ];

        foreach ($headers as $header) {

            if (empty($_SERVER[$header])) {
                continue;
            }

            $ips = explode(',', $_SERVER[$header]);

            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                    )) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    /** 
     * Get User agent data
     * 
     * @return string
     */
    static protected function getUserAgent() 
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Parse the User-Agent string and return structured device data.
     *
     * This method extracts:
     * - platform
     * - platform_version
     * - browser
     * - browser_version
     * - device_type
     * - device_brand
     * - device_model
     * - email_client
     *
     * @return array<string, string|null>
     */
    protected static function parseUserAgent(): array
    {
        $ua = strtolower(self::getUserAgent());
        $emailClient = self::detectEmailClient($ua);
        
        $platformData = self::detectPlatform($ua);
        $browserData = self::detectBrowser($ua);
        $deviceData = self::detectDevice($ua);

        // If a proxy is detected, the device/platform data is likely "fake" (provided by the proxy)
        if ($emailClient === 'Gmail Image Proxy' || str_contains($ua, 'googleimageproxy')) {
            $platformData['platform'] = "Cloud Proxy (Gmail)";
            $deviceData['device_type'] = "Email Proxy";
        }

        return array_merge(
            $platformData,
            $browserData,
            $deviceData,
            [
                "email_client" => $emailClient,
                "user_agent"   => $ua
            ]
        );
    }

    /**
     * Detect operating system and version.
     *
     * @param string $ua
     * @return array<string,string|null>
     */
    protected static function detectPlatform(string $ua): array
    {
        $platforms = [
            'windows nt 10.0' => ['Windows', '10.0'],
            'windows nt 6.3'  => ['Windows', '8.1'],
            'windows nt 6.2'  => ['Windows', '8'],
            'windows nt 6.1'  => ['Windows', '7'],
            'windows nt 6.0'  => ['Windows', 'Vista'],
            'windows nt 5.1'  => ['Windows', 'XP'],

            'android' => ['Android', null],
            'iphone'  => ['iOS', null],
            'ipad'    => ['iOS', null],
            'mac os x'=> ['MacOS', null],
            'linux'   => ['Linux', null]

        ];

        foreach ($platforms as $needle => $data) {
            if (strpos($ua, $needle) !== false) {

                $version = $data[1];
                if ($data[0] === 'Android' && preg_match('/android ([0-9\.]+)/', $ua, $m)) {
                    $version = $m[1];
                }

                if ($data[0] === 'iOS' && preg_match('/os ([0-9\_]+)/', $ua, $m)) {
                    $version = str_replace('_', '.', $m[1]);
                }

                if ($data[0] === 'MacOS' && preg_match('/mac os x ([0-9\_]+)/', $ua, $m)) {
                    $version = str_replace('_', '.', $m[1]);
                }

                return [
                    "platform" => $data[0],
                    "platform_version" => $version
                ];
            }
        }

        return [
            "platform" => "Unknown",
            "platform_version" => null
        ];
    }

    /**
     * Detect browser and version.
     *
     * @param string $ua
     * @return array<string,string|null>
     */
    protected static function detectBrowser(string $ua): array
    {
        $browsers = [
            'edge' => 'edge\/([0-9\.]+)',
            'chrome' => 'chrome\/([0-9\.]+)',
            'firefox' => 'firefox\/([0-9\.]+)',
            'safari' => 'version\/([0-9\.]+).*safari',
            'opera' => 'opr\/([0-9\.]+)',
            'ie' => 'msie ([0-9\.]+)'
        ];

        foreach ($browsers as $name => $regex) {
            if (preg_match("/$regex/i", $ua, $matches)) {
                return [
                    "browser" => ucfirst($name),
                    "browser_version" => $matches[1] ?? null
                ];
            }
        }

        return [
            "browser" => "Unknown",
            "browser_version" => null
        ];
    }

    /**
     * Detect device type, brand, and model if possible.
     *
     * @param string $ua
     * @return array<string,string|null>
     */
    protected static function detectDevice(string $ua): array
    {

        $type = 'Desktop';
        $brand = null;
        $model = null;

        /**
         * Device type detection
         */
        if (preg_match('/mobile|iphone|ipod|android.*mobile/i', $ua)) {
            $type = 'Mobile';
        }

        if (preg_match('/tablet|ipad|android(?!.*mobile)/i', $ua)) {
            $type = 'Tablet';
        }

        if (preg_match('/smart-tv|smarttv|hbbtv|appletv|googletv/i', $ua)) {
            $type = 'Smart TV';
        }

        if (preg_match('/playstation|xbox|nintendo/i', $ua)) {
            $type = 'Console';
        }

        if (preg_match('/bot|crawler|spider|curl|wget|python/i', $ua)) {
            $type = 'Bot';
        }

        /**
         * Apple Devices
         */
        if (preg_match('/iphone/i', $ua)) {
            $brand = 'Apple';
            $model = 'iPhone';
        }

        if (preg_match('/ipad/i', $ua)) {
            $brand = 'Apple';
            $model = 'iPad';
        }

        if (preg_match('/ipod/i', $ua)) {
            $brand = 'Apple';
            $model = 'iPod';
        }

        if (preg_match('/macintosh|mac os x/i', $ua)) {
            $brand = 'Apple';
            $model = 'Mac';
        }


        /**
         * Samsung
         */
        if (preg_match('/samsung|sm-[a-z0-9]+/i', $ua)) {
            $brand = 'Samsung';

            if (preg_match('/sm-[a-z0-9]+/i', $ua, $m)) {
                $model = strtoupper($m[0]);
            }
        }


        /**
         * Huawei / Honor
         */
        if (preg_match('/huawei/i', $ua)) {
            $brand = 'Huawei';
        }

        if (preg_match('/honor/i', $ua)) {
            $brand = 'Honor';
        }


        /**
         * Xiaomi / Redmi / Poco
         */
        if (preg_match('/xiaomi/i', $ua)) {
            $brand = 'Xiaomi';
        }

        if (preg_match('/redmi/i', $ua)) {
            $brand = 'Xiaomi';
            $model = 'Redmi';
        }

        if (preg_match('/poco/i', $ua)) {
            $brand = 'Xiaomi';
            $model = 'Poco';
        }


        /**
         * Google Devices
         */
        if (preg_match('/pixel/i', $ua)) {
            $brand = 'Google';
            $model = 'Pixel';
        }


        /**
         * Oppo / Realme / Vivo
         */
        if (preg_match('/oppo/i', $ua)) {
            $brand = 'Oppo';
        }

        if (preg_match('/realme/i', $ua)) {
            $brand = 'Realme';
        }

        if (preg_match('/vivo/i', $ua)) {
            $brand = 'Vivo';
        }


        /**
         * OnePlus
         */
        if (preg_match('/oneplus/i', $ua)) {
            $brand = 'OnePlus';
        }


        /**
         * Sony
         */
        if (preg_match('/sony/i', $ua)) {
            $brand = 'Sony';
        }

        if (preg_match('/xperia/i', $ua)) {
            $brand = 'Sony';
            $model = 'Xperia';
        }


        /**
         * Motorola
         */
        if (preg_match('/motorola|moto/i', $ua)) {
            $brand = 'Motorola';
        }


        /**
         * Nokia
         */
        if (preg_match('/nokia/i', $ua)) {
            $brand = 'Nokia';
        }


        /**
         * LG
         */
        if (preg_match('/lg-|lg/i', $ua)) {
            $brand = 'LG';
        }


        /**
         * Asus
         */
        if (preg_match('/asus/i', $ua)) {
            $brand = 'Asus';
        }


        /**
         * Lenovo
         */
        if (preg_match('/lenovo/i', $ua)) {
            $brand = 'Lenovo';
        }

        /**
         * Desktop OS fallback brands
         */
        if ($type === 'Desktop') {
            if (preg_match('/windows/i', $ua)) {
                $brand = 'PC';
            }

            if (preg_match('/macintosh/i', $ua)) {
                $brand = 'Apple';
            }

            if (preg_match('/linux/i', $ua)) {
                $brand = 'Linux PC';
            }
        }
        
        return [
            "device_type" => $type,
            "device_brand" => $brand,
            "device_model" => $model
        ];
    }

    /**
     * Detect known email clients and proxy fetchers.
     *
     * Important for email tracking because many providers
     * load images via proxy servers.
     *
     * @param string $ua
     * @return string|null
     */
    protected static function detectEmailClient(string $ua): ?string
    {
        $clients = [
            /** Gmail */
            'googleimageproxy'   => 'Gmail Image Proxy',
            'gmail'              => 'Gmail',

            /** Microsoft */
            'outlook'            => 'Microsoft Outlook',
            'microsoft office'   => 'Microsoft Outlook',
            'windows live mail'  => 'Windows Live Mail',

            /** Apple */
            'applemail'          => 'Apple Mail',
            'iphone mail'        => 'Apple Mail (iOS)',
            'ipad mail'          => 'Apple Mail (iPadOS)',
            'mac mail'           => 'Apple Mail (macOS)',

            /** Mozilla */
            'thunderbird'        => 'Mozilla Thunderbird',

            /** Yahoo */
            'yahoomail'          => 'Yahoo Mail',
            'yahoo'              => 'Yahoo Mail',

            /** Proton */
            'protonmail'         => 'ProtonMail',

            /** Zoho */
            'zoho'               => 'Zoho Mail',

            /** AOL */
            'aolmail'            => 'AOL Mail',
            'aol'                => 'AOL Mail',

            /** Fastmail */
            'fastmail'           => 'Fastmail',

            /** GMX / Mail.com */
            'gmx'                => 'GMX Mail',
            'mail.com'           => 'Mail.com',

            /** Roundcube / Webmail */
            'roundcube'          => 'Roundcube Webmail',
            'squirrelmail'       => 'SquirrelMail',

            /** Horde */
            'horde'              => 'Horde Webmail',

            /** Android email apps */
            'android'            => 'Android Mail Client',

            /** Samsung */
            'samsungbrowser'     => 'Samsung Mail',

            /** Security scanners */
            'proofpoint'         => 'Proofpoint Email Scanner',
            'barracuda'          => 'Barracuda Email Security',
            'mimecast'           => 'Mimecast Email Security',
            'symantec'           => 'Symantec Email Scanner',
            'trend micro'        => 'TrendMicro Email Scanner',
            'mcafee'             => 'McAfee Email Scanner',

            /** Link checkers */
            'urlscan'            => 'Email Link Scanner',
            'linkcheck'          => 'Email Link Checker',

            /** Generic bots */
            'curl'               => 'Curl Bot',
            'wget'               => 'Wget Bot',
            'python'             => 'Python Script',
            'java'               => 'Java Client'
        ];

        foreach ($clients as $needle => $name) {
            if (strpos($ua, $needle) !== false) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Get complete device information from User-Agent.
     *
     * This method should be used by tracking systems
     * to retrieve structured device data.
     *
     * @return array<string,string|null>
     */
    public static function getDeviceInformation(): array
    {
        return self::parseUserAgent();
    }

    /** 
     * Get User agent data
     * 
     * @return bool
     */
    static protected function detectFakeIP() 
    {
        $clientIp = self::getIpAddress();
        $serverIp = $_SERVER['REMOTE_ADDR'] ?? null;

        return $clientIp !== $serverIp;
    }

    /**
     * Detect Email Scanner Bots
     */
    static protected function isEmailScanner(): bool
    {
        $ua = strtolower(self::getUserAgent());
        $ip = self::getIpAddress();

        $botAgents = [
            'googleimageproxy',
            'googlebot',
            'bingbot',
            'proofpoint',
            'barracuda',
            'curl',
            'python',
            'wget'
        ];

        foreach ($botAgents as $bot) {
            if (strpos($ua, $bot) !== false) {
                return true;
            }
        }

        // Google scanner ranges
        if (str_starts_with($ip, '66.249.')) {
            return true;
        }

        return false;
    }

    /** 
     * ISP lookup
     */
    static private function ispEndpoint(?string $ip = null): mixed
    {
        $address = @file_get_contents("http://ip-api.com/json/{$ip}");

        return json_decode(!is_bool($address) ? $address : "", true);
    }

    /**
     * Get the ISP (Internet Service Provider) from an IP address.
     *
     * @param string $ip 
     * @return string|null 
     * - The ISP name or null if it cannot be determined.
     */
    static protected function getISP(?string $ip = null)
    {
        $response = self::ispEndpoint($ip);

        if ($response && isset($response['isp'])) {
            return $response['isp'];
        }
        
        return null; 
    }

    /**
     * Get the host name of the user's IP address.
     *
     * @return string|null The host name or null if it cannot be determined.
     */
    static protected function getHostName() 
    {
        $ipAddress = self::getIpAddress();
        $apiUrl = "http://www.geoplugin.net/json.gp?ip={$ipAddress}";

        $response = file_get_contents($apiUrl);
        $data = json_decode($response, true);

        if (isset($data['geoplugin_request'])) {
            return $data['geoplugin_request'];
        }

        return null;
    }

    /** 
     * Get Real IP Address
     */
    static protected function getServerIp(): string
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Check if the user is browsing in incognito mode.
     *
     * @return bool true if the user is browsing in incognito mode, false otherwise.
     */
    static protected function isIncognitoWindow() 
    {
        return isset($_SERVER['HTTP_INCOGNITO']) && $_SERVER['HTTP_INCOGNITO'] === '1';
    }

}