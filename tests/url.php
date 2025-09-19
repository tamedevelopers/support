<?php 

use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Process\Http;
use Tamedevelopers\Support\Process\HttpRequest;

require_once __DIR__ . '/../vendor/autoload.php';

// Powerful support system form any PHP application without the need of setup
// URL Helpers


config_asset('/', true);

$http = new Http();

dump(
    [
      'Domain: ' . domain(), 
      'Domain with path: ' . domain('admin'),
      'Assets no Cache: ' . tasset('zip.php', false),
      'Assets with Cache & Path Relative: ' . tasset('zip.php', true, true),
    ], // relative link path

    [
      'IP: ' . HttpRequest::ip(),
      'Method: ' . $http->method(),
      'Server: ' . $http->server(),
      'Request: ' . $http->request(),
      'Referral: ' . $http->referral(),
      'URI: ' . $http->uri(),
      'URL: ' . $http->url(),
      'Full URI: ' . $http->full(),
      'Http: ' . $http->http(),
      'Host: ' . $http->host(),
      'Path: ' . $http->path(),
      'Is AJAX: ' . ($http->isAjax() ? 'yes' : 'no'),
      'Accessed via 127.0.0.1: ' . ($http->isIpAccessedVia127Port() ? 'yes' : 'no'),
    ],

    [
      $http->query('query'),
      $http->post('query'),
      $http->input('query'),
      $http->header('host'),
      $http->headers(),
    ],

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