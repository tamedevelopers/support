<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Traits\TameTrait;
use Tamedevelopers\Support\Traits\NumberToWordsTraits;

/**
 * @see \Tamedevelopers\Support\Str
 * @see \Tamedevelopers\Support\Server
 * @see \Tamedevelopers\Support\Time
 */
class Tame {

    use TameTrait, 
        NumberToWordsTraits;
    
    /**
     * Count
     * @var int
     */
    protected const COUNT = 0;

    /**
     * Kilobytes Value
     * @var int
     */
    protected const KB = 1024;

    /**
     * Megabytes Value
     * @var int
     */
    protected const MB = 1024 * self::KB;

    /**
     * Gegabytes Value
     * @var int
     */
    protected const GB = 1024 * self::MB;

    /**
     * Salter String
     * @var string
     */
    private const PBKDF2_SALT = "\x2d\xb7\x68\x1a";
    
    
    /**
     * Echo `json_encode` with response and message
     *
     * @param  int $response
     * @param  mixed $message
     * @return mixed
     */
    public static function echoJson(int $response = 0, $message = null)
    {
        echo json_encode(['response' => $response, 'message' => $message]);
    }

    /**
     * Check IF URL Exists
     * 
     * @param string $url
     * @return bool
     */
    public static function urlExists($url)
    {
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects

        // Execute cURL and get the HTTP status code
        $httpCode = curl_exec($ch);

        // Close cURL handle
        curl_close($ch);

        return $httpCode && preg_match('/\b200\b/', $httpCode);
    }

    /**
     * Check IF Internet is Available
     * 
     * @return bool
     */
    public static function isInternetAvailable()
    {
        return self::urlExists('https://www.google.com');
    }

    /**
     * Check if Class Exists
     *
     * @param  string $class
     * @param  Closure|null $closure
     * @return void
     */
    public static function class_exists($class, $closure = null)
    {
        if(class_exists($class)){
            if(self::isClosure($closure)){
                $closure();
            }
        }
    }

