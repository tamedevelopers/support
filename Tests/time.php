<?php 

use Tamedevelopers\Support\Time;

require_once __DIR__ . '/../vendor/autoload.php';



// set the default time and timezone
// helper function
TameTime(
    timezone: 'Africa/Lagos',
    time: 'now', 
);


// Time::setDate('last week');
// Time::setTimezone('Asia/Tokyo');

// TameTime()->setDate('last week');
// TameTime()->setTimezone('Asia/Tokyo');

// Time::setDate('last week');
// TameTime('yesterday')->time();
// TameTime('last week')->sec();

dd(

    Time::setTimezone('Asia/Tokyo')
        ->format('now')
        ->ago()
    ,

    TameTime()->format('yesterday')->ago(),

    TameTime()->toJs('now')
);
