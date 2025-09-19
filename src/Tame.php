<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;




/**
 * Tame Support Class
 *
 * This class provides a collection of static utility methods for common PHP development tasks,
 * including URL validation, network checks, file operations, unit conversions, mathematical calculations,
 * and more. All methods are designed to be simple, efficient, and easy to use.
 *
 * Available Methods:
 *
 * Network & URL Utilities:
 * - urlExist($url): Check if a given URL is reachable (supports HTTP/HTTPS with custom DNS resolution)
 * - isInternetAvailable($host, $port, $timeout): Check if internet connection is available
 * - getHostFromUrl($url): Extract host from URL with protocol sanitization
 *
 * JSON & Response Handling:
 * - jsonEcho($response, $message, $statusCode): Alias for echoJson
 * - echoJson($response, $message, $statusCode): Output JSON-encoded response with status code
 *
 * Class & Version Checks:
 * - class_exists($class, $closure): Check if class exists and optionally execute closure
 * - checkAnyClassExists(...$classNames): Check if at least one of the given classes exists
 * - versionCompare($version): Compare current PHP version against specified version
 *
 * Framework Detection:
 * - isAppFramework(): Check if running under popular PHP frameworks
 * - isLaravel(): Check if running under Laravel
 * - isCodeIgniter(): Check if running under CodeIgniter
 * - isCakePhp(): Check if running under CakePHP
 * - isSymfony(): Check if running under Symfony
 *
 * File Operations:
 * - include($path): Include file if it exists
 * - includeOnce($path): Include file once if it exists
 * - require($path): Require file if it exists
 * - requireOnce($path): Require file once if it exists
 * - fileTime($path): Get file modification time
 * - exists($path): Check if file exists and is not a directory
 * - unlink($file, $restrictedfileName): Delete file from server
 * - convertJsonData($path, $format): Convert JSON file to array or object
 * - saveDataAsJsonObject($destination, $data, $type): Save data as JSON object to file
 * - saveFileFromURL($url, $destination): Download and save file from URL
 * - readPDFToBrowser($path, $delete): Read PDF file to browser
 * - imageToBase64($path, $useUrl): Convert image to base64 string
 *
 * Unit Conversions:
 * - byteToUnit($bytes, $format, $gb, $mb, $kb): Convert bytes to human-readable units (KB, MB, GB)
 * - sizeToBytes($size): Convert size string (e.g., '1MB') to bytes
 * - unitToByte($size): Alias for sizeToBytes
 * - kgToGrams($weight): Convert kilograms to grams
 * - gramsToKg($weight): Convert grams to kilograms
 *
 * Mathematical Calculations:
 * - countDivisibleNumbers($index, $amount): Count numbers divisible by index within a range
 * - exponent($base, $exponent): Alias for calculateExponent
 * - calculateExponent($base, $exponent): Calculate base raised to exponent
 * - calculateVolumeWeight($length, $width, $height, $format, $decimal): Calculate volume weight for shipping
 * - calculateCubicMeterWeight($length, $width, $height, $format, $decimal): Calculate cubic meter weight
 * - getBetweenBoxLengthAndWeightInKg($length, $width, $height, $weight, $format, $decimal): Get weight between dimensional and actual weight in kg
 * - getBetweenBoxLengthAndWeightInCMB($length, $width, $height, $weight, $format, $decimal): Get weight between dimensional and actual weight in CMB
 * - getBetweenDimensionalWeightAndWeightInKg($dimensional_weight, $actual_weight): Get between dimensional and actual weight in kg
 * - getBetweenDimensionalWeightAndWeightInCBM($dimensional_weight, $actual_weight): Get between dimensional and actual weight in CMB
 * - roundToDecimal($value, $decimal): Round value to nearest specified decimal
 * - calPercentageBetweenNumbers($number, $newNumber): Calculate percentage between two numbers
 * - formatNumberToNearestThousand($number): Format number to nearest thousand (e.g., 1.2k, 1.5m)
 *
 * Array & String Utilities:
 * - isArrayDuplicate($array): Check if array has duplicate values
 * - isArraySame($array): Check if all array values are the same
 * - sortArray($array, $type): Sort array using various sorting types
 * - sortMultipleArray($array, $key, $type): Sort multi-dimensional array by key
 * - cleanPhoneNumber($phone, $allow): Clean phone number string
 * - removeSpecialChars($string): Remove special characters while allowing all languages
 * - cleanTagsForURL($string): Clean tags for URL usage
 * - stringHash($string, $length, $type, $interation): Generate hash for string
 * - shortenString($string, $limit, $replacer): Shorten string to given limit
 * - html($string): Decode HTML entity strings
 * - text($string): Convert HTML to plain text
 * - filter_input($string): Sanitize string with htmlspecialchars
 * - mask($str, $length, $position, $mask): Mask characters in string
 *
 * Email & Validation:
 * - emailValidator($email, $use_internet, $server_verify): Validate email address
 *
 * Encryption & Security:
 * - encryptStr($string): Encrypt string using OpenSSL
 * - decryptStr($jsonString): Decrypt string encrypted with encryptStr
 *
 * Output Buffering:
 * - obStart(): Start output buffering
 * - obFlush(): Flush output buffer for long-running processes
 * - obCronsflush($closure): Flush output buffer for cron jobs
 *
 * Path & Base Directory:
 * - getBasePath($path): Get base path with directory formatting
 * - stringReplacer($path): Replace and format path strings
 *
 * Icons & Assets:
 * - platformIcon($platform, $os_name): Get platform SVG icon path
 * - paymentIcon($payment): Get payment method SVG icon path
 *
 * Currency & Numbers:
 * - allCurrency($iso3): Get all currency information or by ISO3 code
 * - getCurrencyByIso3($code): Get currency by ISO3 code
 * - getUnits(): Get number units for conversion
 *
 * Miscellaneous:
 * - HeadersSent(): Check if HTTP headers have been sent and terminate if so
 * - isClosure($closure): Check if variable is a closure
 * - setCheckbox($status, $reverse_order): Set checkbox value and status
 *
 * @package Tamedevelopers\Support
 * @author Tamedevelopers
 * @link https://github.com/tamedevelopers/support
 *
 * @see \Tamedevelopers\Support\Str
 * @see \Tamedevelopers\Support\Server
 * @see \Tamedevelopers\Support\ApiResponse
 */
class Tame extends TameHelper {
    
}
