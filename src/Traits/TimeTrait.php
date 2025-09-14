<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Closure;
use DateTime;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Time;
use Tamedevelopers\Support\Country;
use Tamedevelopers\Support\Capsule\TimeHelper;
use Tamedevelopers\Support\Capsule\CustomException;


/**
 * Trait TimeTrait
 *
 * Internal helpers used by the Time class for cloning, timezone handling,
 * and common operations. Public API is provided by Time and dynamic dispatch.
 * @property mixed $staticData
*/
trait TimeTrait{

    /**
     * Determine whether the static context already holds a Time instance.
     *
     * @return bool
     */
    static protected function isTimeInstance()
    {
        return self::$staticData instanceof Time;
    }
    
    /**
     * Clone a new instance of the owning class.
     *
     * @return $this A shallow clone of the current instance.
     */
    private function clone()
    {
        return clone $this;
    }
    
    /**
     * Alias for clone() method.
     *
     * @return $this A shallow clone of the current instance.
     */
    public function copy()
    {
        return $this->clone();
    }

    /**
     * Compare the current TameTime instance with another time value.
     * Returns true if the current time is greater than or equal to the given time.
     *
     * @param mixed $time The time to compare with (can be a string, DateTime, or TameTime instance)
     * @return bool
     */
    public function greaterThanOrEqualTo($time): bool
    {
        // Ensure the comparison value is a TameTime instance
        if (!$time instanceof self) {
            $time = new self($time);
        }

        // Compare timestamps (Unix time) for accuracy and simplicity
        return $this->format() >= $time->format();
    }
    
    /**
     * Modify builder for add/sub operations.
     *
     * @param string $mode second|minute|hour|day|week|month|year
     * @param int $value Amount to adjust
     * @param bool $sub If true, subtract instead of add
     * @return static
     */
    private function buildTimeModifier($mode = 'day', $value = 0, $sub = false)
    {
        $clone = $this->clone();
        $date = $clone->format("Y-m-d H:i:s");
        $mode = Str::lower($mode);
        $sign = $sub ? '-' : '+';

        $text = match ($mode) {
            'second' => $value <= 1 ? 'second' : 'seconds',
            'minute' => $value <= 1 ? 'minute' : 'minutes',
            'hour'   => $value <= 1 ? 'hour' : 'hours',
            'week'   => $value <= 1 ? 'week' : 'weeks',
            'month'  => $value <= 1 ? 'month' : 'months',
            'year'   => $value <= 1 ? 'year' : 'years',
            default  => $value <= 1 ? 'day' : 'days',
        };

        // format text
        $clone->date = strtotime("{$date} {$sign} {$value}{$text}");
        $clone->timestamp = $clone->buildTimePrint();

        return $clone;
    }

    /**
     * Get the current timezone.
     * 
     * @return string
     */
    public function getTimezone()
    {
        if (empty($this->timezone)) {
            $this->setTimezone();
        }

        return $this->timezone;
    }

    /**
     * Set the timezone.
     * @param string|null $timezone
     * 
     * @return $this
     */
    public function setTimezone($timezone = null)
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
    public function setDate($date = null)
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
    public static function allTimezone()
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
     * timestampPrint(): rebuilds debug-like timestamp string.
     *
     * @return string
     */
    private function timestampPrint()
    {
        // ensure timezone cached
        $this->timezone = $this->getTimezone();
        $this->timezoneName = $this->timezone;

        // refresh system timezone and cache UTC offset
        $this->setTimeZoneAndTimeStamp($this->timezone);
        $this->utcOffset = date('(P)', (int) $this->date);

        return $this->buildTimePrint();
    }
    
    /**
     * Pretty string including microseconds, tz and UTC offset (compat buildTimePrint())
     *
     * @return string
     */
    private function buildTimePrint()
    {
        $date = date('Y-m-d H:i:s', (int) $this->date);
        $utc  = $this->utcOffset ?? date('(P)', (int) $this->date);

        return "{$date}.{$this->microseconds()} {$this->timezone} {$utc}";
    }

    /**
     * Return a pretty timestamp with microseconds, timezone, and UTC offset.
     * Example: "2025-05-01 10:20:30.123456 America/New_York (-04:00)"
     *
     * @return string
     */
    public function debugTimestamp(): string
    {
        $date   = $this->format('Y-m-d H:i:s');
        $micro  = $this->microseconds();
        $tz     = (string) $this->getTimezone();
        $offset = date('(P)', (int) $this->date);
        return sprintf('%s.%s %s %s', $date, $micro, $tz, $offset);
    }
    
