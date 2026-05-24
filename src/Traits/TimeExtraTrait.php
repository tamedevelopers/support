<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use DateTime;
use DateTimeZone;
use Tamedevelopers\Support\Capsule\TimeHelper;
use Tamedevelopers\Support\Time;

/**
 * Trait TimeExtraTrait
 * Adds date boundaries, comparisons, and difference helpers to Time.
 * 
 * @method mixed startOfWeek()
 */
trait TimeExtraTrait
{
    /**
     * Create a DateTime for the given timestamp in the instance timezone.
     *
     * @param int $timestamp Unix timestamp
     * @return DateTime DateTime adjusted to the instance timezone
     */
    private function dtInTz(int $timestamp): DateTime
    {
        $dt = new DateTime('@' . $timestamp); // start from UTC-based ts
        $dt->setTimezone(new DateTimeZone((string) $this->getTimezone()));
        return $dt;
    }

    /**
     * Normalize various date inputs to a timestamp (int).
     * Accepts string|int|DateTime|Time|Carbon-like.
     *
     * @param mixed $other A date-like value to normalize
     * @return int Unix timestamp
     */
    private function normalizeToTimestamp($other): int
    {
        if ($other instanceof Time) {
            // Use format('U') to get integer timestamp from Time
            return (int) $other->format('U');
        }

        if ($other instanceof DateTime) {
            return (int) $other->getTimestamp();
        }

        // Strings/ints and Carbon-like objects
        return (int) TimeHelper::setPassedDate($other);
    }

    /**
     * Helper to return a cloned instance with a new timestamp set.
     *
     * @param int $timestamp Unix timestamp to set on the clone
     * @return static Cloned instance with timestamp applied
     */
    private function cloneWithTimestamp(int $timestamp)
    {
        $clone = $this->clone();
        $clone->date = $timestamp;
        $clone->timestamp = $clone->timestampPrint();
        return $clone;
    }

    // ---------------------------
    // Date boundary methods
    // ---------------------------

    /**
     * Set time to 00:00:00 of the current day.
     */
    public function startOfDay(): static
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setTime(0, 0, 0);
        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /**
     * Set time to 23:59:59 of the current day.
     */
    public function endOfDay(): static
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setTime(23, 59, 59);
        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /**
     * Beginning of the week (Monday 00:00:00).
     */
    public function startOfWeek(): static
    {
        $dt = $this->dtInTz((int) $this->date);
        $dayOfWeek = (int) $dt->format('N'); // 1 (Mon) .. 7 (Sun)
        $daysBack = $dayOfWeek - 1; // move to Monday
        if ($daysBack > 0) {
            $dt->modify("-{$daysBack} days");
        }
        $dt->setTime(0, 0, 0);

        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /**
     * End of the week (Sunday 23:59:59).
     */
    public function endOfWeek(): static
    {
        $start = $this->startOfWeek();
        $dt = $this->dtInTz((int) $start->date);
        $dt->modify('+6 days');
        $dt->setTime(23, 59, 59);

        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /**
     * First day of current month at 00:00:00.
     */
    public function startOfMonth(): static
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setDate((int) $dt->format('Y'), (int) $dt->format('m'), 1);
        $dt->setTime(0, 0, 0);

        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /**
     * Last day of current month at 23:59:59.
     */
    public function endOfMonth(): static
    {
        $dt = $this->dtInTz((int) $this->date);
        $lastDay = (int) $dt->format('t');
        $dt->setDate((int) $dt->format('Y'), (int) $dt->format('m'), $lastDay);
        $dt->setTime(23, 59, 59);

        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /**
     * January 1st, 00:00:00.
     */
    public function startOfYear(): static
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setDate((int) $dt->format('Y'), 1, 1);
        $dt->setTime(0, 0, 0);

        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /**
     * December 31st, 23:59:59.
     */
    public function endOfYear(): static
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setDate((int) $dt->format('Y'), 12, 31);
        $dt->setTime(23, 59, 59);

        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    // ---------------------------
    // Comparison helpers
    // ---------------------------

    /**
     * Check if two dates are the same calendar day (in instance timezone).
     *
     * @param mixed $otherDate A date-like value to compare
     * @return bool
     */
    public function isSameDay($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date)->format('Y-m-d');
        $b = $this->dtInTz($ts)->format('Y-m-d');
        return $a === $b;
    }

    /**
     * Check if two dates are in the same month and year (in instance timezone).
     *
     * @param mixed $otherDate A date-like value to compare
     * @return bool
     */
    public function isSameMonth($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date)->format('Y-m');
        $b = $this->dtInTz($ts)->format('Y-m');
        return $a === $b;
    }

    /**
     * Check if two dates are in the same year (in instance timezone).
     *
     * @param mixed $otherDate A date-like value to compare
     * @return bool
     */
    public function isSameYear($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date)->format('Y');
        $b = $this->dtInTz($ts)->format('Y');
        return $a === $b;
    }

