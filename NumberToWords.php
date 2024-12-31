<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\NumberToWordsTraits;

class NumberToWords {

    use NumberToWordsTraits;

    /**
     * Allow cents text to be added
     *
     * @var bool|null
     */
    private $allowCents;

    /**
     * Currency data
     *
     * @var mixed
     */
    private $currencyData;

    /**
     * Value figures
     *
     * @var mixed
     */
    private $value;

    /**
     * static
     *
     * @var mixed
     */
    static private $staticData;

    /**
     * Words constructor.
     */
    public function __construct()
    {
        // clone copy of self
        if(!self::isWordsInstance()){
            self::$staticData = clone $this;
        }
    }

    /**
     * Handle the calls to non-existent instance methods.
     * @param string $name
     * @param mixed $args
     * 
     * @return mixed
     */
    public function __call($name, $args) 
    {
        return self::nonExistMethod($name, $args, $this);
    }
    
    /**
     * Handle the calls to non-existent static methods.
     * @param string $name
     * @param mixed $args
     * 
     * @return mixed
     */
    static public function __callStatic($name, $args) 
    {
        return self::nonExistMethod($name, $args, self::$staticData);
    }

    /**
     * Allow Cents
     * @param bool|null $cents
     * - [optional] Default is false
     * 
     * @return $this
     */
    public function __cents($cents = false)
    {
        $this->allowCents = $cents;

        return $this;
    }

    /**
     * Country <iso-3></iso-3> code
     * 
     * @param string|null $code
     * - [optional] Currency code
     * 
     * @return $this
     */
    public function __iso($code = null)
    {
        $this->currencyData = self::getCurrencyValue($code);

        return $this;
    }

    /**
     * Convert a number to its text representation.
     * - Can be able to convert numbers upto <quintillion>
     *
     * @param string|float|int $number
     * 
     * @return $this
     */
    public function __value(string|float|int $number)
    {
        if (is_numeric($number) && !is_string($number)) {
            // Check if the number is larger than a trillion
            if ($number > 1_000_000_000_000) {
                throw new \InvalidArgumentException(
                    'Numbers larger than a trillion must be passed as a string to avoid precision errors.'
                );
            }
    
            // Convert the number to a string for consistency
            $number = strval($number);
        }

        if (is_float($number) || is_int($number)) {
            // Convert to string while preserving decimals and integrity
            $number = strval($number);
        }

        // trim to convert to string
        $this->value = Str::trim($number);

        return $this;
    }

    /**
     * Translate text into number formats
     * 
     * @return string
     */
    public function toNumber()
    {
        // Clean up the string by removing currency and cent names
        $this->value = $this->removeCurrencyNames($this->value);

        // Ensure we split correctly even if there's no decimal part
        [$integerPart, $decimalPart] = explode(',', $this->value ?? '0') + [1 => null];

        // Convert integer part
        $integer = self::convertWordsToNumber($integerPart);
        
        // If decimal part exists, convert it
        $decimal = $this->allowCents ? self::convertWordsToNumber($decimalPart) : 0;

        return $decimal > 0 ? "{$integer}.{$decimal}" : "{$integer}";
    }

    /**
     * Translate numbers into readable text formats
     * 
     * @return string|null
     */
    public function toText()
    {
        return $this->formatToText();
    }

    /**
     * Format the numbers
     * 
     * @return string|null
     */
    private function formatToText()
    {
        // if cents is allowed
        if($this->allowCents){

            // get name of currency
            $currencyText = $this->currencyData['name'] ?? null;

            // allow if not empty
            $currencyText = !empty($currencyText) ? " {$currencyText}" : null;
        } else{
            $currencyText = null;
        }

        // replace thousands with empty string if found
        if(strpos($this->value, ',') !== false){
            $this->value = Str::replace(',', '', $this->value);
        }

        // split numbers into two versions
        // set decimal to default null if not found
        [$number, $decimal] = explode('.', $this->value ?? '0') + [1 => null];

        // convert number to text
        $numberText = self::convertNumberToText($number);

        // remove line break from text
        $numberText = Str::trim(
            Str::replace(["\n", "\r"], '', $numberText)
        );

        // add to text
        return ucfirst($numberText) . $currencyText . $this->toCents($decimal);
    }

