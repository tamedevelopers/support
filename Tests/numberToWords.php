<?php

use Tamedevelopers\Support\NumberToWords;

require_once __DIR__ . '/../vendor/autoload.php';

dd(
    
    // NumberToWords::cents(true)->iso('nga')->value('4531232221205435349345443534.21')->toText(),

    NumberToWords::iso('FRA')->cents(true)->value('1,000,000,000,000,000,057,857,959,942,726,969,827,393,378,689,175,040,438,172,647,424')->toText(),

    NumberToWords::iso('FRA')->cents(true)->value(34590323.231)->toText(),

    NumberToWords::value(12300000.698)->cents(true)->toText(),

    // comma is used to seperate decimals
    NumberToWords::value('Thirty-four million five hundred and ninety thousand three hundred and 
        twenty-three euro, two hundred and thirty-one cents')
        ->cents(false)
        ->toNumber(),


    NumberToWords()->getCurrencyValue('nga'),
    // NumberToWords()->CurrencyNames(),
    // NumberToWords::getUnits(),
);
