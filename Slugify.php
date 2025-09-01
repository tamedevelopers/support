<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Cocur\Slugify\Slugify as CocurSlugify;


/**
 * Cocur\Slugify\Slugify Wrapper
 * Usage: composer require cocur/slugify
 * @link https://github.com/cocur/slugify
 */
class Slugify{
        
    /**
     * language
     *
     * @var string
     */
    static private $locale;
        
    /**
     * slug
     *
     * @param  string $word
     * @param  string|null $locale
     * @param  string $separator
     * @param  bool $case
     * @return mixed
     */
    public static function slug($word, $locale = null, $separator = "-", $case = true)
    {
        // get language 
        $locale = self::switchLanguage($locale); 

        // if language exists
        if( in_array($locale, self::supportedLanguage()) ){
            self::$locale = $locale;
        }
        
        // configuration
        $slugify = new CocurSlugify([
            'separator' => $separator, 
            'lowercase' => $case, 
            'rulesets'  => ['default', self::$locale]
        ]);

        // set language
        $slugify->activateRuleSet(self::$locale);

        // create slugs
        return $slugify->slugify($word);
    }
    
    /**
     * locales
     *
     * @return array
     */
    public static function locales()
    {
        return [
            'ar' => 'arabic', 
            'am' => 'armenian', 
            'as' => 'austrain', 
            'az' => 'azerbaijan',
            'bg' => 'bulgarian', 
            'mm' => 'burmese', 
            'cn' => 'chinese', 
            'hr' => 'croatian', 
            'cz' => 'czech', 
            'dk' => 'danish', 
            'en' => 'default', 
            'es' => 'esperanto', 
            'ee' => 'estonian', 
            'fi' => 'finnish', 
            'fr' => 'french', 
            'ge' => 'georgian', 
            'de' => 'german', 
            'gr' => 'greek', 
            'in' => 'hindi', 
            'hu' => 'hungarian', 
            'it' => 'italian', 
            'iv' => 'latvian', 
            'lt' => 'lithuanian', 
            'mk' => 'macedonian', 
            'no' => 'norwegian', 
            'ir' => 'persian', 
            'pl' => 'polish', 
            'br' => 'portuguese-brazil', 
            'ro' => 'romanian', 
            'ru' => 'russian', 
            'rs' => 'serbian', 
            'sk' => 'slovak', 
            'se' => 'swedish', 
            'tr' => 'turkish', 
            'tm' => 'turkmen', 
            'ua' => 'ukrainian', 
            'vn' => 'vietnamese'
        ];
    }
    
    /**
     * switchLanguage
     *
     * @param  string|null $locale
     * @return string
     */
    static private function switchLanguage($locale = null)
    {
        return self::locales()[$locale] ?? 'default';
    }
     
    /**
     * supported Language
     *
     * @return array
     */
    static private function supportedLanguage()
    {
        return [
            'arabic', 
            'armenian', 
            'austrain', 
            'azerbaijan', 
            'bulgarian', 
            'burmese', 
            'chinese', 
            'croatian', 
            'czech', 
            'danish', 
            'default', 
            'esperanto', 
            'estonian', 
            'finnish', 
            'french', 
            'georgian', 
            'german', 
            'greek', 
            'hindi', 
            'hungarian', 
            'italian', 
            'latvian', 
            'lithuanian', 
            'macedonian', 
            'norwegian', 
            'persian', 
            'polish', 
            'portuguese-brazil', 
            'romanian', 
            'russian', 
            'serbian', 
            'slovak', 
            'swedish', 
            'turkish', 
            'turkmen', 
            'ukrainian', 
            'vietnamese'
        ];
    }

}