<?php 

use Tamedevelopers\Support\Cookie;

require_once __DIR__ . '/../vendor/autoload.php';



// this will return instance of a Cookie
// since by default the function name cookie already exists
// so we can't be able to create helper function with that name
// TameCookie()


TameCookie()->set('test_cookie_name', 'value');

// Cookie::set('test_cookie_name', 'value');

Cookie::queue('queue_cookie_name', 'queue_cookie_value', 10);
Cookie::queue('queue_cookie_name2', 'queue_cookie_value2', 5);
Cookie::setQueue();

dd(

    Cookie::all(),
    TameCookie()->get('test_cookie_name'),
    Cookie::forget('test_cookie_name2'),
    // Cookie::expire('test_cookie_name2'),
);
