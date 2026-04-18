<?php 

use Tamedevelopers\Support\TextToImage;

require_once __DIR__ . '/../vendor/autoload.php';

$ntoimage = new TextToImage();


// 1) Provide a directory as destination; slug is appended automatically
$path1 = TextToImage::run([
    'name' => 'John Doe',
    'font_weight' => 'normal', //normal|bold
    'bg_color' => '#04068dff',     // 8-digit hex supported
    'text_color' => 'rgba(255,255,255,1)',
    'generate' => false, 
    'type' => 'diagonal', //'circle', 'radius', 'square', 'gradient', 'diagonal'
    'output' => 'save', // download|view|save|data,
    'destination' => base_path('storage/avatars'),
    // 'font_path' => __DIR__ . '/fonts/Inter-Bold.ttf', // recommended for best results
]);

// 3) Auto-fit font size (no touching edges)
$path2 = TextToImage::run([
    'name' => '王小明',
    'font_weight' => 'normal',
    'bg_color' => [147, 51, 234],
    'text_color' => '#ffffff',
    'type' => 'square',
]);

$path3 = $ntoimage->run([
    'name' => 'Tamedevelopers Peterson Moore',
    'font_weight' => 'bold',
    'type' => 'radius',
    'text_color' => '#26012b',
]);

$path4 = $ntoimage->run([
    'name' => 'Facebook',
    'font_weight' => 'bold',
    'bg_color' => '#063903ff',
    'text_color' => '#cae6ff',
    'type' => 'gradient',
]);

$path5 = $ntoimage->run([
    'name' => 'GitHub Microsoft',
    'font_weight' => 'bold',
    'bg_color' => '#000000',
    'text_color' => '#4A5568',
    'type' => 'circle',
]);

$path6 = $ntoimage->run([
    'name' => 'آية. مليون.',
    'font_weight' => 'normal',
    'type' => 'circle',
]);

?>

<img src="<?php echo $path1['url']; ?>" alt="Text to Image">
<img src="<?php echo $path2['url']; ?>" alt="Text to Image">
<img src="<?php echo $path3['url']; ?>" alt="Text to Image">
<img src="<?php echo $path4['url']; ?>" alt="Text to Image">
<img src="<?php echo $path5['url']; ?>" alt="Text to Image">
<img src="<?php echo $path6['url']; ?>" alt="Text to Image">