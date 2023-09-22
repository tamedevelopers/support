<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use DateTime;
use DateTimeZone;

class Time 
{   
    /**
     * For storing the time value
     * 
     * @var mixed
     * - int|string
     */
    static protected $time;

    /**
     * For storing the timezone value
     * 
     * @var string
     */
    static protected $timezone;


    /**
     * Time constructor.
     * @param string|null $time
     * @param string|null $timezone
     */
    public function __construct(mixed $time = 'now', ?string $timezone = 'UTC') 
    {
        self::$time = self::timeFormatNumberic($time);
        self::setTimezone((string) $timezone);
    }

    /**
     * Handle the calls to non-existent instance methods.
     * @param string $name
     * @param mixed $arguments
     * 
     * @return mixed
     */
    public function __call($name, $arguments) 
    {
        return self::nonExistMethod($name);
    }
    
    /**
     * Handle the calls to non-existent static methods.
     * @param string $name
     * @param mixed $arguments
     * 
     * @return mixed
     */
    static public function __callStatic($name, $arguments) 
    {
        return self::nonExistMethod($name);
    }

    /**
     * Set the timezone.
     * @param string $timezone
     * 
     * @return void
     */
    static public function setTimezone(?string $timezone = null)
    {
        try {
            @date_default_timezone_set((string) $timezone);
            self::$timezone = date_default_timezone_get();
        } catch (\Exception $e) {
            self::$timezone = 'UTC';
            date_default_timezone_set(self::$timezone);
        }
    }

    /**
     * Get the current timezone.
     * 
     * @return string
     */
    static public function getTimezone()
    {
        if(empty(self::$timezone)){
            self::setTimezone();
        }
        return self::$timezone;
    }

    /**
     * Set the configuration options for text representations.
     * 
     * @param array|null $options
     * 
     * @return void
     */
    static public function config(?array $options = [])
    {
        if(!defined('TIME_TEXT')){
            define('TIME_TEXT', array_merge([
                'night'     => 'Good night!',
                'morning'   => 'Good morning!',
                'afternoon' => 'Good afternoon!',
                'evening'   => 'Good evening!',
                'now'       => 'Just now',
                's'         => 's',
                'd'         => 'd',
                'h'         => 'h',
                'm'         => 'm',
                'w'         => 'w',
                'y'         => 'y',
                'at'        => 'at',
                'ago'       => 'ago',
                'sec'       => 'second',
                'min'       => 'minute',
                'hour'      => 'hour',
                'year'      => 'year',
                'yesterday' => 'Yesterday',
            ], $options));
        }
    }

    /**
     * Get the stored time
     * @return int
     */
    static public function getTime()
    {
        return self::$time;
    }

    /**
     * Get the number of seconds between the stored time and the current time.
     * @return mixed
     */
    static public function getSecond()
    {
        return self::timeDifference('sec');
    }

    /**
     * Get the number of minutes between the stored time and the current time.
     * @return mixed
     */
    static public function getMin() 
    {
        return self::timeDifference('mins');
    }

    /**
     * Get the number of hours between the stored time and the current time.
     * @return mixed
     */
    static public function getHour() 
    {
        return self::timeDifference('hour');
    }
    
    /**
     * Get the number of days between the stored time and the current time.
     * @return mixed
     */
    static public function getDay() 
    {
        return self::timeDifference('days');
    }

    /**
     * Get the number of weeks between the stored time and the current time.
     * @return mixed
     */
    static public function getWeek() 
    {
        $days = self::timeDifference('days');
        return (int) floor($days / 7);
    }
    
    /**
     * Get the number of months between the stored time and the current time.
     * @return mixed
     */
    static public function getMonth() 
    {
        return self::timeDifference('month');
    }
    
    /**
     * Get the number of years between the stored time and the current time.
     * @return mixed
     */
    static public function getYear() 
    {
        return self::timeDifference('year');
    }

    /**
     * Calculate the time difference between the stored time and the current time.
     * @param string|null $mode
     * 
     * @return mixed
     */
    static public function timeDifference(?string $mode  = null) 
    {
        $now    = new DateTime('now', new DateTimeZone(self::getTimezone()));
        $time   = new DateTime();
        $time->setTimestamp(self::carbonInstance());

        // get difference
        $difference = $now->diff($time);

        $timeData = [
            'year'  => $difference->y,
            'month' => ($difference->y * 12) + $difference->m,
            'hour'  => $difference->h,
            'mins'  => $difference->i,
            'sec'   => $difference->s,
            'days'  => $difference->days, //total number of days
        ];

        return $timeData[$mode] ?? $timeData;
    }

    /**
     * Get a greeting based on the current time.
     * @return string
     */
    static public function greeting() 
    {
        $now    = new DateTime('now', new DateTimeZone(self::getTimezone()));
        $hour   = (int) $now->format('H');
        $text   = self::getText();
        
        if ($hour >= 0 && $hour < 12) {
            return $text['morning'];
        } elseif ($hour >= 12 && $hour < 17) {
            return $text['afternoon'];
        } elseif ($hour >= 17 && $hour < 20) {
            return $text['evening'];
        } 

        return $text['night'];
    }