    /**
     * Convert to cents
     *
     * @param string|null $decimal
     * @return string
     */
    private function toCents($decimal) 
    {
        if(!empty($decimal) && $decimal > 0 && $this->allowCents){

            // generate cents text
            $centsText = Str::trim(
                self::convertNumberToText($decimal)
            );

            // cents currency
            $centsCurrency = $this->currencyData['cents'] ?? null;

            // allow if not empty
            $centsCurrency = !empty($centsCurrency) ? " {$centsCurrency}" : '';

            $this->resetCents();

            return ", {$centsText}{$centsCurrency}";
        }

        $this->resetCents();
    }

    /**
     * Convert Words To Number
     *
     * @param  mixed $words
     * @return void
     */
    static private function convertWordsToNumber($words)
    {
        // Lowercase and trim the string
        $words = Str::lower(Str::trim($words));
        $words = preg_replace('/\sand\s/', ' ', $words);
        $parts = preg_split('/\s|-/', $words);

        $number = 0;
        $current = 0;

        foreach ($parts as $part) {
            if (isset(self::$numberMap[$part])) {
                $current += self::$numberMap[$part];
            } elseif (isset(self::$scaleMap[$part])) {
                if ($current == 0) {
                    $current = 1;
                }
                $current *= self::$scaleMap[$part];

                if ($part !== 'hundred') {
                    $number += $current;
                    $current = 0;
                }
            }
        }

        return $number + $current;
    }

    /**
     * Convert the integer part of a number to its text representation.
     *
     * @param string $number
     * @return string
     */
    static private function convertNumberToText($number) 
    {
        $number = (string) $number;
        
        if (intval($number) == 0) {
            return 'zero';
        }

        return self::convertIntegerToText($number);
    }

    /**
     * Convert an integer to its text representation.
     *
     * @param string $number
     * @return string
     */
    static private function convertIntegerToText($number)
    {
        $result = '';
        $i = 0;

        while (intval($number) > 0) {
            // Extract the last three digits
            $chunk = intval(substr($number, -3));

            // Remove the last three digits from the number
            $number = substr($number, 0, -3);

            if (intval($chunk) > 0) {
                $unit   = self::$units[$i] ?? '';
                $result = self::convertChunkToText($chunk) . ($unit ? " $unit" : '') . ' ' . $result;
            }

            $i++;
        }

        return $result;
    }

    /**
     * Convert a chunk of numbers (up to 999) to its text representation.
     *
     * @param string $number
     * @return string
     */
    static private function convertChunkToText($number) 
    {
        $result = '';

        if (intval($number) >= 100) {
            $hundreds = intval($number / 100);
            $result .= self::$words[$hundreds] . ' hundred';
            $number %= 100;

            if ($number > 0) {
                $result .= ' and ';
            }
        }

        if ($number > 0) {
            if ($number < 20) {
                $result .= self::$words[$number];
            } else {
                $tens = intval($number / 10);
                $units = $number % 10;
                $result .= self::$tens[$tens];
                if ($units > 0) {
                    $result .= '-' . self::$words[$units];
                }
            }
        }

        return $result;
    }
    
    /**
     * Remove Currency Names
     *
     * @param  mixed $value
     * @return void
     */
    static private function removeCurrencyNames($value)
    {
        // Get all currency and cent names from the CurrencyNames() method
        $currencyData = self::CurrencyNames();

        // Loop through each currency and remove the corresponding names from the value string
        foreach ($currencyData as $currency) {
            // Remove both currency name and cents from the string (case insensitive)
            $value = preg_replace('/\b' . preg_quote($currency['name'], '/') . '\b/i', '', $value);
            $value = preg_replace('/\b' . preg_quote($currency['cents'], '/') . '\b/i', '', $value);
        }

        // Return the cleaned-up string
        return $value;
    }

    /**
     * Reset Cents
     *
     * @return void
     */
    private function resetCents()
    {
        $this->allowCents = null;
    }

}
