<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\CountryTrait;

class Country {

    use CountryTrait;
    
    /**
     * Get Country ISO 3
     * @param  string|null $mode
     * @return string|null
     */
    static public function getCountryIso3($mode = null)
    {
        return self::countryIso3()[self::mode($mode)] ?? null;
    }

    /**
     * Get Country ISO 2
     * @param  string|null $mode
     * @return string|null
     */
    static public function getCountryIso2($mode = null)
    {
        return self::countryIso2()[self::mode($mode)] ?? null;
    }

    /**
     * Get Country Flags for ISO 3
     * @param  string|null $mode
     * @return string|null
     */
    static public function getCountryFlagIso3($mode = null)
    {
        return self::countryFlagIso3()[self::mode($mode)] ?? null;
    }

    /**
     * Get Country Flags for ISO 2
     * @param  string|null $mode
     * @return string|null
     */
    static public function getCountryFlagIso2($mode = null)
    {
        return self::countryFlagIso2()[self::mode($mode)] ?? null;
    }

    /**
     * Get Months Data
     * 
     * @return string|null
     */
    static public function getMonths($mode = null)
    {
        return self::months()[$mode] ?? null;
    }

    /**
     * Get Week
     * @param  string|null $mode
     * @return string|null
     */
    static public function getWeeks($mode = null)
    {
        return self::weeks()[$mode] ?? null;
    }

    /**
     * Get Time Zones
     *
     * @param  string|null $mode
     * @param  string|null $default
     * 
     * @return string|null
     */
    static public function getTimeZone($mode = null, ?string $default = 'UTC')
    {
        $data = self::timeZone();

        // check if mode is numeric
        if(is_numeric($mode)){
            return $data[(int) $mode] ?? $default;
        }

        // flip array to get position num
        $flip = array_flip($data);

        return $data[$flip[$mode] ?? null] ?? $default;
    }

    /**
     * Get Captcha Locale
     * @param  string|null $mode
     * 
     * @return string|null
     */
    static public function getCaptchaLocale($mode = null)
    {
        $data = self::captchaLocale();

        return $data[$mode] 
                ?? array_flip($data)[$mode]
                ?? null;
    }

}
