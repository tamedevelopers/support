<?php 

use Tamedevelopers\Support\Country;

require_once __DIR__ . '/../vendor/autoload.php';


// helper function
// TameCountry();

// Country::countryIso3()

dd(

    TameCountry()->getTimeZone("Africa/Addis_Ababa"),

    TameCountry()->getCaptchaLocale('ar'),

    Country::getCountryIso3('NGA')
);
