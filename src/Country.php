<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\CountryTrait;

class Country {

    use CountryTrait;

    
    /**
     * Alias for getCountryIso3() method
     * 
     * @param  string|null $mode 
     * @return string|null
     */
    public static function iso3($mode = null)
    {
        return self::getCountryIso3($mode);
    }
    
    /**
     * Alias for getCountryIso2() method
     * 
     * @param  string|null $mode 
     * @return string|null
     */
    public static function iso2($mode = null)
    {
        return self::getCountryIso2($mode);
    }

    /**
     * Alias for getCountryFlagIso3() method
     * 
     * @param  string|null $mode 
     * @return string|null
     */
    public static function flagIso3($mode = null)
    {
        return self::getCountryFlagIso3($mode);
    }

    /**
     * Alias for getCountryFlagIso2() method
     * 
     * @param  string|null $mode 
     * @return string|null
     */
    public static function flagIso2($mode = null)
    {
        return self::getCountryFlagIso2($mode);
    }

    /**
     * Alias for getMonths() method
     * 
     * @param  string|null $mode 
     * @return string|null
     */
    public static function month($mode = null)
    {
        return self::getMonths($mode);
    }

    /**
     * Alias for getWeeks() method
     * 
     * @param  string|null $mode 
     * @return string|null
     */
    public static function week($mode = null)
    {
        return self::getWeeks($mode);
    }

    /**
     * Alias for getTimeZone() method
     * 
     * @param  string|null $mode 
     * @return string|null
     */
    public static function zone($mode = null)
    {
        return self::getTimeZone($mode);
    }

    /**
     * Alias for getCaptchaLocale() method
     * 
     * @return array
     */
    public static function captcha()
    {
        return self::getCaptchaLocale();
    }
    

}
