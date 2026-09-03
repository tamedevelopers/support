<?php

use Tamedevelopers\Support\DistanceCalculator;

require_once __DIR__ . '/../vendor/autoload.php';

// Using Sea mode
$sea = DistanceCalculator::make()
        ->viaSea()
        ->origin(6.4531, 3.3958)     // Lagos Port
        ->destination(51.9244, 4.4777); // Rotterdam Port

// Using Air mode
$air = DistanceCalculator::make()
        ->viaAir()
        ->origin(6.4531, 3.3958)     // Lagos Port
        ->destination(51.9244, 4.4777); // Rotterdam Port


dd(
    $sea->miles(true) . ' Miles',
    $sea->nauticalMiles(true) . ' NM',
    $sea->toSeaAmountFcl(3000) . ' USD',
    $sea->toSeaAmountLcl([
        'length' => 120,
        'width'  => 80,
        'height' => 160,
        'weight' => 450, // in kg
        'quantity' => 2,
    ], ratePerWm: 50),
    $sea->toFormattedDuration(true),

    $air->miles(true) . ' Miles',
    $air->toAirAmount(12.5, 1, 6) . ' USD',
    $air->toFormattedDuration(true),
);