<?php

namespace Tamedevelopers\Support\Engines;

use Exception;

class ExchangeRateHostEngine extends CachedEngine
{
    protected string $baseUrl = 'https://api.exchangerate.host/latest?base=EUR';

    public function __construct()
    {
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        unset($ch);

        if (!$response) {
            throw new Exception("Failed to connect to ExchangeRateHost API.");
        }

        $data = json_decode($response, true);

        if (isset($data['result']) && $data['result'] === 'error') {
            throw new Exception("ExchangeRateHost Error: " . ($data['error-type'] ?? 'Unknown error'));
        }

        if (!isset($data['rates'])) {
            throw new Exception("Invalid response structure from ExchangeRateHost API.");
        }

        file_put_contents($this->cacheFile, json_encode($data));

        return $data['rates'];
    }
}