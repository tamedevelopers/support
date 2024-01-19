<?php

use Tamedevelopers\Support\NumberToWords;

require_once __DIR__ . '/../vendor/autoload.php';


dd(

    NumberToWords::iso('nga')->text(1205435349345443534, true),

    NumberToWords::cents(true)->iso('nga')->text(455987.09),

    NumberToWords::cents(true)->iso('FRA')->text(34590323),

    NumberToWords::cents(true)->iso('TUR')->text('120.953'),

    NumberToWords()->cents(false)->text(1999),

    // NumberToWords()->CurrencyNames()

);
