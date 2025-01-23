<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use DateTime;
use DateTimeZone;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Time;
use Tamedevelopers\Support\Country;
use Tamedevelopers\Support\Traits\TimeTrait;
use Tamedevelopers\Support\Capsule\TimeHelper;

class Time {

    use TimeTrait;

    /**
     * For storing the time value
     * 
     * @var mixed
     * - int|string
     */
    protected $date;

    /**
     * For storing the timezone value
     * 
     * @var string
     */
    protected $timezone;
        
    /**
     * static
     *
     * @var mixed
     */
    static private $staticData;

    /**
     * Time constructor.
     * @param int|string|null $date
     * @param string|null $timezone
     */
    public function __construct($date = null, $timezone = null)
    {
        if(empty($this->date)){
            $this->date = TimeHelper::setPassedDate($date);
        }

        if(empty($this->timezone)){
            $this->timezone = TimeHelper::setPassedTimezone($timezone);
        }

        // clone copy of self
        if(!self::isTimeInstance()){
            self::$staticData = clone $this;
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
        return self::nonExistMethod($name, $args, $this);
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
        return self::nonExistMethod($name, $args, self::$staticData);
    }

    /**
     * Set custom time
     * @param int|string $date
     * @return $this
     */
    public function date($date)
    {
        return $this->__setDate($date);
    }

    /**
     * Add Second to curent date
     * @param int $value
     * @return $this
     */
    public function addSeconds($value = 0)
    {
        return $this->buildTimeModifier('second', $value);
    }

    /**
     * Substract Second from curent date
     * @param int $value
     * @return $this
     */
    public function subSeconds($value = 0)
    {
        return $this->buildTimeModifier('second', $value, true);
    }

    /**
     * Add Minutes to curent date
     * @param int $value
     * @return $this
     */
    public function addMinutes($value = 0)
    {
        return $this->buildTimeModifier('minute', $value);
    }

    /**
     * Substract Minutes from curent date
     * @param int $value
     * @return $this
     */
    public function subMinutes($value = 0)
    {
        return $this->buildTimeModifier('minute', $value, true);
    }

    /**
     * Add Hours to curent date
     * @param int $value
     * @return $this
     */
    public function addHours($value = 0)
    {
        return $this->buildTimeModifier('hour', $value);
    }

    /**
     * Substract Hours from curent date
     * @param int $value
     * @return $this
     */
    public function subHours($value = 0)
    {
        return $this->buildTimeModifier('hour', $value, true);
    }

    /**
     * Add days to curent date
     * @param int $value
     * @return $this
     */
    public function addDays($value = 0)
    {
        return $this->buildTimeModifier('day', $value);
    }

    /**
     * Substract days from curent date
     * @param int $value
     * @return $this
     */
    public function subDays($value = 0)
    {
        return $this->buildTimeModifier('day', $value, true);
    }

    /**
     * Add Week to curent date
     * @param int $value
     * @return $this
     */
    public function addWeeks($value = 0)
    {
        return $this->buildTimeModifier('week', $value);
    }

    /**
     * Substract Week from curent date
     * @param int $value
     * @return $this
     */
    public function subWeeks($value = 0)
    {
        return $this->buildTimeModifier('week', $value, true);
    }

    /**
     * Add Month to curent date
     * @param int $value
     * @return $this
     */
    public function addMonths($value = 0)
    {
        return $this->buildTimeModifier('month', $value);
    }

    /**
     * Substract Month from curent date
     * @param int $value
     * @return $this
     */
    public function subMonths($value = 0)
    {
        return $this->buildTimeModifier('month', $value, true);
    }

    /**
     * Add Year to curent date
     * @param int $value
     * @return $this
     */
    public function addYears($value = 0)
    {
        return $this->buildTimeModifier('year', $value);
    }

    /**
     * Substract Year from curent date
     * @param int $value
     * @return $this
     */
    public function subYears($value = 0)
    {
        return $this->buildTimeModifier('year', $value, true);
    }

    /**
     * Set time to `now`
     * 
     * @return $this
     */
    public function now()
    {
        return $this->__setDate('now');
    }

    /**
     * Set time to `today`
     * 
     * @return $this
     */
    public function today()
    {
        return $this->__setDate('today');
    }

    /**
     * Set time to `yesterday`
     * 
     * @return $this
     */
    public function yesterday()
    {
        return $this->__setDate('yesterday');
    }

    /**
     * Format time input
     * 
     * @param string|null $format
     * - Your defined format type i.e: Y-m-d H:i:s a
     * 
     * @param int|string $date
     * - string|int|float
     * 
     * @return string
     */
    public function __format($format = null, $date = null)
    {
        if(!empty($date)){
            $this->__setDate($date);
        }

        if(empty($format)){
            $format = "Y-m-d H:i:s";
        }

        return date($format, $this->date);
    }

    /**
     * toDateTimeString
     *
     * @return string
     */
    public function toDateTimeString()
    {
        return date('Y-m-d H:i:s', $this->date);
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
    static public function timestamp($date, $format = "Y-m-d H:i:s")
    {
        $date = TimeHelper::setPassedDate($date);

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
        return self::timestamp($date, 'M j, Y H:i:s');
    }

    /**
     * Format a date range.
     *
     * @param string $value The range in the format "1-7" (days from today).
     * @param string $format The desired date format, default is 'D, M j'.
     * 
     * @return Tamedevelopers\Support\Capsule\TimeHelper 
     * - The formatted date, e.g., "Mon, May 27".
     */
    public function dateRange($value, $format = 'D, M j')
    {
        // Check if the range has a hyphen
        if (strpos($value, '-') !== false) {
            // Split the range into start and end days
            [$start, $end] = explode('-', $value);
        } else {
            [$start, $end] = [0, $value];
        }
        
        // Ensure the end value is the maximum number of days
        $daysToStart = (int) Str::trim($start);
        $daysToAdd = (int) Str::trim($end);

        // Create a DateTime object for the current date
        $startDate = $this->today()->addDays($daysToStart);
        $endDate = $this->today()->addDays($daysToAdd);

        return new TimeHelper($startDate, $endDate, $format); 
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
        if(!defined('TAME_TIME_CONFIG')){
            define('TAME_TIME_CONFIG', array_merge([
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
    public function __getSecond()
    {
        return $this->__timeDifference('sec');
    }

    /**
     * Get the number of minutes between the stored time and the current time.
     * @return mixed
     */
    public function __getMin() 
    {
        return $this->__timeDifference('mins');
    }

    /**
     * Get the number of hours between the stored time and the current time.
     * @return mixed
     */
    public function __getHour() 
    {
        return $this->__timeDifference('hour');
    }
    
    /**
     * Get the number of days between the stored time and the current time.
     * @return mixed
     */
    public function __getDay() 
    {
        return $this->__timeDifference('days');
    }

    /**
     * Get the number of weeks between the stored time and the current time.
     * @return mixed
     */
    public function __getWeek() 
    {
        $days = $this->__timeDifference('days');
        return (int) floor($days / 7);
    }
    
    /**
     * Get the number of months between the stored time and the current time.
     * @return mixed
     */
    public function __getMonth() 
    {
        return $this->__timeDifference('month');
    }
    
    /**
     * Get the number of years between the stored time and the current time.
     * @return mixed
     */
    public function __getYear() 
    {
        return $this->__timeDifference('year');
    }

    /**
     * Calculate the time difference between the stored time and the current time.
     * @param string|null $mode
     * 
     * @return mixed
     */
    public function __timeDifference($mode  = null)
    {
        $now    = new DateTime('now', new DateTimeZone($this->timezone));
        $date   = new DateTime();
        $date->setTimestamp(TimeHelper::carbonInstance($this->date));

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
     * @param string|int $date
     * 
     * @return string
     */
    public function __greeting($date = 'now') 
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp(
            TimeHelper::setPassedDate($date)
        );

        $now    = new DateTime($dateTime->format('M d Y H:i:s'), new DateTimeZone($this->timezone));
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
    public function __timeAgo($mode = null)
    {
        $minutes    = $this->__getMin();
        $seconds    = $this->__getSecond();
        $hours      = $this->__getHour();
        $days       = $this->__getDay();
        $weeks      = $this->__getWeek();
        $years      = $this->__getYear();
        $date       = $this->__getDate();
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
            $fullText = str_replace('**', $text['at'], date("D ** h:m a", $date));
            if($days === 1){
                $fullText = "{$text['yesterday']} {$text['at']} " . date("h:m a", $date);
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
                'full'  => str_replace('**', $text['at'], date("d M ** h:m a", $date)),
                'short' => "{$weeks}{$text['w']}",
                'duration'   => $weeks,
            ];
        }

        // merge
        $data = array_merge($data, [
            'time'      => $date,
            'date'      => date('d M, Y', $date),
            'date_time' => date('d M, Y h:ma', $date),
            'time_stamp'=> date('M j, Y H:i:s', $date)
        ]);

        return $data[$mode] ?? $data;
    }

    /**
     * Get the text representation options.
     * @param string|null $mode
     * 
     * @return mixed
     */
    static private function getText($mode  = null)
    {
        if(!defined('TAME_TIME_CONFIG')){
            self::config();
        }

        return TAME_TIME_CONFIG[$mode] ?? TAME_TIME_CONFIG;
    }
}

