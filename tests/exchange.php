<?php

use Tamedevelopers\Support\Engines\ErApiEngine;
use Tamedevelopers\Support\Engines\ExchangeRateHostEngine;
use Tamedevelopers\Support\Engines\FloatRatesEngine;
use Tamedevelopers\Support\Engines\FallbackExchangeEngine;
use Tamedevelopers\Support\Exchange;

require_once __DIR__ . '/../vendor/autoload.php';


// Fallback order: try ExchangeRateHost first, then ER-API, then FloatRates.
// This gives the highest practical uptime across free sources.

$exchange = new Exchange(
    new FloatRatesEngine()
    // new FallbackExchangeEngine([
    //     new ErApiEngine(),
    //     new FloatRatesEngine(),
    //     new ExchangeRateHostEngine(),
    // ])
);

dd(
    $exchange->rate('USD', 'NGN')
);