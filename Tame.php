<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Traits\TameTrait;

/**
 * @see \Tamedevelopers\Support\Str
 * @see \Tamedevelopers\Support\Server
 * @see \Tamedevelopers\Support\Time
 */
class Tame {

    use TameTrait;
    
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
    static public function echoJson(int $response = 0, $message = null)
    {
        echo json_encode(['response' => $response, 'message' => $message]);
    }

    /**
     * Check IF Internet is Available
     *
     * @return bool
     */
    static public function isInternetAvailable()
    {
        // Use cURL to make a request
        $request = curl_init('https://www.google.com');
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_TIMEOUT, 5);
        curl_exec($request);
        
        // Check the HTTP response code
        $httpCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
        curl_close($request);

        // HTTP code 200 means the request was successful
        return $httpCode === 200;
    }

    /**
     * Check if Class Exists
     *
     * @param  string $class
     * @param  Closure|null $closure
     * @return void
     */
    static public function class_exists($class, $closure = null)
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
     * @return bool True if at least one class exists, false otherwise
     */
    static public function checkAnyClassExists(...$classNames)
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
    static public function versionCompare($version)
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
    static public function HeadersSent()
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
     * - [base path will be automatically added]
     * 
     * @return void
     */
    static public function include($path)
    {
        $fullPath = self::getBasePath($path);

        if(self::exists($fullPath)){
            include $fullPath;
        }
    }

    /**
     * include once if file exist
     * 
     * @param string $path
     * - [base path will be automatically added]
     * 
     * @return void
     */
    static public function includeOnce($path)
    {
        $fullPath = self::getBasePath($path);

        if(self::exists($fullPath)){
            include_once $fullPath;
        }
    }

    /**
     * require if file exist
     * 
     * @param string $path
     * - [base path will be automatically added]
     * 
     * @return void
     */
    static public function require($path)
    {
        $fullPath = self::getBasePath($path);

        if(self::exists($fullPath)){
            require $fullPath;
        }
    }

    /**
     * require_once if file exist
     * 
     * @param string $path
     * - [base path will be automatically added]
     * 
     * @return void
     */
    static public function requireOnce($path)
    {
        $fullPath = self::getBasePath($path);

        if(self::exists($fullPath)){
            require_once $fullPath;
        }
    }
    
    /**
     * Convert Bytes to Units 
     *
     * @param  float|int $bytes
     * @param  bool $format
     * @param  string|null $gb
     * @param  string|null $mb
     * @param  string|null $kb
     * 
     * @return string
     */
    static public function byteToUnit($bytes = 0, $format = true, $gb = 'GB', $mb = 'MB', $kb = 'KB')
    {
        $bytes = (int) $bytes;
        if ($bytes >= 1073741824){
            $bytes = round(($bytes / 1073741824)) . $gb;
        } elseif ($bytes >= 1048576){
            $bytes = round(($bytes / 1048576)) . $mb;
        } elseif ($bytes >= 1024){
            $bytes = round(($bytes / 1024)) . $kb;
        }

        return $format ? $bytes : Str::lower($bytes);
    }
    
    /**
     * Convert Megabytes to bytes
     *
     * @param string|int|float $size
     * @return int
     */
    static public function sizeToBytes($size = '1mb')
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
     * Get file modification time
     *
     * @param string|null $path
     * - [base path will be automatically added]
     * 
     * @return int|bool 
     */
    static public function getFiletime($path = null) 
    {
        $fullPath = self::getBasePath($path);

        if(self::exists($fullPath)) {
            return filemtime($fullPath);
        }

        return false;
    }

    /**
     * Get file modification time
     *
     * @param string|null $path
     * - [base path will be automatically added]
     * 
     * @return int|bool
     */
    static public function fileTime($path = null) 
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
    static public function countDivisibleNumbers($index = 100, $amount = 0)
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
    static public function calculateExponent($base = 0, $exponent = 0)
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
     * 
     * @return int
     */
    static public function calculateVolumeWeight(mixed $length = 0, mixed $width = 0, mixed $height = 0, ?bool $format = true) 
    {
        $dimension = ((float) $length * (float) $width * (float) $height) / 5000;
        return  $format ? 
                self::roundToDecimal($dimension) 
                : $dimension;
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
     * 
     * @return int
     */
    static public function getBetweenBoxLengthAndWeightInKg($length = 0, $width = 0, $height = 0, $weight = 0, ?bool $format   = true) 
    {
        $weight = (float) $weight; 
        $dimensional_weight = self::calculateVolumeWeight($length, $width, $height, $format);
        if($dimensional_weight > $weight){
            return $dimensional_weight;
        }
        return $format ? self::roundToDecimal($weight) : $weight;
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
    static public function getBetweenDimensionalWeightAndWeightInKg(mixed $dimensional_weight = 0, mixed $actual_weight = 0) 
    {
        $actual_weight      = (float) $actual_weight;
        $dimensional_weight = (float) $dimensional_weight;
        if($dimensional_weight > $actual_weight){
            return $dimensional_weight;
        }
        return $actual_weight;
    } 

    /**
     * Round to decimal point
     * 
     * @param mixed $value
     * - float|int
     * 
     * @param mixed $decimal
     * - float|int
     * 
     * @return int
     */
    static public function roundToDecimal(mixed $value = 0, mixed $decimal = 0.5)
    {
        if($decimal == self::COUNT) $decimal = 0.1;
        if($value == self::COUNT) return $value; 

        return ceil($value / $decimal) * $decimal;
        //return round($value / $decimal, 1, PHP_ROUND_HALF_UP) * $decimal;
    }

    /**
     * Set Checkbox Value
     *
     * @param  mixed $status
     * @param  bool $reverse_order
     * @return array
     */
    static public function setCheckbox($status = null, ?bool $reverse_order = false)
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
    static public function kgToGrams(float|int $weight = 0)
    {
        return $weight == 0 ? 0 : round(($weight * 1000) + 1, 2);
    }

    /**
     * Convert Grams to Kilograms
     *
     * @param float|int $weight
     * @return int
     */
    static public function gramsToKg(float|int $weight = 0)
    {
        return $weight == 0 || null ? 0 : round((($weight - 1) / 1000), 2);
    }

    /**
     * Calculation Percentage between numbers  
     *
     * @param float|int $total
     * @param float|int $newNumber
     * @return int
     */
    static public function calPercentageBetweenNumbers(float|int $total = 0, float|int $newNumber = 0)
    {
        // default 
        $decreaseValue = self::COUNT;

        if($total > $newNumber){
            $decreaseValue = ($newNumber / $total) * 100;
        } else{
            if($total != self::COUNT && $newNumber != self::COUNT){
                $decreaseValue = ($total / $newNumber) * 100;
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
    static public function isArrayDuplicate(?array $data = [])
    {
        if(count($data) > count(array_unique($data))){
            return true;
        }
        
        return false;
    }

    /**
     * Check if all values of array is same
     *
     * @param array $data
     * @return bool
     * - true|false
     */
    static public function isArraySame(?array $data = [])
    {
        if(count($data) > count(array_unique($data))){
            return true;
        }

        return false;
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
    static public function sortArray(?array $data = [], ?string $type = 'sort')
    {
        switch ($type) {
            case 'rsort':
                rsort($data); return $data; //sort arrays in descending order
                break;
            
            case 'asort':
                asort($data);
                return asort($data); //sort associative arrays in ascending order, according to the value
                break;
            
            case 'ksort':
                ksort($data);  
                return $data; //sort associative arrays in ascending order, according to the key
                break;
            
            case 'arsort':
                arsort($data); 
                return $data; //sort associative arrays in descending order, according to the value
                break;
            
            case 'krsort':
                krsort($data); 
                return $data; //sort associative arrays in descending order, according to the value
                break;
    
            default:
                sort($data); 
                return $data; //sort arrays in descending order
                break;
        }
    }
    
    /**
     * For sorting muti-dimentional array
     *
     * @param  string|null $key
     * @param  array $data
     * @param  string $type
     * - [asc|desc|snum]
     * 
     * @return void
     */
    static public function sortMultipleArray($key = null, ?array &$data = [], ?string $type = 'asc')
    {
        $id = array_column($data, $key);
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
    static public function cleanPhoneNumber($phone = null, ?bool $allow = true)
    {
        $phone = trim((string) $phone);
        $phone = str_replace([' ', '-'], '', $phone);
        $plus = '';
        
        // if plus sign is found in string
        if(Str::contains('+', $phone) && $allow){
            $plus = "+";
        }

        // clean any tags
        $phone = self::removeSpecialChars($phone);

        return "{$plus}{$phone}";
    }

    /**
     * Remove special characters while allowing all languages.
     *
     * @param string|null $string
     * @return string
     * - The cleaned string or null if the input is empty.
     */
    static public function removeSpecialChars($string = null)
    {
        return self::cleanTagsForURL($string);
    }

    /**
     * Clean tags for use in URLs, considering multiple language modules.
     *
     * @param string|null $string The input string to clean.
     * @return string The cleaned string.
     */
    static public function cleanTagsForURL($string = null)
    {
        // Remove unwanted characters from the string
        $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', (string) $string);

        return trim($string);
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
    static public function stringHash($string = null, $length = 100, $type = 'sha256', $interation = 100)
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
    static public function shortenString($string = null, $limit = 50, $replacer = '...')
    {
        // clean string before begin
        $string = strip_tags($string);
        $string = str_replace("、", '', $string);
        $string = trim(str_replace(PHP_EOL, ' ', $string));
        
        if(strlen($string) > $limit) {
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
    static public function html($string = null)
    {
        return html_entity_decode((string) $string, ENT_HTML5, 'UTF-8');
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
        return strip_tags((string) $string);
    }

    /**
     * Filter sanitize string
     *
     * @param string|null $string
     * @return string
    */
    static public function filter_input($string = null)
    {
        return htmlspecialchars((string) $string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Format number to nearest thousand
     * @link https://code.recuweb.com/2018/php-format-numbers-to-nearest-thousands/
     *
     * @param  float|int $number
     * @return void
     */
    static public function formatNumberToNearestThousand(float|int $number = 0)
    {
        if( $number >= 1000 ) {
            $x  = round($number);
            $x_number_format = number_format($x);
            $x_array = explode(',', $x_number_format);

            //[t(trillion) - p(quadrillion) - e(quintillion) - z(sextillion) - y(septillion)]
            $x_parts        = array('k', 'm', 'b', 't', 'p', 'e', 'z', 'y');
            $x_count_parts  = count($x_array) - 1;
            $x_display      = $x;
            $x_display      = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
            
            // if amount in array
            if(in_array($x_count_parts - 1, array_keys($x_parts))){
                $x_display .= $x_parts[$x_count_parts - 1];
            }
            return $x_display;
        }

        return $number;
    }

    /**
     * File exist and not a directory
     * 
     * @param string|null $path
     * - [full path to file]
     * 
     * @return bool
     */
    static public function exists($path = null)
    {
        return !is_dir($path) && file_exists($path);
    }
    
    /**
     * Unlink File from Server
     *
     * @param string $pathToFile
     * - [base path will be automatically added]
     * 
     * @param string|null $fileName
     * - [optional] file name. <avatar.png>
     * 
     * @return void
     */
    static public function unlink(string $pathToFile, $fileName = null)
    {
        $fullPath = self::getBasePath($pathToFile);

        if(self::exists($fullPath)){
            if(basename($fullPath) != basename((string) $fileName)){
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
    static public function convertJsonData($path, $format = true)
    {
        if(self::exists($path)){
            return json_decode(file_get_contents($path), $format);
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
    static public function saveDataAsJsonObject(string $destination, mixed $data, ?bool $type = true)
    {
        $format = JSON_PRETTY_PRINT;
        if(!$type){
            $format = JSON_UNESCAPED_UNICODE;
        }

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
    static public function saveFileFromURL($url, $destination)
    {
        // Check if the destination directory exists, if not, create it
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Try to open the file and save its contents
        $fileContents = @file_get_contents($url);
        if ($fileContents === false) {
            // Handle error, e.g., log it
            error_log("Failed to fetch contents from $url");
            return null;
        }

        // Try to write the contents to the destination file
        $writeResult = @file_put_contents($destination, $fileContents);
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
     * - [base path will be automatically added]
     * 
     * @return void
     */
    static public function readPDFToBrowser($path = null)
    {
        $fullPath  = self::getBasePath($path);

        if(self::exists($fullPath)){
            @header("Content-type: application/pdf");
            @header("Content-Length: " . filesize($fullPath));
            readfile($fullPath);
        }
    }

    /**
     * Convert image to base64
     *
     * @param  string|null $path
     * - [base path will be automatically added]
     * 
     * @return null|string
     */
    static public function imageToBase64($path = null) 
    {
        $fullPath  = self::getBasePath($path);

        if(self::exists($fullPath)){
            $type = pathinfo($fullPath, PATHINFO_EXTENSION);
            $data = file_get_contents($fullPath);

            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
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
        // Check if the mbstring extension is available
        if (!extension_loaded('mbstring')) {
            return $str;
        }

        // Get the length of the string
        $strLength = mb_strlen($str, 'UTF-8');

        // Check if it's an email by finding the last occurrence of "@"
        $atPosition = mb_strrpos($str, "@", 0, 'UTF-8');

        // Check if it's an actual email
        $isEmail = self::emailValidator($str, false, false);

        // Check if the length parameter is greater than the actual length of the string to avoid errors
        if ($isEmail && $atPosition !== false) {
            if(empty($position)){
                $position = 'left';
            }
            $length = $length >= mb_strlen(mb_substr($str, 0, $atPosition, 'UTF-8'), 'UTF-8') ? 4 : $length;
        } else {
            $length = $length >= $strLength ? 4 : $length;
        }

        // position
        if(empty($position)){
            $position = 'right';
        }

        // Calculate string length
        $strMinusLength = $strLength - $length;
        if ($strMinusLength < 0) {
            $strMinusLength = abs(1);
        }

        // For left position
        if ($position == 'left') {
            $length = (int) $length;
            if ($isEmail && $atPosition !== false) {
                // Mask the left part of the string, including the "@" symbol
                return mb_substr(mb_substr($str, 0, $atPosition, 'UTF-8'), 0, $length, 'UTF-8') . str_repeat($mask, $atPosition - $length) . mb_substr($str, $atPosition, null, 'UTF-8');
            } else {
                // Mask the entire string if it's not an email
                return mb_substr($str, 0, $length, 'UTF-8') . str_repeat($mask, $strMinusLength);
            }
        } elseif ($position == 'middle' || $position == 'center') {
            // Mask the middle part of the string
            $length = (int) round($length / 2);

            return mb_substr($str, 0, $length, 'UTF-8') . str_repeat($mask, $strMinusLength) . mb_substr($str, -$length, null, 'UTF-8');
        } else {
            // Mask the right part of the string
            $length = (int) $length;
            return str_repeat($mask, $strMinusLength) . mb_substr($str, -$length, null, 'UTF-8');
        }
    }

    /**
     * Validate an email address.
     *
     * @param string|null $email 
     * - The email address to validate.
     *
     * @param bool $use_internet 
     * - By default is set to true, Which uses the checkdnsrr() and getmxrr()
     * To validate valid domain emails
     *
     * @param bool $server_verify 
     * - Verify Mail Server
     * 
     * @return bool 
     * - Whether the email address is valid (true) or not (false).
     */
    static public function emailValidator($email = null, ?bool $use_internet = true, ?bool $server_verify = false) 
    {
        $filteredEmail = filter_var($email, FILTER_VALIDATE_EMAIL);

        // if internet usage is set to false
        if(!$use_internet){
            return $filteredEmail !== false;
        }
        
        // Email format is invalid
        if (!$filteredEmail) {
            return false; 
        }
        
        // Extract the domain from the email address
        $domain     = Str::contains('@', $email) ? explode('@', $email)[1] : '';
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
     * @param  string $encryption
     * @param  string $key
     * @param  string $passkey
     * @return mixed
     */
    static public function decryptStr(string $encryption, string $key, string $passkey)
    {
        // get encryption
        $openSSL = self::openSSLEncrypt();

        // Store the cipher method
        $ciphering = $openSSL->cipher_algo;

        // Use OpenSSl Encryption method
        $options = $openSSL->options;

        // Store the encryption key
        $key = $key;
        
        // Non-NULL Initialization Vector for encryption
        $passphrase = $passkey;
        
        // Use openssl_decrypt() function to decrypt the data
        return openssl_decrypt($encryption, $ciphering, $key, $options, $passphrase);
    }
    
    /**
     * Encrypt string
     *
     * @param string|null $string
     * @return string
     * - Uses the Open SSL Encryption
     * - BF-CBC
     */
    static public function encryptStr($string = null)
    {
        // get encryption
        $openSSL = self::openSSLEncrypt();

        // Store the cipher method
        $ciphering = $openSSL->cipher_algo;

        // Store the encryption key
        $key = $openSSL->key;
        
        // Use OpenSSl Encryption method
        $options = $openSSL->options;
        
        // Non-NULL Initialization Vector for encryption
        $passphrase = $openSSL->passphrase;
        
        // Use openssl_encrypt() function to encrypt the data
        $encrypt = openssl_encrypt(
            $string, 
            $ciphering, 
            $key, 
            $options, 
            $passphrase
        );
        
        return json_encode([
            'key'           => $key,
            'passphrase'    => $passphrase,
            'encryption'    => $encrypt
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
    static public function platformIcon($platform = null, $os_name = null)
    {
        // platform to lower
        $platform = Str::lower(basename($platform));

        // os name to lower
        $os_name = Str::lower($os_name);

        // set path
        $path = self::stringReplacer( __DIR__ );

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

        return $dataSet[$platform] ?? $dataSet['unknown'];
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
    static public function paymentIcon($payment = null)
    {
        // set path
        $path = self::stringReplacer( __DIR__ );

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

        return $dataSet[$payment] ?? $dataSet['cc'];
    }

    /**
     * Replace and recreate path to
     * - (/) slash
     * 
     * @param string|null $path
     * 
     * @return string
     */
    static public function stringReplacer($path = null)
    {
        return str_replace(
            ['\\', '/'], 
            DIRECTORY_SEPARATOR, 
            trim((string) $path)
        );
    }
    
}
