<?php

namespace Tamedevelopers\Support\Engines;

use Exception;

class FloatRatesEngine extends CachedEngine
{
    protected string $baseUrl = 'https://www.floatrates.com/daily/eur.json';

    public function __construct()
    {
        parent::__construct('floatrates');
    }

    public function getRate(string $from, string $to): float
    {
        $rates = $this->getRatesWithCaching();

        $fromUpper = strtoupper($from);
        $toUpper = strtoupper($to);

        $fromRate = ($fromUpper === 'EUR') ? 1.0 : ($rates[$fromUpper] ?? null);
        $toRate = ($toUpper === 'EUR') ? 1.0 : ($rates[$toUpper] ?? null);

        if ($fromRate === null || $toRate === null) {
            throw new Exception("Unsupported currency codes via FloatRates: {$fromUpper} or {$toUpper}.");
        }

        return (1 / $fromRate) * $toRate;
    }

    protected function fetchFromApi(): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        unset($ch);

        if (!$response) {
            throw new Exception("Failed to connect to FloatRates API.");
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new Exception("FloatRates API returned invalid JSON.");
        }

        $rates = ['EUR' => 1.0];
        foreach ($data as $currencyCode => $currencyData) {
            if (!isset($currencyData['rate'])) {
                continue;
            }

            $rates[strtoupper($currencyCode)] = (float) $currencyData['rate'];
        }

        if (count($rates) <= 1) {
            throw new Exception("FloatRates API returned no usable rates.");
        }

        file_put_contents($this->cacheFile, json_encode(['rates' => $rates]));

        return $rates;
    }
}
