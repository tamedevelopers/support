<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;


/**
 * @property array $config
 */
trait TextUtilityTrait{
    
    /**
     * Merge custom config into defaults.
     *
     * Example:
     * StringUtility::config(['second' => 's', 'minute' => 'm', 'hour' => 'h']);
     */
    public static function config(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Get text input
     * @return string
     */
    public function getText()
    {
        return Str::trim($this->text);
    }

    /**
     * Return formatted reading time using 200 words per minute.
     *
     * Examples:
     *  - "30 seconds"
     *  - "1 minute"
     *  - "2 minutes"
     *  - "1 hr 20 mins"
     *
     * @return string
     */
    public function readingTime(): string
    {
        $words = $this->wordCount();

        // avoid division by zero; 200 words per minute
        $minutesFloat = $words / 200.0;

        // less than 1 minute => show seconds
        if ($minutesFloat < 1.0) {
            $seconds = max(1, (int) round($minutesFloat * 60));
            return self::formatUnit($seconds, 'second');
        }

        // full minutes and remaining seconds
        $totalMinutes = (int) floor($minutesFloat);
        $remainingSeconds = (int) round(($minutesFloat - $totalMinutes) * 60);

        // under an hour
        if ($totalMinutes < 60) {
            $result = self::formatUnit($totalMinutes, 'minute');
            if ($remainingSeconds > 0) {
                $result .= ' ' . self::formatUnit($remainingSeconds, 'second');
            }
            return $result;
        }

        // hours, minutes, seconds
        $hours = (int) floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        $parts = [];
        $parts[] = self::formatUnit($hours, 'hour');
        if ($minutes > 0) {
            $parts[] = self::formatUnit($minutes, 'minute');
        }
        if ($remainingSeconds > 0) {
            $parts[] = self::formatUnit($remainingSeconds, 'second');
        }

        return implode(' ', $parts);
    }

    /**
     * Count words in text.
     *
     * @return int
     */
    public function wordCount(): int
    {
        // str_word_count handles many edge cases; fallback to zero for empty
        $trimmed = $this->getText();

        return $trimmed === '' ? 0 : str_word_count($trimmed);
    }

    /**
     * Count text characters.
     *
     * @param bool $includeSpaces
     * @return int
     */
    public function charCount(bool $includeSpaces = true): int
    {
        if ($includeSpaces) {
            return strlen($this->text);
        }
        // remove all whitespace chars, not just spaces
        return strlen(preg_replace('/\s+/', '', $this->text));
    }

    /**
     * Count text sentences (approximate) by splitting on punctuation.
     *
     * @return int
     */
    public function sentenceCount(): int
    {
        $parts = preg_split('/[.!?]+(?:\s|$)/u', $this->getText());
        if ($parts === false) return 0;
        $filtered = array_filter(array_map('trim', $parts), fn($p) => $p !== '');
        return count($filtered);
    }

    /**
     * Reverse text string (simple).
     *
     * For multibyte safe reversal, a more advanced routine is required.
     *
     * @return string
     */
    public function reverse(): string
    {
        // Multibyte-safe reversal if mb functions exist
        if (function_exists('mb_strlen')) {
            $out = '';
            for ($i = mb_strlen($this->text) - 1; $i >= 0; $i--) {
                $out .= mb_substr($this->text, $i, 1);
            }
            return $out;
        }
        return strrev($this->text);
    }

    /**
     * Check text palindrome ignoring punctuation, spaces, and case.
     *
     * @return bool
     */
    public function isPalindrome(): bool
    {
        // keep only alphanumeric chars
        $clean = preg_replace('/[^a-z0-9]/i', '', $this->getText());
        if ($clean === null) return false;
        $clean = mb_strtolower($clean);
        // reverse using multibyte-safe method
        if (function_exists('mb_strlen')) {
            $rev = '';
            for ($i = mb_strlen($clean) - 1; $i >= 0; $i--) {
                $rev .= mb_substr($clean, $i, 1);
            }
        } else {
            $rev = strrev($clean);
        }
        return $clean === $rev;
    }

    /**
     * Helper to format unit text and singular/plural.
     *
     * @param int $value
     * @param string $unitKey 'second'|'minute'|'hour'
     * @return string e.g. "1 minute" or "30 seconds" or "1 hr"
     */
    protected static function formatUnit(int $value, string $unitKey): string
    {
        $label = self::$config[$unitKey] ?? $unitKey;

        // if pluralize enabled and label looks plural, try to use singular when value == 1
        if (!empty(self::$config['pluralize']) && $value === 1) {
            // Basic heuristic: if label ends with 's', remove it for singular.
            if (substr($label, -1) === 's') {
                $singular = substr($label, 0, -1);
                return $value . ' ' . $singular;
            }
        }

        return $value . ' ' . $label;
    }

}