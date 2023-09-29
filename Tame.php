<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use ZipArchive;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Time;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Traits\TameTrait;


/**
 * @see \Tamedevelopers\Support\Str
 * @see \Tamedevelopers\Support\Server
 * @see \Tamedevelopers\Support\Time
 */
class Tame{

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
    static public function echoJson(int $response = 0, string $message = 'null')
    {
        echo json_encode(['response' => $response, 'message' => $message]);
    }

    /**
     * Check if Class Exists
     *
     * @param  string $class
     * @param  callable|null $function
     * @return mixed
     */
    static public function class_exists($class, callable $function = null)
    {
        if(class_exists($class)){
            if(is_callable($function)){
                $function();
            }
        }
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
     * Instance of Time
     * @param string|null $time
     * @param string|null $timezone
     * 
     * @return \Tamedevelopers\Support\Time
     */
    static public function time(?string $time = 'now', ?string $timezone = 'UTC')
    {
        return new Time($time, $timezone);
    }

    /**
     * Create timestamp
     * 
     * @param mixed $date
     * - string|int|float
     * 
     * @param string $format
     * - Your defined format type i.e: Y-m-d H:i:s a
     * - Converted TimeStamp
     * 
     * @return string
     */
    static public function timestamp($date, ?string $format = "Y-m-d H:i:s")
    {
        if(is_string($date)){
            $date = strtotime($date);   
        }
        return date($format, $date);
    }

    /**
     * Create Javascript timer
     * 
     * @param mixed $time
     * - Converted TimeStamp
     * 
     * @return string
     */
    static public function javascriptTimer($time)
    {
        return self::timestamp($time, 'M j, Y H:i:s');
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
     * @param  mixed $format
     * [optional] Default is true --- UPPER CASE
     * 
     * @return string
     */
    static public function byteToUnit(float|int $bytes = 0, $format = true)
    {
        if ($bytes >= 1073741824){
            $bytes = round(($bytes / 1073741824)) . 'GB';
        } elseif ($bytes >= 1048576){
            $bytes = round(($bytes / 1048576)) . 'MB';
        } elseif ($bytes >= 1024){
            $bytes = round(($bytes / 1024)) . 'KB';
        }

        return $format ? $bytes : strtolower((string) $bytes);
    }
    
    /**
     * Convert Megabytes to bytes
     *
     * @param string|int|float $size
     * @return int
     */
    static public function sizeToBytes($size = '1mb')
    {
        $size = strtolower((string) $size);

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

        return false; // Invalid input
    }

    /**
     * Unzip a file or folder.
     *
     * @param  string $sourcePath
     * @param  string $destination
     * @return bool
     */
    static public function unzip($sourcePath, $destination)
    {
        // If it's a zip file, call the unzipFile function
        if (pathinfo($sourcePath, PATHINFO_EXTENSION) === 'zip') {
            return self::unzipFile($sourcePath, $destination);
        }

        // If it's a folder, call the unzipFolder function
        if (is_dir($sourcePath)) {
            return self::unzipFolder($sourcePath, $destination);
        }

        return false; // Unsupported file type
    }

    /**
     * Zip a file or folder.
     *
     * @param string $sourcePath The path to the file or folder to zip.
     * @param string $destinationZip The path for the resulting zip file.
     * @return bool True if the zip operation was successful, false otherwise.
     */
    static public function zip($sourcePath, $destinationZip)
    {
        // If it's a folder, call the zipFolder function
        if (is_dir($sourcePath)) {
            return self::zipFolder($sourcePath, $destinationZip);
        }

        // If it's a file, create a zip containing just that file
        $zip = new ZipArchive();

        if ($zip->open($destinationZip, ZipArchive::CREATE) !== true) {
            return false;
        }

        // Add the file to the zip
        $zip->addFile($sourcePath, basename($sourcePath));

        $zip->close();

        return file_exists($destinationZip);
    }

    

    /**
     * Get file modification time
     *
     * @param string $path
     * 
     * @return mixed 
     * - int|bool
     */
    static public function getFiletime(?string $path = null) 
    {
        $fullPath = self::getBasePath($path);

        if(self::exists($fullPath)) {
            return filemtime($fullPath);
        }

        return false;
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
     * @param mixed @length
     * - float|int
     * 
     * @param mixed @width
     * - float|int
     * 
     * @param mixed @height
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
     * @param mixed @length
     * - float|int
     * 
     * @param mixed @width
     * - float|int
     * 
     * @param mixed @height
     * - float|int
     * 
     * @param mixed @weight
     * - float|int
     * 
     * @param bool $format
     * 
     * @return int
     */
    static public function getBetweenBoxLengthAndWeightInKg(
        mixed $length   = 0, 
        mixed $width    = 0, 
        mixed $height   = 0, 
        mixed $weight   = 0, 
        ?bool $format   = true
    ) 
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
     * @param mixed @dimensional_weight
     * - float|int
     * 
     * @param mixed @actual_weight
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
     * @return bool
     * - true|false
     */
    static public function isArrayDuplicate(?array $array = [])
    {
        if(count($array) > count(array_unique($array))){
            return true;
        }
        return false;
    }

    /**
     * Check if all values of array is same
     *
     * @return bool
     * - true|false
     */
    static public function isArraySame(?array $array = [])
    {
        if(count($array) > count(array_unique($array))){
            return true;
        }
        return false;
    }

    /**
     * For sorting array
     *
     * @param  array $arry
     * @param  string $type
     * - [rsort|asort|ksort|arsort|krsort|sort]
     * 
     * @return array
     */
    static public function sortArray(?array $arry = [], ?string $type = 'sort')
    {
        switch ($type) {
            case 'rsort':
                rsort($arry); return $arry; //sort arrays in descending order
                break;
            
            case 'asort':
                asort($arry);
                return asort($arry); //sort associative arrays in ascending order, according to the value
                break;
            
            case 'ksort':
                ksort($arry);  
                return $arry; //sort associative arrays in ascending order, according to the key
                break;
            
            case 'arsort':
                arsort($arry); 
                return $arry; //sort associative arrays in descending order, according to the value
                break;
            
            case 'krsort':
                krsort($arry); 
                return $arry; //sort associative arrays in descending order, according to the value
                break;
    
            default:
                sort($arry); 
                return $arry; //sort arrays in descending order
                break;
        }
    }
    
    /**
     * For sorting muti-dimentional array
     *
     * @param  string $key
     * @param  array $arry
     * @param  string $type
     * - [asc|desc|snum]
     * 
     * @return void
     */
    static public function sortMultipleArray(?string $key = null, ?array &$arry = [], ?string $type = 'asc')
    {
        $id = array_column($arry, $key);
        switch ($type) { 
            case 'desc':
                array_multisort($id, SORT_DESC, $arry); //sort associative arrays in descending order
                break;
            
            case 'snum': 
                array_multisort($id, SORT_NUMERIC, $arry); //sort associative arrays in numeric order 
                break;

            default:
                array_multisort($id, SORT_ASC, $arry); //sort arrays in ascending order
                break;
        }
    }

    /**
     * Clean phone string
     *
     * @param string $phone
     * 
     * @param bool $allow --- Default is true
     * [optional] to allow `+` before number
     * 
     * @return string
     */
    static public function cleanPhoneNumber(?string $phone = null, ?bool $allow = true)
    {
        $phone = str_replace(' ', '', str_replace('-', '', $phone));
        $phone = str_replace('(', '', str_replace(')', '', $phone));
        $phone = str_replace(' ', '', $phone);
        if(Str::contains($phone, '+')){
            $phone = str_replace('+', '', $phone);
            if($allow){
                $phone = "+{$phone}";
            }
        }

        return $phone;
    }

    /**
     * Remove special characters while allowing all languages.
     *
     * @param string $string
     * @return string|null 
     * - The cleaned string or null if the input is empty.
     */
    static public function removeSpecialChars(?string $string = null)
    {
        if (empty($string)) {
            return null;
        }

        return self::cleanTagsForURL($string);
    }

    /**
     * Clean tags for use in URLs, considering multiple language modules.
     *
     * @param string|null $string The input string to clean.
     * @return string The cleaned string.
     */
    static public function cleanTagsForURL(?string $string = null)
    {
        // Remove unwanted characters from the string
        $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', (string) $string);

        return trim($string);
    }

    /**
     * Hash String
     *
     * @param  string $string
     * @param  int $length
     * @param  string $type
     * @param  int $interation
     * @return void
     */
    static public function stringHash(?string $string = null, $length = 100, $type = 'sha256', $interation = 100)
    {
        return hash_pbkdf2($type, mt_rand() . $string, self::PBKDF2_SALT, $interation, $length);
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
    static public function shortenString($string = null, $limit = 50, $replacer = '...')
    {
        // clean string before begin
        $string = strip_tags($string);
        $string = str_replace("、", '', $string);
        $string = trim(str_replace(PHP_EOL, ' ', $string));
        // $string = trim(preg_replace('/\s+/', ' ', $string));
        
        // https://codeflarelimited.com/blog/php-shorten-string-with-three-dots/
        if(strlen($string) > $limit) {
            return mb_strcut($string, 0, $limit) . $replacer; 
        }

        return $string;
    }

    /**
     * Decode entity html strings
     * 
     * @param string $string
     * @return string
     */
    static public function html($string = null)
    {
        return html_entity_decode($string, ENT_HTML5, 'UTF-8');
    }

    /**
     * Filter sanitize string
     *
     * @param string $string
     * @return string
    */
    static public function filter_input(?string $string = null)
    {
        return htmlspecialchars((string) $string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Format number to nearest thousand
     *
     * @param  float|int $number
     * @return void
     */
    static public function formatNumberToNearestThousand(float|int $number = 0)
    {
        // https://code.recuweb.com/2018/php-format-numbers-to-nearest-thousands/
        if( $number >= 1000 ) {
            $x = round($number);
            $x_number_format = number_format($x);
            $x_array = explode(',', $x_number_format);
            //[t(trillion) - p(quadrillion) - e(quintillion) - z(sextillion) - y(septillion)]
            $x_parts = array('k', 'm', 'b', 't', 'p', 'e', 'z', 'y');
            $x_count_parts = count($x_array) - 1;
            $x_display = $x;
            $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
            
            // if amount in array
            if(in_array($x_count_parts - 1, array_keys($x_parts))){
                $x_display .= $x_parts[$x_count_parts - 1];
            }
            return $x_display;
        }

        return $number;
    }

    /**
     * Convert json data to array|object
     * 
     * @param string $path
     * 
     * @param bool $format
     * - [optional] Default is true and this converts to an array
     * false will convert to and object 
     *
     * @return array
     */
    static public function convertJsonData(?string $path = null, $format = true)
    {
        if(self::exists($path)){
            return json_decode(file_get_contents($path), $format);
        }
    }
    
    /**
     * Unlink File from Server
     *
     * @param string $fileToUnlink
     * @param string $checkFile
     * [optional] File to check against before unlinking
     * 
     * @return void
     */
    static public function unlinkFile(string $fileToUnlink, ?string $checkFile = null)
    {
        $fileToUnlink = self::getBasePath($fileToUnlink);
        $checkFile = self::getBasePath($checkFile);

        if(self::exists($fileToUnlink)){
            if(basename($fileToUnlink) != basename($checkFile)){
                @unlink($fileToUnlink);
            }
        }
    }
    
    /**
     * Save Data to Path
     *
     * @param  mixed $destination
     * @param  mixed $data
     * @param  bool $type
     * 
     * @return void
     */
    static public function saveDataToPath(?string $destination = null, array $data = [], bool $type = true)
    {
        $format = JSON_PRETTY_PRINT;
        if(!$type){
            $format = JSON_UNESCAPED_UNICODE;
        }

        // JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        $fopen = fopen($destination, "w");
        @fwrite($fopen, json_encode($data, $format));
        @fclose(@$fopen); 
    }
    
    /**
     * Save File From Url
     *
     * @param  mixed $urlFile
     * @param  mixed $destination
     * @return string
     */
    static public function saveFileFromURL(?string $urlFile = null, ?string $destination = null)
    {
        if(!empty($urlFile)){
            @file_put_contents($destination, fopen($urlFile, 'r'));
        }

        return $destination;
    }
    
    /**
     * Read PDF TO Browser
     *
     * @param  mixed $path
     * [localhost] PDF path
     * 
     * @return void
     */
    static public function readPDFToBrowser(?string $path = null)
    {
        if(!empty($path) && self::exists($path)){
            // Header content type
            header("Content-type: application/pdf");
            
            header("Content-Length: " . filesize($path));
            
            // Send the file to the browser.
            readfile($path);
        }
    }

    /**
     * Convert image to base64
     *
     * @param  mixed $path_to_image
     * @return null|string
     */
    static public function imageToBase64(?string $path = null) 
    {
        if(!empty($path) && self::exists($path)){
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);

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
     * @param string $position 
     * - The position to apply the mask: 'left', 'middle' or 'center', 'right'. Default is 'right'.
     * 
     * @param string $mask 
     * - The character used for masking. Default is '*'.
     * 
     * @return string 
     * - The masked string.
     */
    static public function mask(?string $str = null, ?int $length = 4, ?string $position = 'right', ?string $mask = '*')
    {
        // Get the length of the string
        $strLength = strlen($str); 

        // Check if it's an email by finding the last occurrence of "@"
        $atPosition = strrpos($str, "@"); 

        // check if it's an actual email
        $isEmail = self::emailValidator($str, false, false);

        // Check if the length parameter is greater than the actual length of the string to avoid errors
        if ($isEmail && $atPosition) {
            $length = $length >= strlen(substr($str, 0, $atPosition)) ? 4 : $length;
        } else {
            $length = $length >= $strLength ? 4 : $length;
        }

        // string length
        $strminusLegnth = $strLength - $length;
        if($strminusLegnth < 0){
            $strminusLegnth = abs(1);
        }
        
        // for left position
        if ($position == 'left') {
            if ($isEmail && $atPosition) {
                // Mask the left part of the string, including the "@" symbol
                return substr(substr($str, 0, $atPosition), 0, $length) . str_repeat($mask, $atPosition - $length) . substr($str, $atPosition);
            } else {
                // Mask the entire string if it's not an email
                return substr($str, 0, $length) . str_repeat($mask, $strminusLegnth);
            }
        } elseif ($position == 'middle' || $position == 'center') {
            // Mask the middle part of the string
            return substr($str, 0, $length / 2) . str_repeat($mask, $strminusLegnth) . substr($str, -$length / 2);
        } else {
            // Mask the right part of the string
            return str_repeat($mask, $strminusLegnth) . substr($str, -$length);
        }
    }

    /**
     * Validate an email address.
     *
     * @param string $email 
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
    static public function emailValidator(?string $email = null, ?bool $use_internet = true, ?bool $server_verify = false) 
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
     * @return string
     */    
    /**
     * Encrypt string
     *
     * @param string $string
     * @return string
     * - Uses the Open SSL Encryption
     * - BF-CBC
     */
    static public function encryptStr(?string $string = null)
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
     * @param string $platform
     * - windows|linux|android|mobile|phone|unknown|mac|macintosh|ios|iphone|c|os x
     * 
     * @param string $os_name
     * - macos|os x|ios
     * 
     * @return string
     */
    static public function platformIcon(?string $platform = null, ?string $os_name = null)
    {
        // platform to lower
        $platform = strtolower((string) basename($platform));

        // os name to lower
        $os_name = strtolower((string) $os_name);

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
     * - Storage location
     * - public_path/svg_path/
     * 
     * @param string $payment
     * -- add-money|alipay|bank|cc|credit-card|discover|faster-pay|groupbuy|maestro|mastercard
     * -- pay|payme|payment-card|payment-wallet|paypal|stripe-circle|tripe-sqaure|stripe|visa
     * 
     * @return mixed
     * - string|null
     */
    static public function paymentIcon(?string $payment = null)
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
     * File exist and not a directory
     * 
     * @param string $path
     * @return bool
     * - True|False
     */
    static public function exists(?string $path = null)
    {
        return !is_dir($path) && file_exists($path);
    }

    /**
     * Replace and recreate path to
     * - (/) slash
     * 
     * @param string $path
     * @return string
     */
    static protected function stringReplacer(?string $path = null)
    {
        return Server::cleanServerPath($path);
    }

}
