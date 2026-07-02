<?php

use Tamedevelopers\Support\Exchange;

require_once __DIR__ . '/../vendor/autoload.php';


// Fallback order: try ExchangeRateHost first, then ER-API, then FloatRates.
// This gives the highest practical uptime across free sources.

$exchange = new Exchange();

// $exchange->setEngine(
//     new ExchangeRateHostEngine('sss')
// );

dd(
    $exchange->convert('USD', 'NGN', 1),
    $exchange->convert('USD', 'GHS', 1),
);