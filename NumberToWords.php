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
    static private $allowCents;

    /**
     * Currency data
     *
     * @var mixed
     */
    static private $currencyData;

    /**
     * Allow Cents
     * @param bool|null $cents
     * - [optional] Default is false
     * 
     * @return $this
     */
    static public function cents($cents = null)
    {
        $self = clone new self();

        self::$allowCents = $cents;

        return $self;
    }

    /**
     * Country <iso-3></iso-3> code
     * 
     * @param string|null $code
     * - [optional] Currency code
     * 
     * @return $this
     */
    static public function iso($code = null)
    {
        $self = clone new self();

        self::$currencyData = self::getCurrencyText($code);

        return $self;
    }

    /**
     * Convert a number to its text representation.
     * - Can be able to convert numbers upto <quintillion>
     *
     * @param string|int|float $number
     * 
     * @param bool|null $cents
     * - [optional] Default is false
     * 
     * @return string
     */
    static public function text($number, $cents = null) 
    {
        if(is_null(self::$allowCents) && is_bool($cents)){
            self::$allowCents = $cents;
        }

        // trim to convert to string
        $number = Str::trim($number);

        // if cents is allowed
        if(self::$allowCents){ 

            // get name of currency
            $currencyText = self::$currencyData['name'] ?? null;

            // allow if not empty
            $currencyText = !empty($currencyText) ? " {$currencyText}" : null;
        } else{
            $currencyText = null;
        }

        // convert number to text
        $numberText = self::convertNumberToText($number);

        // remove line break from text
        $numberText = Str::trim(
            Str::replace(["\n", "\r"], '', $numberText)
        );

        return ucfirst($numberText) . $currencyText . self::toCents($number);
    }

    /**
     * Convert to cents
     *
     * @param string $number
     * @return string
     */
    static private function toCents($number) 
    {
        // if number contain (.) dot
        // we treat as cents
        if(Str::contains('.', $number)){
            $cents = explode('.', $number);
            $decimalNumber = isset($cents[1]) ? $cents[1] : null;

            if(!empty($decimalNumber) && self::$allowCents){

                $centsCurrency = Str::trim(
                    self::convertNumberToText($decimalNumber)
                );

                // cents text
                $centsText = self::$currencyData['cents'] ?? null;

                // allow if not empty
                $centsText = !empty($centsText) ? " {$centsText}" : '';

                // reset allowCents
                self::$allowCents = null;

                return ", {$centsCurrency}{$centsText}";
            }
        }

        // reset allowCents
        self::$allowCents = null;
    }

    /**
     * Convert the integer part of a number to its text representation.
     *
     * @param string $number
     * @return string
     */
    static private function convertNumberToText($number) 
    {
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
            $chunk = intval($number) % 1000;
            $number = intval($number) / 1000;

            if (intval($chunk) > 0) {
                $result = self::convertChunkToText($chunk) . ' ' . self::$units[$i] . ' ' . $result;
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
            $result .= self::$words[(int)intval($number) / 100] . ' hundred';
            $number = intval($number) % 100;
            if (intval($number) > 0) {
                $result .= ' and ';
            }
        }

        if (intval($number) > 0) {
            if (intval($number) < 20) {
                $result .= self::$words[intval($number)];
            } else {
                $result .= self::$tens[(int)intval($number) / 10];
                if (intval($number) % 10 > 0) {
                    $result .= '-' . self::$words[intval($number) % 10];
                }
            }
        }

        return $result;
    }

    /**
     * Get the text representation of a currency code.
     *
     * @param string|null $code
     * - [NGA, USD, EUR]
     * 
     * @return array|null
     */
    static public function getCurrencyText($code = null) 
    {
        // convert code to upper
        $code = Str::upper($code);

        // get data
        $data = self::currencyNames()[$code] ?? null;

        if(is_null($data)){
            return;
        }

        return Str::convertArrayCase($data, 'lower', 'lower');
    }

}
