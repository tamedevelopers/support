<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Closure;
use Tamedevelopers\Support\Str;
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
     * Get the stored date time
     * @return int
     */
    public function __getDate()
    {
        if(empty($this->date)){
            $this->__setDate('now');
        }

        return $this->date;
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
        $this->timezone = TimeHelper::setPassedTimezone($timezone);

        return $this;
    }

    /**
     * Set Date Time 
     * @param int|string|null $date
     * 
     * @return $this
     */
    public function __setDate($date = null)
    {
        $this->date = TimeHelper::setPassedDate($date);

        return $this;
    }

    /**
     * Set Date Time 
     * @param int|string|null $date
     * 
     * @return $this
     */
    static public function setDate($date = null)
    {
        if(!self::isTimeInstance()){
            new static(date: $date);
        } else{
            self::$staticData->date = TimeHelper::setPassedDate(
                $date ?? 'now'
            );
        }
        
        return self::$staticData;
    }

    /**
     * Set the timezone.
     * @param string|null $timezone
     * 
     * @return $this
     */
    static public function setTimezone($timezone = null)
    {
        if(!self::isTimeInstance()){
            new static(timezone: $timezone);
        } 

        // set timezone
        self::$staticData->timezone = TimeHelper::setPassedTimezone(
            $timezone ?? 'UTC'
        );
        
        return self::$staticData;
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
            'getweek', 'getweeks', 'getw' => '__getWeek',
            'getmonths', 'getmonth', 'getm' => '__getMonth',
            'getyr', 'getyears', 'getyear', 'year', 'years', 'yr', 'yrs', 'y' => '__getYear',
            'time', 'gettimes', 'gettime', 'getdate' => '__getDate',
            'diff', 'difference', 'timedifference' => '__timeDifference',
            'format' => '__format',
            'gettimezone' => '__getTimeZone',
            'settimezone' => '__setTimeZone',
            'setdate' => '__setDate',
            default => '__timeAgo'
        };

        return $clone->$method(...$args);
    }

}