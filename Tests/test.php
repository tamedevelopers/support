<?php 

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;

require_once __DIR__ . '/../vendor/autoload.php';


// Env::loadOrFail();


// Tame::unlink('welcome.png', 'default.png')

// Tame::platformIcon('windows')

// Tame::byteToUnit(2235235235)

// Tame::sizeToBytes('1.5mb')

// Tame::checkHeadersSent()

// Tame::include('normal.php');
// Tame()->include('normal.php');


$svg = Tame::platformIcon('windows');

include $svg;


// Define an array to sort
$data = [4, 2, 7, 1, 5];

Str::sortArray($data, 'krsort');

dd(
    Tame::platformIcon('windows'), 

    $data,
);

