<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Slugify;

class Str{

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @param  mixed  $value
     * @return array
     */
    static public function wrap($value)
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
    static public function head($array = null)
    {
        return isset($array[0]) ? $array[0] : null;
    }

    /**
     * Get the last element of an array.
     *
     * @param array|null $array
     * @return mixed|null
     */
    static public function last($array = null)
    {
        if (!is_array($array)) {
            return null;
        }

        return end($array);
    }

    /**
     * Change Keys of Array
     *
     * @param  array $data
     * @param  string $fromKey
     * @param  string $toKey
     * @return array
     * - Returns an associative array
     */
    static public function changeKeysFromArray($data, $fromKey, $toKey)
    {
        // always convert to an array
        $data = Server::toArray($data);

        // If $data is an associative array, convert it to a numeric array
        if (self::isAssoc($data)) {
            $data = [$data];
        }
        
        // If you don't want to modify the original array and create a new one without 'id' columns:
        return array_map(function($item) use($fromKey, $toKey) {
            if (isset($item[$fromKey])) {
                $item[$toKey] = $item[$fromKey];
                unset($item[$fromKey]);
            }
            return $item;
        }, $data);
    }

    /**
     * Remove Keys From Array
     *
     * @param  array $data
     * @param  mixed $keys
     * @return array
     * - Returns an associative array
     */
    static public function removeKeysFromArray($data, ...$keys)
    {
        // always convert to an array
        $data = Server::toArray($data);

        // If $data is an associative array, convert it to a numeric array
        if (self::isAssoc($data)) {
            $data = [$data];
        }
        
        // If you don't want to modify the original array and create a new one without 'id' columns:
        return array_map(function($items) use($keys) {
            $keys = self::flattenValue($keys);
            foreach($keys as $key){
                if(isset($items[$key])){
                    unset($items[$key]);
                }
            }
            return $items;
        }, $data);
    }
    
    /**
     * Convert array keys to specified key if available, else return the original array.
     *
     * @param array $data The input data array.
     * @param string $key The key to use for conversion.
     * @param int $case The case sensitivity option for key comparison (upper, lower).
     * 
     * @return array
     * - The converted array with specified key as keys if available, else the original array
     */
    static public function convertArrayKey(array $data, string $key, $case = null)
    {
        // Extract the specified key values from the sub-arrays
        $values = array_column($data, $key);

        // Check if specified key values are available
        if ($values) {
            // Apply case transformation based on the specified option
            $matchValue = match (self::lower($case)) {
                'upper' => array_map('strtoupper', $values),
                'lower' => array_map('strtolower', $values),
                default => $values,
            };

            // Combine specified key values as keys with the original sub-arrays as values
            return array_combine($matchValue, $data);
        }

        return $data;
    }

    /**
     * Merge the binding arrays into a single array.
     *
     * @param array $bindings
     * @return array
     */
    static public function mergeBinding(array $bindings)
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
    static public function bindings(array $bindings)
    {
        return self::mergeBinding($bindings);
    }

