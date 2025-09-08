<?php

use Tamedevelopers\Support\Slugify;

require_once __DIR__ . '/../vendor/autoload.php';



dd(
    Slugify::slug('新款 iPhone 12 Pro max', 'cn', '_'),
    Slugify::slug('New Iphone 12 promax'),


    Slugify::locales(),
);