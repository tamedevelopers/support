<?php 

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;

require_once __DIR__ . '/../vendor/autoload.php';


$arrayCollection = [
    0 => [
        'id' => 1,
        'name' => 'Fred Peter',
        'age' => 18,
        'is_active' => 1
    ],
    1 => [
        'id' => 2,
        'name' => 'Cli Patton',
        'age' => 25,
        'is_active' => 0
    ]
];

$changeArrayKeys = Str::changeKeysFromArray(
    $arrayCollection,
    'is_active',
    'active'
);

$removeArrayKeys = Str::removeKeysFromArray(
    $arrayCollection,
    ['is_active', 'id']
);

dd(
    $changeArrayKeys,
    $removeArrayKeys,
    
    Str::snake('Peterso More'),
    Str::camel('peterson more'),
    Str::studly('peterson more'),
    Str::kebab('Peterson More'),
    Str::slug('Peterson More'),
    Str::slugify('【2023最新】香港郵政本地平郵郵費計算、基本郵費一覽', 'cn'),
    Str::random(),
    Str::uuid(),
    Str::randomWords(10),
    Str::mask('tamedevelopers@gmail.com', 2, 'left'),
    Str::shorten('【2023最新】香港郵政本地平郵郵費計算、基本郵費一覽', 20),
    Str::html('<span class="pul-text pul-text--bold smb-web-view-dynamic-list-item-title">lhkexpressvps.com <script></script></span>'),
    Str::text('<span class="pul-text pul-text--bold smb-web-view-dynamic-list-item-title">lhkexpressvps.com <script></script></span>'),
    Str::encrypt('hoping for more'),
    Str::decrypt('{"k":"dadb5dd1a0558257","e":"7ZMcZv6tALEVq4k7MHpJCQ==","s":"cUFmY0ZwRlpobVJ6bGxTYUJrVDdydz09"}'),
    Str::phone('+234 (90) 012-234'),
    Str::phone('+234 (90) + - 012-234', false),
);

