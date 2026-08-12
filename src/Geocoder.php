<?php

namespace Tamedevelopers\Support;

use Exception;
use Tamedevelopers\Support\Capsule\FileCache;
use Tamedevelopers\Support\Capsule\Logger;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Process\Http;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Time;

class Geocoder
{
    protected static mixed $defaultTTL;
    protected static bool $initialized = false;
    protected static string $defaultUserAgent;
    protected static ?string $staticGoogleApiKey = null;
    protected static ?string $staticGeoapifyKey = null;
    protected static ?string $staticLocationIqKey = null;
    protected static ?string $staticGeocodeMapsCoKey = null;

    protected mixed $ttl;
    protected bool $serialize;
    protected string $userAgent;
    protected ?string $googleApiKey;
    protected ?string $geoapifyKey;
    protected ?string $locationIqKey;
    protected ?string $geocodeMapsCoKey;

    /**
     * Boot and load environment configurations ONCE per lifecycle.
     */
    protected static function bootIfNotBooted(mixed $userAgent, mixed $ttl): void
    {
        if (self::$initialized) {
            return;
        }

        Manager::startEnvIFNotStarted();
        $server = new Server();

        $appName  = $server->config('app.name', 'TameDeveloperApp');
        $appEmail = $server->config('mail.from.address', 'admin@domain.com');

        self::$defaultTTL               = $ttl;
        self::$defaultUserAgent         = $userAgent ?: "{$appName}/1.0 ({$appEmail})";
        self::$staticGoogleApiKey       = $server->config('services.google.maps_key') ?? env('GOOGLE_MAPS_API_KEY');
        self::$staticGeoapifyKey        = $server->config('services.geoapify.key') ?? env('GEOAPIFY_API_KEY');
        self::$staticLocationIqKey      = $server->config('services.locationiq.key') ?? env('LOCATIONIQ_API_KEY');
        self::$staticGeocodeMapsCoKey   = $server->config('services.geocodemapsco.key') ?? env('GEOCODE_MAPS_CO_API_KEY');

        self::$initialized = true;
    }
        
    /**
     * __construct
     *
     * @param  string|null $userAgent
     * @param int|null|Time|\DateTimeInterface $ttl Expiration time in seconds (null for no expiration)
     * @param  bool $serialize
     * @return void 
     */
    public function __construct(?string $userAgent = null, $ttl = null, $serialize = false)
    {
        self::bootIfNotBooted($userAgent, $ttl);

        $this->serialize        = $serialize;
        $this->ttl              = self::$defaultTTL;
        $this->userAgent        = self::$defaultUserAgent;
        $this->googleApiKey     = self::$staticGoogleApiKey;
        $this->geoapifyKey      = self::$staticGeoapifyKey;
        $this->locationIqKey    = self::$staticLocationIqKey;
        $this->geocodeMapsCoKey = self::$staticGeocodeMapsCoKey;
    }

    /**
     * Static shortcut helper
     */
    public static function geocode(string $address): array|null
    {
        return (new static())->getCoordinates($address);
    }

    /**
     * Static shortcut helper.
     *
     * @param string $address
     * @param string|null $userAgent
     * @param int|null|Time|\DateTimeInterface $ttl Expiration time in seconds (null for no expiration)
     * @param bool $serialize
     * @return array|null
     */
    public static function locate($address, $userAgent = null, $ttl = null, $serialize = false)
    {
        return (new static($userAgent, $ttl, $serialize))->geocode($address);
    }

    /**
     * Convert address into coordinates using multi-engine fallback
     *
     * @param string $address
     * @return array{lat: float, lng: float, formatted_address: string, engine: string}|null
     */
    public function getCoordinates(string $address)
    {
        $cleanAddress = trim($address);
        if (empty($cleanAddress)) {
            return null;
        }

        $cacheKey = 'geocode_' . md5(mb_strtolower($cleanAddress));
        
        return FileCache::serializeMode($this->serialize)->remember($cacheKey, $this->ttl, function () use ($cleanAddress) {
            // Generate sanitization levels from most clean/simplified -> exact raw
            $queries = array_unique(array_filter([
                $this->sanitizeAddress($cleanAddress),
                $cleanAddress
            ]));
    
            // Strict & Reliable engines first -> Fuzzy fallbacks last
            $engines = [
                'tryNominatim',     // Primary OSM Engine
                'tryLocationIq',    // Keyed OSM
                'tryGeoapify',      // Keyed Engine
                'tryGeocodeMapsCo', // Keyed Engine
                'tryGoogle',        // Google Maps API
                'tryPhoton',        // Fuzzy Fallback (Only runs if all above strict engines fail)
            ];
    
            foreach ($engines as $engine) {
                // Skip engines without API keys
                if ($engine === 'tryGoogle' && !$this->googleApiKey) continue;
                if ($engine === 'tryLocationIq' && !$this->locationIqKey) continue;
                if ($engine === 'tryGeoapify' && !$this->geoapifyKey) continue;
                if ($engine === 'tryGeocodeMapsCo' && !$this->geocodeMapsCoKey) continue;
    
                foreach ($queries as $query) {
                    if ($result = $this->$engine($query)) {
                        return $result;
                    }
                }
            }
        
            return null;
        });
    }

