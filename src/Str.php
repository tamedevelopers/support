<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Traits\StrTrait;

class Str
{
    use StrTrait;
    
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
        return str_repeat($string, $times);
    }

    /**
     * Uppercase the first character of a string.
     *
     * @param string $string
     * @return string
     */
    public static function ucfirst(string $string)
    {
        return ucfirst($string);
    }

    /**
     * Lowercase the first character of a string.
     *
     * @param string $string
     * @return string
     */
    public static function lcfirst(string $string)
    {
        return lcfirst($string);
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
        preg_match($pattern, $subject, $matches);
        return $matches ?: null;
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
        $pattern = str_replace('*', '.*', preg_quote($pattern, '/'));
        return (bool) preg_match('/^' . $pattern . '\z/', $value);
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
            $string = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove', $string);
        } else {
            $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        }
        return preg_replace('/[^A-Za-z0-9_]/', '', $string);
    }

    /**
     * Limit the number of words in a string.
     *
     * @param string $string
     * @param int $words
     * @param string $end
     * @return string
     */
    public static function words(string $string, int $words, string $end = '...')
    {
        $array = preg_split('/\s+/', trim($string));
        if (count($array) <= $words) {
            return $string;
        }
        return implode(' ', array_slice($array, 0, $words)) . $end;
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
     *
     * @param array $array The input data array.
     * @param string $key The key to use for conversion.
     * @param int $case The case sensitivity option for key comparison (upper, lower).
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
     * Get the singular form of a word.
     *
     * @param string $value
     * @return string
     */
    public static function singular(string $value)
    {
        $rules = [
            '/(matr|vert|ind)ices$/i' => '$1ix',
            '/(quiz)zes$/i' => '$1',
            '/(database)s$/i' => '$1',
            '/(s)tatuses$/i' => '$1tatus',
            '/(ox)en$/i' => '$1',
            '/(alias|status|bus)es$/i' => '$1',
            '/([m|l])ice$/i' => '$1ouse',
            '/(x|ch|ss|sh)es$/i' => '$1',
            '/([^aeiouy]|qu)ies$/i' => '$1y',
            '/(s)eries$/i' => '$1eries',
            '/(movie)s$/i' => '$1',
            '/(hive)s$/i' => '$1',
            '/(tive)s$/i' => '$1',
            '/([lr])ves$/i' => '$1f',
            '/(shea|lea|loa|thie)ves$/i' => '$1f',
            '/(^analy)ses$/i' => '$1sis',
            '/([ti])a$/i' => '$1um',
            '/(tomat|potat|ech|her|vet)oes$/i' => '$1o',
            '/(bu)ses$/i' => '$1s',
            '/(octop|vir)i$/i' => '$1us',
            '/(us)es$/i' => '$1',
            '/(person)s$/i' => '$1',
            '/(child)ren$/i' => '$1',
            '/(man|woman)en$/i' => '$1',
            '/(tooth)teeth$/i' => '$1',
            '/(foot)feet$/i' => '$1',
            '/(goose)geese$/i' => '$1',
            '/(mouse)mice$/i' => '$1',
            '/(deer)$/i' => '$1',
            '/(sheep)$/i' => '$1',
        ];
        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $value)) {
                return preg_replace($pattern, $replacement, $value);
            }
        }
        if (substr($value, -1) === 's') {
            return substr($value, 0, -1);
        }
        return $value;
    }

    /**
     * Get the plural form of an English word.
     *
     * @param  string|null  $value
     * @return string
     */
    public static function pluralize($value = null)
    {
        $value = (string) $value;
        if (strlen($value) === 1) {
            return $value;
        }

        // Pluralization rules for common cases
        $rules = [
            '/(s)tatus$/i'                          => '$1tatuses',
            '/(quiz)$/i'                            => '$1zes',
            '/^(ox)$/i'                             => '$1en',
            '/([m|l])ouse$/i'                       => '$1ice',
            '/(matr|vert|ind)ix|ex$/i'              => '$1ices',
            '/(x|ch|ss|sh)$/i'                      => '$1es',
            '/([^aeiouy]|qu)y$/i'                   => '$1ies',
            '/(hive)$/i'                            => '$1s',
            '/(?:([^f])fe|([lr])f)$/i'              => '$1$2ves',
            '/(shea|lea|loa|thie)f$/i'              => '$1ves',
            '/sis$/i'                               => 'ses',
            '/([ti])um$/i'                          => '$1a',
            '/(tomat|potat|echo|hero|vet)o$/i'      => '$1es',
            '/(tomat|potat|ech|her|vet)o$/i'        => '$1oes',
            '/(bu)s$/i'                             => '$1ses',
            '/(alias)$/i'                           => '$1es',
            '/(octop)us$/i'                         => '$1i',
            '/(ax|test)is$/i'                       => '$1es',
            '/(us)$/i'                              => '$1es',
            '/(person)$/i'                          => '$1s',
            '/(child)$/i'                           => '$1ren',
            '/(man)$/i'                             => '$1en',
            '/(woman)$/i'                           => '$1en',
            '/(tooth)$/i'                           => '$1teeth',
            '/(foot)$/i'                            => '$1feet',
            '/(goose)$/i'                           => '$1geese',
            '/(mouse)$/i'                           => '$1mice',
            '/(deer)$/i'                            => '$1',
            '/(sheep)$/i'                           => '$1',
        ];

        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $value)) {
                return preg_replace($pattern, $replacement, $value);
            }
        }

        // Default case: append 's' to the word
        return $value . 's';
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
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Generate a random string of a given length.
     *
     * @param int $length
     * @return string
     */
    public static function random(int $length = 16)
    {
        // Define the character pool for the random string
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Generate a random string of the specified length
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    /**
     * Generate a string with a specified number of random words.
     *
     * @param int $wordCount
     * @return string
     */
    public static function randomWords(int $wordCount)
    {
        return self::generateRandomWords($wordCount);
    }

    /**
     * Generate a UUID (Universally Unique Identifier).
     *
     * @return string
     */
    public static function uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
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
     * Convert a string to camelCase.
     *
     * @param string $value
     * @return string
     */
    public static function camel(string $value)
    {
        // Remove special characters and spaces
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);

        // Convert to camelCase
        $value = ucwords(self::trim($value));
        $value = str_replace(' ', '', $value);
        $value = lcfirst($value);

        return $value;
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
        $value = preg_replace('/[^a-zA-Z0-9]+/', $separator, $value);
        $value = self::trim($value, $separator);
        $value = self::lower($value);

        return $value;
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
     * Convert a string to StudlyCase (PascalCase or UpperCamelCase).
     *
     * @param  string  $value
     * @return string
     */
    public static function studly(string $value)
    {
        $value = ucwords(preg_replace('/[\s_]+/', ' ', $value));
        $value = str_replace(' ', '', $value);

        return $value;
    }

    /**
     * Convert a string to kebab-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function kebab(string $value)
    {
        return self::lower(
            preg_replace('/\s+/u', '-', $value)
        );
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
     * Replace all occurrences of the search string with the replacement string
     * @param array|string $search
     * @param array|string $replace
     * @param string|null $subject
     * 
     * @return string
     */
    public static function replace($search, $replace, $subject = null)
    {
        $subject = self::replaceSubject($subject);
        $replace = self::replaceSubject($replace);

        return str_replace($search, $replace, $subject);
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
     * Check if a string or an array of words contains a given substring.
     *
     * @param string $needle
     * @param string|array $haystack
     * @return bool
     */
    public static function contains(string $needle, string|array $haystack)
    {
        if (is_array($haystack)) {
            // Check if any word in the array contains the substring
            foreach ($haystack as $word) {
                if (strpos($word, $needle) !== false) {
                    return true;
                }
            }
            return false;
        }

        // Check if the string contains the substring
        return strpos($haystack, $needle) !== false;
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
        if (strlen($value) <= $length) {
            return $value;
        }

        // Truncate the string and append the ellipsis
        $truncated = substr($value, 0, $length - strlen($ellipsis)) . $ellipsis;

        return $truncated;
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
     * Generate a string with a specified number of random words.
     *
     * @param int $wordCount
     * @param int $minLength
     * @param int $maxLength
     * @return string
     */
    public static function generateRandomWords(int $wordCount, int $minLength = 3, int $maxLength = 10)
    {
        $words = [];
        $characters = 'abcdefghijklmnopqrstuvwxyz';

        for ($i = 0; $i < $wordCount; $i++) {
            $length = rand($minLength, $maxLength);
            $word = '';

            for ($j = 0; $j < $length; $j++) {
                $word .= $characters[rand(0, strlen($characters) - 1)];
            }

            $words[] = $word;
        }

        return implode(' ', $words);
    }

    /**
     * Alias for (getFileExtension) method
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

        return $pos !== false ? substr($value, 0, $pos) : $value;
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
            ? substr($value, $pos + strlen($delimiter))
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
        $startPos = strpos($value, $start);
        $endPos = strpos($value, $end, $startPos + strlen($start));

        return $startPos !== false && $endPos !== false
            ? substr($value, $startPos + strlen($start), $endPos - $startPos - strlen($start))
            : '';
    }

}
