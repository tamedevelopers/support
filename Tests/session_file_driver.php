<?php

use Tamedevelopers\Support\Process\SessionManager;

require __DIR__ . '/../vendor/autoload.php';

// File session driver with custom directory
$sessionPath = __DIR__ . '/../storage/sessions';
$session = new SessionManager([
    'driver' => 'file',
    // If omitted, defaults to storage_path('session')
    // 'path' => $sessionPath,
    'lifetime' => 1800,
]);

$session->start();
$session->put('foo', 'bar');
$session->destroy('foo');

echo 'Session ID: ' . $session->id() . PHP_EOL;
echo 'foo=' . var_export($session->get('foo'), true) . PHP_EOL;