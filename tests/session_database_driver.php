<?php

use Tamedevelopers\Support\Process\SessionManager;

require __DIR__ . '/../vendor/autoload.php';

// SQLite for quick testing
$dsn = 'sqlite:' . __DIR__ . '/../storage/sessions.sqlite';

$session = new SessionManager([
    'driver' => 'database',
    'lifetime' => 1800,
    'database' => [
        'dsn' => $dsn,
        'username' => null,
        'password' => null,
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ],
        'table' => 'sessions',
    ],
]);

$session->start();
$session->put('db_key', 'db_value');
$session->forget('db_key');
$session->destroy('db_key');

echo 'Session ID: ' . $session->id() . PHP_EOL;
echo 'db_key=' . $session->get('db_key') . PHP_EOL;