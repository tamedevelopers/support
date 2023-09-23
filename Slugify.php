<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Cocur\Slugify\Slugify as CocurSlugify;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Capsule\CustomException;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * https://github.com/cocur/slugify
 * composer require cocur/slugify
*/
class Slugify
{
        
    /**
     * language
     *
     * @var string
     */
    static private $language;
    
        
    /**
     * slug
     *
     * @param  string $word
     * @param  string|null $language
     * @param  string $separator
     * @param  bool $case
     * @return mixed
     */
    static public function slug($word, $language = null, $separator = "-", $case = true)
    {
        self::isSlugifyInstalled();

        // get language 
        $language = self::switchLanguage($language); 

        // if language exists
        if( in_array($language, self::supportedLanguage()) ){
            self::$language = $language;
        }
        
        // configuration
        $slugify = new CocurSlugify([
            'separator' => $separator, 
            'lowercase' => $case, 
            'rulesets'  => ['default', self::$language]
        ]);

        // set language
        $slugify->activateRuleSet(self::$language);

        // create slugs
        return $slugify->slugify($word);
    }
    
    /**
     * switchLanguage
     *
     * @param  string|null $language
     * @return string
     */
    static protected function switchLanguage($language = null)
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
        ][$language] ?? 'default';
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

    /**
     * Check If DOM PDF has been installed
     *
     * @return mixed
     */
    static private function isSlugifyInstalled()
    {
        try {
            if (class_exists('Cocur\Slugify\Slugify')) {
                return true;
            } else {
                throw new CustomException(
                    "Class Cocur\Slugify\Slugify not found: \nRequire the package by running: `composer require cocur/slugify`\n" . 
                    (new Exception)->getTraceAsString()
                );
            }
        } catch (CustomException $e) {
            // Handle the exception silently (turn off error reporting)
            error_reporting(0);

            Manager::setHeaders(404, function() use($e){

                // create error logger
                Env::bootLogger();

                // Trigger a custom error
                trigger_error($e->getMessage(), E_USER_ERROR);
            });
        }
    }

}