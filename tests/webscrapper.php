<?php 

use Tamedevelopers\Support\WebScraper;

require_once __DIR__ . '/../vendor/autoload.php';

$url = 'https://www.jumia.com.ng/20000-mah-utra-slim-portable-power-bank-ace-elec-mpg11495891.html';



dd(
    (new WebScraper)
        ->setUrl($url)
        ->setSelectors([
            'title' => 'h1',
            'price' => 'span.price',
            'description' => 'p.description',
        ])
        ->fetch()
        ->getData()
);