<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Traits\TextSanitizerTrait;

class TextSanitizer{
    
    use TextSanitizerTrait;

    /**
     * Extract phone numbers written in words or mixed with digits.
     *
     * @param string $text
     * @param int $minDigits Minimum digits required to qualify as a phone
     * @param int $gapLimit Allowed gap words between digits
     * @return array<string> Array of extracted phone numbers
     */
    public static function findPhoneWords(string $text, int $minDigits = 7, int $gapLimit = 3): array
    {
        $digitMap = [
            'zero' => '0', 'oh' => '0', 'o' => '0', 'nought' => '0', 'aught' => '0',
            'one' => '1', 'two' => '2', 'three' => '3',
            'four' => '4', 'five' => '5', 'six' => '6',
            'seven' => '7', 'eight' => '8', 'nine' => '9',
        ];
        $multiplier = [ 'double' => 2, 'triple' => 3, 'quadruple' => 4, 'quintuple' => 5 ];

        if (!preg_match_all('/[A-Za-z]+|\d+|[^A-Za-z\d]+/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }
        $tokens = $m[0];
        $count  = count($tokens);

        $phones = [];
        $digits = '';
        $gapRun = 0;

        $flush = function () use (&$digits, &$phones, $minDigits) {
            if (strlen($digits) >= $minDigits) {
                $phones[] = $digits;
            }
            $digits = '';
        };

        $isWord = fn($s) => (bool)preg_match('/^[A-Za-z]+$/u', $s);

        for ($i = 0; $i < $count; $i++) {
            [$tok] = $tokens[$i];
            $word = strtolower(preg_replace('/[^A-Za-z]/u', '', $tok));

            if (ctype_digit($tok)) {
                $digits .= $tok;
                $gapRun = 0;
                continue;
            }

            if (isset($digitMap[$word])) {
                $digits .= $digitMap[$word];
                $gapRun = 0;
                continue;
            }

            if (isset($multiplier[$word])) {
                $j = $i + 1;
                while ($j < $count && !$isWord($tokens[$j][0])) { $j++; }
                if ($j < $count) {
                    $nextWord = strtolower(preg_replace('/[^A-Za-z]/u', '', $tokens[$j][0]));
                    if (isset($digitMap[$nextWord])) {
                        $digits .= str_repeat($digitMap[$nextWord], $multiplier[$word]);
                        $i = $j;
                        $gapRun = 0;
                        continue;
                    }
                }
            }

            if ($digits !== '') {
                $gapRun++;
                if ($gapRun > $gapLimit) {
                    $flush();
                    $gapRun = 0;
                }
            }
        }

        $flush();

        return $phones;
    }

    /**
     * Replace phone numbers written in digits.
     *
     * @param string $text
     * @param string $replacer Default replacer: "[phone]"
     * @return string
     */
    public static function phoneInt(string $text, string $replacer = "[phone]")
    {
        // Match global phone patterns (not just Nigeria)
        $pattern = '/\b(?:\+?\d{1,3}[-.\s]?)?(?:\(?\d{2,4}\)?[-.\s]?){2,5}\d{2,6}\b/';

        return preg_replace_callback($pattern, function ($match) use ($replacer) {
            $candidate = preg_replace('/\D/', '', $match[0]); // keep digits only
            // Heuristic: valid phone numbers usually between 7–15 digits
            if (strlen($candidate) >= 7 && strlen($candidate) <= 15) {
                return $replacer;
            }
            return $match[0]; // leave unchanged if just random numbers
        }, $text);
    }

    /**
     * Replace phone numbers written in words or mixed with digits using an advanced detector.
     *
     * @param string $text
     * @param string $replacer Default replacer: "[phone]"
     * @return string
     */
    public static function phoneWords(string $text, string $replacer = "[phone]")
    {
        $ranges = self::detectPhoneWordSpans($text, 7, 3);
        if (empty($ranges)) {
            return $text;
        }

        $result = '';
        $cursor = 0;
        foreach ($ranges as [$s, $e]) {
            if ($s > $cursor) {
                $result .= substr($text, $cursor, $s - $cursor);
            }
            $result .= $replacer;
            $cursor = $e;
        }
        if ($cursor < strlen($text)) {
            $result .= substr($text, $cursor);
        }
        return $result;
    }

    /**
     * Replace URLs.
     * Handles bare domains (example.com), www.*, and http/https links.
     * Avoids matching email domains (e.g., user@example.com).
     *
     * @param string $text
     * @param string $replacer Default replacer: "[url]"
     * @return string
     */
    public static function url(string $text, string $replacer = "[url]")
    {
        // Match:
        // - Optional scheme or www
        // - Domain with TLD (letters only, len >= 2)
        // - Optional :port and path/query/fragment
        // - Exclude email domains by asserting no '@' immediately before
        $pattern = '~(?<!@)\b(?:https?://|www\.)?(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}(?::\d{2,5})?(?:/[^\s]*)?~i';

        return preg_replace_callback($pattern, function ($m) use ($replacer) {
            $match = $m[0];
            // Preserve common trailing punctuation outside the URL
            if (preg_match('/([\.,!\?;:\)\]\}]+)$/', $match, $pm)) {
                $trail = $pm[1];
                $core  = substr($match, 0, -strlen($trail));
                if ($core === '') {
                    return $match; // safety
                }
                return $replacer . $trail;
            }
            return $replacer;
        }, $text);
    }

    /**
     * Replace Emails.
     *
     * @param string $text
     * @param string $replacer Default replacer: "[email]"
     * @return string
     */
    public static function email(string $text, string $replacer = "[email]")
    {
        $pattern = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i';
        return preg_replace($pattern, $replacer, $text);
    }

    /**
     * Replace Mentions (@username).
     *
     * @param string $text
     * @param string $replacer Default replacer: "[username]"
     * @return string
     */
    public static function mention(string $text, string $replacer = "[username]")
    {
        $pattern = '/@\w{3,}/';
        return preg_replace($pattern, $replacer, $text);
    }

    /**
     * Replace Hashtags (#topic).
     *
     * @param string $text
     * @param string $replacer Default replacer: "[hashtag]"
     * @return string
     */
    public static function hastag(string $text, string $replacer = "[hashtag]")
    {
        $pattern = '/#\w+/';
        return preg_replace($pattern, $replacer, $text);
    }

    /**
     * Replace Hashtags (#topic).
     *
     * @param string $text
     * @param array $rules
     * @return string
     */
    public static function sanitize(
        string $text,
        array $rules = [
            'phoneInt' => '[phone]',
            'phoneWords' => '[phone]',
            'url' => '[url]',
            'email' => '[email]',
            'mention' => '[mention]',
            'hastag' => '[hastag]',
        ]
    ){

        foreach ($rules as $method => $replacer) {
            if ($replacer === false || $replacer === null) {
                continue; // skip disabled rule
            }

            // Ensure method exists in this class
            if (method_exists(__CLASS__, $method)) {
                $text = self::$method($text, $replacer);
            }
        }

        return $text;
    }
    
}