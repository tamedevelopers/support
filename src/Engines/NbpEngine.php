<?php

namespace Tamedevelopers\Support\Engines;

use Exception;

class NbpEngine extends CachedEngine
{
    // NBP API returns PLN base rates; we query multiple bases for coverage
    protected string $baseUrl = 'https://api.nbp.pl/api/exchangerates/tables/A?format=json';

    public function __construct()
    {
        parent::__construct('nbp');
    }

    public function getRate(string $from, string $to): float
    {
        $rates = $this->getRatesWithCaching();

        $fromUpper = strtoupper($from);
        $toUpper = strtoupper($to);

        $fromRate = ($fromUpper === 'PLN') ? 1.0 : ($rates[$fromUpper] ?? null);
        $toRate = ($toUpper === 'PLN') ? 1.0 : ($rates[$toUpper] ?? null);

        if ($fromRate === null || $toRate === null) {
            throw new Exception("Unsupported currency codes via NBP: {$fromUpper} or {$toUpper}.");
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
            throw new Exception("Failed to connect to NBP API.");
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !isset($data[0]['rates'])) {
            throw new Exception("NBP API returned invalid JSON structure.");
        }

        $rates = ['PLN' => 1.0];
        foreach ($data[0]['rates'] as $rateEntry) {
            if (!isset($rateEntry['code'], $rateEntry['mid'])) {
                continue;
            }

            $rates[strtoupper($rateEntry['code'])] = (float) $rateEntry['mid'];
        }

        if (count($rates) <= 1) {
            throw new Exception("NBP API returned no usable rates.");
        }

        file_put_contents($this->cacheFile, json_encode(['rates' => $rates]));

        return $rates;
    }
}
