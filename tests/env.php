<?php

use Tamedevelopers\Support\Env;

require_once __DIR__ . '/../vendor/autoload.php';


$env = new Env();

// loading the environment (.env)
$env->load();

dd(
    [
        $env->isDotEnvInstanceAvailable(), 
        $env->isApplicationOnDebug(),
    ],
    [
        $env->isEnvStarted(),
        $env->isEnvFileLoaded()
    ],

    $env->environment('live', true)
);
