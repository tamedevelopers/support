<?php 

use Tamedevelopers\Support\WebScraper;

require_once __DIR__ . '/../vendor/autoload.php';

$jumia = 'https://www.jumia.com.ng/20000-mah-utra-slim-portable-power-bank-ace-elec-mpg11495891.html';
$jumia2 = 'https://www.jumia.com.ng/ashion-kq-mens-stylish-grey-silver-turf-football-shoes-durable-training-sports-footwear-for-match-casual-419139185.html';
$ebay = 'https://www.ebay.com/itm/386936766515?epid=25041703781&itmmeta=01KQ5CQ29YPDTKGFFP5J9ZHCHK&hash=item5a173a8033:g:fe4AAOSwVkRmIHzv&itmprp=enc%3AAQALAAAA4DKQclQvzFwZQpmMrsO4LuqtqiBRS9IPy67MwDdNlgVL3m67KPgdV8I%2B7PRdo5hm2RVzOCfBrIP0P6ixbYBfwq8GE4YHfWrzrfmAxNbdxhpU%2F6nUY9zI0iznvJgyG3yaieNAIBiZ%2B3kcuXnIXhDuRbGn2yFJAK3BDcMT%2Ba7D%2For6YYgYa%2Bftdn5fXcMItQ%2Blj6%2FuS%2BSMmScpnXffYS2v66%2FD4VjGkNHWhBXEn%2FiuvBX8fe1X%2B4GFyqki1vJd7AL8%2FsxdrWxDV3OaI1oQyh4zuJ6nBwybkcsAfBb90%2F6RHGXX%7Ctkp%3ABFBMiKXcrLln&var=654209735321';
$aliexpress = 'https://www.aliexpress.com/item/1005010595606666.html';


dd(
    WebScraper::setUrl("
            $aliexpress
        ")
        ->setSelectors([
            // 'title' => 'h1',
            // 'price' => 'span.price',
            // 'description' => 'p.description',
        ])
        // ->chromiumBinary(base_path('chrome-win64/chrome.exe'))
        ->setEngine('dom') //chromium|dom
        ->fetch()
        ->getData()
);