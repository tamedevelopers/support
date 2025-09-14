<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use DateTime;
use DateTimeZone;
use Tamedevelopers\Support\Capsule\TimeHelper;
use Tamedevelopers\Support\Time;

/**
 * Trait TimeExtraTrait
 *
 * Adds date boundaries, comparisons, and difference helpers to Time.
 */
trait TimeExtraTrait
{
    /**
     * Create a DateTime for the given timestamp in the instance timezone.
     */
    private function dtInTz(int $timestamp): DateTime
    {
        $dt = new DateTime('@' . $timestamp); // start from UTC-based ts
        $dt->setTimezone(new DateTimeZone((string) $this->__getTimezone()));
        return $dt;
    }

    /**
     * Normalize various date inputs to a timestamp (int).
     * Accepts string/int/DateTime/Time.
     */
    private function normalizeToTimestamp($other): int
    {
        if ($other instanceof Time) {
            // Access protected via class context
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

    /** Set time to 00:00:00 of the current day. */
    public function startOfDay()
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setTime(0, 0, 0);
        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /** Set time to 23:59:59 of the current day. */
    public function endOfDay()
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setTime(23, 59, 59);
        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /** Beginning of the week (Monday 00:00:00). */
    public function startOfWeek()
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

    /** End of the week (Sunday 23:59:59). */
    public function endOfWeek()
    {
        $start = $this->startOfWeek();
        $dt = $this->dtInTz((int) $start->date);
        $dt->modify('+6 days');
        $dt->setTime(23, 59, 59);
        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /** First day of current month at 00:00:00. */
    public function startOfMonth()
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setDate((int) $dt->format('Y'), (int) $dt->format('m'), 1);
        $dt->setTime(0, 0, 0);
        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /** Last day of current month at 23:59:59. */
    public function endOfMonth()
    {
        $dt = $this->dtInTz((int) $this->date);
        $lastDay = (int) $dt->format('t');
        $dt->setDate((int) $dt->format('Y'), (int) $dt->format('m'), $lastDay);
        $dt->setTime(23, 59, 59);
        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /** January 1st, 00:00:00. */
    public function startOfYear()
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setDate((int) $dt->format('Y'), 1, 1);
        $dt->setTime(0, 0, 0);
        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    /** December 31st, 23:59:59. */
    public function endOfYear()
    {
        $dt = $this->dtInTz((int) $this->date);
        $dt->setDate((int) $dt->format('Y'), 12, 31);
        $dt->setTime(23, 59, 59);
        return $this->cloneWithTimestamp((int) $dt->getTimestamp());
    }

    // ---------------------------
    // Comparison helpers
    // ---------------------------

    /** Check if two dates are the same calendar day (in instance timezone). */
    public function isSameDay($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date)->format('Y-m-d');
        $b = $this->dtInTz($ts)->format('Y-m-d');
        return $a === $b;
    }

    /** Check if two dates are in the same month and year (in instance timezone). */
    public function isSameMonth($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date)->format('Y-m');
        $b = $this->dtInTz($ts)->format('Y-m');
        return $a === $b;
    }

    /** Check if two dates are in the same year (in instance timezone). */
    public function isSameYear($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date)->format('Y');
        $b = $this->dtInTz($ts)->format('Y');
        return $a === $b;
    }

    /** Strict equality (same timestamp). */
    public function equalTo($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        return (int) $this->date === $ts;
    }

    /** Greater than (after). */
    public function gt($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        return (int) $this->date > $ts;
    }

    /** Less than (before). */
    public function lt($otherDate): bool
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        return (int) $this->date < $ts;
    }

    /** Check if current date is in the inclusive range [date1, date2]. */
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

    /** Absolute number of full days difference (calendar aware). */
    public function diffInDays($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $a = $this->dtInTz((int) $this->date);
        $b = $this->dtInTz($ts);
        return (int) $a->diff($b)->days;
    }

    public function diffInHours($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $seconds = abs(((int) $this->date) - $ts);
        return (int) floor($seconds / 3600);
    }

    public function diffInMinutes($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        $seconds = abs(((int) $this->date) - $ts);
        return (int) floor($seconds / 60);
    }

    public function diffInSeconds($otherDate): int
    {
        $ts = $this->normalizeToTimestamp($otherDate);
        return (int) abs(((int) $this->date) - $ts);
    }

    // ---------------------------
    // Convenience checks
    // ---------------------------

    public function isToday(): bool
    {
        $now = new DateTime('now', new DateTimeZone((string) $this->__getTimezone()));
        $today = $now->format('Y-m-d');
        $cur = $this->dtInTz((int) $this->date)->format('Y-m-d');
        return $today === $cur;
    }

    public function isTomorrow(): bool
    {
        $tz = new DateTimeZone((string) $this->__getTimezone());
        $tomorrow = new DateTime('tomorrow', $tz);
        $cur = $this->dtInTz((int) $this->date)->format('Y-m-d');
        return $tomorrow->format('Y-m-d') === $cur;
    }

    public function isYesterday(): bool
    {
        $tz = new DateTimeZone((string) $this->__getTimezone());
        $yesterday = new DateTime('yesterday', $tz);
        $cur = $this->dtInTz((int) $this->date)->format('Y-m-d');
        return $yesterday->format('Y-m-d') === $cur;
    }
}