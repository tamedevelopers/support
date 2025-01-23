<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

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
    public function __construct($startDate = null, $endDate = null, string $format = null)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->format    = $format;
    }

    /**
     * format
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
     * Set the timezone.
     * @param string|null $timezone
     * 
     * @return string
     */
    static public function setPassedTimezone($timezone = null)
    {
        if(in_array($timezone, Country::timeZone())){
            $timezone = $timezone;
        } else{
            $timezone = date_default_timezone_get() ?? 'UTC';
        }

        return $timezone;
    }

    /**
     * Set Date Time 
     * @param int|string|null $date
     * 
     * @return int|false
     */
    static public function setPassedDate($date = null)
    {
        // backdate default time
        $default = 'Jan 01 1970';

        if(empty($date)){
            // $date = date('M d Y', strtotime('this year January'));
            $date = $default;
        }

        if (is_numeric($date)) {
            $date = date('M d Y', (int) $date);
        }

        // if instance of Carbon
        // then convert to date time
        if($date instanceof \Illuminate\Support\Carbon){
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
    static public function carbonInstance($date) 
    {
        return $date?->timestamp ?? $date;
    }

}