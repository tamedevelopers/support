<?php

use Tamedevelopers\Support\NumberToWords;

require_once __DIR__ . '/../vendor/autoload.php';

dd(
    
    NumberToWords::cents(true)->iso('FRA')->value('4531232221205435349345443534.21')->toText(),

    // NumberToWords::iso('FRA')->cents(true)->value('1000000000000000057857959942726969827393378689175040438172647424')->toText(),

    NumberToWords::iso('FRA')->cents(true)->value(34590323.231)->toText(),

    NumberToWords::value(12300000.698)->cents(true)->toText(),

    // comma is used to seperate decimals
    NumberToWords::value('Thirty-four million five hundred and ninety thousand three hundred and 
        twenty-three euro, two hundred and thirty-one cents')
        ->cents(false)
        ->toNumber(),

    // comma is used to seperate decimals
    NumberToWords::value('Four octillion five hundred and thirty-one septillion two hundred 
            and thirty-two sextillion two hundred and twenty-one quintillion two 
            hundred and five quadrillion four hundred and thirty-five trillion 
            three hundred and forty-nine billion three hundred and forty-five 
            million four hundred and forty-three thousand five hundred and 
            thirty-four Euro, twenty-one cents')
        ->cents(false)
        ->toNumber(),


    NumberToWords()->getCurrencyByIso3('nga'),
    // NumberToWords()->allCurrency(),
    // NumberToWords::getUnits(),
);
