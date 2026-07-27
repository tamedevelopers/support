<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use DateTime;

trait TimeGetterTrait{

    /**
     * Magic: handle property access for dynamic properties
     *
     * @param string $name Property name
     * @return int|null
     */
    public function __get($name)
    {
        $propertyMap = [
            // Date components
            'year'          => 'getActualYear',
            'month'         => 'getActualMonth',
            'day'           => 'getActualDay',
            'hour'          => 'getActualHour',
            'minute'        => 'getActualMinute',
            'second'        => 'getActualSecond',
            
            // Date numbers
            'dayOfWeek'     => 'getDateDayOfWeek',
            'dayOfYear'     => 'getDateDayOfYear',
            'weekOfYear'    => 'getDateWeekOfYear',
            'quarter'       => 'getDateQuarter',
            'daysInMonth'   => 'getDateDaysInMonth',
            
            // Date names
            'monthName'     => 'getDateMonthName',
            'shortMonth'    => 'getDateShortMonthName',
            'dayName'       => 'getDateDayName',
            'shortDay'      => 'getDateShortDayName',
            
            // Time formats
            'amPm'          => 'getDateAmPm',
            'timezoneName'  => 'getDateTimezoneName',
            'timezoneOffset'=> 'getDateTimezoneOffset',
            
            // Boolean comparisons
            'isToday'       => 'isDateToday',
            'isTomorrow'    => 'isDateTomorrow',
            'isYesterday'   => 'isDateYesterday',
            'isWeekend'     => 'isDateWeekend',
            'isWeekday'     => 'isDateWeekday',
            'isLeapYear'    => 'isDateLeapYear',
            'isPast'        => 'isDatePast',
            'isFuture'      => 'isDateFuture',
            
            // Age
            'age'           => 'getDateAge',
            'ageInDays'     => 'getDateAgeInDays',
            
            // Timestamps & strings
            'timestamp'     => 'getDateTimestamp',
            'dateString'    => 'getDateString',
            'timeString'    => 'getDateTimeString',
            'dateTime'      => 'getDateTimeFullString',
            'rfc3339'       => 'getDateRfc3339',
            
            // Position in time
            'startOfDay'    => 'getDateStartOfDay',
            'endOfDay'      => 'getDateEndOfDay',
            'startOfMonth'  => 'getDateStartOfMonth',
            'endOfMonth'    => 'getDateEndOfMonth',
            'startOfYear'   => 'getDateStartOfYear',
            'endOfYear'     => 'getDateEndOfYear',
        ];

        if (isset($propertyMap[$name])) {
            return $this->{$propertyMap[$name]}();
        }
    }

    /**
     * Get the actual year from the stored date
     * @return int
     */
    public function getActualYear()
    {
        return (int) date('Y', $this->date);
    }

    /**
     * Get the actual month from the stored date (1-12)
     * @return int
     */
    public function getActualMonth()
    {
        return (int) date('n', $this->date);
    }

    /**
     * Get the actual day from the stored date (1-31)
     * @return int
     */
    public function getActualDay()
    {
        return (int) date('j', $this->date);
    }

    /**
     * Get the actual hour from the stored date (0-23)
     * @return int
     */
    public function getActualHour()
    {
        return (int) date('H', $this->date);
    }

    /**
     * Get the actual minute from the stored date (0-59)
     * @return int
     */
    public function getActualMinute()
    {
        return (int) date('i', $this->date);
    }

    /**
     * Get the actual second from the stored date (0-59)
     * @return int
     */
    public function getActualSecond()
    {
        return (int) date('s', $this->date);
    }

    // ============================================
    // ===== DATE NUMBERS =====
    // ============================================
    /**
     * Get the day of week (1-7, Monday=1)
     * @return int
     */
    public function getDateDayOfWeek()
    {
        return (int) date('N', $this->date);
    }

    /**
     * Get the day of year (1-366)
     * @return int
     */
    public function getDateDayOfYear()
    {
        return (int) date('z', $this->date) + 1;
    }

    /**
     * Get the week of year (1-53)
     * @return int
     */
    public function getDateWeekOfYear()
    {
        return (int) date('W', $this->date);
    }

    /**
     * Get the quarter of year (1-4)
     * @return int
     */
    public function getDateQuarter()
    {
        return (int) ceil(date('n', $this->date) / 3);
    }

    /**
     * Get the number of days in the month (28-31)
     * @return int
     */
    public function getDateDaysInMonth()
    {
        return (int) date('t', $this->date);
    }

    // ============================================
    // ===== DATE NAMES =====
    // ============================================
    /**
     * Get the full month name (e.g., "January")
     * @return string
     */
    public function getDateMonthName()
    {
        return date('F', $this->date);
    }

    /**
     * Get the short month name (e.g., "Jan")
     * @return string
     */
    public function getDateShortMonthName()
    {
        return date('M', $this->date);
    }

    /**
     * Get the full day name (e.g., "Monday")
     * @return string
     */
    public function getDateDayName()
    {
        return date('l', $this->date);
    }

