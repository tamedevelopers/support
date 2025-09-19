<?php 

use Tamedevelopers\Support\Zip;

require_once __DIR__ . '/../vendor/autoload.php';

// Powerful Zip Class


$gzip = Zip::gzip('domains.json', 'domains.json.gz');
$zip = Zip::zip('tests', 'newData.zip');


// TameZip()->zip('tests', 'newData.zip');
// TameZip()->unzip('newData.zip', '/');
// TameZip()->download('newData.zip');

$folderPath = 'tests/layout';
$filePath = base_path('hello.php');

dd(
    $gzip->compress(),
    $zip,

    $folderPath,
    $filePath
);

