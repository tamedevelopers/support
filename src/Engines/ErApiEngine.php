<?php

namespace Tamedevelopers\Support\Engines;

use Exception;

class ErApiEngine extends CachedEngine
{
    // Use EUR as the base to align with other engines
    protected string $baseUrl = 'https://open.er-api.com/v6/latest/EUR';

    public function __construct()
    {
        parent::__construct('erapi');
    }

    public function getRate(string $from, string $to): float
    {
        $rates = $this->getRatesWithCaching();

        $fromUpper = strtoupper($from);
        $toUpper = strtoupper($to);

        // Treat EUR as 1.0 since endpoint is requested with EUR as base
        $fromRate = ($fromUpper === 'EUR') ? 1.0 : ($rates[$fromUpper] ?? null);
        $toRate = ($toUpper === 'EUR') ? 1.0 : ($rates[$toUpper] ?? null);

        if ($fromRate === null || $toRate === null) {
            throw new Exception("Unsupported currency codes via ER-API: {$fromUpper} or {$toUpper}.");
        }

        return (1 / $fromRate) * $toRate;
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
            throw new Exception("Failed to connect to ER-API.");
        }

        $data = json_decode($response, true);

        if (isset($data['result']) && $data['result'] !== 'success') {
            throw new Exception("ER-API Error: " . ($data['error-type'] ?? 'Unknown error'));
        }

        if (!isset($data['rates'])) {
            throw new Exception("Invalid response structure from ER-API.");
        }

        file_put_contents($this->cacheFile, json_encode($data));

        return $data['rates'];
    }
}
