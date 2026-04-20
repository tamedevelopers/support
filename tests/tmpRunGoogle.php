<?php

declare(strict_types=1);

use Tamedevelopers\Support\ChromePdf\ChromePdf;

require __DIR__ . '/../vendor/autoload.php';

$start = microtime(true);
ChromePdf::create()
    ->fromUrl('https://www.google.com')
    ->margin(20)
    ->generate();

echo 'google=' . number_format(microtime(true) - $start, 2) . "s\n";
