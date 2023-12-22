<?php 

use Tamedevelopers\Support\Time;

require_once __DIR__ . '/../vendor/autoload.php';



// set the default time and timezone
// helper function
$mainTime = TameTime(
    timezone: 'Africa/Lagos',
    time: 'now',
);

// Time::setDate('last week');
// Time::setTimezone('Pacific/Pago_Pago');

// TameTime('yesterday')->time();
// TameTime('last week')->sec();

$time2 = Time::setTimezone('Atlantic/South_Georgia');
$time3 = Time::setTimezone('Indian/Antananarivo');

dd(
    TameTime()->format('yesterday')->ago(),
    TameTime()->toJs('now'),

    Time::setTimezone('Asia/Tokyo'),

    $mainTime->format('last year december'),
    $mainTime->getTimeZone(),

    $time2->format('this year october')->ago(),
    $time2->getTimeZone(),

    $time3,
    $time3->greetings()
);
