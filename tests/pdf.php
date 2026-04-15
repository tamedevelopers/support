<?php 

use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\PDF;

require_once __DIR__ . '/../vendor/autoload.php';

$name = strtotime('now') . '.pdf';


$template = File::get(base_path('template2.html'));


PDF::create([
    // 'content'     => '<h1>Hello World! <p>Good that im here.</p></h1>',
    'content'     => $template,
    'isRemoteEnabled' => true,
    'destination' => base_path("tests/{$name}"),
    'output'      => 'view',
]);