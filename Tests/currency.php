<?php

use Tamedevelopers\Support\NumberToWords;

require_once __DIR__ . '/../vendor/autoload.php';


dd(

    NumberToWords::iso('nga')->number(1205435349345443534, true)->translate(),

    NumberToWords::cents(true)->iso('nga')->number(455987.09)->translate(),

    NumberToWords::iso('FRA')->number(34590323, true)->translate(),

    NumberToWords::iso('TUR')->number('120.953', true)->translate(),

    NumberToWords()->number(1999),

    // NumberToWords()->CurrencyNames()

    NumberToWords()->iso('TUR')->number('120.953', true)->translate()

);
