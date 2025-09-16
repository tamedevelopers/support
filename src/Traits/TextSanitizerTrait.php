<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;


trait TextSanitizerTrait{

    /**
     * Detect spans (byte ranges) in the text that represent disguised phone numbers
     * written in words and/or digits, including multiples like "double", "triple", etc.
     * This is intentionally heavy-weight and used internally by phoneWords().
     *
     * @param string $text
     * @param int $minDigits Minimum digits required to qualify as a phone
     * @param int $gapLimit Allowed gap words between digits
     * @return array<int, array{0:int,1:int}> List of [startOffset, endOffset] ranges
     */
    private static function detectPhoneWordSpans(string $text, int $minDigits = 7, int $gapLimit = 3)
    {
        // Word-to-digit map with common variants
        $digitMap = [
            'zero' => '0', 'oh' => '0', 'o' => '0', 'nought' => '0', 'aught' => '0', 'nil' => '0',
            'one' => '1', 'two' => '2', 'three' => '3',
            'four' => '4', 'five' => '5', 'six' => '6',
            'seven' => '7', 'eight' => '8', 'nine' => '9',
        ];

        // Extended multipliers
        $multiplier = [
            'double' => 2, 'triple' => 3, 'quadruple' => 4, 'quintuple' => 5,
            'sextuple' => 6, 'septuple' => 7, 'octuple' => 8, 'nonuple' => 9, 'decuple' => 10,
        ];

        // Tokenize into words, digits, and everything else, preserving offsets
        if (!preg_match_all('/[A-Za-z]+|\d+|[^A-Za-z\d]+/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }
        $tokens = $m[0];
        $count  = count($tokens);

        $isWord = static function (string $s): bool {
            return (bool)preg_match('/^[A-Za-z]+$/u', $s);
        };

        $groups = [];
        $inGroup = false;
        $groupStartIdx = null;
        $lastPhoneTokenIdx = null;
        $digitsCount = 0;
        $gapRun = 0; // counts WORD tokens between contributions

        for ($i = 0; $i < $count; $i++) {
            [$tok] = $tokens[$i];
            $tokLower = strtolower($tok);

            $contributes = false;
            $consumeUntil = $i;
            $digitsAdded = 0;

            // Numeric chunk contributes its length
            if (ctype_digit($tok)) {
                $contributes = true;
                $digitsAdded = strlen($tok);
            } else {
                // Word tokens: digit words or multipliers
                if ($isWord($tok)) {
                    $word = strtolower(preg_replace('/[^A-Za-z]/u', '', $tok));

                    if (isset($digitMap[$word])) {
                        $contributes = true;
                        $digitsAdded = 1;
                    } elseif (isset($multiplier[$word])) {
                        // Look ahead to next token that's either a digit WORD or a one-digit number
                        $j = $i + 1;
                        while ($j < $count && !$isWord($tokens[$j][0]) && !ctype_digit($tokens[$j][0])) { $j++; }
                        if ($j < $count) {
                            $nextTok = $tokens[$j][0];
                            if ($isWord($nextTok)) {
                                $nextWord = strtolower(preg_replace('/[^A-Za-z]/u', '', $nextTok));
                                if (isset($digitMap[$nextWord])) {
                                    $contributes = true;
                                    $digitsAdded = $multiplier[$word];
                                    $consumeUntil = $j; // include the digit word
                                }
                            } elseif (ctype_digit($nextTok)) {
                                // Only treat single-digit numeric after multiplier for safety
                                if (strlen($nextTok) === 1) {
                                    $contributes = true;
                                    $digitsAdded = $multiplier[$word];
                                    $consumeUntil = $j; // include the numeric digit
                                }
                            }
                        }
                    }
                }
            }

            if ($contributes) {
                if (!$inGroup) {
                    $inGroup = true;
                    $groupStartIdx = $i;
                    $digitsCount = 0;
                    $gapRun = 0;
                }

                $lastPhoneTokenIdx = $consumeUntil;
                $digitsCount += $digitsAdded;
                $gapRun = 0;
                $i = $consumeUntil;
                continue;
            }

            if ($inGroup) {
                // Count only WORD tokens towards the gap (punctuation/spaces are neutral)
                if ($isWord($tok)) {
                    $gapRun++;
                    if ($gapRun > $gapLimit) {
                        if ($digitsCount >= $minDigits && $groupStartIdx !== null && $lastPhoneTokenIdx !== null) {
                            $groups[] = [$tokens[$groupStartIdx][1], $tokens[$lastPhoneTokenIdx][1] + strlen($tokens[$lastPhoneTokenIdx][0])];
                        }
                        // reset
                        $inGroup = false;
                        $groupStartIdx = null;
                        $lastPhoneTokenIdx = null;
                        $digitsCount = 0;
                        $gapRun = 0;
                    }
                }
            }
        }

        // Close trailing group
        if ($inGroup && $digitsCount >= $minDigits && $groupStartIdx !== null && $lastPhoneTokenIdx !== null) {
            $groups[] = [$tokens[$groupStartIdx][1], $tokens[$lastPhoneTokenIdx][1] + strlen($tokens[$lastPhoneTokenIdx][0])];
        }

        if (empty($groups)) {
            return [];
        }

        // Merge overlapping ranges
        usort($groups, static fn($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($groups as $r) {
            if (empty($merged) || $r[0] > $merged[count($merged)-1][1]) {
                $merged[] = $r;
            } else {
                $merged[count($merged)-1][1] = max($merged[count($merged)-1][1], $r[1]);
            }
        }

        return $merged;
    }

}