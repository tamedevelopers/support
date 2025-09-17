<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Server;

trait StrTrait{

    /**
     * Rename Keys of an Array
     *
     * @param  array $array
     * @param  array|string $fromKey
     * @param  string|null $toKey
     * @return array
     */
    public static function changeKeysFromArray($array, $fromKey, $toKey = null): array
    {
        // always convert to an array
        $array = Server::toArray($array);

        // Normalize into mapping format: ['old' => 'new']
        $mappings = is_array($fromKey) ? $fromKey : [$fromKey => $toKey];        

        // If you don't want to modify the original array and create a new one without 'id' columns:
        return array_map(function ($item) use ($mappings) {
            foreach ($mappings as $oldKey => $newKey) {
                if (isset($item[$oldKey])) {
                    $item[$newKey] = $item[$oldKey];
                    unset($item[$oldKey]);
                }
            }
            return $item;
        }, $array);
    }
    
    /**
     * Convert array keys to specified key if available, else return the original array.
     *
     * @param array $array The input data array.
     * @param string $key The key to use for conversion.
     * @param string $case The case sensitivity option for key comparison (upper, lower).
     * 
     * @return array
     * - The converted array with specified key as keys if available, else the original array
     */
    public static function convertArrayKey(array $array, string $key, $case = null): array
    {
        // Extract the specified key values from the sub-arrays
        $values = array_column($array, $key);

        // Check if specified key values are available
        if ($values) {
            // Apply case transformation based on the specified option
            $matchValue = match (self::lower($case)) {
                'upper', 'uppercase', 'upper_case' => array_map('strtoupper', $values),
                'lower', 'lowercase', 'lower_case' => array_map('strtolower', $values),
                default => $values,
            };

            // Combine specified key values as keys with the original sub-arrays as values
            return array_combine($matchValue, $array);
        }

        return $array;
    }

    /**
     * Remove Keys From Array
     *
     * @param  array $array
     * @param  string|array $keys
     * @return array
     */
    public static function removeKeysFromArray($array, ...$keys): array
    {
        // always convert to an array
        $array = (array) $array;
        
        // flattern keys
        $keys = self::flattenValue($keys);

        // Iterate through each data item
        foreach ($array as &$item) {
            // Remove specified keys from the item
            foreach ($keys as $key) {
                if (is_array($item) && array_key_exists($key, $item)) {
                    unset($item[$key]);
                } else{
                    if(array_key_exists($key, $array)){
                        unset($array[$key]);
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Get the file extension from a filename or path.
     *
     * @param string $filename
     * @return string|null
     */
    public static function getFileExtension(string $filename): ?string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return !empty($extension) ? $extension : null;
    }

    /**
     * Flatten a multidimensional array into a single-dimensional array.
     *
     * @param array $array The multidimensional array to flatten.
     * @return array The flattened array.
     */
    public static function flattenValue(array $array): array
    {
        $result = [];
        
        array_walk_recursive($array, function ($item) use (&$result) {
            $result[] = $item;
        });

        return $result;
    }

    /**
     * Check if a string matches a given pattern.
     *
     * @param string $value
     * @param string $pattern
     * @return bool
     */
    public static function matchesPattern(string $value, string $pattern): bool
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
    public static function removeSubstring(string $value, string $substring): string
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
    public static function padString(string $value, int $length, string $padChar = ' ', int $padType = STR_PAD_RIGHT): string
    {
        return str_pad($value, $length, $padChar, $padType);
    }

    /**
     * Normalize a string: trim and convert case.
     *
     * @param string|null $value
     * @param string $case 'lower' or 'upper'
     * @param string $encoding Optional, for multibyte support
     * @return string
     */
    private static function normalize($value = null, string $case = 'lower', string $encoding = 'UTF-8'): string
    {
        $value = self::trim($value);

        if ($case === 'upper') {
            return function_exists('mb_strtoupper') 
                ? mb_strtoupper(self::trim($value), $encoding) 
                : strtoupper(self::trim($value));
        }

        // default: lower
        return function_exists('mb_strtolower')
            ? mb_strtolower(self::trim($value), $encoding) 
            : strtolower(self::trim($value));
    }

    /**
     * Replace Subject
     *
     * @param  mixed $subject
     * @return mixed
     */
    private static function replaceSubject($subject = null): mixed
    {
        return is_null($subject) ? (string) $subject : $subject;
    }

    /**
     * Convert the case of a string based on the specified type.
     *
     * @param mixed $string The input string
     * @param string|null $type   The case to convert: 'lower', 'upper', or 'unchanged'
     *
     * @return string The string with converted case
     */
    private static function convertCase($string = null, $type = null): string
    {
        return match (self::lower($type)) {
            'upper', 'uppercase', 'upper_case' => self::upper($string),
            'lower', 'lowercase', 'lower_case' => self::lower($string),
            default => (string) $string,
        };
    }

}