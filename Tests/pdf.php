<?php 

use Tamedevelopers\Support\PDF;

require_once __DIR__ . '/../vendor/autoload.php';


PDF::create([
    'content'     => '<h1>Hello World! <p>Good that im here.</p></h1>',
    // 'destination' => base_path('Tests/1735048049.pdf'),
    'destination' => strtotime('now') . '.pdf',
    'output'      => 'view',
]);