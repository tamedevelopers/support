<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use DateTime;
use DateTimeZone;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\TimeTrait;
use Tamedevelopers\Support\Capsule\TimeHelper;
use Tamedevelopers\Support\Traits\TimeExtraTrait;
use Tamedevelopers\Support\Traits\TimeGetterTrait;

/**
 * Time utility with dynamic static/instance method support.
 *
 * @method int second() Number of seconds since the stored time
 * @method int min() Number of minutes since the stored time
 * @method int hour() Number of hours since the stored time
 * @method int day() Number of days since the stored time
 * @method int week() Number of weeks since the stored time
 * @method int month() Number of months since the stored time
 * @method int year() Number of years since the stored time
 * @method array|int diff(string|null $unit = null) Time difference; array when unit is null, integer when unit provided (sec|mins|hour|days|weeks|month|year)
 * @method array|string timeAgo(string|null $mode = null) Humanized time-ago; array for default, string for specific modes like "short"
 * @method string greeting(int|string $date = 'now') Greeting based on hour of $date in current timezone
 *
 * @property int $year          - Full year (e.g., 2026)
 * @property int $month         - Month number (1-12)
 * @property int $day           - Day of month (1-31)
 * @property int $hour          - Hour (0-23)
 * @property int $minute        - Minute (0-59)
 * @property int $second        - Second (0-59)
 * @property int $seconds|ttl   - Total elapsed time in seconds (TTL)
 * 
 * @property int $dayOfWeek     - Day of week (1-7, Monday=1)
 * @property int $dayOfYear     - Day of year (1-366)
 * @property int $weekOfYear    - Week of year (1-53)
 * @property int $quarter       - Quarter of year (1-4)
 * @property int $daysInMonth   - Days in month (28-31)
 * 
 * @property string $monthName  - Full month name (e.g., "January")
 * @property string $shortMonth - Short month name (e.g., "Jan")
 * @property string $dayName    - Full day name (e.g., "Monday")
 * @property string $shortDay   - Short day name (e.g., "Mon")
 * 
 * @property string $amPm           - AM/PM indicator (e.g., "AM")
 * @property string $timezoneName   - Timezone name (e.g., "UTC")
 * @property string $timezoneOffset - Timezone offset (e.g., "+00:00")
 * 
 * @property bool $isToday     - Whether date is today
 * @property bool $isTomorrow  - Whether date is tomorrow
 * @property bool $isYesterday - Whether date is yesterday
 * @property bool $isWeekend   - Whether date is weekend
 * @property bool $isWeekday   - Whether date is weekday
 * @property bool $isLeapYear  - Whether year is leap year
 * @property bool $isPast      - Whether date is in past
 * @property bool $isFuture    - Whether date is in future
 * 
 * @property int $age         - Age in years
 * @property int $ageInDays   - Age in days
 * 
 * @property int $timestamp       - Unix timestamp
 * @property string $dateString   - Date string (Y-m-d)
 * @property string $timeString   - Time string (H:i:s)
 * @property string $dateTime     - Date time string (Y-m-d H:i:s)
 * @property string $rfc3339      - RFC 3339 formatted date (Y-m-d\TH:i:sP)
 * 
 * @property int $startOfDay   - Start of day timestamp (00:00:00)
 * @property int $endOfDay     - End of day timestamp (23:59:59)
 * @property int $startOfMonth - Start of month timestamp
 * @property int $endOfMonth   - End of month timestamp
 * @property int $startOfYear  - Start of year timestamp
 * @property int $endOfYear    - End of year timestamp
 */
final class Time {

    use TimeTrait, TimeGetterTrait, TimeExtraTrait;

    /**
     * For storing the time value.
     *
     * @var int|string
     */
    protected $date;

    /**
     * For storing a human-friendly timestamp snapshot
     * (e.g., "2024-01-01 12:00:00.123456 UTC (+00:00)").
     *
     * @var string
     */
    protected $timestamp;

    /**
     * For storing the timezone value.
     *
     * @var string
     */
    protected $timezone;

    /**
     * Cached timezone name (alias of $timezone) for quick access.
     *
     * @var string|null
     */
    protected $timezoneName;

    /**
     * Cached UTC offset for the current $date (e.g., "+00:00").
     *
     * @var string|null
     */
    protected $utcOffset;
        
    /**
     * Static Self Instance
     *
     * @var mixed
     */
    private static $staticData;

    /**
     * Stored translation configuration array.
     *
     * @var array|null
     */
    private static ?array $translations = null;

