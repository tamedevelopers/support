<?php 

use Tamedevelopers\Support\WebScraper;

require_once __DIR__ . '/../vendor/autoload.php';

$url = 'https://www.jumia.com.ng/20000-mah-utra-slim-portable-power-bank-ace-elec-mpg11495891.html';
$url2 = 'https://www.jumia.com.ng/ashion-kq-mens-stylish-grey-silver-turf-football-shoes-durable-training-sports-footwear-for-match-casual-419139185.html';


dd(
    (new WebScraper)
        ->setUrl('https://www.aliexpress.com/item/1005010595606666.html')
        ->setSelectors([
            'title' => 'h1',
            'price' => 'span.price',
            'description' => 'p.description',
        ])
        ->fetch()
        ->getData()
);