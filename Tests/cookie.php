<?php 

use Tamedevelopers\Support\Cookie;

require_once __DIR__ . '/../vendor/autoload.php';



// this will return instance of a Cookie
// since by default the function name cookie already exists
// so we can't be able to create helper function with that name
// TameCookie()


TameCookie()->set('cookie_name', 'value');

// Cookie::set('cookie_name', 'value');


dd(

    Cookie::all(),

    TameCookie()->get('cookie_name'),

    Cookie::forget('cookie_name2'),
    
    Cookie::expire('cookie_name2'),
);