    /**
     * Time constructor.
     * @param int|string|null $date
     * @param string|null $timezone
     */
    public function __construct($date = null, $timezone = null)
    {
        if (empty($this->timezone)) {
            $this->timezone = TimeHelper::configureAndSetTimezone($timezone);
        }

        // Avoid recursive static initialization by computing directly
        $resolvedDate    = TimeHelper::setPassedDate($date ?: 'now');
        $this->date      = $resolvedDate;
        $this->timestamp = $this->timestampPrint();

        // Bind the freshly constructed instance for static context reuse
        $this->keepStaticBinding($this->clone());
    }

    /**
     * Magic: instance dynamic calls map to supported methods.
     *
     * @param string $name Invoked method name
     * @param array $args Arguments
     * @return mixed
     */
    public function __call($name, $args) 
    {
        return self::nonExistMethod($name, $args, $this);
    }
    
    /**
     * Magic: static dynamic calls map to supported methods using stored static instance.
     *
     * @param string $name Invoked static method name
     * @param array $args Arguments
     * @return mixed
     */
    public static function __callStatic($name, $args) 
    {
        return self::nonExistMethod($name, $args, self::$staticData);
    }

    /**
     * Target a specific day within the current week anchored state.
     *
     * @param string $dayName e.g. 'mon', 'tuesday', 'saturday'
     * @return $this
     */
    public function same(string $dayName)
    {
        return $this->buildDayModifier($dayName);
    }

    /**
     * Target the same day in the next week.
     *
     * @param string $dayName
     * @return $this
     */
    public function next(string $dayName)
    {
        return $this->addWeek()->same($dayName);
    }

    /**
     * Target the same day in the previous week.
     *
     * @param string $dayName
     * @return $this
     */
    public function previous(string $dayName)
    {
        return $this->subWeek()->same($dayName);
    }

    /**
     * Add seconds to the current date.
     *
     * @param int $value
     * @return $this 
     */
    public function addSeconds($value = 0)
    {
        return $this->buildTimeModifier('second', $value);
    }

    /**
     * Add one second to the current date.
     *
     * @return $this 
     */
    public function addSecond()
    {
        return $this->addSeconds(1);
    }

    /**
     * Substract Seconds from current date
     * 
     * @param int $value
     * @return $this
     */
    public function subSeconds($value = 0)
    {
        return $this->buildTimeModifier('second', $value, true);
    }

    /**
     * Substract one Second from current date
     * 
     * @return $this
     */
    public function subSecond()
    {
        return $this->subSeconds(1);
    }

    /**
     * Add Minutes to current date
     * 
     * @param int $value
     * @return $this
     */
    public function addMinutes($value = 0)
    {
        return $this->buildTimeModifier('minute', $value);
    }

    /**
     * Add one Minute to current date
     * 
     * @return $this
     */
    public function addMinute()
    {
        return $this->addMinutes(1);
    }

    /**
     * Substract Minutes from current date
     * 
     * @param int $value
     * @return $this
     */
    public function subMinutes($value = 0)
    {
        return $this->buildTimeModifier('minute', $value, true);
    }

    /**
     * Substract one Minute from current date
     * 
     * @return $this
     */
    public function subMinute()
    {
        return $this->subMinutes(1);
    }

    /**
     * Add Hours to current date
     * 
     * @param int $value
     * @return $this
     */
    public function addHours($value = 0)
    {
        return $this->buildTimeModifier('hour', $value);
    }

    /**
     * Add one Hour to current date
     * 
     * @return $this
     */
    public function addHour()
    {
        return $this->addHours(1);
    }

    /**
     * Substract Hours from current date
     * 
     * @param int $value
     * @return $this
     */
    public function subHours($value = 0)
    {
        return $this->buildTimeModifier('hour', $value, true);
    }

    /**
     * Substract one Hour from current date
     * 
     * @return $this
     */
    public function subHour()
    {
        return $this->subHours(1);
    }

    /**
     * Add days to current date
     * 
     * @param int $value
     * @return $this
     */
    public function addDays($value = 0)
    {
        return $this->buildTimeModifier('day', $value);
    }

    /**
     * Add one day to current date
     * 
     * @return $this
     */
    public function addDay()
    {
        return $this->addDays(1);
    }

    /**
     * Substract days from current date
     * 
     * @param int $value
     * @return $this
     */
    public function subDays($value = 0)
    {
        return $this->buildTimeModifier('day', $value, true);
    }

    /**
     * Substract one day from current date
     * 
     * @return $this
     */
    public function subDay()
    {
        return $this->subDays(1);
    }

    /**
     * Add Week to current date
     * 
     * @param int $value
     * @return $this
     */
    public function addWeeks($value = 0)
    {
        return $this->buildTimeModifier('week', $value);
    }

