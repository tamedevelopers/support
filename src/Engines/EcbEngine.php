<?php

namespace Tamedevelopers\Support\Engines;

use Exception;

class EcbEngine extends CachedEngine
{
    // European Central Bank provides daily rates in XML with EUR as base (completely free, no auth)
    protected string $baseUrl = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    public function __construct()
    {
        parent::__construct('ecb');
    }

    public function getRate(string $from, string $to): float
    {
        $rates = $this->getRatesWithCaching();

        $fromUpper = strtoupper($from);
        $toUpper = strtoupper($to);

        $fromRate = ($fromUpper === 'EUR') ? 1.0 : ($rates[$fromUpper] ?? null);
        $toRate = ($toUpper === 'EUR') ? 1.0 : ($rates[$toUpper] ?? null);

        if ($fromRate === null || $toRate === null) {
            throw new Exception("Unsupported currency codes via ECB: {$fromUpper} or {$toUpper}.");
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
            throw new Exception("Failed to connect to ECB API.");
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);
        libxml_clear_errors();

        if (!$xml) {
            throw new Exception("ECB API returned invalid XML.");
        }

        $rates = ['EUR' => 1.0];

        // Navigate ECB XML structure: Cube/Cube (with time)/Cube (currencies)
        $cubes = $xml->Cube->Cube;
        if (!$cubes) {
            throw new Exception("ECB API returned no rate data.");
        }

        foreach ($cubes->Cube as $cube) {
            $currency = (string) $cube['currency'];
            $rate = (float) $cube['rate'];

            if (!empty($currency) && $rate > 0) {
                $rates[strtoupper($currency)] = $rate;
            }
        }

        if (count($rates) <= 1) {
            throw new Exception("ECB API returned no usable rates.");
        }

        file_put_contents($this->cacheFile, json_encode(['rates' => $rates]));

        return $rates;
    }
}
