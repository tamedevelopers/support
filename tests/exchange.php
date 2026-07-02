<?php

use Tamedevelopers\Support\Engines\ErApiEngine;
use Tamedevelopers\Support\Engines\FloatRatesEngine;
use Tamedevelopers\Support\Engines\ExchangeRateHostEngine;
use Tamedevelopers\Support\Engines\OpenExchangeEngine;
use Tamedevelopers\Support\Exchange;

require_once __DIR__ . '/../vendor/autoload.php';


// Fallback order: try ExchangeRateHost first, then ER-API, then FloatRates.
// This gives the highest practical uptime across free sources.

$exchange = new Exchange();

$exchange->setEngine(
    new OpenExchangeEngine()
);

dd(
    $exchange->rate('USD', 'NGN')
);