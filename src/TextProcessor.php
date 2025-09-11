<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;



class TextProcessor{
    

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
     * @param string $text
     * @param string $replacer Default replacer: "[phone]"
     * @return string
     */
    public static function phoneWords(string $text, string $replacer = "[phone]")
    {
        $wordToDigit = [
            'zero' => '0', 'oh' => '0', // "oh" is common
            'one' => '1', 'two' => '2', 'three' => '3',
            'four' => '4', 'five' => '5', 'six' => '6',
            'seven' => '7', 'eight' => '8', 'nine' => '9',
        ];

        // Tokenize words
        $tokens = preg_split('/\s+/', strtolower($text));

        $digitSeq   = '';
        $replaced   = false;
        $finalText  = '';

        foreach ($tokens as $token) {
            $clean = preg_replace('/[^a-z]/', '', $token);

            if (isset($wordToDigit[$clean])) {
                $digitSeq .= $wordToDigit[$clean];
                $finalText .= ' '; // placeholder
            } else {
                // if a digit sequence was forming and now interrupted
                if (strlen($digitSeq) >= 7) {
                    $finalText .= " {$replacer} ";
                    $replaced = true;
                } elseif ($digitSeq !== '') {
                    // not long enough, just restore as words
                    $finalText .= ' ' . $digitSeq;
                }
                $digitSeq = '';
                $finalText .= ' ' . $token;
            }
        }

        // End of text: check last sequence
        if (strlen($digitSeq) >= 7) {
            $finalText .= " {$replacer}";
            $replaced = true;
        } elseif ($digitSeq !== '') {
            $finalText .= ' ' . $digitSeq;
        }

        return $replaced ? trim(preg_replace('/\s+/', ' ', $finalText)) : $text;
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
            'phoneInt' => '[phoneInt]',
            'phoneWords' => '[phoneWords]',
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