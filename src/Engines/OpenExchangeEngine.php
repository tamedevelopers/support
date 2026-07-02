<?php

namespace Tamedevelopers\Support\Engines;

use Exception;


class OpenExchangeEngine implements ExchangeEngineInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://openexchangerates.org/api/latest.json';
    
    // Memory cache to prevent duplicate file/network reads in a single script execution
    protected ?array $staticCache = null; 
    
    // Cache duration in seconds (e.g., 3600 = 1 hour)
    protected int $cacheTtl = 3600; 
    protected string $cacheFile;

    public function __construct($apiKey = null)
    {
        if (empty($apiKey)) {
            throw new Exception("Api key is required to use OpenExchange.");
        }

        $this->apiKey = $apiKey;
        
        // Define a local temporary file path for fallback caching
        $this->cacheFile = sys_get_temp_dir() . '/open_exchange_rates.json';
    }

    public function getRate(string $from, string $to): float
    {
        $rates = $this->getRatesWithCaching();

        $fromUpper = strtoupper($from);
        $toUpper = strtoupper($to);

        if (!isset($rates[$fromUpper]) || !isset($rates[$toUpper])) {
            throw new Exception("Unsupported currency codes: {$fromUpper} or {$toUpper}.");
        }

        // Convert via base currency (USD) math: (1 / rate_from) * rate_to
        $rateInBase = 1 / $rates[$fromUpper];
        return $rateInBase * $rates[$toUpper];
    }

    /**
     * Fetches rates from memory, local file cache, or external API if expired.
     */
    protected function getRatesWithCaching(): array
    {
        // 1. Memory Cache (Fastest: Useful if getRate() is called multiple times in one loop)
        if ($this->staticCache !== null) {
            return $this->staticCache;
        }

        // 2. File Cache (Fast: Prevents hitting the network if hit within TTL)
        if (file_exists($this->cacheFile) && (time() - filemtime($this->cacheFile) < $this->cacheTtl)) {
            $cachedData = json_decode(file_get_contents($this->cacheFile), true);
            if (isset($cachedData['rates'])) {
                $this->staticCache = $cachedData['rates'];
                return $this->staticCache;
            }
        }

        // 3. Network Fetch (Slowest: Only happens once per hour)
        $rates = $this->fetchFromApi();
        
        $this->staticCache = $rates;
        return $rates;
    }

    /**
     * The raw API caller method
     */
    protected function fetchFromApi(): array
    {
        $url = "{$this->baseUrl}?app_id={$this->apiKey}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Don't let a slow API freeze your entire server
        $response = curl_exec($ch);
        unset($ch);

        if (!$response) {
            throw new Exception("Failed to connect to OpenExchange API.");
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            throw new Exception("OpenExchange Error: " . $data['description']);
        }

        if (!isset($data['rates'])) {
            throw new Exception("Invalid response structure from OpenExchange API.");
        }

        // Save to file cache for next time
        file_put_contents($this->cacheFile, json_encode($data));

        return $data['rates'];
    }
}