<?php 

use Tamedevelopers\Support\WebScraper;

require_once __DIR__ . '/../vendor/autoload.php';


$url = [
    'jiji' => 'https://jiji.ng/lekki/cars/kia-sportage-ex-4dr-suv-2-4l-4cyl-6a-2012-gray-GZO5ddb73LGlt7jYDg9AU7E.html',
    'ebay' => 'https://www.ebay.com/itm/365749807558',
    'ebay2' => 'https://www.ebay.com/itm/386936766515',
    'jumia' => 'https://www.jumia.com.ng/nivea-sun-uv-sunscreen-face-shine-control-cream-spf-50-50ml-pack-of-2-404808434.html',
    'jumia2' => 'https://www.jumia.com.ng/20000-mah-utra-slim-portable-power-bank-ace-elec-mpg11495891.html',
    'jumia3' => 'https://www.jumia.com.ng/ashion-kq-mens-stylish-grey-silver-turf-football-shoes-durable-training-sports-footwear-for-match-casual-419139185.html',
    'aliexpress' => 'https://www.aliexpress.com/item/1005008737860974.html',
    'aliexpress2' => 'https://www.aliexpress.com/item/1005010595606666.html',
    'walmart' => 'https://www.walmart.com/ip/Acer-Nitro-ED270RS3-27-Class-Full-HD-Gaming-LCD-Monitor-16-9-Black/5046447739',
    'amazon' => 'https://www.amazon.com/Instant-Pot-Multi-Use-Programmable-Pressure/dp/B00FLYWNYQ/'
];


$scraper = WebScraper::url($url['jumia3'])
            ->setEngine('dom')
            ->fetch()
            ->getData();

echo "
    <img src='{$scraper['main_image']}' style='width:200px; height: 150px; object-fit: cover; margin-bottom: 10px;'>
";

dd(
    $scraper->toArray()
);