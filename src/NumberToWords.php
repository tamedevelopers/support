<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\NumberToWordsTraits;

/**
 * Number-to-words converter with dynamic fluent API via __call/__callStatic.
 *
 * Builder-style magic methods (documented for static analysis):
 * @method self iso(?string $code = null) Set currency by (iso-4217) code
 * @method self cents(?bool $allow = false) Toggle cents handling
 * @method self value(string|float|int $number) Set the value to convert
 *
 * Static builder equivalents:
 * @method static self iso(?string $code = null)
 * @method static self cents(?bool $allow = false)
 * @method static self value(string|float|int $number)
 */
class NumberToWords
{
    use NumberToWordsTraits;

    /**
     * Allow cents text to be added
     */
    private ?bool $allowCents = null;

    /**
     * Currency data
     */
    private mixed $currencyData = null;

    /**
     * Value figures (raw user input)
     */
    private mixed $value = null;

    /**
     * Static instance for fluent static calls
     */
    private static mixed $staticData = null;

    
    public function __construct()
    {
        if (!self::isWordsInstance()) {
            self::$staticData = clone $this;
        }
    }

    public function __call($name, $args)
    {
        return self::nonExistMethod($name, $args, $this);
    }

    public static function __callStatic($name, $args)
    {
        return self::nonExistMethod($name, $args, self::$staticData);
    }

    /**
     * Allow Cents
     */
    public function __cents(?bool $cents = false): self
    {
        $this->allowCents = $cents;
        return $this;
    }

    /**
     * Currency code
     */
    public function __iso(?string $code = null): self
    {
        $this->currencyData = self::getCurrencyByCode($code);
        return $this;
    }

    /**
     * Set value
     * - Accepts string|float|int; large integers should be passed as string
     */
    public function __value(string|float|int $number): self
    {
        if (is_numeric($number) && !is_string($number)) {
            if ($number > 1_000_000_000_000) {
                throw new \InvalidArgumentException(
                    'Numbers larger than a trillion must be passed as a string to avoid precision errors.'
                );
            }
            $number = strval($number);
        }

        if (is_float($number) || is_int($number)) {
            $number = strval($number);
        }

        $this->value = Str::trim($number);
        return $this;
    }

    /**
     * Convert a text value to its numeric representation.
     * Example supported:
     *  "Thirty-four million ... twenty-three euro, two hundred and thirty-one cents"
     */
    public function toNumber(): string
    {
        // Clean currency terms
        $clean = Str::trim(self::removeCurrencyNames((string) $this->value));

        // Normalize spacing and punctuation
        $clean = preg_replace('/[\r\n]+/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', (string) $clean);

        [$integerWords, $centsWords] = $this->extractIntegerAndCents($clean);

        // Use string-based big integer conversion
        $integer = self::convertWordsToNumber($integerWords); // string
        $decimal = '0';

        if ($this->allowCents && $centsWords !== null) {
            $decimal = self::convertWordsToNumber($centsWords); // string
        }

        return $decimal !== '0' ? ($integer . '.' . $decimal) : $integer;
    }

    /**
     * Translate numbers into readable text formats
     */
    public function toText(): ?string
    {
        return $this->formatToText();
    }

    /**
     * Convert Words To Number (arbitrary-precision using strings)
     */
    private static function convertWordsToNumber($words)
    {
        if ($words === null || $words === '') {
            return '0';
        }

        // Normalize
        $words = Str::lower(Str::trim((string) $words));
        $words = preg_replace('/\s+and\s+/', ' ', $words); // remove optional 'and'
        $words = preg_replace('/\s+/', ' ', $words);
        // keep only letters, hyphen and spaces
        $words = preg_replace('/[^a-z\-\s]/', ' ', $words);
        $words = preg_replace('/\s+/', ' ', $words);

        $parts = preg_split('/\s+|\-/', trim($words));

        $total = '0';       // big integer as string
        $current = 0;       // safe small accumulator (<1000)

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (isset(self::$numberMap[$part])) {
                $current += self::$numberMap[$part];
                continue;
            }

            if ($part === 'hundred') {
                if ($current === 0) {
                    $current = 1;
                }
                $current *= 100;
                continue;
            }

            // Check scale words: thousand, million, ... using units list
            $scaleIndex = array_search($part, self::$units, true);
            if ($scaleIndex !== false && $scaleIndex > 0) {
                if ($current === 0) {
                    $current = 1;
                }
                $shifted = self::bigMulPow10((string) $current, 3 * (int) $scaleIndex);
                $total = self::bigAdd($total, $shifted);
                $current = 0;
                continue;
            }
        }

        // Add remaining current
        $total = self::bigAdd($total, (string) $current);

