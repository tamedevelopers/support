<?php 

use Tamedevelopers\Support\Zip;

require_once __DIR__ . '/../vendor/autoload.php';

// Powerful Zip Class


$zip = Zip::zip('testss', 'newData.zip');
$rar = Zip::rar('tests', 'newData.rar');
$gzip = Zip::gzip('tests', 'newData.gz');

// TameZip()->zip('tests', 'newData.zip');
// TameZip()->unzip('newData.zip', '/');
// TameZip()->download('newData.zip');

$folderPath = 'tests/layout';
$basePath = base_path('hello.php');

dd(
    $zip,
    $rar,
    $gzip,
    $zip->compress(),
    'nothing has been zipped yet',
    $folderPath,
    $basePath
);

