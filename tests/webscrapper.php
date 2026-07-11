<?php 

use Tamedevelopers\Support\WebScraper;

require_once __DIR__ . '/../vendor/autoload.php';

$jumia = 'https://www.jumia.com.ng/20000-mah-utra-slim-portable-power-bank-ace-elec-mpg11495891.html';
$jumia2 = 'https://www.jumia.com.ng/ashion-kq-mens-stylish-grey-silver-turf-football-shoes-durable-training-sports-footwear-for-match-casual-419139185.html';
$ebay = 'https://www.ebay.com/itm/386936766515';
$aliexpress = 'https://www.aliexpress.com/item/1005010595606666.html';
$amazon = 'https://www.amazon.com/Instant-Pot-Multi-Use-Programmable-Pressure/dp/B00FLYWNYQ/';

dd(
    WebScraper::url("
            $amazon
        ")
        ->setSelectors([
            // 'title' => 'h1',
            // 'price' => 'span.price',
            // 'description' => 'p.description',
        ])
        // ->chromiumBinary(base_path('chrome-win64/chrome.exe'))
        // ->setEngine('chromium') //chromium|dom
        ->fetch()
        ->getData()
);