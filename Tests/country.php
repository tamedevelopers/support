<?php 

use Tamedevelopers\Support\Country;

require_once __DIR__ . '/../vendor/autoload.php';


// helper function
// country();

// Country::countryIso3()

dd(

    country()->getTimeZone("Africa/Addis_Ababa"),

    country()->getCaptchaLocale('ar'),

    Country::getCountryIso3('NGA')
);
