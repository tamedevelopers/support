<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;


class Translator{
        
    /**
     * locale
     *
     * @var string
     */
    static public $locale;

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
    static public function setLocale($locale)
    {
        self::$locale = $locale;
    }
    
    /**
     * Get Locale
     * @return string
     */
    static public function getLocale()
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
     * 
     * @return string|array|null
     */
    static public function trans($key = null)
    {
        $locale = self::getLocale();
        
        return Server::config(
            "{$locale}/$key", 
            "{$key}", 
            'lang'
        );
    }
    
    /**
     * Check if lang folder exists or published
     *
     * @return void
     */
    static private function isLanguagePublished()
    {
        if(! is_dir(lang_path())){
            // create directory
            @mkdir(lang_path(), 0777, true);

            // create default path folder
            @mkdir(lang_path(self::getLocale()), 0777);
        }
    }

}
