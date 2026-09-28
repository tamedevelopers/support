<?php

require_once __DIR__ . '/../vendor/autoload.php';



$recovery   = TameRecoveryKey();
$batchCode  = $recovery->generateBatchCode();
$hasedCode  = $recovery->hash($batchCode);


dd(
    $batchCode,
    $hasedCode,
    $recovery->verify($batchCode, $hasedCode),
    $recovery->verifyAndConsume($batchCode, $hasedCode),
);

