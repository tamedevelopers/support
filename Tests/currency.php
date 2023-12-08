<?php

use Tamedevelopers\Support\NumberToWords;

require_once __DIR__ . '/../vendor/autoload.php';


dd(

    NumberToWords::text(1205435349345443534),

    NumberToWords::text(455987.09, 'nga', true),

    NumberToWords::text(34590323, 'FRA', true),

    NumberToWords::text('120.95', 'tUr', true),

    NumberToWords::text(1999),

    // NumberToWords::CurrencyNames()

);
