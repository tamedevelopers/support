<?php 

use Tamedevelopers\Support\Zip;

require_once __DIR__ . '/../vendor/autoload.php';

// Powerful Zip Class


$zip = Zip::zip('vendor', 'newData.zip');
$rar = Zip::rar('tests', 'newData.rar');
$gzip = Zip::gzip('domains.json', 'domains.json.gz');

// TameZip()->zip('tests', 'newData.zip');
// TameZip()->unzip('newData.zip', '/');
// TameZip()->download('newData.zip');

$folderPath = 'tests/layout';
$basePath = base_path('hello.php');

dd(
    $zip->compress(),
    $gzip->compress(),
    $rar,
    'nothing has been zipped yet',
    $folderPath,
    $basePath
);

