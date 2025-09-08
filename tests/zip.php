<?php 

use Tamedevelopers\Support\Zip;

require_once __DIR__ . '/../vendor/autoload.php';

// Powerful Zip Class


// Zip::zip('tests', 'newData.zip');

// TameZip()->zip('tests', 'newData.zip');
// TameZip()->unzip('newData.zip', '/');
// TameZip()->download('newData.zip');

$folderPath = 'tests/layout';
$basePath = base_path('hello.php');

dd(
    'nothing has been zipped yet',
    $folderPath,
    $basePath
);

