<?php 

use Tamedevelopers\Support\WebScraper;

require_once __DIR__ . '/../vendor/autoload.php';

$url = 'https://www.jumia.com.ng/20000-mah-utra-slim-portable-power-bank-ace-elec-mpg11495891.html';
$url2 = 'https://www.jumia.com.ng/ashion-kq-mens-stylish-grey-silver-turf-football-shoes-durable-training-sports-footwear-for-match-casual-419139185.html';


dd(
    (new WebScraper)
        ->setUrl('https://www.aliexpress.com/item/1005010595606666.html?spm=a2g0o.tm1000029706.d1.1.519c474cIxz1fd&sourceType=561&pvid=5be201e4-688d-408d-9406-d9afd9354f39&pdp_ext_f=%7B%22ship_from%22%3A%22CN%22%2C%22sku_id%22%3A%2212000052930334905%22%7D&scm=1007.28480.478283.0&scm-url=1007.28480.478283.0&scm_id=1007.28480.478283.0&aecmd=true')
        ->setSelectors([
            'title' => 'h1',
            'price' => 'span.price',
            'description' => 'p.description',
        ])
        ->fetch()
        ->getData()
);