    /**
     * Add one Week to current date
     * 
     * @return $this
     */
    public function addWeek()
    {
        return $this->addWeeks(1);
    }

    /**
     * Substract Week from current date
     * 
     * @param int $value
     * @return $this
     */
    public function subWeeks($value = 0)
    {
        return $this->buildTimeModifier('week', $value, true);
    }

    /**
     * Substract one Week from current date
     * 
     * @return $this
     */
    public function subWeek()
    {
        return $this->subWeeks(1);
    }

    /**
     * Add Month to current date
     * 
     * @param int $value
     * @return $this
     */
    public function addMonths($value = 0)
    {
        return $this->buildTimeModifier('month', $value);
    }

    /**
     * Add one Month to current date
     * 
     * @return $this
     */
    public function addMonth()
    {
        return $this->addMonths(1);
    }

    /**
     * Substract Month from current date
     * 
     * @param int $value
     * @return $this
     */
    public function subMonths($value = 0)
    {
        return $this->buildTimeModifier('month', $value, true);
    }

    /**
     * Substract one Month from current date
     * 
     * @return $this
     */
    public function subMonth()
    {
        return $this->subMonths(1);
    }

    /**
     * Add Year to current date
     * 
     * @param int $value
     * @return $this
     */
    public function addYears($value = 0)
    {
        return $this->buildTimeModifier('year', $value);
    }

    /**
     * Add one Year to current date
     * 
     * @return $this
     */
    public function addYear()
    {
        return $this->addYears(1);
    }

    /**
     * Substract Year from current date
     * 
     * @param int $value
     * @return $this
     */
    public function subYears($value = 0)
    {
        return $this->buildTimeModifier('year', $value, true);
    }

    /**
     * Substract one Year from current date
     * 
     * @return $this
     */
    public function subYear()
    {
        return $this->subYears(1);
    }
    
    /**
     * Set custom time
     * 
     * @param int|string $date
     * @return $this
     */
    public static function date($date)
    {
        $base = self::baseInstance();

        return $base->setDate($date);
    }

    /**
     * Alias for `date` method 
     * 
     * @param int|string $date
     * @return $this
     */
    public static function parse($date)
    {
        return self::date($date);
    }

    /**
     * Format a date range.
     *
     * @param string $value The range in the format "1-7" (days from today).
     * @param string $format The desired date format, default is 'D, M j'.
     * 
     * @return \Tamedevelopers\Support\Capsule\TimeHelper 
     * - The formatted date, e.g., "Mon, May 27".
     */
    public static function dateRange($value, $format = 'D, M j')
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
        $startDate = self::today()->addDays($daysToStart);
        $endDate = self::today()->addDays($daysToAdd);

