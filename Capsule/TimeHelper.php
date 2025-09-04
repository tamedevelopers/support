<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Illuminate\Support\Carbon as carbonInstance;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Country;

class TimeHelper {
    
    /**
     * startDate
     *
     * @var mixed
     */
    protected $startDate;    
    /**
     * endDate
     *
     * @var mixed
     */
    protected $endDate;
    
    /**
     * format
     *
     * @var string|null
     */
    protected $format;

        
    /**
     * __construct
     *
     * @param  mixed $startDate
     * @param  mixed $endDate
     * @param  mixed $format
     * @return void
     */
    public function __construct($startDate = null, $endDate = null, $format = null)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->format    = $format;
    }

    /**
     * Format the range.
     *
     * @param  bool $start Whether to return the start date (true) or the end date (false).
     * @param  bool $year Whether to include the year in the result.
     * @return string The formatted date or range.
     */
    public function format($start = false, $year = false)
    {
        // Ensure startDate and endDate are formatted
        $startFormatted = $this->startDate->format($this->format);
        $endFormatted = $this->endDate->format($this->format);

        // Get the year from the relevant date
        $yearValue = $start ? ($this->startDate->format('Y')) : ($this->endDate->format('Y'));

        // Build the result
        if ($start) {
            return $year
                ? "{$startFormatted} - {$endFormatted}, {$yearValue}" // Start date, end date, and year
                : "{$startFormatted} - {$endFormatted}";             // Start date and end date only
        }
        
        return $year
            ? "{$endFormatted}, {$yearValue}"       // End date with year
            : $endFormatted;                       // End date only
    }

    /**
     * Configure and return a valid timezone string. Falls back to system default or UTC.
     *
     * @param string|null $timezone IANA timezone name or null. If invalid, fallback is used.
     * @return string Valid IANA timezone identifier.
     */
    public static function configureAndSetTimezone($timezone = null)
    {
        $timezone = Str::trim($timezone);

        if(!empty($timezone) && in_array($timezone, Country::timeZone())){
            $timezone = $timezone;
        } else{
            $timezone = date_default_timezone_get() ?: 'UTC';
        }

        try {
            date_default_timezone_set($timezone);
        } catch (\Throwable $th) {
            $timezone = 'UTC';
            date_default_timezone_set($timezone);
        }

        return $timezone;
    }

    /**
     * Set Date Time 
     * @param int|string|null $date
     * 
     * @return int
     */
    public static function setPassedDate($date = null)
    {
        // backdate default time
        $default = 'Jan 01 1970';

        if(empty($date)){
            // $date = date('M d Y', strtotime('this year January'));
            $date = $default;
        }

        if (is_numeric($date)) {
            $date = date('M d Y h:ia', (int) $date);
        }

        // if instance of Carbon
        // then convert to date time
        if($date instanceof carbonInstance){
            $date = $date->toDateTimeString();
        }
        
        // convert to time int
        $time = strtotime($date);

        return !$time ? strtotime($default) : $time;
    }
    
    /**
     * Check if an Instance of Carbon
     * - When using in Laravel, the Default Time Class automatically changed to Carbon by Laravel
     *
     * @param  mixed $date
     * @return int
     */
    public static function carbonInstance($date) 
    {
        return $date?->timestamp ?? $date;
    }

    /**
     * Build a microsecond string (6 digits) for pretty prints.
     *
     * @return string e.g. "123456"
     */
    public static function microseconds(): string
    {
        $micro = explode(' ', microtime());
        $micros = (int) round(((float) ($micro[0] ?? 0)) * 1_000_000);
        return str_pad((string) $micros, 6, '0', STR_PAD_LEFT);
    }

}