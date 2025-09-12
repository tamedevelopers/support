<?php 

use Tamedevelopers\Support\NameToImage;

require_once __DIR__ . '/../vendor/autoload.php';

$ntoimage = new NameToImage();


// 1) Provide a directory as destination; slug is appended automatically
$path = NameToImage::create([
    'name' => 'John Doe',
    'bg_color' => '#04068dff',     // 8-digit hex supported
    'text_color' => 'rgba(255,255,255,1)',
    'destination' => base_path('storage/avatars'),
    'regenerate' => true, 
]);

// 2) Provide base path without .png; slug is appended
$path = NameToImage::create([
    'name' => 'Alice',
    'type' => 'circle',
    'destination' => base_path('storage/avatars/custom'),
]);

// 3) Auto-fit font size (no touching edges)
$path = NameToImage::create([
    'name' => 'Jane Smith',
    'bg_color' => [147, 51, 234],
    'text_color' => '#ffffff',
    'font_path' => __DIR__ . '/fonts/Inter-Bold.ttf', // recommended for best results
    'destination' => base_path('storage/avatars'),
]);


dd(
    $ntoimage->create([
        'name' => 'Tamedevelopers Peterson Moore',
        'type' => 'circle',
        'output' => 'view'
    ])
);