        return self::bigTrimZeros($total);
    }

    /**
     * Trim leading zeros from a numeric string.
     */
    private static function bigTrimZeros(string $s): string
    {
        $s = ltrim($s, '0');
        return $s === '' ? '0' : $s;
    }

    /**
     * Add two non-negative integer strings.
     */
    private static function bigAdd(string $a, string $b): string
    {
        $a = preg_replace('/\D/', '', $a);
        $b = preg_replace('/\D/', '', $b);
        $a = $a === '' ? '0' : $a;
        $b = $b === '' ? '0' : $b;

        $i = strlen($a) - 1;
        $j = strlen($b) - 1;
        $carry = 0;
        $res = '';

        while ($i >= 0 || $j >= 0 || $carry) {
            $da = $i >= 0 ? (ord($a[$i]) - 48) : 0;
            $db = $j >= 0 ? (ord($b[$j]) - 48) : 0;
            $sum = $da + $db + $carry;
            $res .= chr(($sum % 10) + 48);
            $carry = intdiv($sum, 10);
            $i--; $j--;
        }

        return self::bigTrimZeros(strrev($res));
    }

    /**
     * Multiply an integer string by 10^pow (append zeros).
     */
    private static function bigMulPow10(string $a, int $pow): string
    {
        $a = self::bigTrimZeros($a);
        if ($a === '0') {
            return '0';
        }
        return $a . str_repeat('0', max(0, $pow));
    }

    /**
     * Convert the integer part of a number to its text representation.
     */
    private static function convertNumberToText($number)
    {
        $number = (string) $number;
        if ((int) $number === 0) {
            return 'zero';
        }
        return self::convertIntegerToText($number);
    }

    /**
     * Convert an integer to its text representation.
     */
    private static function convertIntegerToText($number)
    {
        $result = '';
        $i = 0;

        while ((int) $number > 0) {
            $chunk = (int) substr($number, -3);
            $number = substr($number, 0, -3);

            if ($chunk > 0) {
                $unit = self::$units[$i] ?? '';
                $result = self::convertChunkToText($chunk) . ($unit ? " $unit" : '') . ' ' . $result;
            }

            $i++;
        }

        return trim($result);
    }

    /**
     * Convert a chunk of numbers (up to 999) to its text representation.
     */
    private static function convertChunkToText($number)
    {
        $result = '';
        $n = (int) $number;

        if ($n >= 100) {
            $hundreds = (int) floor($n / 100);
            $result .= self::$words[$hundreds] . ' hundred';
            $n = $n % 100;
            if ($n > 0) {
                $result .= ' and ';
            }
        }

        if ($n > 0) {
            if ($n < 20) {
                $result .= self::$words[$n];
            } else {
                $tens = (int) floor($n / 10);
                $units = $n % 10;
                $result .= self::$tens[$tens];
                if ($units > 0) {
                    $result .= '-' . self::$words[$units];
                }
            }
        }

        return $result;
    }

    /**
     * Remove Currency Names and cents words from a sentence.
     */
    private static function removeCurrencyNames($value)
    {
        $value = (string) $value;
        $currencyData = self::allCurrency();
        foreach ($currencyData as $currency) {
            if (!empty($currency['name'])) {
                $value = preg_replace('/\b' . preg_quote((string) $currency['name'], '/') . '\b/i', '', $value);
            }
            if (!empty($currency['cents'])) {
                // do not remove the word 'cents' here entirely because it's used in parsing patterns; remove only specific plural forms like local ones
                $localCents = (string) $currency['cents'];
                if (Str::lower($localCents) !== 'cents') {
                    $value = preg_replace('/\b' . preg_quote($localCents, '/') . '\b/i', '', $value);
                }
            }
        }
        return $value;
    }

    /**
     * Format the numbers to text
     */
    private function formatToText(): ?string
    {
        $currencyText = null;
        if ($this->allowCents) {
            $currencyText = $this->currencyData['name'] ?? null;
            $currencyText = !empty($currencyText) ? " {$currencyText}" : null;
        }

        if (strpos((string) $this->value, ',') !== false) {
            $this->value = Str::replace(',', '', (string) $this->value);
        }

        [$number, $decimal] = explode('.', (string) ($this->value ?? '0')) + [1 => null];

        $numberText = self::convertNumberToText($number);
        $numberText = Str::trim(Str::replace(["\n", "\r"], '', $numberText));

        return ucfirst($numberText) . $currencyText . $this->toCents($decimal);
    }

    /**
     * Convert cents text
     */
    private function toCents($decimal): ?string
    {
        if (!empty($decimal) && $decimal > 0 && $this->allowCents) {
            $centsText = Str::trim(self::convertNumberToText($decimal));
            $centsCurrency = $this->currencyData['cents'] ?? null;
            $centsCurrency = !empty($centsCurrency) ? " {$centsCurrency}" : '';
            $this->resetCents();
            return ", {$centsText}{$centsCurrency}";
        }

        $this->resetCents();
        return null;
    }

    /**
     * Extract integer and cents words from a possibly complex phrase.
     * Fallbacks:
     * - First try pattern with explicit "cents"
     * - Then fallback to comma-separated parts
     * - Else treat full text as integer
     */
    private function extractIntegerAndCents(string $text): array
    {
        $int = $text;
        $cents = null;

        // Pattern: "<int words> , <cents words> cents"
        if (preg_match('/^(.*?)\s*,\s*(.*?)\s+cent(s)?\b/i', $text, $m)) {
            $int = Str::trim($m[1]);
            $cents = Str::trim($m[2]);
            return [$int, $cents];
        }

        // Pattern without comma but with explicit cents
        if (preg_match('/^(.*?)\s+(.*?)\s+cent(s)?\b/i', $text, $m)) {
            $int = Str::trim($m[1]);
            $cents = Str::trim($m[2]);
            return [$int, $cents];
        }

        // Fallback: comma separation only
        if (strpos($text, ',') !== false) {
            [$a, $b] = explode(',', $text, 2);
            $int = Str::trim($a);
            $cents = Str::trim($b);
            return [$int, $cents];
        }

        return [$int, $cents];
    }

    /**
     * Reset cents config
     */
    private function resetCents(): void
    {
        $this->allowCents = null;
    }
}
