<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;


class Translator{
        
    /**
     * locale
     *
     * @var string
     */
    public static $locale;

    /**
     * Translator constructor.
     * @param string|null $locale
     */
    public function __construct(?string $locale = 'en')
    {
        self::$locale = $locale;

        self::isLanguagePublished();
    }

    /**
     * Set Locale
     * @param  string $locale
     * @return void
     */
    public static function setLocale($locale)
    {
        self::$locale = $locale;
    }
    
    /**
     * Get Locale
     * @return string
     */
    public static function getLocale()
    {
        if(empty(self::$locale)){
            new self();
        }
        
        return self::$locale;
    }

    /**
     * Translate the given message.
     *
     * @param  string|null  $key
     * @param  string|null  $locale
     * @param  string|null  $base_folder
     * 
     * @return string|array|null
     */
    public static function trans($key = null, $locale = null, $base_folder = null)
    {
        $base_folder    = empty($base_folder) ? 'lang' : $base_folder;
        $locale         = is_null($locale) ? self::getLocale() : $locale;
        
        return Server::config(
            "{$locale}/$key", 
            "{$key}", 
            $base_folder
        );
    }
    
    /**
     * Check if lang folder exists or published
     *
     * @return void
     */
    private static function isLanguagePublished()
    {
        if(! is_dir(lang_path())){
            // create directory
            @mkdir(lang_path(), 0777, true);

            // create default path folder
            @mkdir(lang_path(self::getLocale()), 0777);
        }
    }

}
