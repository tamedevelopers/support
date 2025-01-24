<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Closure;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Time;
use Tamedevelopers\Support\Country;
use Tamedevelopers\Support\Capsule\TimeHelper;

/**
 * @property mixed $staticData
*/
trait TimeTrait{

    /**
     * isTimeInstance
     *
     * @return bool
     */
    static protected function isTimeInstance()
    {
        return self::$staticData instanceof Time;
    }
    
    /**
     * clone
     *
     * @return void
     */
    private function clone()
    {
        return clone $this;
    }
    
    /**
     * build Time Modifier
     *
     * @param  string $mode
     * @param  int $value
     * @param  bool $sub
     * @return $clone
     */
    private function buildTimeModifier($mode = 'day', $value = 0, $sub = false)
    {
        $clone = $this->clone();
        $date = $clone->format();
        $mode = Str::lower($mode);
        $sign = !$sub ? '+' : '-';

        $text = match ($mode) {
            'second',  => $value <= 1 ? 'second' : 'seconds',
            'minute',  => $value <= 1 ? 'minute' : 'minutes',
            'hour',  => $value <= 1 ? 'hour' : 'hours',
            'week',  => $value <= 1 ? 'week' : 'weeks',
            'month',  => $value <= 1 ? 'month' : 'months',
            'year',  => $value <= 1 ? 'year' : 'years',
            default => $value <= 1 ? 'day' : 'days',
        };

        // format text
        $clone->date = strtotime("{$date} {$sign} {$value}{$text}");
        $clone->timestamp = $clone->buildTimePrint();

        return $clone;
    }

    /**
     * Get the stored date time
     * @return int
     */
    public function __getDate()
    {
        return (int) $this->date;
    }

    /**
     * Get the current timezone.
     * 
     * @return string
     */
    public function __getTimezone()
    {
        if(empty($this->timezone)){
            $this->__setTimezone();
        }

        return $this->timezone;
    }

    /**
     * Set the timezone.
     * @param string|null $timezone
     * 
     * @return $this
     */
    public function __setTimezone($timezone = null)
    {
        $clone = $this->clone();

        $clone->setTimeZoneAndTimeStamp($timezone);
        $clone->timestamp = $clone->timestampPrint();
        
        return $clone;
    }

    /**
     * Set Date Time 
     * @param int|string|null $date
     * 
     * @return $this
     */
    public function __setDate($date = null)
    {
        $clone = $this->clone();
        $clone->date = TimeHelper::setPassedDate($date);
        $clone->timestamp    = $clone->timestampPrint();

        return $clone;
    }

    /**
     * Get all timezones.
     * 
     * @return array
     */
    static public function allTimezone()
    {
        return Country::timeZone();
    }

    /**
     * Set TimeZone And TimeStamp
     *
     * @param  mixed $timezone
     * @return void
     */
    private function setTimeZoneAndTimeStamp($timezone = null)
    {
        $this->timezone = TimeHelper::configureAndSetTimezone($timezone);
    }

    /**
     * create timestamp
     *
     * @return string
     */
    private function timestampPrint()
    {
        // get timezone
        $this->timezone = $this->getTimeZone();

        // set timezone
        $this->setTimeZoneAndTimeStamp($this->timezone);

        return $this->buildTimePrint();
    }
    
    /**
     * buildTimePrint
     *
     * @return string
     */
    private function buildTimePrint()
    {
        $date = date('Y-m-d H:i:s', $this->date);
        $utc  = date('(P)', $this->date);

        return "{$date}.{$this->microseconds()} {$this->timezone} {$utc}";
    }
    
    /**
     * create microseconds
     *
     * @return string
     */
    private function microseconds()
    {
        $microtime = explode(' ', microtime());
        $milliseconds = (int) round($microtime[0] * 1000000); // Get microseconds

        return str_pad(Str::trim($milliseconds), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Handle the calls to non-existent methods.
     * @param string|null $method
     * @param mixed $args
     * @param mixed $clone
     * @return mixed
     */
    static private function nonExistMethod($method = null, $args = null, $clone = null) 
    {
        // convert to lowercase
        $name = Str::lower($method);

        // create correct method name
        $method = match ($name) {
            'greetings', 'greeting' => '__greeting',
            'tojs', 'jstimer' => 'toJsTimer',
            'hours', 'hour', 'hr', 'hrs', 'gethr', 'gethours', 'gethour' => '__getHour',
            'getseconds', 'getsec', 'sec', 's' => '__getSecond',
            'min', 'mins', 'getminute', 'getminutes', 'getmins' => '__getMin',
            'getday', 'getdays', 'getd', 'day', 'days' => '__getDay',
            'getweek', 'weeks', 'week', 'getweeks', 'getw' => '__getWeek',
            'getmonths', 'getmonth', 'getm' => '__getMonth',
            'getyr', 'getyears', 'getyear', 'year', 'years', 'yr', 'yrs', 'y' => '__getYear',
            'time', 'gettimes', 'gettime', 'getdate' => '__getDate',
            'setdate' => '__setDate',
            'gettimezone' => '__getTimezone',
            'settimezone' => '__setTimezone',
            'diff', 'difference', 'timedifference', 'timediff' => '__timeDifference',
            'formatdaterange', 'formatrange', 'daterange' => 'dateRange',
            'ago', 'timeago' => '__timeAgo',
            'addsecond' => 'addSeconds',
            'subsecond' => 'subSeconds',
            'addminute' => 'addMinutes',
            'subminute' => 'subMinutes',
            'addhour' => 'addHours',
            'subhour' => 'subHours',
            'addday' => 'addDays',
            'subday' => 'subDays',
            'addweek' => 'addWeeks',
            'subweek' => 'subWeeks',
            'addmonth' => 'addMonths',
            'submonth' => 'subMonths',
            'addyear' => 'addYears',
            'subyear' => 'subYears',
            default => 'format'
        };

        // this will happen if __construct has not been called 
        // before calling an existing method
        // mostly when using [setglobaltimezone|getglobaltimezone] methods
        if(empty($clone)){
            $clone = new static();
        }

        return $clone->$method(...$args);
    }

}