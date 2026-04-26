<?php

use Tamedevelopers\Support\TextToImage;

require_once __DIR__ . '/../vendor/autoload.php';

$ntoimage = new TextToImage();


// Shapes: diagonal | stripe | ring | gloss | corner | split (solid fills only, no stroke seam)
// 1) Square + diagonal + cosmic gradient

// Type: circle | radius | square | reuleaux3 | reuleaux | hexagon | decagram | octagram
$path1 = TextToImage::run([
    'name' => 'John Doe',
    'font_weight' => 'normal',
    'bg_color' => '#008000',
    // 'text_color' => 'rgb(255, 0, 0)',
    'transparent' => true,
    'type' => 'octagram',
    'shape' => 'split',
    // 'gradient' => 'cosmic',
]);

$path2 = TextToImage::run([
    'name' => 'GitHub Microsoft',
    'font_weight' => 'bold',
    'bg_color' => '#000000',
    'text_color' => '#4A5568',
    'type' => 'circle',
    'shape' => 'diagonal',
]);

// $path3 = $ntoimage->run([
//     'name' => 'Tamedevelopers Peterson Moore',
//     'font_weight' => 'bold',
//     'text_color' => '#26012b',
//     'gradient' => 'aurora',
// ]);

// $path4 = $ntoimage->run([
//     'name' => 'Facebook',
//     'font_weight' => 'bold',
//     'bg_color' => '#063903ff',
//     'text_color' => '#cae6ff',
//     'type' => 'radius',
//     'gradient' => 'forest',
// ]);

// $path5 = $ntoimage->run([
//     'name' => '王小明',
//     'font_weight' => 'normal',
//     'bg_color' => [147, 51, 234],
//     'text_color' => '#ffffff',
// ]);

// $path6 = $ntoimage->run([
//     'name' => 'آية. مليون.',
//     'font_weight' => 'normal',
//     'type' => 'circle',
// ]);

    dump(
        $path1
    );
?>


<div style="background-color:rgb(10, 85, 106); text-align: center;">
    <img src="<?= $path1['url']; ?>" alt="Text to Image">
    <img src="<?= $path2['url']; ?>" alt="Text to Image">
</div>


<!-- 
<img src="<?= $path3['url']; ?>" alt="Text to Image">
<img src="<?= $path4['url']; ?>" alt="Text to Image">
<img src="<?= $path5['url']; ?>" alt="Text to Image">
<img src="<?= $path6['url']; ?>" alt="Text to Image"> -->
