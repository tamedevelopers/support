<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use DateTime;
use DateTimeZone;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Country;

class Time {
     
    /**
     * For storing the time value
     * 
     * @var mixed
     * - int|string
     */
    static protected $date;

    /**
     * For storing the timezone value
     * 
     * @var string
     */
    static protected $timezone;


    /**
     * Time constructor.
     * @param int|string|null $date
     * @param string|null $timezone
     */
    public function __construct($date = 'now', ?string $timezone = 'UTC')
    {
        if(empty(self::$date)){
            self::setDate($date);
        }

        if(empty(self::$timezone)){
            self::setTimezone($timezone);
        }
    }

    /**
     * Handle the calls to non-existent instance methods.
     * @param string $name
     * @param mixed $args
     * 
     * @return mixed
     */
    public function __call($name, $args) 
    {
        return self::nonExistMethod($name, $args);
    }
    
    /**
     * Handle the calls to non-existent static methods.
     * @param string $name
     * @param mixed $args
     * 
     * @return mixed
     */
    static public function __callStatic($name, $args) 
    {
        return self::nonExistMethod($name, $args);
    }

    /**
     * Set the timezone.
     * @param string $timezone
     * 
     * @return $this
     */
    static public function setTimezone(?string $timezone = null)
    {
        if(in_array($timezone, Country::timeZone())){
            self::$timezone = $timezone;
        } else{
            // get default timezone
            self::$timezone = date_default_timezone_get() ?? 'UTC';
        }

        // set timezone
        date_default_timezone_set($timezone);

        return new self(self::$date, self::$timezone);
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
     * Set Date Time 
     * @param int|string|null $date
     * 
     * @return $this
     */
    static public function setDate($date = null) 
    {
        if (is_numeric($date)) {
            self::$date = (int) $date;

            return new self(self::$date);
        }

        // if empty date
        if(empty($date)){
            $date = 'Jan 01 1970';
        }

        self::$date = strtotime($date);

        return new self(self::$date);
    }

    /**
     * Get the stored date time
     * @return int
     */
    static public function getDate()
    {
        if(empty(self::$date)){
            self::setDate('now');
        }

        return self::$date;
    }

    /**
     * Set time to now
     * 
     * @return $this
     */
    static public function now() 
    {
        return self::format('now');
    }

    /**
     * Format time input
     * @param int|string $date
     * 
     * @return $this
     */
    static public function format(int|string $date) 
    {
        return self::setDate($date);
    }

    /**
     * Create timestamp
     * 
     * @param int|string $date
     * - string|int|float
     * 
     * @param string $format
     * - Your defined format type i.e: Y-m-d H:i:s a
     * - Converted TimeStamp
     * 
     * @return string
     */
    static public function timestamp($date, ?string $format = "Y-m-d H:i:s")
    {
        if(is_string($date)){
            $date = strtotime($date);   
        }

        return date($format, $date);
    }

    /**
     * Create Javascript timer
     * 
     * @param string|int $date
     * - Converted TimeStamp
     * 
     * @return string
     */
    static public function toJsTimer($date)
    {
        self::setDate($date);

        return self::timestamp($date, 'M j, Y H:i:s');
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
        $date   = new DateTime();
        $date->setTimestamp(self::carbonInstance());

        // get difference
        $difference = $now->diff($date);

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
            $fullText = str_replace('**', $text['at'], date("D ** h:m a", self::getDate()));
            if($days === 1){
                $fullText = "{$text['yesterday']} {$text['at']} " . date("h:m a", self::getDate());
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
                'full'  => str_replace('**', $text['at'], date("d M ** h:m a", self::getDate())),
                'short' => "{$weeks}{$text['w']}",
                'duration'   => $weeks,
            ];
        }

        // merge
        $data = array_merge($data, [
            'time'      => self::getDate(),
            'date'      => date('d M, Y', self::getDate()),
            'date_time' => date('d M, Y h:ma', self::getDate()),
            'time_stamp'=> date('M j, Y H:i:s', self::getDate())
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
     * Check if an Instance of Carbon
     * When using in Laravel, the Default Time Class automatically changed to Carbon by Laravel
     * 
     * @return int
     */
    static private function carbonInstance() 
    {
        return self::$date->timestamp ?? self::$date;
    }

    /**
     * Handle the calls to non-existent methods.
     * @param string|null $method
     * @param mixed $args
     * @return mixed
     */
    static private function nonExistMethod($method = null, $args = null) 
    {
        // convert to lowercase
        $name = Str::lower($method);

        switch ($name) {
            case in_array($name, ['tojs', 'jstimer']):
                $method = 'toJsTimer';
                break;
            
            case in_array($name, ['time', 'gettimes', 'gettime']):
                $method = 'getDate';
                break;
            
            case in_array($name, ['hours', 'hr', 'hrs', 'gethr', 'gethours']):
                $method = 'getHour';
                break;
            
            case in_array($name, ['getseconds', 'getsec', 'sec', 's']):
                $method = 'getSecond';
                break;
            
            case in_array($name, ['min', 'mins', 'getminute', 'getminutes', 'getmins']):
                $method = 'getMin';
                break;
            
            case in_array($name, ['getday', 'getdays', 'getd', 'day', 'days']):
                $method = 'getDay';
                break;
            
            case in_array($name, ['getweek', 'getweeks', 'getw']):
                $method = 'getWeek';
                break;
            
            case in_array($name, ['getmonths', 'getmonth', 'getm']):
                $method = 'getMonth';
                break;
            
            case in_array($name, ['getyr', 'getyears', 'getyear', 'year', 'years', 'yr', 'yrs', 'y']):
                $method = 'getYear';
                break;
            
            case $name === 'greetings':
                $method = 'greeting';
                break;
            
            default:
                $method = 'timeAgo';
                break;
        }
        
        // create instance of new static self
        $instance = new static(self::$date, self::$timezone);

        return $instance->$method(...$args);
    }

}