    /**
     * Flatten a multidimensional array into a single-dimensional array.
     *
     * @param array $array The multidimensional array to flatten.
     * @return array The flattened array.
     */
    static public function flattenValue(array $array)
    {
        $result = [];

        foreach ($array as $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flattenValue($value));
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Exclude specified keys from an array.
     *
     * @param array $array The input array
     * @param mixed $keys The key(s) to exclude
     * @return array The filtered array
     */
    static public function exceptArray(array $array, $keys)
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
     * @param  string  $subject  The original string.
     * @return string  
     * - The modified string.
     */
    static public function replaceFirst(string $search, string $replace, string $subject)
    {
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
     * @param  string  $subject  The original string.
     * @return string  
     * - The modified string.
     */
    static public function replaceLast(string $search, string $replace, string $subject)
    {
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
     * Get the plural form of an English word.
     *
     * @param  string|null  $value
     * @return string
     */
    static public function pluralize($value = null)
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
    static public function startsWith(string $haystack, string $needle)
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
    static public function endsWith(string $haystack, string $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Generate a string with a specified number of random words.
     *
     * @param int $wordCount
     * @param int $minLength
     * @param int $maxLength
     * @return string
     */
    static public function randomWords(int $wordCount, int $minLength = 3, int $maxLength = 10)
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
     * Generate a random string of a given length.
     *
     * @param int $length
     * @return string
     */
    static public function random(int $length = 16)
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
    static public function snake(string $value, string $delimiter = '_')
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
    static public function camel(string $value)
    {
        // Remove special characters and spaces
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);

        // Convert to camelCase
        $value = ucwords(trim($value));
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
    static public function slug(string $value, string $separator = '-')
    {
        $value = preg_replace('/[^a-zA-Z0-9]+/', $separator, $value);
        $value = trim($value, $separator);
        $value = self::lower($value);

        return $value;
    }

    /**
     * Convert a string to StudlyCase (PascalCase or UpperCamelCase).
     *
     * @param  string  $value
     * @return string
     */
    static public function studly(string $value)
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
    static public function kebab(string $value)
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
    static public function title(string $value)
    {
        return ucwords(self::lower($value));
    }

    /**
     * Convert a string to a URL-friendly slug.
     *
     * @param  string  $value
     * @param  string  $language
     * @param  string  $separator
     * @param  bool  $case
     * @return string
     */
    static public function slugify(string $value, $language = null, string $separator = '-', $case = true)
    {
        return Slugify::slug(
            $value, $language, $separator, $case
        );
    }

    /**
     * Masks characters in a string.
     *
     * @param string|null $str 
     * - The string to be masked.
     * 
     * @param int $length 
     * - The desired length of the masked string. Default is 4.
     * 
     * @param string|null $position 
     * - The position to apply the mask: 'left', 'middle' or 'center', 'right'. Default is 'right'.
     * 
     * @param string $mask 
     * - The character used for masking. Default is '*'.
     * 
     * @return string 
     * - The masked string.
     */
    static public function mask($str = null, ?int $length = 4, ?string $position = null, ?string $mask = '*')
    {
        return Tame::mask(
            $str, $length, $position, $mask
        );
    }

    /**
     * Shorten String to Given Limit
     * 
     * @param  mixed $string
     * 
     * @param  mixed $limit
     * [optional] Default 50
     * 
     * @param  mixed $replacer
     * [optional] Default ...
     * 
     * @return string
     */
    static public function shorten($string = null, $limit = 50, $replacer = '...')
    {
        return Tame::shortenString(
            $string, $limit, $replacer
        );
    }

    /**
     * Decode entity html strings
     * 
     * @param string|null $string
     * @return string
     */
    static public function html($string = null)
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
    static public function text($string = null)
    {
        return Tame::text($string);
    }

    /**
     * Hash String
     *
     * @param  string|null $string
     * @param  int $length
     * @param  string $type
     * @param  int $interation
     * @return void
     */
    static public function hash($string = null, $length = 100, $type = 'sha256', $interation = 100)
    {
        return Tame::stringHash(
            $string, $length, $type, $interation 
        );
    }

    /**
     * Clean phone string
     *
     * @param string|null $phone
     * 
     * @param bool $allow --- Default is true
     * - [optional] to allow int format `+` (before number)
     * 
     * @return string
     */
    static public function phone($phone = null, ?bool $allow = true)
    {
        return Tame::cleanPhoneNumber(
            $phone, $allow
        );
    }

    /**
     * Convert a string to lowercase.
     * @param string|null $value
     * 
     * @return string
     */
    static public function lower($value = null)
    {
        return trim(strtolower((string) $value));
    }

    /**
     * Convert a string to uppercase.
     * @param string|null $value
     * 
     * @return string
     */
    static public function upper($value = null)
    {
        return trim(strtoupper((string) $value));
    }

    /**
     * Check if a string or an array of words contains a given substring.
     *
     * @param string $needle
     * @param string|array $haystack
     * @return bool
     */
    static public function contains(string $needle, string|array $haystack)
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
    static public function truncate(string $value, int $length, string $ellipsis = '...')
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
    static public function reverse(string $value)
    {
        return strrev($value);
    }

    /**
     * Count the occurrences of a substring in a string.
     *
     * @param string $haystack
     * @param string $needle
     * @return int
     */
    static public function countOccurrences(string $haystack, string $needle)
    {
        return substr_count($haystack, $needle);
    }

    /**
     * Remove all whitespace characters from a string.
     *
     * @param string $value
     * @return string
     */
    static public function removeWhitespace(string $value)
    {
        return preg_replace('/\s+/', '', $value);
    }

    /**
     * Get the file extension from a filename or path.
     *
     * @param string $filename
     * @return string|null
     */
    static public function fileExtension(string $filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return !empty($extension) ? $extension : null;
    }

    /**
     * Get the substring before the first occurrence of a delimiter.
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    static public function before(string $value, string $delimiter)
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
    static public function after(string $value, string $delimiter)
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
    static public function between(string $value, string $start, string $end)
    {
        $startPos = strpos($value, $start);
        $endPos = strpos($value, $end, $startPos + strlen($start));

        return $startPos !== false && $endPos !== false 
                ? substr($value, $startPos + strlen($start), $endPos - $startPos - strlen($start)) 
                : '';
    }

    /**
     * Check if a string matches a given pattern.
     *
     * @param string $value
     * @param string $pattern
     * @return bool
     */
    static public function matchesPattern(string $value, string $pattern)
    {
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Remove all occurrences of a substring from a string.
     *
     * @param string $value
     * @param string $substring
     * @return string
     */
    static public function removeSubstring(string $value, string $substring)
    {
        return str_replace($substring, '', $value);
    }

    /**
     * Pad a string with a specified character to a certain length.
     *
     * @param string $value
     * @param int $length
     * @param string $padChar
     * @param int $padType
     * @return string
     */
    static public function padString(string $value, int $length, string $padChar = ' ', int $padType = STR_PAD_RIGHT)
    {
        return str_pad($value, $length, $padChar, $padType);
    }

    /**
     * Check if an array is associative.
     *
     * @param array $array
     * @return bool
     */
    static private function isAssoc(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

}