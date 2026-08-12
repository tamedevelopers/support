<?php

use Tamedevelopers\Support\Exchange;
use Tamedevelopers\Support\Geocoder;
use Tamedevelopers\Support\Process\Http;

require_once __DIR__ . '/../vendor/autoload.php';


// $response = Http::timeout(4)->get('https://maps.googleapis.com/maps/api/geocode/json', [
//     'address' => 'asoro benin city',
//     'key'     => '',
// ]);

dd(
    // $response->json(),
    Geocoder::geocode('Hong Kong, Cheung Sha Wan, Wing Hong St, 83號16樓B9室'),
    Geocoder::geocode('Hong Kong, Cheung Sha Wan, Wing Hong St'),
);