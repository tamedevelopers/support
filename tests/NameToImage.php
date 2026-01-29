<?php 

use Tamedevelopers\Support\NameToImage;

require_once __DIR__ . '/../vendor/autoload.php';

$ntoimage = new NameToImage();


// 1) Provide a directory as destination; slug is appended automatically
$path = NameToImage::run([
    'name' => 'John Doe',
    'bg_color' => '#04068dff',     // 8-digit hex supported
    'text_color' => 'rgba(255,255,255,1)',
    'destination' => base_path('storage/avatars'),
    'generate' => true, 
]);

// 3) Auto-fit font size (no touching edges)
$path = NameToImage::run([
    'name' => '王小明',
    'font_weight' => 'bold',
    'bg_color' => [147, 51, 234],
    'text_color' => '#ffffff',
    // 'font_path' => __DIR__ . '/fonts/Inter-Bold.ttf', // recommended for best results
    'destination' => base_path('storage/avatars'),
]);


dd(
    $ntoimage->run([
        'name' => 'Tamedevelopers Peterson Moore',
        'font_weight' => 'bold',
        'type' => 'radius',
        'output' => 'save'
    ]),

    $ntoimage->run([
        'name' => 'Oluchi Grace',
        'font_weight' => 'bold',
        'bg_color' => '#063903ff',
        'type' => 'circle',
        'output' => 'save'
    ]),
    'sss'
);