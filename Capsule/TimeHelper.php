<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Country;


class TimeHelper {

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
        if(empty($date)){
            $date = date('M d Y', strtotime('this year January'));
            $date = "Jan 01 1970";
        }

        if (is_numeric($date)) {
            $date = date('M d Y', (int) $date);
        }

        return strtotime($date);
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