        return new TimeHelper($startDate, $endDate, $format); 
    }

    /**
     * Convert any time expression into seconds.
     *
     * Supported formats:
     *  - "5 mins"
     *  - "1h 30m"
     *  - "2 days 4 hours 10 mins"
     *  - "1h30m20s"
     *  - "2.5 hours"
     *  - "3weeks 2days 4hrs 10mins"
     *
     * @param string|int|bool $time
     * @return int
     */
    public static function toSeconds($time = false)
    {
        // Default time in seconds (10 minutes)
        $defaultTime = 600; 

        // If numeric seconds
        if (is_numeric($time) && $time > 0) {
            return (int) $time;
        }

        // If invalid input
        if (!is_string($time) || Str::trim($time) === '') {
            return $defaultTime;
        }

        $time = Str::lower($time);

        // Units map
        $units = [
            's|sec|secs|second|seconds'    => 1,
            'm|min|mins|minute|minutes'    => 60,
            'h|hr|hrs|hour|hours'          => 3600,
            'd|day|days'                   => 86400, // 24hours
            'w|wk|wks|week|weeks'          => 604800, // 7days
            'mo|mon|month|months'          => 2592000, // 30days (approximate)
            'y|yr|yrs|year|years'          => 31536000, // 365days
        ];

        // Build a pattern for all units
        $unitPattern = implode('|', array_keys($units));

        // REGEX: extract (value + unit) pairs even without spaces
        preg_match_all('/(\d+(\.\d+)?)\s*(' . $unitPattern . ')/i', $time, $matches, PREG_SET_ORDER);

        // If no match, return default
        if (empty($matches)) {
            return $defaultTime;
        }

        $seconds = 0;

        // Process each matched (value, unit)
        foreach ($matches as $match) {
            $value = floatval($match[1]);
            $unit  = $match[3];

            foreach ($units as $key => $multiplier) {
                if (preg_match('/^(' . $key . ')$/i', $unit)) {
                    $seconds += $value * $multiplier;
                    break;
                }
            }
        }

        return (int) $seconds;
    }

    /**
     * Create a new Time instance from specific date and time components.
     *
     * @param int|string|null $year
     * @param int|string|null $month
     * @param int|string|null $day
     * @param int|string|null $hour
     * @param int|string|null $minute
     * @param int|string|null $second
     * @param string|DateTimeZone|null $tz
     * 
     * @return static
     */
    public static function create($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null) 
    {
        $tz     = !empty($tz) ? $tz : self::now()->timezone;
        $now    = new DateTime('now', new DateTimeZone((string) $tz));

        // Fall back to current date/time components if arguments are omitted
        $year   = $year   ?? (int) $now->format('Y');
        $month  = $month  ?? (int) $now->format('m');
        $day    = $day    ?? (int) $now->format('d');
        $hour   = $hour   ?? (int) $now->format('H');
        $minute = $minute ?? (int) $now->format('i');
        $second = $second ?? (int) $now->format('s');

        // Pad components to construct standard Y-m-d H:i:s format string
        $formattedDate = sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            (int) $year,
            (int) $month,
            (int) $day,
            (int) $hour,
            (int) $minute,
            (int) $second
        );

        $timezone = $tz instanceof DateTimeZone ? $tz->getName() : $tz;

        return new static($formattedDate, $timezone);
    }

    /**
     * Set time to `now`
     * @return $this
     */
    public static function now()
    {
        return self::date('now');
    }

    /**
     * Set time to `today`
     * @return $this
     */
    public static function today()
    {
        return self::date('today');
    }

    /**
     * Set time to `yesterday`
     * @return $this
     */
    public static function yesterday()
    {
        return self::date('yesterday');
    }

    /**
     * Create timestamp
     * 
     * @param int|string $date
     * - string|int
     * 
     * @param string $format
     * - Your defined format type i.e: Y-m-d H:i:s a
     * - Converted TimeStamp
     * 
     * @return string
     */
    public static function timestamp($date, $format = "Y-m-d H:i:s")
    {
        $date = TimeHelper::setPassedDate($date);

        return date($format, $date);
    }

    /**
     * Create date from Format
     *
     * @param  string $format
     * @param  int|string|null $datetime
     * @return string 
     */
    public static function createFromFormat($format = 'Y-m-d H:i:s.u', $datetime = null)
    {
        return self::date(
            self::timestamp($datetime ?: 'now')
        )->format($format);
    }

    /**
     * Create date from date string
     *
     * @param  int|string $datetime
     * @return string 
     */
    public static function createFromDateString($datetime)
    {
        return self::date(
            self::timestamp($datetime)
        )->format('Y-m-d H:i:s.u');
    }

    /**
     * Format time input
     * 
     * @param string|null $format
     * - Your defined format type i.e: Y-m-d H:i:s a
     * 
     * @param int|string|null $date
     * - string|int|float
     * 
     * @return string
     */
    public function format($format = null, $date = null)
    {
        if (!empty($date)) {
            $this->date = TimeHelper::setPassedDate($date);
        }

        if(empty($format)){
            $format = "Y-m-d H:i:s";
        }

        return date($format, $this->date);
    }

    /**
     * toDateTimeString
     * @return string
     */
    public function toDateTimeString()
    {
        return $this->format();
    }

    /**
     * toDateString
     * @return string
     */
    public function toDateString()
    {
        return $this->format('Y-m-d');
    }

    /**
     * toTimeString
     * @return string
     */
    public function toTimeString()
    {
        return $this->format('H:i:s');
    }

    /**
     * toDateInt
     * @return string
     */
    public function toDateInt()
    {
        return strtotime($this->format());
    }

    /**
     * Create Javascript timer
     * 
     * @param string|int $date
     * - Converted TimeStamp
     * 
     * @return string
     */
    public static function toJsTimer($date)
    {
        return self::timestamp($date, 'M j, Y H:i:s');
    }

    /**
     * Retrieve text configuration entries.
     *
     * @param string|null $mode Specific key to fetch or null for all
     * @return mixed
     */
    private static function getText($mode  = null)
    {
        if(empty(self::$translations)){
            self::config();
        }

        return self::$translations[$mode] ?? self::$translations;
    }

    /**
     * Set the configuration options for text representations.
     * 
     * @param array|null $options
     * @return void
     */
    public static function config(?array $options = [])
    {
        $defaults = [
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
        ];

        self::$translations = array_merge($defaults, $options ?? []);
    }

    /**
     * Get the stored date time
     * @return int
     */
    public function __date()
    {
        return (int) $this->date;
    }

    /**
     * Get the number of seconds between the stored time and the current time.
     * @return int
     */
    public function __second()
    {
        return $this->__timeDifference('sec');
    }

    /**
     * Get the number of minutes between the stored time and the current time.
     * @return int
     */
    public function __min() 
    {
        return $this->__timeDifference('mins');
    }

    /**
     * Get the number of hours between the stored time and the current time.
     * @return int
     */
    public function __hour() 
    {
        return $this->__timeDifference('hour');
    }
    
    /**
     * Get the number of days between the stored time and the current time.
     * @return int
     */
    public function __day() 
    {
        return $this->__timeDifference('days');
    }

    /**
     * Get the number of weeks between the stored time and the current time.
     * @return int
     */
    public function __week() 
    {
        return $this->__timeDifference('weeks');
    }
    
    /**
     * Get the number of months between the stored time and the current time.
     * @return int
     */
    public function __month()
    {
        return $this->__timeDifference('month');
    }
    
    /**
     * Get the number of years between the stored time and the current time.
     * @return int
     */
    public function __year() 
    {
        return $this->__timeDifference('year');
    }

    /**
     * Get a greeting based on the current time.
     * 
     * @param string|int $date
     * @return string
     */
    public function __greeting($date = 'now') 
    {
        $clone = $this->clone();
        $clone->date = TimeHelper::setPassedDate($date);
        if (is_object($clone)) {
            $clone->timestamp = $clone->timestampPrint();
        }

        $dateTime = new DateTime();
        $dateTime->setTimestamp($clone->date);

        $now    = new DateTime($dateTime->format('M d Y H:i:s'), new DateTimeZone($clone->timezone));
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
     * Calculate the time difference between both given time.
     * 
     * @param mixed $firstDate
     * @param mixed $lastDate
     * @param string|null $mode
     * @return mixed
     */
    public function __timeDifferenceBetween($firstDate, $lastDate, $mode = null)
    {
        $clone = $this->clone();

        // convert to actual time as int
        $firstDate = TimeHelper::setPassedDate($firstDate);
        $lastDate = TimeHelper::setPassedDate($lastDate);

        // Get the current time in the specified timezone
        $first  = new DateTime($clone->timestamp($firstDate), new DateTimeZone($clone->timezone));
        $last   = new DateTime($clone->timestamp($lastDate), new DateTimeZone($clone->timezone));

        return $this->calculateTimeDifference($first, $last, $mode);
    }

    /**
     * Calculate the time difference between the stored time and the current time.
     * 
     * @param string|null $mode
     * @return mixed
     */
    public function __timeDifference($mode  = null)
    {
        $clone = $this->clone();

        // Convert the stored time to a DateTime object
        $selfDate   = TimeHelper::carbonInstance($clone->date);
        $date       = new DateTime();
        if (!empty($selfDate)) {
            $date->setTimestamp($selfDate);
        }

        // Get the current time in the specified timezone
        $now = new DateTime('now', new DateTimeZone($clone->timezone));

        return $this->calculateTimeDifference($now, $date, $mode);
    }

    /**
     * Get a time ago representation based on the time difference.
     * 
     * @param string|null $mode
     * - [optional] int|short|full
     * 
     * @return string
     */
    public function __timeAgo($mode = null)
    {
        $diff = $this->__timeDifference();

        $minutes    = $diff['mins'];
        $seconds    = $diff['sec'];
        $hours      = $diff['hour'];
        $days       = $diff['days'];
        $weeks      = $diff['weeks'];
        $years      = $diff['year'];
        $date       = $this->__date();
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
     * Magic: customize what is displayed during var-dump/dd().
     * Provides a pretty, safe snapshot of the current time object.
     *
     * - timestamp: the unix timestamp (int)
     * - formatted: default formatted string (Y-m-d H:i:s)
     * - timezone: current timezone name
     * - utc_offset: offset at the timestamp time
     * - greeting: localized greeting based on hour
     * - time_ago_short: a compact time-ago string
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        $time = (int) $this->date;

        return [
            'date'          => $time,
            'timestamp'      => $this->timestamp,
            'formatted'      => date('Y-m-d H:i:s', $time),
            'timezone'       => (string) ($this->timezoneName ?? $this->timezone),
            'utc_offset'     => ($this->utcOffset ?? date('(P)', $time)),
            'greeting'       => $this->__greeting($time),
            'time_ago_short' => $this->__timeAgo('short'),
            'time_ago'       => $this->__timeAgo(),
            'time_diff'      => $this->__timeDifference(),
        ];
    }
}

