<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Purify;
use Tamedevelopers\Support\Traits\StrTextTrait;
use Tamedevelopers\Support\Traits\StrTrait;

class Str
{
    use StrTrait, StrTextTrait;
    
    /**
     * Pad the left side of a string.
     * @return string
     */
    public static function padLeft(string $string, int $length, string $pad = ' ')
    {
        return str_pad($string, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * Pad the right side of a string.
     *
     * @param string $string
     * @param int $length
     * @param string $pad
     * @return string
     */
    public static function padRight(string $string, int $length, string $pad = ' ')
    {
        return str_pad($string, $length, $pad, STR_PAD_RIGHT);
    }

    /**
     * Repeat a string multiple times.
     *
     * @param string $string
     * @param int $times
     * @return string
     */
    public static function repeat(string $string, int $times)
    {
        return str_repeat($string, max(0, $times));
    }

    /**
     * Uppercase the first character of a string.
     *
     * @param string $string
     * @return string
     */
    public static function ucfirst(string $string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, null, 'UTF-8');
    }

    /**
     * Lowercase the first character of a string.
     *
     * @param string $string
     * @return string
     */
    public static function lcfirst(string $string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, null, 'UTF-8');
    }

    /**
     * Perform a regex match.
     *
     * @param string $pattern
     * @param string $subject
     * @return array|null
     */
    public static function match(string $pattern, string $subject)
    {
        return preg_match($pattern, $subject, $matches) ? $matches : null;
    }

    /**
     * Check if a string matches a given pattern (wildcards *).
     *
     * @param string $pattern
     * @param string $value
     * @return bool
     */
    public static function is(string $pattern, string $value)
    {
        if ($pattern === $value) {
            return true;
        }

        $pattern = str_replace('\*', '.*', preg_quote($pattern, '/'));

        return (bool) preg_match('/^' . $pattern . '\z/u', $value);
    }

    /**
     * Convert a string to its ASCII representation.
     *
     * @param string $string
     * @return string
     */
    public static function ascii(string $string)
    {
        if (function_exists('transliterator_transliterate')) {
            $string = transliterator_transliterate(
                'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove', 
                $string
            ) ?: '';
        } else {
            $string = (string) @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        }

        return (string) preg_replace('/[^A-Za-z0-9_]/', '', $string);
    }

    /**
     * Limit the number of words in a string.
     *
     * @param string $string
     * @param int $words
     * @param string $end
     * @return string
     */
    public static function words(string $string, int $words = 100, string $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $string, $matches);

        if (!isset($matches[0]) || mb_strlen($string) === mb_strlen($matches[0])) {
            return $string;
        }

