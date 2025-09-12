<?php 

use Tamedevelopers\Support\NameToImage;

require_once __DIR__ . '/../vendor/autoload.php';

$ntoimage = new NameToImage();


dd(
    $ntoimage->create([
        'name' => 'Tamedevelopers Peterson Moore',
        'type' => 'circle',
        'output' => 'view'
    ])
);