    /**
     * Check if at least one class exists
     *
     * @param string|array $classNames Array of class names to check
     * @return bool 
     * - True if at least one class exists, false otherwise
     */
    public static function checkAnyClassExists(...$classNames)
    {
        $classNames = Str::flattenValue($classNames);
        foreach ($classNames as $name) {
            if (class_exists($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * PHP Version Compare
     * 
     * @param string $version
     * @return bool
     */
    public static function versionCompare($version)
    {
        if(version_compare(PHP_VERSION, $version, '>=')){
            return true;
        }
        
        return false;
    }

    /**
     * Check if headers have been sent.
     * This function checks if headers have been sent and outputs information about where headers were sent.
     * If headers are sent, it outputs the file and location where the headers were sent and terminates the script.
     */
    public static function HeadersSent()
    {
        $file = null;
        $location = null;
        if (headers_sent($file, $location)) {
            // Headers have been sent, output the file and location
            echo "Headers sent in file: {$file} <br/> Location: {$location} <br/>";
            die('Script terminated after detecting headers sent.');
        }
    }
    
    /**
     * include if file exist
     * 
     * @param string $path
     * - [full path to file]
     * 
     * @return void
     */
    public static function include($path)
    {
        if(self::exists($path)){
            include $path;
        }
    }

    /**
     * include once if file exist
     * 
     * @param string $path
     * - [full path to file]
     * 
     * @return void
     */
    public static function includeOnce($path)
    {
        if(self::exists($path)){
            include_once $path;
        }
    }

    /**
     * require if file exist
     * 
     * @param string $path
     * - [full path to file]
     * 
     * @return void
     */
    public static function require($path)
    {
        if(self::exists($path)){
            require $path;
        }
    }

    /**
     * require_once if file exist
     * 
     * @param string $path
     * - [full path to file]
     * 
     * @return void
     */
    public static function requireOnce($path)
    {
        if(self::exists($path)){
            require_once $path;
        }
    }
    
    /**
     * Convert Bytes to Units 
     *
     * @param  int|float $bytes The size in bytes to be converted.
     * @param  bool $format Whether to preserve case (default: lowercase).
     * @param  string|null $gb Custom label for GB (default: 'GB').
     * @param  string|null $mb Custom label for MB (default: 'MB').
     * @param  string|null $kb Custom label for KB (default: 'KB').
     * 
     * @return string
     */
    public static function byteToUnit($bytes = 0, $format = false, $gb = 'GB', $mb = 'MB', $kb = 'KB')
    {
        // Define byte thresholds.
        $units = [
            'GB' => [1073741824, $gb],
            'MB' => [1048576, $mb],
            'KB' => [1024, $kb],
        ];

        if (!is_numeric($bytes) || $bytes < $units['KB'][0]) {
            return "{$bytes} {$kb}"; // Handle invalid or negative input.
        }

        foreach ($units as $unit => [$threshold, $label]) {
            if ($bytes >= $threshold) {
                $value = round($bytes / $threshold) . $label;

                return $format ? $value : Str::lower($value); 
            }
        }
    }
    
    /**
     * Convert Units to bytes
     *
     * @param string|int|float $size
     * @return int
     */
    public static function sizeToBytes($size = '1mb')
    {
        $size = Str::lower(str_replace(' ', '', (string) $size));

        // Match the size and unit from the input string
        if (preg_match('/^(\d+(\.\d+)?)([kmg]b?)?$/', $size, $matches)) {
            $value = (float) $matches[1];
            $unit = isset($matches[3]) ? $matches[3] : '';

            switch ($unit) {
                case 'kb':
                    return (int) ($value * self::KB);
                case 'mb':
                    return (int) ($value * self::MB);
                case 'gb':
                    return (int) ($value * self::GB);
                default:
                    // If no unit specified, default to megabytes
                    return (int) ($value * self::MB);
            }
        }

        // Invalid input
        $size = (int) $size;

        // check if input is greter than 1kb, else default to 1KB
        return $size > self::KB ? $size : $size * self::KB; 
    }
    
    /**
     * Convert Units to bytes
     *
     * @param string|int|float $size
     * @return int
     */
    public static function unitToByte($size = '1mb')
    {
        return self::sizeToBytes($size);
    }

    /**
     * Get file modification time
     *
     * @param string|null $path
     * - [full path to file is required]
     * 
     * @return int|bool
     */
    public static function fileTime($path = null) 
    {
        return self::getFiletime($path);
    }

    /**
     * Count the numbers between $index and $amount (inclusive) that are divisible by $index.
     *
     * @param int $index The divisor.
     * @param int $amount The range end.
     * @return int 
     * - The count of divisible numbers.
     */
    public static function countDivisibleNumbers($index = 100, $amount = 0)
    {
        if ($index <= 0 || $amount < $index) {
            return 0;
        }

        // Calculate the count of divisible numbers
        return floor($amount / $index);
    }

    /**
     * Calculate the result of raising a base to an exponent.
     *
     * @param float|int $base The base number.
     * @param float|int $exponent The exponent to raise the base to.
     * @return float|int 
     * - The result of the exponentiation.
     */
    public static function calculateExponent($base = 0, $exponent = 0)
    {
        return pow($base, $exponent);
    }

    /**
     * Getting weight calculation
     *
     * @param mixed $length
     * - float|int
     * 
     * @param mixed $width
     * - float|int
     * 
     * @param mixed $height
     * - float|int
     * 
     * @param bool $format
     * - [optional] Default is `true` and round using provided or default decimal of `0.5`
     * if set to false, it return converted value without rounding
     * 
     * @param int|float|string $decimal
     * - [optional] Default is `0.5` value to round to if $format is `true`
     * 
     * @return int
     */
    public static function calculateVolumeWeight($length = 0, $width = 0, $height = 0, ?bool $format = true, $decimal = 0.5) 
    {
        $value = ((float) $length * (float) $width * (float) $height) / 5000;
        return  $format ? 
                self::roundToDecimal($value, $decimal)
                : $value;
    } 

    /**
     * Getting weight calculation
     *
     * @param mixed $length
     * - float|int
     * 
     * @param mixed $width
     * - float|int
     * 
     * @param mixed $height
     * - float|int
     * 
     * @param bool $format
     * - [optional] Default is `true` and round using provided or default decimal of `0.1`
     * if set to false, it return converted value without rounding
     * 
     * @param int|float|string $decimal
     * - [optional] Default is `0.1` value to round to if $format is `true`
     * 
     * @return int
     */
    public static function calculateCubicMeterWeight($length = 0, $width = 0, $height = 0, ?bool $format = true, $decimal = 0.1)
    {
        $value = ((float) $length * (float) $width * (float) $height) / 1000000;
        return  $format ? 
                self::roundToDecimal($value, $decimal)
                : $value;
    } 

    /**
     * Getting actual weight length
     *
     * @param mixed $length
     * - float|int
     * 
     * @param mixed $width
     * - float|int
     * 
     * @param mixed $height
     * - float|int
     * 
     * @param mixed $weight
     * - float|int
     * 
     * @param bool $format
     * - [optional] Default is `true` and round using provided or default decimal of `0.5`
     * if set to false, it return converted value without rounding
     * 
     * @param int|float|string $decimal
     * - [optional] Default is `0.5` value to round to if $format is `true`
     * 
     * @return int
     */
    public static function getBetweenBoxLengthAndWeightInKg($length = 0, $width = 0, $height = 0, $weight = 0, ?bool $format = true, $decimal = 0.5) 
    {
        $weight = (float) $weight; 
        $dimensional_weight = self::calculateVolumeWeight($length, $width, $height, $format, $decimal);
        if($dimensional_weight >= $weight){
            return $dimensional_weight;
        }
        
        return self::roundToDecimal($weight, $decimal);
    }

    /**
     * Getting actual weight length
     *
     * @param mixed $length
     * - float|int
     * 
     * @param mixed $width
     * - float|int
     * 
     * @param mixed $height
     * - float|int
     * 
     * @param mixed $weight
     * - float|int
     * 
     * @param bool $format
     * - [optional] Default is `true` and round using provided or default decimal of `0.1`
     * if set to false, it return converted value without rounding
     * 
     * @param int|float|string $decimal
     * - [optional] Default is `0.1` value to round to if $format is `true`
     * 
     * @return int
     */
    public static function getBetweenBoxLengthAndWeightInCMB($length = 0, $width = 0, $height = 0, $weight = 0, ?bool $format = true, $decimal = 0.1) 
    {
        $weight = (float) $weight; 
        $dimensional_weight = self::calculateCubicMeterWeight($length, $width, $height, $format, $decimal);
        if($dimensional_weight >= $weight){
            return $dimensional_weight;
        }
        
        return self::roundToDecimal($weight, $decimal);
    }

    /**
     * Getting actual weight between Volume/Dimensional weight and Weight in `kg`
     *
     * @param mixed $dimensional_weight
     * - float|int
     * 
     * @param mixed $actual_weight
     * - float|int
     * 
     * @return int
     */
    public static function getBetweenDimensionalWeightAndWeightInKg(mixed $dimensional_weight = 0, mixed $actual_weight = 0) 
    {
        $actual_weight      = (float) $actual_weight;
        $dimensional_weight = (float) $dimensional_weight;
        if($dimensional_weight > $actual_weight){
            return $dimensional_weight;
        }
        return $actual_weight;
    } 

    /**
     * Getting actual weight between Volume/Dimensional weight and Weight in `CBM`
     *
     * @param mixed $dimensional_weight
     * - float|int
     * 
     * @param mixed $actual_weight
     * - float|int
     * 
     * @return int
     */
    public static function getBetweenDimensionalWeightAndWeightInCBM(mixed $dimensional_weight = 0, mixed $actual_weight = 0) 
    {
        return self::getBetweenDimensionalWeightAndWeightInKg($dimensional_weight, $actual_weight);
    } 

    /**
     * Round a value to the nearest specified decimal.
     * 
     * @param float|int|string $value The value to be rounded.
     * @param float|int|string $decimal The decimal value for rounding. Default is 0.5.
     * 
     * @return int|float
     */
    public static function roundToDecimal(mixed $value = 0, mixed $decimal = 0.5)
    {
        $value  = (float) $value;
        $decimal = (float) $decimal;

        if($decimal == self::COUNT){
            $decimal = 0.1;
        } 

        if($value == self::COUNT){
            return $value;
        }

        // Perform the rounding calculation
        $result = ceil($value / $decimal) * $decimal;

        return round($result, 1);
    }

    /**
     * Set Checkbox Value
     *
     * @param  mixed $status
     * @param  bool $reverse_order
     * @return array
     */
    public static function setCheckbox($status = null, ?bool $reverse_order = false)
    {
        $order = ['on' => 1, 'off' => 0];
        if($reverse_order){
            $order = ['on' => 0, 'off' => 1];
        }

        if($status != '0' && $status != '1'){
            return ['status' => null, 'value' => null];
        } 

        if($status == '1'){
            return ['status' => "checked='checked'", 'value' => $order['on']]; 
        } 

        return ['status' => null, 'value' => $order['off']]; 
    }

    /**
     * Convert Kilograms to Grams
     * 
     * @param float|int $weight
     * @return int
     */
    public static function kgToGrams(float|int $weight = 0)
    {
        return $weight == 0 ? 0 : round(($weight * 1000) + 1, 2);
    }

    /**
     * Convert Grams to Kilograms
     *
     * @param float|int $weight
     * @return int
     */
    public static function gramsToKg(float|int $weight = 0)
    {
        return $weight == 0 || null ? 0 : round((($weight - 1) / 1000), 2);
    }

    /**
     * Calculation Percentage between numbers  
     *
     * @param float|int $number
     * @param float|int $newNumber
     * @return int
     */
    public static function calPercentageBetweenNumbers(float|int $number = 0, float|int $newNumber = 0)
    {
        // default 
        $decreaseValue = self::COUNT;

        if($number > $newNumber){
            $decreaseValue = ($newNumber / $number) * 100;
        } else{
            if($number != self::COUNT && $newNumber != self::COUNT){
                $decreaseValue = ($number / $newNumber) * 100;
            } elseif($newNumber != self::COUNT){
                $decreaseValue = ($newNumber * 100) / $newNumber;
            }
        }

        return $decreaseValue;
    }    

    /**
     * Check if array has duplicate value
     *
     * @param array $data
     * @return bool
     * - true|false
     */
    public static function isArrayDuplicate(?array $data = [])
    {
        return Str::arrayDuplicate($data);
    }

    /**
     * Check if all values of array is same
     *
     * @param array $data
     * @return bool
     * - true|false
     */
    public static function isArraySame(?array $data = [])
    {
        return Str::arraySame($data);
    }

    /**
     * For sorting array
     *
     * @param  array $data
     * @param  string $type
     * - [rsort|asort|ksort|arsort|krsort|sort]
     * 
     * @return array
     */
    public static function sortArray(?array &$data = [], ?string $type = 'sort')
    {
        // Validate that $data is an array
        if (!is_array($data)) {
            return [];
        }

        // Perform sorting based on the specified type
        switch ($type) {
            case 'rsort':
                rsort($data); // Sort arrays in descending order
                break;

            case 'asort':
                asort($data); // Sort associative arrays in ascending order, according to the value
                break;

            case 'ksort':
                ksort($data); // Sort associative arrays in ascending order, according to the key
                break;

            case 'arsort':
                arsort($data); // Sort associative arrays in descending order, according to the value
                break;

            case 'krsort':
                krsort($data); // Sort associative arrays in descending order, according to the value
                break;

            default:
                sort($data); // Sort arrays in ascending order
                break;
        }

        return $data;
    }
    
    /**
     * For sorting muti-dimentional array
     *
     * @param  array $data
     * @param  string|null $key
     * @param  string $type
     * - [asc|desc|snum]
     * 
     * @return array
     */
    public static function sortMultipleArray(?array &$data = [], $key = null, ?string $type = 'asc')
    {
        // Check if $data is an array and not empty
        if (!is_array($data) || empty($data)) {
            return [];
        }

        // Check if $key is provided
        // If $key is not provided, return without sorting
        if (is_null($key)) {
            return $data;
        }

        // Extract values of the specified key from each sub-array
        $id = array_column($data, $key);

        // Ensure $id and $data have the same size before sorting
        if (count($id) !== count($data)) {
            return;
        }

        switch ($type) { 
            case 'desc':
                array_multisort($id, SORT_DESC, $data); //sort associative arrays in descending order
                break;
            
            case 'snum': 
                array_multisort($id, SORT_NUMERIC, $data); //sort associative arrays in numeric order 
                break;

            default:
                array_multisort($id, SORT_ASC, $data); //sort arrays in ascending order
                break;
        }

        return $data;
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
    public static function cleanPhoneNumber($phone = null, ?bool $allow = true)
    {
        $phone = Str::trim($phone);
        $phone = str_replace([' ', '-'], '', $phone);
        $phone = str_replace(['(', ')'], '', $phone);
        
        if(Str::contains('+', $phone)){
            $phone = str_replace('+', '', $phone);
        }
        if($allow){
            $phone = "+{$phone}";
        }

        return $phone;
    }

    /**
     * Remove special characters while allowing all languages.
     *
     * @param string|null $string
     * @return string
     * - The cleaned string or null if the input is empty.
     */
    public static function removeSpecialChars($string = null)
    {
        return self::cleanTagsForURL($string);
    }

    /**
     * Clean tags for use in URLs, considering multiple language modules.
     *
     * @param string|null $string The input string to clean.
     * @return string The cleaned string.
     */
    public static function cleanTagsForURL($string = null)
    {
        // Remove unwanted characters from the string
        $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', Str::trim($string));

        return Str::trim($string);
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
    public static function stringHash($string = null, $length = 100, $type = 'sha256', $interation = 100)
    {
        return hash_pbkdf2($type, mt_rand() . $string, self::PBKDF2_SALT, $interation, $length);
    }
    
    /**
     * Shorten String to Given Limit
     * @link https://codeflarelimited.com/blog/php-shorten-string-with-three-dots/
     * 
     * @param  mixed $string
     * @param  mixed $limit
     * @param  mixed $replacer
     * [optional]
     * 
     * @return string
     */
    public static function shortenString($string = null, $limit = 50, $replacer = '...')
    {
        // clean string before begin
        $string = strip_tags(Str::trim($string));
        $string = str_replace("、", '', $string);
        $string = Str::trim(str_replace(PHP_EOL, ' ', $string));
        
        if(mb_strlen($string) > $limit) {
            return mb_strcut($string, 0, (int) $limit) . $replacer; 
        }

        return $string;
    }

    /**
     * Decode entity html strings
     * 
     * @param string|null $string
     * @return string
     */
    public static function html($string = null)
    {
        return html_entity_decode(Str::trim($string), ENT_HTML5, 'UTF-8');
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
        return strip_tags(Str::trim($string));
    }

    /**
     * Filter sanitize string
     *
     * @param string|null $string
     * @return string
    */
    public static function filter_input($string = null)
    {
        return htmlspecialchars(Str::trim($string), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Format number to nearest thousand
     * @param  float|int $number
     * @return void
     */
    public static function formatNumberToNearestThousand(float|int $number = 0)
    {
        if ($number < 1000) {
            return $number; // Return the number as is if it's less than 1000.
        }

        // Define suffixes for each magnitude
        $suffixes = self::$suffixes;

        // Calculate the magnitude
        $magnitude  = floor(log($number, 1000)); // Find the magnitude in powers of 1000
        $suffix     = $suffixes[$magnitude] ?? ''; // Use suffix if it exists, otherwise empty

        // Scale the number down to the appropriate magnitude
        $scaledNumber = $number / pow(1000, $magnitude);

        // Keep one decimal place only if the scaled number has significant decimals
        $formattedNumber = floor($scaledNumber * 10) / 10;

        return "{$formattedNumber}{$suffix}";
    }

    /**
     * File exist and not a directory
     * 
     * @param string|null $path
     * - [full path to file]
     * 
     * @return bool
     */
    public static function exists($path = null)
    {
        return is_file($path);
    }
    
    /**
     * Unlink File from Server
     *
     * @param string $file
     * - [full path to file is required]
     * 
     * @param string|null $restrictedfileName
     * - [optional] file name. <avatar.png>
     * 
     * @return void
     */
    public static function unlink(string $file, $restrictedfileName = null)
    {
        $fullPath = self::stringReplacer($file);

        if(self::exists($fullPath)){
            if(basename($fullPath) != basename((string) $restrictedfileName)){
                @unlink($fullPath);
            }
        }
    }

    /**
     * Convert json data to array|object
     * 
     * @param string $path
     * - [full path to destination]
     * 
     * @param bool $format
     * - [optional] `true` will convert to an array
     *
     * @return array|object|null
     */
    public static function convertJsonData($path, $format = true)
    {
        if(self::exists($path)){
            return json_decode(File::get($path), $format);
        }
    }
    
    /**
     * Save Data to Path as a Json Object
     *
     * @param  string $destination
     * - [full path to destination]
     * 
     * @param  mixed $data
     * @param  bool $type
     * - Saveas[JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE]
     * 
     * @return bool
     */
    public static function saveDataAsJsonObject(string $destination, mixed $data, ?bool $type = true)
    {
        // Choose the JSON encoding format
        $format = $type ? JSON_PRETTY_PRINT : JSON_UNESCAPED_UNICODE;

        // check or convert data to an array
        if(!is_array(!$data)){
            $data = Server::toArray($data);
        }

        // try to read destination
        $fopen = fopen($destination, "w");

        // must be a type of resource
        if(is_resource($fopen)){
            fwrite($fopen, json_encode($data, $format));
            fclose($fopen); 
            return true;
        }

        return false;
    }
    
    /**
     * Save File From Url
     *
     * @param  string $url
     * - [url path] <https://google.com/file.pdf>
     * 
     * @param  string $destination
     * - [full path to destination]
     * 
     * @return string|null
     */
    public static function saveFileFromURL($url, $destination)
    {
        // Check if the destination directory exists, if not, create it
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Try to open the file and save its contents
        $fileContents = File::get($url);
        if ($fileContents === false) {
            // Handle error, e.g., log it
            error_log("Failed to fetch contents from $url");
            return null;
        }

        // Try to write the contents to the destination file
        $writeResult = File::put($destination, $fileContents);
        if ($writeResult === false) {
            // Handle error, e.g., log it
            error_log("Failed to write contents to $destination");
            return null;
        }

        // Extract the file name from the URL and return it
        $fileName = basename($destination);
        
        return $fileName;
    }
    
    /**
     * Read PDF TO Browser
     *
     * @param  string|null $path
     * - [full path to file is required]
     * 
     * @param  bool $delete
     * - [optional] Delete file from server after reading
     * 
     * @return void
     */
    public static function readPDFToBrowser($path = null, $delete = false)
    {
        $fullPath  = self::stringReplacer($path);

        if(self::exists($fullPath)){
            // Clear any existing output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Force browser preview by setting appropriate headers
            @header("Content-Type: application/pdf");
            @header("Content-Disposition: inline; filename=\"" . basename($fullPath) . "\"");
            @header("Content-Transfer-Encoding: binary");
            @header("Accept-Ranges: bytes");
            @header("Cache-Control: no-store, no-cache, must-revalidate");
            @header("Pragma: public");
            @header("Expires: 0");

            // Get file size
            $fileSize = filesize($fullPath);
            if ($fileSize) {
                @header("Content-Length: $fileSize");
            }
            
            // Output the PDF content
            readfile($fullPath);

            if($delete){
                @flush(); // Ensure everything is sent
                @ob_flush(); // Flush PHP output buffer

                // Check if file is writable before attempting to delete
                if (is_writable($fullPath)) {
                    self::unlink($fullPath);
                }
            }
            exit;
        }
    }

    /**
     * Convert image to base64
     *
     * @param string|null $path
     * - [full path to file is required]
     * 
     * @param bool $url
     * - [If path should be treated as direct url]
     * 
     * @return null|string
     */
    public static function imageToBase64($path = null, $url = false) 
    {
        $fullPath  = self::stringReplacer($path);

        if($url){
            // Parse the URL to get the path
            $parse  = parse_url($path, PHP_URL_PATH);
            $type   = pathinfo($parse, PATHINFO_EXTENSION);
            $data   = File::get($path);
        } else{
            if(self::exists($fullPath)){
                $type   = pathinfo($fullPath, PATHINFO_EXTENSION);
                $data   = File::get($fullPath);
            }
        }

        // if true
        if($data){
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    }
    
    /**
     * Masks characters in a string while keeping a specified number of visible characters.
     *
     * @param string|null $str 
     * - The string to be masked.
     * 
     * @param int $length 
     * - The number of visible characters. Default is 4.
     * 
     * @param string $position 
     * - The position to keep visible: 'left', 'center', 'right'. Default is 'right'.
     * 
     * @param string $mask 
     * - The character used for masking. Default is '*'.
     * 
     * @return string 
     * - The masked string.
     */
    public static function mask($str = null, ?int $length = 4, ?string $position = 'right', ?string $mask = '*')
    {
        // Check if the mbstring extension is available
        if (!extension_loaded('mbstring')) {
            return $str;
        }

        // Trim string and position input
        $str = Str::trim($str);
        $position = Str::trim($position);

        // Get the length of the string
        $strLength = mb_strlen($str, 'UTF-8');

        // If length is greater than or equal to the string length, return the original string (nothing to mask)
        if ($length >= $strLength) {
            return $str;
        }

        // Calculate the number of masked characters
        $maskedLength = max(0, $strLength - $length);

        // Check if it's an email by finding the last occurrence of "@"
        $atPosition = mb_strrpos($str, "@", 0, 'UTF-8');
        $isEmail = self::emailValidator($str, false, false);

        // If it's a valid email, mask only the email part (excluding the domain)
        if ($isEmail && $atPosition !== false) {
            $email = mb_substr($str, 0, mb_strpos($str, "@"));
            $tld = mb_substr($str, mb_strpos($str, "@"));

            // Mask only the email part, keeping visibility as per the $length
            $maskedEmail = self::mask($email, $length, $position);
            return "{$maskedEmail}{$tld}";
        }

        // Left masking: Show first 'length' characters, mask the rest
        if ($position === 'left') {
            return mb_substr($str, 0, $length, 'UTF-8') . str_repeat($mask, $maskedLength);
        } 
        // Right masking: Mask everything except the last 'length' characters
        elseif ($position === 'right') {
            return str_repeat($mask, $maskedLength) . mb_substr($str, -$length, null, 'UTF-8');
        } 
        // Center masking: Keep equal parts visible on both sides
        else {
            $halfVisible = (int) floor($length / 2);
            $start = mb_substr($str, 0, $halfVisible, 'UTF-8');
            $end = mb_substr($str, -$halfVisible, null, 'UTF-8');
            return $start . str_repeat($mask, $maskedLength) . $end;
        }
    }
    
    /**
     * Validate an email address.
     *
     * @param string|null $email 
     * - The email address to validate.
     *
     * @param bool $use_internet 
     * - By default is set to false, Which uses the checkdnsrr() and getmxrr()
     * To validate valid domain emails
     *
     * @param bool $server_verify 
     * - Verify Mail Server
     * 
     * @return bool 
     * - Whether the email address is valid (true) or not (false).
     */
    public static function emailValidator($email = null, ?bool $use_internet = false, ?bool $server_verify = false) 
    {
        $filteredEmail = filter_var($email, FILTER_VALIDATE_EMAIL);

        // if internet usage if set to false
        if(!$use_internet){
            return $filteredEmail !== false;
        }
        
        // Email format is invalid
        if (!$filteredEmail) {
            return false; 
        }
        
        // Extract the domain from the email address
        $domain     = explode('@', $email)[1];
        $mxRecords  = [];
        
        // Check DNS records corresponding to a given Internet host name or IP address
        if (checkdnsrr($domain, 'MX')) {
            getmxrr($domain, $mxRecords);
        } else {
            // Domain does not have MX records
            return false; 
        }

        // Check if domain validated in mxRecords is greater than 0 or not
        // returns bool\ true|false
        $mxCount = count($mxRecords);
        
        // if server verify is not true
        if(!$server_verify){
            return $mxCount > 0;
        }

        // verify domain and mx records
        return self::verifyDomain_AndMxRecord($domain, $mxCount);
    }

    /**
     * Decrypt string
     *
     * @param  string|null $jsonString
     * @return mixed
     */
    public static function decryptStr($jsonString = null)
    {
        // get encryption
        $openSSL = self::openSSLEncrypt();

        // Decode the JSON string
        $data = Server::toArray($jsonString);

        if (empty($data)) {
            return;
        }

        // Decode base64-encoded IV and encrypted string
        $iv = base64_decode($data['e']);
        $encryptedString = base64_decode($data['s']);

        // Get encryption settings
        $openSSL = self::openSSLEncrypt();

        // Store the encryption key
        $key = $data['k'];

        // Decryption
        return openssl_decrypt(
            $encryptedString, 
            $openSSL->cipher_algo, 
            $key, 
            $openSSL->options, 
            $iv
        );
    }
    
    /**
     * Encrypt string
     *
     * @param string|null $string
     * @return string
     * - Uses the Open SSL Encryption
     * - BF-CBC
     */
    public static function encryptStr($string = null)
    {
        // get encryption
        $openSSL = self::openSSLEncrypt();

        $string = mb_convert_encoding($string, 'UTF-8');

        // Store the encryption key
        $key = $openSSL->key;

        // Generate a random Initialization Vector (IV) using openssl_random_pseudo_bytes.
        // The length of the IV is determined by the openssl_cipher_iv_length function for AES-256-CBC.
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($openSSL->cipher_algo));

        // Encryption
        $encryptedString = openssl_encrypt(
            $string, 
            $openSSL->cipher_algo, 
            $key, 
            $openSSL->options, 
            $iv
        );
        
        return json_encode([
            'k' => $key,
            'e' => base64_encode($iv),
            's' => base64_encode($encryptedString),
        ]);
    }

    /**
     * Get platform svg icon set
     * 
     * @param string|null $platform
     * - [windows|linux|android|mobile|phone|unknown|mac|macintosh|ios|iphone|c|os x]
     * 
     * @param string|null $os_name
     * - [macos|os x|ios]
     * 
     * @return string
     */
    public static function platformIcon($platform = null, $os_name = null)
    {
        // platform to lower
        $platform = Str::lower(basename($platform));

        // os name to lower
        $os_name = Str::lower($os_name);

        // set path
        $path = self::stringReplacer( __DIR__ ) . DIRECTORY_SEPARATOR;

        // Create items data set
        $dataSet = [
            'windows'   => "{$path}icons/platform/windows.svg",
            'linux'     => "{$path}icons/platform/linux.svg",
            'mac'       => "{$path}icons/platform/mac.svg",
            'iphone'    => "{$path}icons/platform/iphone.svg",
            'android'   => "{$path}icons/platform/android.svg",
            'mobile'    => "{$path}icons/platform/mobile.svg",
            'phone'     => "{$path}icons/platform/phone.svg",
            'unknown'   => "{$path}icons/platform/unknown.svg",
        ];

        // check for extra validations
        if(in_array($platform, ['macintosh', 'c', 'os x']) || in_array($os_name, ['macos', 'os x'])){
            $platform = 'mac';
        } elseif(in_array($platform, ['iphone', 'ios']) || in_array($os_name, ['ios'])){
            $platform = 'iphone';
        }

        return self::stringReplacer($dataSet[$platform] ?? $dataSet['unknown']);
    }

    /**
     * Get path to payment svg icon
     * 
     * @param string|null $payment
     * - [add-money|alipay|bank|cc|credit-card|discover|faster-pay|groupbuy|maestro|mastercard]
     * - [pay|payme|payment-card|payment-wallet|paypal|stripe-circle|tripe-sqaure|stripe|visa]
     * 
     * @return mixed
     * - string|null
     */
    public static function paymentIcon($payment = null)
    {
        // set path
        $path = self::stringReplacer( __DIR__ ) . DIRECTORY_SEPARATOR;

        // Create items data set
        $dataSet = [
            'add-money'     => "{$path}icons/payment/add-money.svg",
            'alipay'        => "{$path}icons/payment/alipay.svg",
            'bank'          => "{$path}icons/payment/bank.svg",
            'cc'            => "{$path}icons/payment/cc.svg",
            'credit-card'   => "{$path}icons/payment/credit-card.svg",
            'discover'      => "{$path}icons/payment/discover.svg",
            'faster-pay'    => "{$path}icons/payment/faster-pay.svg",
            'groupbuy'      => "{$path}icons/payment/groupbuy.svg",
            'maestro'       => "{$path}icons/payment/maestro.svg",
            'mastercard'    => "{$path}icons/payment/mastercard.svg",
            'pay'           => "{$path}icons/payment/pay.svg",
            'payme'         => "{$path}icons/payment/payme.svg",
            'payment-card'  => "{$path}icons/payment/payment-card.svg",
            'payment-wallet'=> "{$path}icons/payment/payment-wallet.svg",
            'paypal'        => "{$path}icons/payment/paypal.svg",
            'stripe-circle' => "{$path}icons/payment/stripe-circle.svg",
            'stripe-sqaure' => "{$path}icons/payment/stripe-sqaure.svg",
            'stripe'        => "{$path}icons/payment/stripe.svg",
            'visa'          => "{$path}icons/payment/cc.svg",
        ];  

        return self::stringReplacer($dataSet[$payment] ?? $dataSet['cc']);
    }

    /**
     * Replace and recreate path to
     * - (/) slash
     * 
     * @param string|null $path
     * 
     * @return string
     */
    public static function stringReplacer($path = null)
    {
        return str_replace(
            ['\\', '/'], 
            DIRECTORY_SEPARATOR, 
            Str::trim($path)
        );
    }

    /**
     * Get file modification time
     *
     * @param string|null $path
     * - [full path to file is required]
     * 
     * @return int|bool 
     */
    private static function getFiletime($path = null) 
    {
        $fullPath = self::stringReplacer($path);

        if(self::exists($fullPath)) {
            return filemtime($fullPath);
        }

        return false;
    }
    
}
