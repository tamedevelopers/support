<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Traits\GeoIPTrait;


/*
 * GeoIP Helper
 * (c) Tame Developers 2026.
 * - tamedevelopers@gmail.com
 * 
 * @author  IP-API
 * @url     https://www.geoplugin.net
 * @url2    https://www.geoplugin.net/json.gp?ip=
 */
class GeoIP {

    use GeoIPTrait;

    /** 
     * IP Address
     * @var string
     */
    static protected $IPAddress;

    /** 
     * Output data
     * @var mixed
     */
    static protected $output;

    /**
     * List of free IP lookup endpoints.
     * Use {ip} as placeholder.
     * 
     * @var array
     */
    static protected array $providers = [
        "http://ip-api.com/json/{ip}?fields=status,message,country,countryCode,region,regionName,city,lat,lon,timezone,isp,query",
        "http://ipwho.is/{ip}",
        "https://freegeoip.app/json/{ip}"
    ];

    /**
     * Add a new free IP provider endpoint.
     *
     * @param string $url
     * @return void
     */
    static public function addProvider(string $url): void
    {
        self::$providers[] = $url;
    }

    /** 
     * Check against API and Collect Data
     * 
     * @param string|null $ip
     * @return mixed
     */
    static public function getIPData($ip = null)
    {
        // default
        self::$IPAddress = $ip ?? self::getIpAddress();

        return self::routeGetData(self::$IPAddress);
    }

    /** 
     * Get IP Address By Preference alone
     * 
     * @param string|null $ip
     * @param string $preference
     * - Type of ip data needed\Default is `country`
     * - ip|city|state|region|country|country_code|country_code|continent|continent_code
     * - latitude|longitude|timezone|isp|proxy|browser|platform|incognito_window|address
     * 
     * @return mixed
     */
    static public function IP($ip = null, ?string $preference = 'country')
    {
        // get data from api
        $data = self::getIPData($ip); 

        return $data[$preference] ?? null;
    }

    /** 
     * Try all providers in order until success
     * 
     * @param string|null $ip
     * @return mixed
     */
    static private function ipEndpoint($ip = null) 
    {
        if (empty($ip)) {
            $ip = self::getServerIp();
        }

        foreach (self::$providers as $provider) {
            $url = str_replace('{ip}', $ip, $provider);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // 2 second limit
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            unset($ch);

            if ($httpCode === 200 && $response) {
                $json = json_decode($response);
                if (is_object($json)) return $json;
            }
        }

        // All providers failed
        return null;
    }

    /**
     * Normalize all providers into one unified format
     */
    static private function normalizeProviderData(mixed $apiData): array
    {
        return [
            "city"          => $apiData->city ?? null,
            "region"        => $apiData->region ?? $apiData->regionName ?? $apiData->region_name ?? $apiData->geoplugin_region ?? null,
            "state"         => $apiData->regionName ?? $apiData->region ?? $apiData->region_name ?? $apiData->geoplugin_region ?? null,
            "country"       => $apiData->country ?? $apiData->country_name ?? $apiData->geoplugin_countryName ?? null,
            "country_code"  => $apiData->countryCode ?? $apiData->country_code ?? $apiData->geoplugin_countryCode ?? null,
            "continent"     => $apiData->continent ?? $apiData->continent_name ?? $apiData->geoplugin_continentName ?? null,
            "continent_code"=> $apiData->continentCode ?? $apiData->continent_code ?? $apiData->geoplugin_continentCode ?? null,
            "latitude"      => $apiData->lat ?? $apiData->latitude ?? $apiData->geoplugin_latitude ?? null,
            "longitude"     => $apiData->lon ?? $apiData->longitude ?? $apiData->geoplugin_longitude ?? null,
            "timezone"      => $apiData->timezone ?? $apiData->geoplugin_timezone ?? null,
            "isp"           => $apiData->isp ?? $apiData->org ?? null,
            "request_ip"    => $apiData->query ?? $apiData->ip ?? $apiData->geoplugin_request ?? null,
        ];
    }

    /** 
     * Build human readable address
     * 
     * @param array $data
     * @return string
     */
    static private function createAddress($data = null) 
    {
        $parts = [];

        if (!empty($data['city']))     $parts[] = $data['city'];
        if (!empty($data['state']))    $parts[] = $data['state'];
        if (!empty($data['country']))  $parts[] = $data['country'];

        return implode(", ", $parts);
    }

    /** 
     * Process and map all data
     * 
     * @param string|null $ip
     * - Type of ip data needed\Default is `location`
     * - request_ip|fake_ip|city|state|region|country|countrycode
     * 
     * @return mixed
     */
    static private function routeGetData($ip = null) 
    {
        // create API endpoint
        $raw = self::ipEndpoint($ip);

        if (!$raw) {
            return [
                "error" => "All providers failed",
                "ip"    => $ip
            ];
        }

        // Normalize API result into unified format
        $mapped = self::normalizeProviderData($raw);

        // continent
        if(empty($mapped['continent_code'])){
            $continent = self::parseTimezoneContinent($mapped['timezone']);
            $mapped['continent'] = $continent['continent'];
            $mapped['continent_code'] = $continent['continent_code'];
        }

        self::$output = [
            "date"          => date('Y-m-d H:i:s'),
            "ip"            => $ip,
            "city"          => $mapped['city'],
            "state"         => $mapped['state'],
            "region"        => $mapped['region'],
            "country"       => $mapped['country'],
            "country_code"  => $mapped['country_code'],
            "continent"     => $mapped['continent'],
            "continent_code"=> $mapped['continent_code'],
            "latitude"      => $mapped['latitude'],
            "longitude"     => $mapped['longitude'],
            "timezone"      => $mapped['timezone'],
            "isp"           => $mapped['isp'],
            "proxy" => [
                "isFake"    => self::detectFakeIP(),
                "serverIp"  => self::getServerIp(),
                "fakeIp"    => $mapped['request_ip']
            ],
            "incognito_window" => self::isIncognitoWindow(),
        ];

        // merge with agent data
        self::$output = array_merge(self::$output, self::getDeviceInformation());

        // add full address
        self::$output["address"] = self::createAddress(self::$output);

        return self::$output;
    }
    
}