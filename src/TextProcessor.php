<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;



class TextProcessor{
    

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
     * Replace phone numbers written in words.
     *
     * Advanced detection that:
     * - Maps digit words (zero..nine, oh, o) to digits
     * - Supports "double" / "triple" before a digit word (e.g., "double three" => 33)
     * - Allows up to GAP_LIMIT consecutive non-digit WORDS between digit words to catch disguised phones
     * - Replaces the entire disguised span with $replacer when >= MIN_DIGITS digits are detected
     *
     * @param string $text
     * @param string $replacer Default replacer: "[phone]"
     * @return string
     */
    public static function phoneWords(string $text, string $replacer = "[phone]")
    {
        // Configurable heuristics
        $MIN_DIGITS = 7;   // Common phone numbers: 7–15 digits
        $GAP_LIMIT  = 3;   // Allow up to 3 consecutive non-digit WORDS between digit words

        $digitMap = [
            'zero' => '0', 'oh' => '0', 'o' => '0', 'nought' => '0', 'aught' => '0',
            'one' => '1', 'two' => '2', 'three' => '3',
            'four' => '4', 'five' => '5', 'six' => '6',
            'seven' => '7', 'eight' => '8', 'nine' => '9',
        ];
        $multiplier = [ 'double' => 2, 'triple' => 3 ];

        // Split into tokens preserving everything (words, spaces, punctuation) with offsets
        // This lets us replace exact spans without losing formatting
        if (!preg_match_all('/[A-Za-z]+|[^A-Za-z]+/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return $text;
        }
        $tokens = $m[0]; // [ [token, offset], ... ]
        $count  = count($tokens);

        $groups = []; // [ ['startIdx'=>int,'endIdx'=>int,'digits'=>int], ... ]

        $inGroup = false;
        $groupStartIdx = null;
        $lastPhoneTokenIdx = null; // last token index that contributed a digit
        $digitsCount = 0;          // digits accumulated in current group
        $gapRun = 0;               // consecutive non-digit WORD tokens within a group

        // Helper: is pure word token
        $isWord = function (string $s): bool {
            return (bool)preg_match('/^[A-Za-z]+$/u', $s);
        };

        // Iterate token by token
        for ($i = 0; $i < $count; $i++) {
            [$tok, $off] = $tokens[$i];
            $tokLower = strtolower($tok);

            $word = null;
            $isWordToken = $isWord($tok);

            if ($isWordToken) {
                // Clean only alphabetic chars for mapping
                $word = strtolower(preg_replace('/[^A-Za-z]/u', '', $tok));
            }

            // Determine if current token contributes to digits
            $contributes = false;
            $digitsAdded = 0;
            $consumeUntil = $i; // how far index will move if we consume lookahead for double/triple

            if ($isWordToken) {
                if (isset($digitMap[$word])) {
                    $contributes = true;
                    $digitsAdded = 1;
                } elseif (isset($multiplier[$word])) {
                    // Look ahead to the next WORD token that is a digit word (skip non-words between)
                    $j = $i + 1;
                    while ($j < $count && !$isWord($tokens[$j][0])) { $j++; }
                    if ($j < $count) {
                        $nextWord = strtolower(preg_replace('/[^A-Za-z]/u', '', $tokens[$j][0]));
                        if (isset($digitMap[$nextWord])) {
                            $contributes = true;
                            $digitsAdded = $multiplier[$word];
                            $consumeUntil = $j; // consume up to the digit word
                        }
                    }
                }
            }

            if ($contributes) {
                // Start a new group if needed
                if (!$inGroup) {
                    $inGroup = true;
                    $groupStartIdx = $i;
                    $digitsCount = 0;
                    $gapRun = 0;
                }

                // If we consumed through a multiplier, include all tokens up to that point
                $lastPhoneTokenIdx = $consumeUntil;
                $digitsCount += $digitsAdded;
                $gapRun = 0; // reset gap since we just saw (effective) digit(s)

                // Advance the loop index if we consumed lookahead
                $i = $consumeUntil;
                continue;
            }

            // Non-contributing token
            if ($inGroup) {
                if ($isWordToken) {
                    $gapRun++;
                    if ($gapRun > $GAP_LIMIT) {
                        // finalize current group up to the last phone token
                        if ($digitsCount >= $MIN_DIGITS && $groupStartIdx !== null && $lastPhoneTokenIdx !== null) {
                            $groups[] = [
                                'startIdx' => $groupStartIdx,
                                'endIdx'   => $lastPhoneTokenIdx,
                                'digits'   => $digitsCount,
                            ];
                        }
                        // reset group state; current token will be processed as outside any group
                        $inGroup = false;
                        $groupStartIdx = null;
                        $lastPhoneTokenIdx = null;
                        $digitsCount = 0;
                        $gapRun = 0;
                    }
                }
                // For non-word tokens inside a group, we include them implicitly but don't increment gap
            }
        }

        // End of stream: close any open group
        if ($inGroup && $digitsCount >= $MIN_DIGITS && $groupStartIdx !== null && $lastPhoneTokenIdx !== null) {
            $groups[] = [
                'startIdx' => $groupStartIdx,
                'endIdx'   => $lastPhoneTokenIdx,
                'digits'   => $digitsCount,
            ];
        }

        if (empty($groups)) {
            return $text; // nothing to replace
        }

        // Build final string by replacing groups (ensure non-overlapping and ordered)
        // Convert token index groups to byte ranges
        $ranges = [];
        foreach ($groups as $g) {
            $startIdx = $g['startIdx'];
            $endIdx   = $g['endIdx'];
            $startOff = $tokens[$startIdx][1];
            $endOff   = $tokens[$endIdx][1] + strlen($tokens[$endIdx][0]);
            $ranges[] = [$startOff, $endOff];
        }

        // Merge overlapping ranges just in case
        usort($ranges, function ($a, $b) { return $a[0] <=> $b[0]; });
        $merged = [];
        foreach ($ranges as $r) {
            if (empty($merged) || $r[0] > $merged[count($merged)-1][1]) {
                $merged[] = $r;
            } else {
                $merged[count($merged)-1][1] = max($merged[count($merged)-1][1], $r[1]);
            }
        }

        // Apply replacements
        $result = '';
        $cursor = 0;
        foreach ($merged as [$s, $e]) {
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
     *
     * @param string $text
     * @param string $replacer Default replacer: "[url]"
     * @return string
     */
    public static function url(string $text, string $replacer = "[url]")
    {
        $pattern = '/\b(?:https?:\/\/|www\.)[^\s]+/i';
        return preg_replace($pattern, $replacer, $text);
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