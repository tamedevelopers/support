<?php

namespace Tamedevelopers\Support\Engines;

use Exception;

class ExchangeRateHostEngine extends CachedEngine
{
    protected string $baseUrl = 'https://api.exchangerate.host/latest?base=EUR';
    protected ?string $apiKey = null;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey;
        parent::__construct('exchangerate.host');
    }

    public function getRate(string $from, string $to): float
    {
        $rates = $this->getRatesWithCaching();

        $fromUpper = strtoupper($from);
        $toUpper = strtoupper($to);

        if (!isset($rates[$fromUpper]) || !isset($rates[$toUpper])) {
            throw new Exception("Unsupported currency codes via ExchangeRateHost: {$fromUpper} or {$toUpper}.");
        }

        return (1 / $rates[$fromUpper]) * $rates[$toUpper];
    }

    protected function fetchFromApi(): array
    {
        $url = $this->baseUrl;
        if (!empty($this->apiKey)) {
            $url .= '&access_key=' . urlencode($this->apiKey);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        unset($ch);

        if (!$response) {
            throw new Exception("Failed to connect to ExchangeRateHost API.");
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new Exception("ExchangeRateHost API returned invalid JSON.");
        }

        if (isset($data['success']) && $data['success'] === false) {
            $message = $data['error']['info'] 
                ?? $data['error']['type'] 
                ?? 'Unknown ExchangeRateHost error.';
            
            throw new Exception("ExchangeRateHost Error: {$message}");
        }

        if (!isset($data['rates']) || !is_array($data['rates'])) {
            throw new Exception("Invalid response structure from ExchangeRateHost API.");
        }

        file_put_contents($this->cacheFile, json_encode(['rates' => $data['rates']]));

        return $data['rates'];
    }
}