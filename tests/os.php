<?php

use Tamedevelopers\Support\Str;

require_once __DIR__ . '/../vendor/autoload.php';


dd(
    Str::serverSoftware(),
    Str::osMode(),
    Str::osMode('desktop'),
    Str::amount('$ 275,900.43'),
); 