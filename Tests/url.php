<?php 

use Tamedevelopers\Support\Translator;

require_once __DIR__ . '/../vendor/autoload.php';

// Powerful support system form any PHP application without the need of setup
// URL Helpers


config_asset('/', true);


dd(
    domain(), 
    domain('admin'),
    asset('zip.php'),

    urlHelper()->server(),
    
    [
        urlHelper()->url(),
        urlHelper()->full(),
        urlHelper()->request(),
        urlHelper()->referral(),
        urlHelper()->http(),
        urlHelper()->host(),
        urlHelper()->path(),
        urlHelper()->path(),
    ]

);