    /**
     * Get the short day name (e.g., "Mon")
     * @return string
     */
    public function getDateShortDayName()
    {
        return date('D', $this->date);
    }

    // ============================================
    // ===== TIME FORMATS =====
    // ============================================
    /**
     * Get the AM/PM indicator (e.g., "AM")
     * @return string
     */
    public function getDateAmPm()
    {
        return date('A', $this->date);
    }

    /**
     * Get the timezone name
     * @return string
     */
    public function getDateTimezoneName()
    {
        return $this->timezoneName ?? $this->timezone;
    }

    /**
     * Get the timezone offset (e.g., "+00:00")
     * @return string
     */
    public function getDateTimezoneOffset()
    {
        return $this->utcOffset ?? date('P', $this->date);
    }

    // ============================================
    // ===== BOOLEAN COMPARISONS =====
    // ============================================
    /**
     * Check if the date is today
     * @return bool
     */
    public function isDateToday()
    {
        return date('Y-m-d', $this->date) === date('Y-m-d');
    }

    /**
     * Check if the date is tomorrow
     * @return bool
     */
    public function isDateTomorrow()
    {
        return date('Y-m-d', $this->date) === date('Y-m-d', strtotime('+1 day'));
    }

    /**
     * Check if the date is yesterday
     * @return bool
     */
    public function isDateYesterday()
    {
        return date('Y-m-d', $this->date) === date('Y-m-d', strtotime('-1 day'));
    }

    /**
     * Check if the date is a weekend
     * @return bool
     */
    public function isDateWeekend()
    {
        return (int) date('N', $this->date) >= 6;
    }

    /**
     * Check if the date is a weekday
     * @return bool
     */
    public function isDateWeekday()
    {
        return !$this->isDateWeekend();
    }

    /**
     * Check if the year is a leap year
     * @return bool
     */
    public function isDateLeapYear()
    {
        return (bool) date('L', $this->date);
    }

    /**
     * Check if the date is in the past
     * @return bool
     */
    public function isDatePast()
    {
        return $this->date < time();
    }

    /**
     * Check if the date is in the future
     * @return bool
     */
    public function isDateFuture()
    {
        return $this->date > time();
    }

    // ============================================
    // ===== AGE =====
    // ============================================
    /**
     * Get age in years from the stored date
     * @return int
     */
    public function getDateAge()
    {
        $birthDate = new DateTime();
        $birthDate->setTimestamp($this->date);
        $now = new DateTime();
        $diff = $now->diff($birthDate);

        return (int) $diff->y;
    }

    /**
     * Get age in days from the stored date
     * @return int
     */
    public function getDateAgeInDays()
    {
        return (int) floor((time() - $this->date) / 86400);
    }

    // ============================================
    // ===== TIMESTAMPS & STRINGS =====
    // ============================================
    /**
     * Get the Unix timestamp
     * @return int
     */
    public function getDateTimestamp()
    {
        return (int) $this->date;
    }

    /**
     * Get date string (Y-m-d)
     * @return string
     */
    public function getDateString()
    {
        return date('Y-m-d', $this->date);
    }

    /**
     * Get time string (H:i:s)
     * @return string
     */
    public function getDateTimeString()
    {
        return date('H:i:s', $this->date);
    }

    /**
     * Get date time string (Y-m-d H:i:s)
     * @return string
     */
    public function getDateTimeFullString()
    {
        return date('Y-m-d H:i:s', $this->date);
    }

    /**
     * Get RFC 3339 formatted date (Y-m-d\TH:i:sP)
     * @return string
     */
    public function getDateRfc3339()
    {
        return date('Y-m-d\TH:i:sP', $this->date);
    }

    // ============================================
    // ===== POSITION IN TIME =====
    // ============================================
    /**
     * Get the start of the day (00:00:00)
     * @return int
     */
    public function getDateStartOfDay()
    {
        return (int) strtotime(date('Y-m-d 00:00:00', $this->date));
    }

    /**
     * Get the end of the day (23:59:59)
     * @return int
     */
    public function getDateEndOfDay()
    {
        return (int) strtotime(date('Y-m-d 23:59:59', $this->date));
    }

    /**
     * Get the start of the month (1st 00:00:00)
     * @return int
     */
    public function getDateStartOfMonth()
    {
        return (int) strtotime(date('Y-m-01 00:00:00', $this->date));
    }

    /**
     * Get the end of the month (last day 23:59:59)
     * @return int
     */
    public function getDateEndOfMonth()
    {
        return (int) strtotime(date('Y-m-t 23:59:59', $this->date));
    }

    /**
     * Get the start of the year (Jan 1 00:00:00)
     * @return int
     */
    public function getDateStartOfYear()
    {
        return (int) strtotime(date('Y-01-01 00:00:00', $this->date));
    }

    /**
     * Get the end of the year (Dec 31 23:59:59)
     * @return int
     */
    public function getDateEndOfYear()
    {
        return (int) strtotime(date('Y-12-31 23:59:59', $this->date));
    }

}