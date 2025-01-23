<?php 

use Tamedevelopers\Support\Translator;

require_once __DIR__ . '/../vendor/autoload.php';

// Powerful support system form any PHP application without the need of setup
// URL Helpers


config_asset('/', true);


dump(
    domain(), 
    domain('admin'),
    tasset('zip.php'),
    tasset('zip.php', true, true),

    urlHelper()->server(),

    [
        urlHelper()->server(),
        urlHelper()->url(),
        urlHelper()->full(),
        urlHelper()->request(),
        urlHelper()->referral(),
        urlHelper()->http(),
        urlHelper()->host(),
        urlHelper()->path(),
    ]

);

$unitImg    = tasset('thousand_units.png');
$unitImg2   = tasset('thousand_units.png', true, true);

?>

<a href="<?= $unitImg?>" target="_blank">
  <img src="<?= $unitImg?>" alt="units" width="300">  
</a>

<a href="<?= $unitImg2?>" target="_blank">
  <img src="<?= $unitImg2?>" alt="units" width="300">  
</a>