    /**
     * Aggressive sanitization for Chinese/English floor levels, unit numbers, and suffixes
     */
    protected function sanitizeAddress(string $address): string
    {
        // 1. Remove Chinese floor, room, flat, block suffixes (e.g., 16樓, B9室, A座, 3單元)
        $sanitized = preg_replace('/[0-9A-Za-z\-]+[樓楼].*/u', '', $address);
        $sanitized = preg_replace('/[0-9A-Za-z\-]+[室|房|座|單元|单元].*/u', '', $sanitized);

        // 2. Convert Chinese building/street numbers to standard numbers (e.g., "83號" -> "83")
        $sanitized = preg_replace('/([0-9]+)\s*號/u', '$1', $sanitized);

        // 3. Remove English Suite, Apt, Unit, Room, Floor patterns (e.g., "Room 102", "Apt 4B", "#04-12")
        $sanitized = preg_replace('/(?i)(suite|apt|apartment|unit|room|rm|ste|fl|floor|#)\s*[a-z0-9\-]+/u', '', $sanitized);

        // 4. Strip leftover trailing commas and whitespace
        return trim($sanitized, " \t\n\r\0\x0B,");
    }

    /**
     * Engine 1: Nominatim (OpenStreetMap)
     */
    protected function tryNominatim(string $query): array|null
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->timeout(4)->get('https://nominatim.openstreetmap.org/search', [
                'q'               => $query,
                'format'          => 'json',
                'limit'           => 1,
                'addressdetails'  => 1,
            ]);

            if ($response->successful() && !empty($response->json())) {
                $data = $response->json()[0];
                return [
                    'lat'               => (float) $data['lat'],
                    'lng'               => (float) $data['lon'],
                    'formatted_address' => $data['display_name'] ?? $query,
                    'engine'            => 'nominatim',
                ];
            }
        } catch (Exception $e) {
            Logger::error("Nominatim Geocoding Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Engine 2: LocationIQ API (Free Tier: 10,000 reqs/day)
     */
    protected function tryLocationIq(string $query): array|null
    {
        try {
            $response = Http::timeout(4)->get('https://us1.locationiq.com/v1/search', [
                'key'    => $this->locationIqKey,
                'q'      => $query,
                'format' => 'json',
                'limit'  => 1,
            ]);

            if ($response->successful() && !empty($response->json())) {
                $data = $response->json()[0];
                return [
                    'lat'               => (float) $data['lat'],
                    'lng'               => (float) $data['lon'],
                    'formatted_address' => $data['display_name'] ?? $query,
                    'engine'            => 'locationiq',
                ];
            }
        } catch (Exception $e) {
            Logger::warning("LocationIQ Geocoding Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Engine 3: Geoapify API (Free Tier: 3,000 reqs/day)
     */
    protected function tryGeoapify(string $query): array|null
    {
        try {
            $response = Http::timeout(4)->get('https://api.geoapify.com/v1/geocode/search', [
                'text'   => $query,
                'apiKey' => $this->geoapifyKey,
                'limit'  => 1,
            ]);

            if ($response->successful() && !empty($response->json()['features'])) {
                $feature = $response->json()['features'][0];
                $props   = $feature['properties'];

                return [
                    'lat'               => (float) $props['lat'],
                    'lng'               => (float) $props['lon'],
                    'formatted_address' => $props['formatted'] ?? $query,
                    'engine'            => 'geoapify',
                ];
            }
        } catch (Exception $e) {
            Logger::warning("Geoapify Geocoding Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Engine 4: Geocode.maps.co API (Requires Free API Key)
     */
    protected function tryGeocodeMapsCo(string $query): array|null
    {
        try {
            $response = Http::timeout(4)->get('https://geocode.maps.co/search', [
                'q'      => $query,
                'api_key' => $this->geocodeMapsCoKey,
            ]);

            if ($response->successful() && !empty($response->json())) {
                $data = $response->json()[0];
                return [
                    'lat'               => (float) $data['lat'],
                    'lng'               => (float) $data['lon'],
                    'formatted_address' => $data['display_name'] ?? $query,
                    'engine'            => 'geocode_maps_co',
                ];
            }
        } catch (Exception $e) {
            Logger::warning("Geocode.maps.co Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Engine 5: Google Geocoding API
     */
    protected function tryGoogle(string $query): array|null
    {
        try {
            $response = Http::timeout(4)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $query,
                'key'     => $this->googleApiKey,
            ]);

            if ($response->successful() && $response->json()['status'] === 'OK') {
                $result   = $response->json()['results'][0];
                $location = $result['geometry']['location'];

                return [
                    'lat'               => (float) $location['lat'],
                    'lng'               => (float) $location['lng'],
                    'formatted_address' => $result['formatted_address'],
                    'engine'            => 'google',
                ];
            }
        } catch (Exception $e) {
            Logger::warning("Google Geocoding Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Engine 6: Photon API (Komoot - Lenient Fuzzy Fallback)
     */
    protected function tryPhoton(string $query): array|null
    {
        try {
            $response = Http::timeout(4)->get('https://photon.komoot.io/api/', [
                'q'     => $query,
                'limit' => 1,
            ]);

            if ($response->successful() && !empty($response->json()['features'])) {
                $feature = $response->json()['features'][0];
                $coords  = $feature['geometry']['coordinates']; // [lng, lat]
                $props   = $feature['properties'];

                $formatted = implode(', ', array_filter([
                    $props['name'] ?? null,
                    $props['street'] ?? null,
                    $props['city'] ?? $props['town'] ?? null,
                    $props['country'] ?? null
                ]));

                return [
                    'lat'               => (float) $coords[1],
                    'lng'               => (float) $coords[0],
                    'formatted_address' => $formatted ?: $query,
                    'engine'            => 'photon',
                ];
            }
        } catch (Exception $e) {
            Logger::warning("Photon Geocoding Exception: " . $e->getMessage());
        }

        return null;
    }
}