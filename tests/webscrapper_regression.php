<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Tamedevelopers\Support\WebScraper;

$webScraper = new WebScraper([]);
$reflection = new ReflectionClass(WebScraper::class);
$method = $reflection->getMethod('normalizePriceValue');
$method->setAccessible(true);

$cases = [
    '11400001140000.883200883200.23' => '1140000.883200.23',
    'NGN 1,140,000.83' => '1140000.83',
    '$19.99' => '19.99',
];

foreach ($cases as $input => $expected) {
    $result = $method->invoke($webScraper, $input);
    if ($result !== $expected) {
        fwrite(STDERR, "FAIL for input {$input}: got {$result}, expected {$expected}\n");
        exit(1);
    }
}

echo "PASS\n";