        return rtrim($matches[0]) . $end;
    }

    /**
     * Swap multiple values in a string using an associative array.
     *
     * @param array $map
     * @param string $string
     * @return string
     */
    public static function swap(array $map, string $string)
    {
        return strtr($string, $map);
    }

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @param  mixed $value
     * @return array
     */
    public static function wrap($value)
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Get the first element of an array.
     *
     * @param  array|null  $array
     * @return mixed|null
     */
    public static function head($array = null)
    {
        return reset($array);
    }

    /**
     * Get the last element of an array.
     *
     * @param array|null $array
     * @return mixed|null
     */
    public static function last($array = null)
    {
        if (!is_array($array)) {
            return null;
        }

        return end($array);
    }

    /**
     * For sorting array
     *
     * @param  array $array
     * @param  string $type
     * - [rsort|asort|ksort|arsort|krsort|sort]
     * 
     * @return array
     */
    public static function sortArray(&$array = [], $type = 'sort')
    {
        return Tame::sortArray($array, $type);
    }

    /**
     * For sorting muti-dimentional array
     *
     * @param  array $array
     * @param  string|null $key
     * @param  string $type
     * - [asc|desc|snum]
     * 
     * @return array
     */
    public static function sortMultipleArray(&$array = [], $key = null, $type = 'asc')
    {
        return Tame::sortMultipleArray($array, $key, $type);
    }

    /**
     * Alias for changeKeysFromArray() method
     * - Rename keys of an Array
     *
     * @param  array $array
     * @param  array|string $fromKey
     * @param  string|null $toKey
     * @return array
     */
    public static function renameArrayKeys($array, $fromKey, $toKey = null)
    {
        return self::changeKeysFromArray($array, $fromKey, $toKey);
    }

    /**
     * Alias for removeKeysFromArray() method.
     * - Remove keys from an Array
     *
     * @param  array $array
     * @param  string|array $keys
     * @return array
     */
    public static function forgetArrayKeys($array, ...$keys)
    {
        return self::removeKeysFromArray($array, $keys);
    }

    /**
     * Alias for convertArrayKey() method.
     * - Convert array keys to specified key if available, else return the original array.
     *
     * @param array $array The input data array.
     * @param string $key The key to use for conversion.
     * @param string $case The case sensitivity option for key comparison (upper, lower).
     * 
     * @return array
     * - The converted array with specified key as keys if available, else the original array
     */
    public static function changeKeyCase(array $array, string $key, $case = null)
    {
        return self::convertArrayKey($array, $key, $case);
    }

    /**
     * Change the case of keys and/or values in a multi-dimensional array.
     *
     * @param array  $array  The input array
     * @param string $key   The case to convert for keys: 'lower', 'upper', or 'unchanged'
     * @param string $value The case to convert for values: 'lower', 'upper', or 'unchanged'
     *
     * @return array The array with converted case
     */
    public static function convertArrayCase($array, $key = 'lower', $value = 'unchanged')
    {
        $result = [];

        $allowed = ['lower', 'upper', 'lowercase', 'uppercase'];

        // convert to lowercase
        $key = self::lower($key);
        $value = self::lower($value);

        $key = in_array($key, $allowed) ? $key : 'unchanged';
        $value = in_array($value, $allowed) ? $value : 'unchanged';

        foreach ($array as $currentKey => $currentValue) {
            
            // convert the key at first
            $convertedKey = self::convertCase($currentKey, $key);

            if (is_array($currentValue)) {
                $result[$convertedKey] = self::convertArrayCase($currentValue, $key, $value);
            } else {
                $convertedValue = self::convertCase($currentValue, $value);
                $result[$convertedKey] = $convertedValue;
            }
        }

        return $result;
    }

    /**
     * Check if array has duplicate value
     *
     * @param array $array
     * @param bool $strict
     * @return bool
     */
    public static function arrayDuplicate(?array $array = [], bool $strict = false)
    {
        return count($array) > count(array_unique($array, $strict ? SORT_STRING : SORT_REGULAR));
    }

    /**
     * Check if all values of array is same
     *
     * @param array $array
     * @return bool
     */
    public static function arraySame(?array $array = [])
    {
        return !empty($array) && count(array_unique($array)) === 1;
    }

    /**
     * Merge the binding arrays into a single array.
     *
     * @param array $bindings
     * @return array
     */
    public static function mergeBinding(array $bindings)
    {
        // Extract the values from the associative array
        $values = array_values($bindings);

        // Merge all the arrays into a single array
        $mergedBindings = array_merge(...$values);

        // Return the merged bindings
        return $mergedBindings;
    }

    /**
     * Merge the binding arrays into a single array.
     *
     * @param array $bindings
     * @return array
     */
    public static function bindings(array $bindings)
    {
        return self::mergeBinding($bindings);
    }

    /**
     * Alias for flattenValue() method.
     * - Flatten a multidimensional array into a single-dimensional array.
     *
     * @param array $array The multidimensional array to flatten.
     * @return array The flattened array.
     */
    public static function flatten(array $array)
    {
        return self::flattenValue($array);
    }

    /**
     * Exclude specified keys from an array.
     *
     * @param array $array The input array
     * @param mixed $keys The key(s) to exclude
     * @return array The filtered array
     */
    public static function exceptArray(array $array, $keys)
    {
        // Convert single key to an array
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        // Use array_filter to keep only the elements with keys not present in $keys
        return array_filter($array, function ($key) use ($keys) {
            return !in_array($key, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Replace the first occurrence of a substring in a string.
     *
     * @param  string  $search   The substring to search for.
     * @param  string  $replace  The replacement substring.
     * @param  string|null  $subject  The original string.
     * @return string  
     * - The modified string.
     */
    public static function replaceFirst(string $search, string $replace, $subject = null)
    {
        $subject = self::replaceSubject($subject);
        $replace = self::replaceSubject($replace);

        // Find the position of the first occurrence of the search string
        $pos = strpos($subject, $search);

        // If a match is found, replace that portion of the subject string
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        // Return the modified subject string
        return $subject;
    }

    /**
     * Replace the last occurrence of a substring in a string.
     *
     * @param  string  $search   The substring to search for.
     * @param  string  $replace  The replacement substring.
     * @param  string|null  $subject  The original string.
     * @return string  
     * - The modified string.
     */
    public static function replaceLast(string $search, string $replace, $subject = null)
    {
        $subject = self::replaceSubject($subject);
        $replace = self::replaceSubject($replace);

        // Find the position of the first occurrence of the search string
        $pos = strrpos($subject, $search);

        // If a match is found, replace that portion of the subject string
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        // Return the modified subject string
        return $subject;
    }

    /**
     * Format Strings with Seperator
     *
     * @param  string $string
     * @param  int $number
     * @param  string $seperator
     * @return void
     */
    public static function formatString($string, $number = 4, $seperator = '-')
    {
        $string = implode($seperator, str_split(self::trim($string), $number));
        
        return self::replace(' ', '', $string);
    }

    /**
     * Format String Once with Separator
     *
     * @param  string $string
     * @param  int $number
     * @param  string $separator
     * @return string
     */
    public static function formatOnlyString($string, $number = 4, $separator = '-')
    {
        $string = self::trim($string);
        
        if (strlen($string) > $number) {
            $string = substr_replace($string, $separator, $number, 0);
        }
        
        return self::replace(' ', '', $string);
    }

    /**
     * Clean phone string
     *
     * @param string|null $phone
     * @param bool $allow
     * - [optional] to allow int format `+` (before number)
     * 
     * @return string
     */
    public static function phone($phone = null, ?bool $allow = true)
    {
        return Tame::cleanPhoneNumber($phone, $allow);
    }

    /**
     * Masks characters in a string.
     *
     * @param string|null $str 
     * - The string to be masked.
     * 
     * @param int $length 
     * - The number of visible characters. Default is 4.
     * 
     * @param string $position 
     * - The position to apply the mask: 'left', 'middle' or 'center', 'right'. Default is 'right'.
     * 
     * @param string $mask 
     * - The character used for masking. Default is '*'.
     * 
     * @return string 
     * - The masked string.
     */
    public static function mask($str = null, $length = 4, $position = 'right', $mask = '*')
    {
        return Tame::mask($str, $length, $position, $mask);
    }

    /**
     * Decode entity html strings
     * 
     * @param string|null $string
     * @return string
     */
    public static function html($string = null)
    {
        return Tame::html($string);
    }

    /**
     * Convert string to clean text without html tags
     * 
     * @param string|null $string
     * 
     * @return string
     * - strip all tags from string content
     */
    public static function text($string = null)
    {
        return Tame::text($string);
    }

    /**
     * Shorten String to Given Limit
     * 
     * @param  mixed $string
     * @param  mixed $limit
     * @param  mixed $replacer
     * [optional]
     * 
     * @return string
     */
    public static function shorten($string = null, $limit = 50, $replacer = '...')
    {
        return Tame::shortenString($string, $limit, $replacer);
    }

    /**
     * Filter sanitize string|text
     * 
     * @param string|null $string
     * @return string
     */
    public static function sanitize($string = null)
    {
        return Tame::filter_input($string);
    }

    /**
     * Convert HTML content to readable string
     *
     * @param string $content
     * @param bool $allowUrl
     * @return string
     */
    public static function readable(string $content, bool $allowUrl = true)
    {
        return Purify::readable($content, $allowUrl);
    }

    /**
     * Format number to nearest thousand
     * 
     * @param  float|int $number
     * @return string
     */
    public static function toThousand($number = 0)
    {
        return Tame::formatNumberToNearestThousand($number);
    }
    
    /**
     * Encrypt string
     *
     * @param string|null $string
     * @return string
     */
    public static function encrypt($string = null)
    {
        return Tame::encryptStr($string);
    }

    /**
     * Derypt string
     *
     * @param string|null $jsonString
     * @return mixed
     */
    public static function decrypt($jsonString = null)
    {
        return Tame::decryptStr($jsonString);
    }

    /**
     * Get the singular form of a word.
     *
     * @param string $value
     * @return string
     */
    public static function singular(string $value)
    {
        if (strlen($value) <= 1) {
            return $value;
        }

        $rules = [
            // --- Uncountable / No Change ---
            '/(advice|information|knowledge|furniture|equipment|series|species|news|rice|fish|deer|sheep)$/i' => '$1',

            // --- Irregular Plurals (Full Overwrites) ---
            '/people$/i'                => 'person',
            '/geese$/i'                 => 'goose',
            '/teeth$/i'                 => 'tooth',
            '/feet$/i'                  => 'foot',
            '/mice$/i'                  => 'mouse',
            '/lice$/i'                  => 'louse',
            '/(child)ren$/i'            => '$1',
            '/(ox)en$/i'                => '$1',
            '/^(men)$/i'                => 'man',
            '/^(women)$/i'              => 'woman',
            '/(wo|man)en$/i'            => '$1',

            // --- Greek / Latin / Scientific (Reverse) ---
            '/(database|status)es$/i'   => '$1',
            '/(matr|vert|ind|append)ices$/i' => '$1ix',
            '/(alumn|termin|stimul|bacill|nucle)i$/i' => '$1us',
            '/(alumn|formul|vertebr)ae$/i' => '$1a',
            '/(ax|test|diagnos|analys|parenthes|bas|emphas|thes)es$/i' => '$1is',
            '/(crit|phenomen)era$/i'    => '$1erion',
            '/(crit|phenomen)a$/i'      => '$1on',
            '/([ti])a$/i'               => '$1um',

            // --- Sibilants & "O" Rules ---
            '/(quiz)zes$/i'             => '$1',
            '/(tomat|potat|ech|her|vet|volcan|buffal|carg)oes$/i' => '$1o',
            '/(alias|status|bus)es$/i'  => '$1',
            '/(bu)ses$/i'               => '$1s',
            '/(x|ch|ss|sh)es$/i'        => '$1',

            // --- The "Y" Rule ---
            '/([^aeiouy]|qu)ies$/i'     => '$1y',

            // --- The "F/FE" Rule (ves -> f/fe) ---
            '/(?:([^f])fe|([lr])f)$/i'  => '$1$2', // Handles leaf/leaves
            '/(shea|lea|loa|thie|wi|li|hal|wol)ves$/i' => '$1f',
            '/(hive|tive|movie)s$/i'    => '$1',
        ];

        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $value)) {
                return preg_replace($pattern, $replacement, $value);
            }
        }

        // Default: If it ends in 's', strip it
        if (strtolower(mb_substr($value, -1)) === 's') {
            return mb_substr($value, 0, -1);
        }

        return $value;
    }

    /**
     * Get the plural form of an English word.
     *
     * @param  string|null  $value
     * @param  bool         $isPossessive  If true, returns "word's" or "words'"
     * @return string
     */
    public static function pluralize($value = null, bool $isPossessive = false)
    {
        $value = (string) $value;
        if (strlen($value) === 1) {
            return $isPossessive ? $value . "'s" : $value;
        }

        $rules = [
            // --- Special Case: Uncountable / Same as Singular ---
            '/(advice|information|knowledge|furniture|equipment|series|species|news|rice|fish|deer|sheep)$/i' => '$1',

            // --- Pronouns & Irregulars ---
            '/^i$/i'                                => 'we',
            '/^me$/i'                               => 'us',
            '/^he$|^she$|^it$/i'                    => 'they',
            '/^(child)$/i'                          => '$1ren',
            '/^(ox)$/i'                             => '$1en',
            '/^(person)$/i'                         => 'people',
            '/([m|l])ouse$/i'                       => '$1ice',
            '/(tooth)$/i'                           => 'teeth',
            '/(foot)$/i'                            => 'feet',
            '/(goose)$/i'                           => 'geese',
            '/(man|woman)$/i'                       => '$1en',

            // --- Greek / Latin / Scientific ---
            '/(database|status)$/i'                 => '$1es',
            '/(quiz)$/i'                            => '$1zes',
            '/(matr|vert|ind|append)ix|ex$/i'       => '$1ices',
            '/(alumn|termin|stimul|bacill|nucle)us$/i' => '$1i',
            '/(alumn|formul|vertebr)a$/i'           => '$1ae',
            '/(ax|test|diagnos|analys|parenthes|bas|emphas|thes)is$/i' => '$1es',
            '/(crit|phenomen)erion|on$/i'           => '$1era',
            '/([ti])um$/i'                          => '$1a',

            // --- Sibilant Endings (s, x, ch, sh) ---
            '/(x|ch|ss|sh)$/i'                      => '$1es',
            '/(bu)s$/i'                             => '$1ses',
            '/(alias)$/i'                           => '$1es',
            '/(us)$/i'                              => '$1es',

            // --- The "Y" Rule ---
            '/([^aeiouy]|qu)y$/i'                   => '$1ies',

            // --- The "O" Rule (Potato/Tomato) ---
            '/(tomat|potat|ech|her|vet|volcan|buffal|carg)o$/i' => '$1oes',

            // --- The "F/FE" Rule (Leaf/Knife) ---
            '/(?:([^f])fe|([lr])f)$/i'              => '$1$2ves',
            '/(shea|lea|loa|thie|wi|li|hal|wol)f$/i' => '$1ves',
            '/(hive)$/i'                            => '$1s',
        ];

        $plural = $value;
        $found = false;

        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $value)) {
                $plural = preg_replace($pattern, $replacement, $value);
                $found = true;
                break;
            }
        }

        // Only add 's' if no specific rule was found and we AREN'T doing a possessive proper name
        if (!$found) {
            $plural = ($isPossessive) ? $value : $value . 's';
        }

        // Handle Possessive logic
        if ($isPossessive) {
            // If it ends in 's', strip the 's' and replace with "'s"
            // Example: James -> Jame's | Wolves -> Wolve's
            if (strtolower(mb_substr($plural, -1)) === 's') {
                return mb_substr($plural, 0, -1) . "'s";
            }

            // Otherwise just append 's (e.g., Isaiah -> Isaiah's)
            return $plural . "'s";
        }

        return $plural;
    }

    /**
     * Check if a string starts with a given substring.
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle)
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    /**
     * Check if a string ends with a given substring.
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle)
    {
        return mb_substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Generate a random string of a given length.
     *
     * @param int $length
     * @return string
     */
    public static function random(int $length = 16)
    {
        $bytes = random_bytes((int) ceil($length / 2));

        return mb_substr(bin2hex($bytes), 0, $length);
    }

    /**
     * Alias for generateRandomWords($words)    Generate a string with a specified number of random words.
     *
     * @param int $wordCount Number of words to generate.
     * @param bool $isLorem Whether to generate Lorem Ipsum style words.
     * @param int $minLength Minimum word length (used if $isLorem is false).
     * @param int $maxLength Maximum word length (used if $isLorem is false).
     * @return string
     */
    public static function randomWords(int $wordCount, $isLorem = true, int $minLength = 3, int $maxLength = 10)
    {
        return self::generateRandomWords($wordCount, $isLorem, $minLength, $maxLength);
    }

    /**
     * Generate a UUID (Universally Unique Identifier).
     *
     * @return string
     */
    public static function uuid()
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Convert a string to snake_case.
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function snake(string $value, string $delimiter = '_')
    {
        // Replace spaces with delimiter and capitalize each word
        $value = preg_replace('/\s+/u', $delimiter, ucwords($value));

        return self::lower($value);
    }

    /**
     * Convert a string to StudlyCase (PascalCase or UpperCamelCase).
     *
     * @param  string  $value
     * @return string
     */
    public static function studly(string $value)
    {
        $value = ucwords(preg_replace('/[\s_-]+/', ' ', $value));
        $value = str_replace(' ', '', $value);

        return $value;
    }

    /**
     * Convert a string to camelCase.
     *
     * @param string $value
     * @return string
     */
    public static function camel(string $value)
    {
        return lcfirst(self::studly($value));
    }

    /**
     * Convert a string to kebab-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function kebab(string $value)
    {
        return self::snake($value, '-');
    }

    /**
     * Generate a slug from a string.
     *
     * @param string $value
     * @param string $separator
     * @return string
     */
    public static function slug(string $value, string $separator = '-')
    {
        $removeWhiteSpace = function($value) use($separator) {
            // Remove all special characters but keep letters, numbers, and Chinese characters
            $value = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $value);
            
            // Replace whitespace with separator
            $value = preg_replace('/[\s-]+/u', $separator, $value);

            // Convert to lowercase
            return mb_strtolower($value, 'UTF-8');
        };

        // Try PHP's transliterator first (most comprehensive)
        if (extension_loaded('intl')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII', $value);
            if ($transliterated !== false) {
                return $removeWhiteSpace($transliterated);
            }
        }

        // Convert to UTF-8 if not already
        if (!mb_detect_encoding($value, 'UTF-8', true)) {
            $value = mb_convert_encoding($value, 'UTF-8');
        }

        return $removeWhiteSpace($value);
    }
    
    /**
     * Replaces all spaces in the given string with the specified separator.
     *
     * @param string $value The input string in which spaces will be replaced.
     * @param string $separator The string to replace spaces with. Defaults to '_'.
     * @return string
     */
    public static function spaceReplacer(string $value, string $separator = '_')
    {
        return self::replace(' ', $separator, $value);
    }

    /**
     * Splits a full name into first and last name components.
     *
     * Handles extra whitespace, single-word names, and groups any 
     * middle names together with the last name.
     *
     * @param string $fullName The raw name string to split.
     * @return array{firstName: string, lastName: string} An associative array containing 'firstName' and 'lastName'.
     */
    public static function splitName(string $fullName) 
    {
        // Trim spaces and split by one or more whitespace characters
        $parts = preg_split('/\s+/', trim($fullName));

        if (empty($parts) || $parts[0] === '') {
            return ['firstName' => '', 'lastName' => ''];
        }

        if (count($parts) === 1) {
            return ['firstName' => $parts[0], 'lastName' => ''];
        }

        // First element as first name, the rest joined together as last name
        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);

        return [
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ];
    }

    /**
     * Capitalize words in a string to StudlyCase.
     *
     * @param string $value
     * @return string
     */
    public static function capitalizeWords(string $value): string
    {
        // Replace any non-alphanumeric characters with space
        $value = preg_replace('/[^a-zA-Z0-9]+/', ' ', $value);

        // Capitalize first letter of each word
        $value = ucwords($value);

        // Remove spaces
        $value = str_replace(' ', '', $value);

        return $value;
    }

    /**
     * Convert a string to Title Case.
     *
     * @param  string  $value
     * @return string
     */
    public static function title(string $value)
    {
        return ucwords(self::lower($value));
    }

    /**
     * Convert a string to a URL-friendly slug.
     *
     * @param  string  $value
     * @param  string  $separator
     * @return string
     */
    public static function slugify(string $value, string $separator = '-')
    {
        // Try to transliterate using intl extension
        if (function_exists('transliterator_transliterate')) {
            $value = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
        } else {
            // Fallback to iconv for transliteration if intl is not available
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }

        // Replace non-alphanumeric characters with the separator
        $value = preg_replace('/[^a-z0-9-]+/', $separator, $value);

        // Remove leading and trailing separators
        $value = self::trim($value, $separator);

        return $value;
    }

    /**
     * Check if a string or an array of words contains a given substring.
     *
     * @param string|null $haystack
     * @param string|iterable<string> $needles
     * @return bool
     */
    public static function contains($haystack = null, $needles = null, $ignoreCase = false)
    {
        if (empty($haystack) || empty($needles)) {
            return false;
        }

        if (!is_iterable($needles)) {
            $needles = [$needles];
        }

        foreach ($needles as $needle) {
            if ((string) $needle === '') {
                continue;
            }

            if ($ignoreCase) {
                if (mb_stripos($haystack, (string) $needle) !== false) {
                    return true;
                }
            } else {
                if (mb_strpos($haystack, (string) $needle) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Replace all occurrences of the search string with the replacement string
     * @param mixed <array|string|null> $search
     * @param mixed <array|string|null> $replace
     * @param string|null $subject 
     * 
     * @return string
     */
    public static function replace($search, $replace, $subject = null)
    {
        $search  = self::replaceSubject($search);
        $replace = self::replaceSubject($replace);
        $subject = self::replaceSubject($subject);

        return str_replace($search, $replace, $subject);
    }

    /**
     * Truncate a string to a specified length and append an ellipsis if necessary.
     *
     * @param string $value
     * @param int $length
     * @param string $ellipsis
     * @return string
     */
    public static function truncate(string $value, int $length, string $ellipsis = '...')
    {
        // Check if truncation is necessary
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        // Truncate the string and append the ellipsis
        $truncated = mb_substr($value, 0, $length - mb_strlen($ellipsis)) . $ellipsis;

        return $truncated;
    }

    /**
     * Strip whitespace (or other characters) from the beginning and end of a string
     * @param string|null $string — The string that will be trimmed.
     *
     * @param string $characters
     * [optional] Optionally, the stripped characters can also be specified using the charlist parameter. 
     * Simply list all characters that you want to be stripped. With .. you can specify a range of characters.
     * 
     * @return string
     */
    public static function trim($string = null, string $characters = " \n\r\t\v\0")
    {
        $string = is_array($string) ? $string[0] ?? null : $string;
        return trim((string) $string, $characters);
    }

    /**
     * Convert a string to lowercase.
     * @param string|null $value
     * 
     * @return string
     */
    public static function lower($value = null)
    {
        return self::normalize($value);
    }

    /**
     * Convert a string to uppercase.
     * @param string|null $value
     * 
     * @return string
     */
    public static function upper($value = null)
    {
        return self::normalize($value, 'upper');
    }

    /**
     * Reverse the order of characters in a string.
     *
     * @param string $value
     * @return string
     */
    public static function reverse(string $value)
    {
        return strrev($value);
    }

    /**
     * Count the length of a string|array
     *
     * @param string|array $value
     * @return int
     */
    public static function count($value)
    {
        return is_array($value) ? count($value) : self::trim(strlen($value));
    }

    /**
     * Count the occurrences of a substring in a string.
     *
     * @param string $haystack
     * @param string $needle
     * @return int
     */
    public static function countOccurrences(string $haystack, string $needle)
    {
        return substr_count($haystack, $needle);
    }

    /**
     * Remove all whitespace characters from a string.
     *
     * @param string $value
     * @return string
     */
    public static function removeWhitespace(string $value)
    {
        return preg_replace('/\s+/', '', $value);
    }

    /**
     * Generate a readable or Lorem Ipsum word string.
     *
     * @param int $wordCount Number of words to generate.
     * @param bool $isLorem Whether to generate Lorem Ipsum style words.
     * @param int $minLength Minimum word length (used if $isLorem is false).
     * @param int $maxLength Maximum word length (used if $isLorem is false).
     * @return string
     */
    public static function generateRandomWords(int $wordCount, $isLorem = true, int $minLength = 3, int $maxLength = 10)
    {
        if ($wordCount <= 0) {
            return '';
        }

        if ($isLorem) {
            $dictionary = [
                'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
                'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
                'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
                'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
                'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
                'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint',
                'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui', 'officia',
                'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum'
            ];

            $words = [];
            $max = count($dictionary) - 1;

            for ($i = 0; $i < $wordCount; $i++) {
                $words[] = $dictionary[random_int(0, $max)];
            }

            return ucfirst(implode(' ', $words));
        }

        return self::generatePronounceableWords($wordCount);
    }

    /**
     * Alias for (getFileExtension) method
     * - Get the file extension from a filename or path.
     *
     * @param string $filename
     * @return string|null
     */
    public static function extension(string $filename)
    {
        return self::getFileExtension($filename);
    }

    /**
     * Get the substring before the first occurrence of a delimiter.
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function before(string $value, string $delimiter)
    {
        $pos = strpos($value, $delimiter);

        return $pos !== false ? mb_substr($value, 0, $pos) : $value;
    }

    /**
     * Get the substring after the first occurrence of a delimiter.
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function after(string $value, string $delimiter)
    {
        $pos = strpos($value, $delimiter);

        return $pos !== false
            ? mb_substr($value, $pos + mb_strlen($delimiter))
            : '';
    }

    /**
     * Get the substring between two delimiters.
     *
     * @param string $value
     * @param string $start
     * @param string $end
     * @return string
     */
    public static function between(string $value, string $start, string $end)
    {
        if ($start === '' || $end === '') {
            return $value;
        }
        
        $startPos = strpos($value, $start);
        $endPos = strpos($value, $end, $startPos + strlen($start));

        return $startPos !== false && $endPos !== false
            ? mb_substr($value, $startPos + mb_strlen($start), $endPos - $startPos - mb_strlen($start))
            : '';
    }

}