    /**
     * Get a time ago representation based on the time difference.
     * @param string|null $mode
     * - [optional] int|short|full
     * 
     * @return string
     */
    static public function timeAgo(?string $mode = null)
    {
        $minutes    = self::getMin();
        $seconds    = self::getSecond();
        $hours      = self::getHour();
        $days       = self::getDay();
        $weeks      = self::getWeek();
        $years      = self::getYear();
        $text       = self::getText();

        if ($days === 0 && $hours === 0 && $minutes < 1) {
            $data = [
                'full'  => $text['now'],
                'short' => $text['now'],
                'duration'   => $seconds,
            ];
        } elseif ($days === 0 && $hours === 0) {
            $data = [
                'full'  => "{$minutes} {$text['min']}" . ($minutes > 1 ? $text['s'] : '') . " {$text['ago']}",
                'short' => "{$minutes}{$text['m']}",
                'duration'   => $minutes,
            ];
        } elseif ($days === 0 && $hours < 24) {
            $data = [
                'full'  => "{$hours} {$text['hour']}" . ($hours > 1 ? $text['s'] : '') . " {$text['ago']}",
                'short' => "{$hours}{$text['h']}",
                'duration'   => $hours,
            ];
        } elseif ($days < 7) {
            // create default
            $fullText = str_replace('**', $text['at'], date("D ** h:m a", self::getTime()));
            if($days === 1){
                $fullText = "{$text['yesterday']} {$text['at']} " . date("h:m a", self::getTime());
            } 

            $data = [
                'full'  => $fullText,
                'short' => "{$days}{$text['d']}",
                'duration'   => $days,
            ];
        } elseif ($years > 0) {
            $data = [
                'full'  => "{$years} {$text['year']}" . ($years > 1 ? $text['s'] : '') . " {$text['ago']}",
                'short' => "{$years}{$text['y']}",
                'duration'   => $years,
            ];
        } else {
            $data = [
                'full'  => str_replace('**', $text['at'], date("d M ** h:m a", self::getTime())),
                'short' => "{$weeks}{$text['w']}",
                'duration'   => $weeks,
            ];
        }

        // merge
        $data = array_merge($data, [
            'time'      => self::getTime(),
            'date'      => date('d M, Y', self::getTime()),
            'date_time' => date('d M, Y h:ma', self::getTime()),
        ]);

        return $data[$mode] ?? $data;
    }

    /**
     * Get the text representation options.
     * @param string|null $mode
     * 
     * @return mixed
     */
    static private function getText(?string $mode  = null)
    {
        if(!defined('TIME_TEXT')){
            self::config();
        }
        return TIME_TEXT[$mode] ?? TIME_TEXT;
    }

    /**
     * Convert a string to lowercase.
     * @param string|null $name
     * 
     * @return string
     */
    static private function toLower(?string $name = null)
    {
        return trim(strtolower((string) $name));
    }

    /**
     * Convert the input time to a numeric value.
     * @param mixed $time_input
     * 
     * @return mixed
     */
    static private function timeFormatNumberic(mixed $time_input) 
    {
        if (is_string($time_input)) {
            return strtotime($time_input);
        }
    
        return $time_input;
    }

    /**
     * Format the input time as a string in the default format.
     * @param mixed $time_input
     * @return string
     */
    static private function timeFormat(mixed $time_input) 
    {
        if (is_numeric($time_input)) {
            return date('Y-m-d H:i:s', $time_input);
        }
    
        return date('Y-m-d H:i:s', strtotime($time_input));
    }

    /**
     * Check if an Instance of Carbon
     * When using in Laravel, the Default Time Class automatically changed to Carbon by Laravel
     * 
     * @return int
     */
    static private function carbonInstance() 
    {
        if(class_exists('Illuminate\Support\Carbon')){
            return self::$time->timestamp;
        }

        return self::$time;
    }

    /**
     * Handle the calls to non-existent methods.
     * @param string|null $name
     * @return mixed
     */
    static private function nonExistMethod(?string $name = null) 
    {
        // convert to lowercase
        $name = self::toLower($name);
        
        // for hour
        if ($name === 'gettimes') {
            return self::getTime();
        }
        
        // for hour
        if (in_array($name, ['gethr', 'gethours'])) {
            return self::getHour();
        }
        
        // for seconds
        if (in_array($name, ['getseconds', 'getsec'])) {
            return self::getSecond();
        }
        
        // for minutes
        if (in_array($name, ['getminute', 'getminutes'])) {
            return self::getMin();
        }
        
        // for day
        if ($name === 'getdays') {
            return self::getDay();
        }
        
        // for weeks
        if ($name === 'getweeks') {
            return self::getWeek();
        }
        
        // for months
        if ($name === 'getmonths') {
            return self::getMonth();
        }
        
        // for year
        if (in_array($name, ['getyr', 'getyears'])) {
            return self::getYear();
        }
        
        // for greetings
        if ($name === 'greetings') {
            return self::greeting();
        }
        
        // for timeago
        if ($name === 'timesago') {
            return self::timeAgo();
        }
    }

}

