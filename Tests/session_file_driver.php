<?php

use Tamedevelopers\Support\Process\SessionManager;

require __DIR__ . '/../vendor/autoload.php';

// File session driver with custom directory
$sessionPath = __DIR__ . '/../storage/sessions';
$session = new SessionManager([
    'driver' => 'file',
    'path' => $sessionPath,
    'lifetime' => 1800,
]);

$session->start();
$session->put('foo', 'bar');

echo 'Session ID: ' . $session->id() . PHP_EOL;
echo 'foo=' . $session->get('foo') . PHP_EOL;