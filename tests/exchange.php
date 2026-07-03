<?php

use Tamedevelopers\Support\Exchange;

require_once __DIR__ . '/../vendor/autoload.php';


// Fallback order: try ExchangeRateHost first, then ER-API, then FloatRates.
// This gives the highest practical uptime across free sources.

$exchange = new Exchange();

// $exchange->highExchange();
// $exchange->openExchange($apiKey);
// $exchange->exchangeRate($apiKey);

dd(
    $exchange->rate('USD', 'NGN'),
    $exchange->convert('USD', 'NGN', 1),
    $exchange->convert('USD', 'GHS', 1),
);