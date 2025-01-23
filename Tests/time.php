<?php 

use Tamedevelopers\Support\Time;

require_once __DIR__ . '/../vendor/autoload.php';


// set global time zone for server
// now server time will be set to the given timezone as default
Time::setGlobalTimeZone('Pacific/Pago_Pago');


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
$time  = TameTime('first day of December 2008');

dd(
    TameTime()->date('yesterday')->ago(),
    TameTime()->toJs('now'),

    Time::setTimezone('Asia/Tokyo'),

    $mainTime->date('last year december')->format(),
    $mainTime->getTimeZone(),

    $time2->date('this year october')->ago(),
    $time2->getTimeZone(),

    $time3,
    $time3->greetings(),
    Time::getGlobalTimeZone(),

    TameTime()->dateRange('1-14'),
    TameTime()->dateRange('0-40')->format(true, true),
    // Time::allTimezone(),

    $time->timeDifference(),
    $time->addDay(2000)->ago('date'),
    $time->subDay(10)->ago('date'),
    $time->ago(),
    $time->date('last week monday')->ago(),
    $time->today()->format(),
    $time->now()->addMonth(1)->subDay(1)->format(),
    $time->yesterday()->toDateTimeString(),
    $time->format(null, 'first day of December 2008'),

);