    /**
     * create microseconds
     *
     * @return string
     */
    private function microseconds()
    {
        $micro = explode(' ', microtime());
        $micros = (int) round(((float) ($micro[0] ?? 0)) * 1_000_000);
        return str_pad((string) $micros, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Helper method to calculate the time difference between two dates.
     * 
     * @param DateTime $firstDate
     * @param DateTime $lastDate
     * @param string|null $mode
     * 
     * @return mixed
     */
    private function calculateTimeDifference(DateTime $firstDate, DateTime $lastDate, $mode = null)
    {
        // Get difference
        $difference = $firstDate->diff($lastDate);

        // Time difference breakdown
        $timeData = [
            'year'  => $difference->y,
            'month' => ($difference->y * 12) + $difference->m,
            'hour'  => $difference->h,
            'mins'  => $difference->i,
            'sec'   => $difference->s,
            'days'  => $difference->days, // Total number of days
            'weeks' => (int) floor($difference->days / 7), // Weeks
        ];

        return $timeData[$mode] ?? $timeData;
    }

    /**
     * Handle the calls to non-existent methods.
     * @param string|null $method
     * @param mixed $args
     * @param mixed $clone
     * @return mixed
     */
    private static function nonExistMethod($method = null, $args = null, $clone = null) 
    {
        // convert to lowercase
        $name = Str::lower($method);

        // create correct method name
        $method = match ($name) {
            'greetings', 'greeting' => '__greeting',
            'tojs', 'jstimer' => 'toJsTimer',
            's', 'sec', 'secs', 'getseconds', 'getsec' => '__getSecond',
            'min', 'mins', 'getminute', 'getminutes', 'getmin', 'getmins' => '__getMin',
            'hr', 'hrs', 'hour', 'hours', 'gethr', 'gethours', 'gethour' => '__getHour',
            'getday', 'getdays', 'getd', 'day', 'days' => '__getDay',
            'getweek', 'getweeks', 'week', 'weeks', 'getw' => '__getWeek',
            'getmonths', 'getmonth', 'getm', 'month', 'months' => '__getMonth',
            'getyr', 'getyears', 'getyear', 'year', 'years', 'yr', 'yrs', 'y' => '__getYear',
            'time', 'gettimes', 'gettime', 'getdate' => '__getDate',
            'setdate' => '__setDate',
            'gettimezone' => '__getTimezone',
            'settimezone' => '__setTimezone',
            'diffbetween', 'timediffbetween' => '__timeDifferenceBetween',
            'diff', 'timediff' => '__timeDifference',
            'daterange', 'range' => 'dateRange',
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

            // New: direct support for extra time helpers via dynamic dispatch
            'startofday' => 'startOfDay',
            'endofday' => 'endOfDay',
            'startofweek' => 'startOfWeek',
            'endofweek' => 'endOfWeek',
            'startofmonth' => 'startOfMonth',
            'endofmonth' => 'endOfMonth',
            'startofyear' => 'startOfYear',
            'endofyear' => 'endOfYear',
            'issameday' => 'isSameDay',
            'issamemonth' => 'isSameMonth',
            'issameyear' => 'isSameYear',
            'equalto' => 'equalTo',
            'gt' => 'gt',
            'lt' => 'lt',
            'between' => 'between',
            'diffindays' => 'diffInDays',
            'diffinhours' => 'diffInHours',
            'diffinminutes' => 'diffInMinutes',
            'diffinseconds' => 'diffInSeconds',
            'istoday' => 'isToday',
            'istomorrow' => 'isTomorrow',
            'isyesterday' => 'isYesterday',

            default => null //format
        };

        // this will happen if __construct has not been called 
        // before calling an existing method
        // mostly when using [setglobaltimezone|getglobaltimezone] methods
        if(empty($clone)){
            $clone = new static();
        }

        // if method name doesn't exists
        if(!method_exists($clone, (string) $method)){
            throw new CustomException(
                "Method name [{$name}] doesn't exists."
            );
        }

        return $clone->$method(...($args ?? []));
    }

}