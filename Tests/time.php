<?php 

use Tamedevelopers\Support\Time;

require_once __DIR__ . '/../vendor/autoload.php';



// set the default time and timezone
// helper function
ttime(
    timezone: 'Africa/Lagos',
    time: 'now', 
);


// Time::setDate('last week');
// Time::setTimezone('Asia/Tokyo');

// ttime()->setDate('last week');
// ttime()->setTimezone('Asia/Tokyo');

// Time::setDate('last week');
// ttime('yesterday')->time();
// ttime('last week')->sec();

dd(

    Time::setTimezone('Asia/Tokyo')
        ->format('now')
        ->ago()
    ,

    ttime()->format('yesterday')->ago(),

    ttime()->toJs('now')
);
