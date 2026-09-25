<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;


trait StrTextTrait { 

    /**
     * Standard Left-To-Left language prefixes / tags. 
     */
    private static array $ltrLanguages = [
        'en', 'en-US', 'en-GB',  // English variants
        'es', 'es-ES', 'es-MX',   // Spanish variants
        'fr', 'fr-FR', 'fr-CA',   // French variants
        'de', 'de-DE', 'de-CH',   // German variants 
        'it', 'it-IT',            // Italian
        'pt', 'pt-PT', 'pt-BR',   // Portuguese variants
        'nl', 'nl-NL', 'nl-BE',   // Dutch variants
        'ru', 'ru-RU',            // Russian
        'zh', 'zh-CN', 'zh-TW', 'zh-Hant',   // Chinese variants
        'ja', 'ja-JP',            // Japanese
        'ko', 'ko-KR',            // Korean
        'hi', 'hi-IN',            // Hindi
        'bn', 'bn-BD', 'bn-IN',   // Bengali
        'pa', 'pa-IN',            // Punjabi
        'ta', 'ta-IN', 'ta-LK',   // Tamil
        'te', 'te-IN',            // Telugu
        'tr', 'tr-TR',            // Turkish
        'vi', 'vi-VN',            // Vietnamese
        'th', 'th-TH',            // Thai
        'pl', 'pl-PL',            // Polish
        'uk', 'uk-UA',            // Ukrainian
        'el', 'el-GR',            // Greek
        'cs', 'cs-CZ',            // Czech
        'hu', 'hu-HU',            // Hungarian
        'sv', 'sv-SE',            // Swedish
        'da', 'da-DK',            // Danish
        'fi', 'fi-FI',            // Finnish
        'no', 'no-NO',            // Norwegian
        'id', 'id-ID',            // Indonesian
        'ms', 'ms-MY', 'ms-ID',   // Malay
        'ro', 'ro-RO',            // Romanian
        'bg', 'bg-BG',            // Bulgarian
        'sr', 'sr-RS', 'sr-Latn', // Serbian
        'hr', 'hr-HR',            // Croatian
        'sk', 'sk-SK',            // Slovak
        'sl', 'sl-SI',            // Slovenian
        'lt', 'lt-LT',            // Lithuanian
        'lv', 'lv-LV',            // Latvian
        'et', 'et-EE',            // Estonian
        'mk', 'mk-MK',            // Macedonian
        'sq', 'sq-AL',            // Albanian
        'mt', 'mt-MT',            // Maltese
        'is', 'is-IS',            // Icelandic
        'ga', 'ga-IE',            // Irish
        'cy', 'cy-GB',            // Welsh
        'af', 'af-ZA',            // Afrikaans
        'sw', 'sw-KE', 'sw-TZ',   // Swahili
        'tl', 'tl-PH'             // Filipino
    ];

    /**
     * Standard Right-To-Left language prefixes / tags.
     */
    private static array $rtlLanguages = [
        'ar', 'he', 'fa', 'ur', 'ps', 'sd', 'ku', 'ug', 'yi', 'dv',
        'ckb', 'ks', 'syr', 'az-Arab'
    ];
    
    /**
     * Check if language is LTR
     * 
     * @param string|null $iso Language ISO code
     * @return bool True if LTR, false if RTL
     */
    public static function isLTR($iso = null) 
    {
        if (empty($iso)) {
            return true;
        }

        // Normalize separator (ar_SA -> ar-SA) and force lowercase
        $normalizedIso = Str::lower(Str::replace('_', '-', trim($iso)));

        // Extract primary language code (e.g. "en-US" -> "en")
        $primaryCode = Str::before($normalizedIso, '-');

        // Check if the primary language code or full code matches any RTL definition
        $isRtl = in_array($primaryCode, self::$rtlLanguages, true) || 
                in_array($normalizedIso, self::$rtlLanguages, true);

        return !$isRtl;
    }
    
    /**
     * Get direction as string
     * 
     * @param string $iso Language ISO code
     * @return string 'ltr' or 'rtl'
     */
    public static function getDirection($iso) 
    {
        return self::isLTR($iso) ? 'ltr' : 'rtl';
    }
    
    /**
     * Get HTML direction attribute for browser formatting
     * 
     * @param string $iso Language ISO code
     * @return string HTML dir attribute
     */
    public static function getHtmlDir($iso) 
    {
        return 'dir="' . self::getDirection($iso) . '"';
    }

}