<?php 

use Tamedevelopers\Support\Time;

require_once __DIR__ . '/../vendor/autoload.php';


// set time zone for server
// now server time will be set to the given timezone as default
// Time::setTimeZone('Pacific/Pago_Pago');


// set the default time and timezone for each time 
// using helper function
$time = TameTime(
    timezone: 'Africa/Lagos',
    time: 'now',
);

// Time::allTimezone(),

$time2  = (new Time)->setTimezone('America/St_Barthelemy');
$time3  = (new Time)->setTimezone('Indian/Antananarivo');
$time4  = TameTime('first day of December 2008', 'Pacific/Pago_Pago');

// [
//     $time->toJsTimer('24 Jan 2025 14:00:00'),
//     $time4->time(),
//     $time4->sec(),
//     $time4->min(),
//     $time4->hour(),
//     $time4->day(),
//     $time4->week(),
//     $time4->month(),
//     $time4->year(),
// ];

dd(
    $time2,
    $time3::greetings('24 Jan 2025 14:00:00'),
);

dd(
    [
        'Time-1: '. $time->getTimeZone(),
        'Time-2: '. $time2->getTimeZone(),
        'Time-3: '. $time3->getTimeZone(),
        'Time-4: '. $time4->getTimeZone(),
    ],

    [
        $time->startOfYear()->format(),
        $time->startOfDay()->format(),
        $time->startOfWeek()->format(),
        $time->startOfMonth()->format(),
        $time->isSameDay(TameTime('now')->format()),
        $time->isSameMonth('today'),
        $time->gt('today'),
        $time->lt('today'),
    ],

    // [
    //     $time3->date('first day of December 2008')->format(),
    //     $time3->yesterday()->format(),
    //     $time3->today()->format(),
    //     $time3->now()->format(),
    // ],

    $time4->date('first day of this month')->subDays(4),
    
    [
        $time->greetings(),
        $time2->greetings('now'),
        $time3->greetings('24 Jan 2025 14:00:00'),
        TameTime()->toJs('today 9:23pm'),
        // $time->date('last year december')->format(),
        // $time->addMonth(10)->addWeek(2)->format(),
        // $time4->addDay(20000)->ago('date'),
        // $time4->subDay(10)->ago('date'),
        // $time4->addYear(10)->ago('date'),
        // $time4->date('last week monday')->ago('date_time'),
        // $time4->date('last year december')->diff('weeks'),
    ],

    $time4->setTimezone('Indian/Antananarivo'),

    [
        $time4->yesterday()->toDateTimeString(),
        $time4->format(null, 'first day of December 2008'),
        $time4->date('last year december')->diff('weeks'),
        $time4->date('today')->ago(),
        // $time4->setTimezone('Indian/Antananarivo')->diffBetween('last year', 'today', 'weeks'),
    ],
    
    
    TameTime()->range('1-14'),
    TameTime()->range('0-40')->format(true, true),
    //

);
