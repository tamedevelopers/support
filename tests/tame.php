<?php 

use Tamedevelopers\Support\Tame;

require_once __DIR__ . '/../vendor/autoload.php';


// Tame()->HeadersSent();

$volume = [
    1 => ['length' => 20, 'width' => 10, 'height' => 40,],
    2 => ['length' => 120, 'width' => 87, 'height' => 450,],
];

$tame = new Tame();

$VolumeWeight = $tame->calculateVolumeWeight(
    $volume[1]['length'], 
    $volume[1]['width'], 
    $volume[1]['height'], 
    false, 0.5
);

$CubicMeterWeight = $tame->calculateCubicMeterWeight(
    $volume[2]['length'], 
    $volume[2]['width'], 
    $volume[2]['height'], 
    false, 0.1
);

$placeholder = $tame->svgPlaceholder('AliExpress');

echo "
    <img src='{$placeholder}' style='width:200px; height: 150px; object-fit: cover; margin-bottom: 10px;'>
";

dd(
    [
      $tame->urlExist('google.com'),
      $tame->isInternetAvailable(), 
    ],
    [
        $tame->countDivisibleNumbers(100, 680),
        $tame->byteToUnit(6880),
        $tame->sizeToBytes('24mb'),
        $tame->fileTime('tests/tame.php')
    ], 

    'br',
    $VolumeWeight,
    $CubicMeterWeight,

    [
        Tame()->getBetweenBoxLengthAndWeightInKg(20, 10, 40, $VolumeWeight, true, 0.5),
        Tame()->getBetweenBoxLengthAndWeightInCMB(20, 10, 40, $CubicMeterWeight),
        Tame()->kgToGrams(2.7),
        Tame()->gramsToKg(2701.0),
        Tame()->calPercentageBetweenNumbers(2701.0, 4320),
        Tame()->formatNumberToNearestThousand(300196366636753)
    ],

    
); 