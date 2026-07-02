<?php

use Tamedevelopers\Support\Engines\ErApiEngine;
use Tamedevelopers\Support\Engines\ExchangeRateHostEngine;
use Tamedevelopers\Support\Engines\OpenExchangeEngine;
use Tamedevelopers\Support\Exchange;

require_once __DIR__ . '/../vendor/autoload.php';


// OpenExchangeEngine
// ErApiEngine
// ExchangeRateHostEngine


$exchange = new Exchange(
    new ExchangeRateHostEngine()
);

dd(
    $exchange->rate('USD', 'EUR')
);