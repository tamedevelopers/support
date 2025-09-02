<?php 

use Tamedevelopers\Support\PDF;

require_once __DIR__ . '/../vendor/autoload.php';

$name = strtotime('now') . '.pdf';

PDF::create([
    'content'     => '<h1>Hello World! <p>Good that im here.</p></h1>',
    'destination' => base_path("Tests/{$name}"),
    'output'      => 'view',
]);