    /**
     * Strict equality (same timestamp).
     *
     * @param mixed $otherDate A date-like value to compare
     * @return bool
     */
    public function equalTo($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        return (int) $this->date === $ts;
    }

    /**
     * Greater than (after).
     *
     * @param mixed $otherDate A date-like value to compare
     * @return bool
     */
    public function gt($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        return (int) $this->date > $ts;
    }

    /**
     * Less than (before).
     *
     * @param mixed $otherDate A date-like value to compare
     * @return bool
     */
    public function lt($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        return (int) $this->date < $ts;
    }

    /**
     * Check if current date is in the inclusive range [date1, date2].
     *
     * @param mixed $date1 First boundary of range
     * @param mixed $date2 Second boundary of range
     * @return bool
     */
    public function between($date1, $date2): bool
    {
        $ts1 = $this->normalizeToTimestamp($date1);
        $ts2 = $this->normalizeToTimestamp($date2);
        $min = min($ts1, $ts2);
        $max = max($ts1, $ts2);
        $cur = (int) $this->date;
        return $cur >= $min && $cur <= $max;
    }

    // ---------------------------
    // Difference calculations
    // ---------------------------

    /**
     * Absolute number of full years difference (calendar aware).
     *
     * @param mixed $otherDate A date-like value to compare against
     * @return int Number of years difference (absolute)
     */
    public function diffInYears($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date);
        $b = $this->dtInTz($ts);
        return (int) $a->diff($b)->y;
    }

    /**
     * Absolute number of full decades difference (calendar aware).
     *
     * @param mixed $otherDate A date-like value to compare against
     * @return int Number of decades difference (absolute)
     */
    public function diffInDecades($otherDate): int
    {
        // Reuses your calendar-aware year difference
        return (int) floor($this->diffInYears($otherDate) / 10);
    }

    /**
     * Absolute number of full months difference (calendar aware).
     *
     * @param mixed $otherDate A date-like value to compare against
     * @return int Number of months difference (absolute)
     */
    public function diffInMonths($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date);
        $b = $this->dtInTz($ts);
        $interval = $a->diff($b);
        
        // Total months = (years * 12) + months
        return ($interval->y * 12) + $interval->m;
    }

    /**
     * Absolute number of full weeks difference.
     *
     * @param mixed $otherDate A date-like value to compare against
     * @return int Number of weeks difference (absolute)
     */
    public function diffInWeeks($otherDate): int
    {
        // Reuses your calendar-aware day difference to guarantee accuracy
        return (int) floor($this->diffInDays($otherDate) / 7);
    }

    /**
     * Absolute number of full days difference (calendar aware).
     *
     * @param mixed $otherDate A date-like value to compare against
     * @return int Number of days difference (absolute)
     */
    public function diffInDays($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date);
        $b = $this->dtInTz($ts);
        return (int) $a->diff($b)->days;
    }

    /**
     * Absolute number of hours difference.
     *
     * @param mixed $otherDate A date-like value to compare against
     * @return int Number of hours difference (absolute)
     */
    public function diffInHours($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $seconds = abs(((int) $this->date) - $ts);
        return (int) floor($seconds / 3600);
    }

    /**
     * Absolute number of minutes difference.
     *
     * @param mixed $otherDate A date-like value to compare against
     * @return int Number of minutes difference (absolute)
     */
    public function diffInMinutes($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $seconds = abs(((int) $this->date) - $ts);
        return (int) floor($seconds / 60);
    }

    /**
     * Absolute number of seconds difference.
     *
     * @param mixed $otherDate A date-like value to compare against
     * @return int Number of seconds difference (absolute)
     */
    public function diffInSeconds($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        return (int) abs(((int) $this->date) - $ts);
    }

    // ---------------------------
    // Convenience checks
    // ---------------------------

    /**
     * Whether current instance date is today (in instance timezone).
     *
     * @return bool
     */
    public function isToday(): bool
    {
        $now = new DateTime('now', new DateTimeZone((string) $this->getTimezone()));
        $today = $now->format('Y-m-d');
        $cur = $this->dtInTz((int) $this->date)->format('Y-m-d');
        return $today === $cur;
    }

    /**
     * Whether current instance date is tomorrow (in instance timezone).
     *
     * @return bool
     */
    public function isTomorrow(): bool
    {
        $tz = new DateTimeZone((string) $this->getTimezone());
        $tomorrow = new DateTime('tomorrow', $tz);
        $cur = $this->dtInTz((int) $this->date)->format('Y-m-d');
        return $tomorrow->format('Y-m-d') === $cur;
    }

    /**
     * Whether current instance date is yesterday (in instance timezone).
     *
     * @return bool
     */
    public function isYesterday(): bool
    {
        $tz = new DateTimeZone((string) $this->getTimezone());
        $yesterday = new DateTime('yesterday', $tz);
        $cur = $this->dtInTz((int) $this->date)->format('Y-m-d');
        return $yesterday->format('Y-m-d') === $cur;
    }
}