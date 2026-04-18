<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\TameTrait;


trait FontPathTrait{

    use TameTrait;
    

    /**
     * Try to resolve a readable TTF/TTC font path. Use provided path if valid; otherwise try system fonts.
     * Picks fonts by script (Arabic, Hebrew, Thai, Devanagari, CJK, then generic Unicode / Latin).
     * Returns null if none found.
     *
     * @param string $path
     * @param string $weight (Default is 'bold')
     * @param string $textForFont (Default is '')
     * @return null|string
     */
    public static function resolveFontPath($path = null, $weight = null, $textForFont = null)
    {
        if (empty($textForFont)) {
            $textForFont = '';
        }

        $weight = Str::lower($weight);
        $weight = in_array($weight, ['normal', 'bold'], true) ? $weight : 'bold';

        // If user provided a readable path, use it as-is
        if (is_string($path) && $path !== '' && @is_readable($path)) {
            return $path;
        }

        $isUnicode = self::needsUnicodeFont($textForFont);

        $path = self::stringReplacer(__DIR__ . DIRECTORY_SEPARATOR);

        $bundledUnicode = "{$path}icons/fonts/" . ($weight === 'bold' ? 'NotoSansSC-Bold.ttf' : 'NotoSansSC-Medium.ttf');
        $bundledLatin = "{$path}icons/fonts/" . ($weight === 'bold' ? 'Inter-Bold.ttf' : 'Inter-Medium.ttf');

        // Script-specific fonts (NotoSansSC does not cover Arabic — avoid blank Arabic initials)
        if ($isUnicode) {
            if (self::textContainsArabicScript($textForFont) && self::textContainsCjkScript($textForFont)) {
                $resolved = self::firstReadableFont(self::panUnicodeFontCandidates($weight));
                if ($resolved !== null) {
                    return $resolved;
                }
            }
            if (self::textContainsArabicScript($textForFont)) {
                $resolved = self::firstReadableFont(self::arabicFontCandidates($weight));
                if ($resolved !== null) {
                    return $resolved;
                }
            }
            if (self::textContainsHebrewScript($textForFont)) {
                $resolved = self::firstReadableFont(self::hebrewFontCandidates($weight));
                if ($resolved !== null) {
                    return $resolved;
                }
            }
            if (self::textContainsThaiScript($textForFont)) {
                $resolved = self::firstReadableFont(self::thaiFontCandidates($weight));
                if ($resolved !== null) {
                    return $resolved;
                }
            }
            if (self::textContainsDevanagariScript($textForFont)) {
                $resolved = self::firstReadableFont(self::devanagariFontCandidates($weight));
                if ($resolved !== null) {
                    return $resolved;
                }
            }
            if (self::textContainsCjkScript($textForFont) && file_exists($bundledUnicode)) {
                return $bundledUnicode;
            }
        }

        if (file_exists($bundledLatin)) {
            return $bundledLatin;
        }

        // Unicode/CJK-capable fonts (Windows, then Linux/macOS) – try first when text has non-ASCII
        $unicodeFontsBold = [
            'C:\\Windows\\Fonts\\msyhbd.ttf',   // Microsoft YaHei Bold
            'C:\\Windows\\Fonts\\simhei.ttf',   // SimHei
            'C:\\Windows\\Fonts\\simsun.ttc',   // SimSun (TTC; GD uses first font)
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Bold.ttc',
            '/usr/share/fonts/truetype/noto/NotoSansCJK-Bold.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-microhei.ttc',
            '/Library/Fonts/PingFang.ttc',
            '/System/Library/Fonts/PingFang.ttc',
            '/Library/Fonts/Supplemental/Songti.ttc',
        ];

        $unicodeFontsRegular = [
            'C:\\Windows\\Fonts\\msyh.ttf',     // Microsoft YaHei
            'C:\\Windows\\Fonts\\simsun.ttc',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-microhei.ttc',
            '/Library/Fonts/PingFang.ttc',
            '/System/Library/Fonts/PingFang.ttc',
        ];

        // Latin-only fonts (Arial, Segoe, DejaVu)
        $winFontsBold = [
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\segoeuib.ttf',
        ];
        $winFontsRegular = [
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\segoeui.ttf',
        ];
        $unixFontsBold = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/Library/Fonts/Arial Bold.ttf',
        ];
        $unixFontsRegular = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/Library/Fonts/Arial.ttf',
        ];

        $unicodeOrdered = $weight === 'normal'
            ? array_merge($unicodeFontsRegular, $unicodeFontsBold)
            : array_merge($unicodeFontsBold, $unicodeFontsRegular);

        $latinOrdered = $weight === 'normal'
            ? array_merge($winFontsRegular, $unixFontsRegular, $winFontsBold, $unixFontsBold)
            : array_merge($winFontsBold, $unixFontsBold, $winFontsRegular, $unixFontsRegular);

        // When text has CJK/Unicode, try Unicode fonts first so glyphs render; else Latin first
        $ordered = $isUnicode
            ? array_merge($unicodeOrdered, $latinOrdered)
            : array_merge($latinOrdered, $unicodeOrdered);

        foreach ($ordered as $cand) {
            if (@is_readable($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * @param list<string> $candidates
     */
    private static function firstReadableFont(array $candidates): ?string
    {
        foreach ($candidates as $cand) {
            if (@is_readable($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Fonts that cover Arabic + CJK in one face (rare; try before script-specific fallback).
     *
     * @return list<string>
     */
    private static function panUnicodeFontCandidates(string $weight): array
    {
        $uni = [
            '/Library/Fonts/Arial Unicode.ttf',
            '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
            'C:\\Windows\\Fonts\\ARIALUNI.TTF',
            'C:\\Windows\\Fonts\\arialuni.ttf',
        ];
        if ($weight === 'bold') {
            $uni[] = 'C:\\Windows\\Fonts\\arialbd.ttf';
        }
        $uni[] = 'C:\\Windows\\Fonts\\arial.ttf';

        return $uni;
    }

    /**
     * @return list<string>
     */
    private static function arabicFontCandidates(string $weight): array
    {
        $bold = $weight === 'bold';

        return $bold ? [
            __DIR__ . '/icons/fonts/NotoNaskhArabic-Bold.ttf',
            __DIR__ . '/icons/fonts/NotoSansArabic-Bold.ttf',
            'C:\\Windows\\Fonts\\tradbdo.ttf',
            'C:\\Windows\\Fonts\\arabtype.ttf',
            'C:\\Windows\\Fonts\\tahomabd.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\segoeuib.ttf',
            'C:\\Windows\\Fonts\\calibrib.ttf',
            '/usr/share/fonts/truetype/noto/NotoNaskhArabic-Bold.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansArabic-Bold.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansArabic-Bold.otf',
            '/usr/share/fonts/opentype/noto/NotoSansArabic-Bold.otf',
            '/Library/Fonts/Arial Unicode.ttf',
            '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
        ] : [
            __DIR__ . '/icons/fonts/NotoNaskhArabic-Regular.ttf',
            __DIR__ . '/icons/fonts/NotoSansArabic-Regular.ttf',
            'C:\\Windows\\Fonts\\trado.ttf',
            'C:\\Windows\\Fonts\\arabtype.ttf',
            'C:\\Windows\\Fonts\\tahoma.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\segoeui.ttf',
            'C:\\Windows\\Fonts\\calibri.ttf',
            '/usr/share/fonts/truetype/noto/NotoNaskhArabic-Regular.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansArabic-Regular.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansArabic-Regular.otf',
            '/usr/share/fonts/opentype/noto/NotoSansArabic-Regular.otf',
            '/Library/Fonts/Arial Unicode.ttf',
            '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
        ];
    }

    /**
     * @return list<string>
     */
    private static function hebrewFontCandidates(string $weight): array
    {
        $bold = $weight === 'bold';

        return $bold ? [
            'C:\\Windows\\Fonts\\davidbd.ttf',
            'C:\\Windows\\Fonts\\segoeuib.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansHebrew-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
        ] : [
            'C:\\Windows\\Fonts\\david.ttf',
            'C:\\Windows\\Fonts\\segoeui.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansHebrew-Regular.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
        ];
    }

    /**
     * @return list<string>
     */
    private static function thaiFontCandidates(string $weight): array
    {
        $bold = $weight === 'bold';

        return $bold ? [
            'C:\\Windows\\Fonts\\tahomabd.ttf',
            'C:\\Windows\\Fonts\\LeelawUI.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Bold.ttf',
        ] : [
            'C:\\Windows\\Fonts\\tahoma.ttf',
            'C:\\Windows\\Fonts\\LeelawUI.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
        ];
    }

    /**
     * @return list<string>
     */
    private static function devanagariFontCandidates(string $weight): array
    {
        $bold = $weight === 'bold';

        return $bold ? [
            'C:\\Windows\\Fonts\\mangalb.ttf',
            'C:\\Windows\\Fonts\\NirmalaB.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansDevanagari-Bold.ttf',
        ] : [
            'C:\\Windows\\Fonts\\mangal.ttf',
            'C:\\Windows\\Fonts\\Nirmala.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansDevanagari-Regular.ttf',
        ];
    }

    public static function textContainsArabicScript(string $text): bool
    {
        $text = Str::trim($text);

        return $text !== '' && (bool) preg_match('/\p{Arabic}/u', $text);
    }

    public static function textContainsHebrewScript(string $text): bool
    {
        $text = Str::trim($text);

        return $text !== '' && (bool) preg_match('/\p{Hebrew}/u', $text);
    }

    public static function textContainsThaiScript(string $text): bool
    {
        $text = Str::trim($text);

        return $text !== '' && (bool) preg_match('/\p{Thai}/u', $text);
    }

    public static function textContainsDevanagariScript(string $text): bool
    {
        $text = Str::trim($text);

        return $text !== '' && (bool) preg_match('/\p{Devanagari}/u', $text);
    }

    public static function textContainsCjkScript(string $text): bool
    {
        $text = Str::trim($text);

        return $text !== '' && (bool) preg_match('/\p{Han}|\p{Hiragana}|\p{Katakana}|\p{Hangul}/u', $text);
    }

    /**
     * True if text contains any non-ASCII character (CJK, Arabic, etc.) and thus needs a Unicode font.
     * 
     * @param string|null $text 
     * @return bool
     */
    protected static function needsUnicodeFont($text = null)
    {
        $text = Str::trim($text);
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $cp = self::mbOrd(mb_substr($text, $i, 1, 'UTF-8'));
            if ($cp > 0x7F) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Unicode codepoint of a single UTF-8 character.
     * 
     * @param string $char
     * @return int
     */
    private static function mbOrd($char)
    {
        $c = mb_convert_encoding($char, 'UCS-4BE', 'UTF-8');
        return $c === false ? 0 : unpack('N', $c)[1];
    }

    /**
     * True if $text contains at least one Unicode emoji / extended pictographic character.
     * Useful for OCR post-checks or choosing emoji-friendly pipelines.
     */
    public static function textContainsEmoji(string $text): bool
    {
        return $text !== '' && (bool) preg_match('/\p{Extended_Pictographic}/u', $text);
    }

    /**
     * Split a user OCR language string into tokens (e.g. "eng+chi_sim", "en, ja", "auto").
     *
     * @return list<string>
     */
    public static function parseOcrLanguageTokens(string $language): array
    {
        $language = Str::trim($language);
        if ($language === '' || strcasecmp($language, 'auto') === 0) {
            return [];
        }
        $parts = preg_split('/[\s,+;]+/u', $language) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = Str::trim((string) $p);
            if ($p !== '') {
                $out[] = $p;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Map one user/language token to Tesseract traineddata code (lowercase alpha, 3+ chars typical).
     */
    public static function normalizeTokenToTesseract(string $token): string
    {
        $t = Str::lower(Str::trim($token));
        if ($t === '') {
            return '';
        }
        $tHyphen = str_replace('_', '-', $t);
        if (preg_match('/^zh-(hans|cn)$/i', $tHyphen)) {
            return 'chi_sim';
        }
        if (preg_match('/^zh-(hant|tw|hk|mo)$/i', $tHyphen)) {
            return 'chi_tra';
        }
        if (in_array($t, ['emoji', 'emojis', 'symbol', 'symbols', 'pic'], true)) {
            return '';
        }
        static $aliases = [
            'en' => 'eng', 'english' => 'eng',
            'zh' => 'chi_sim', 'zh-cn' => 'chi_sim', 'zh-hans' => 'chi_sim', 'zh_cn' => 'chi_sim', 'cn' => 'chi_sim',
            'zh-tw' => 'chi_tra', 'zh-hant' => 'chi_tra', 'zh_hk' => 'chi_tra', 'tw' => 'chi_tra', 'hk' => 'chi_tra',
            'ja' => 'jpn', 'jp' => 'jpn', 'japanese' => 'jpn',
            'ko' => 'kor', 'korean' => 'kor',
            'ar' => 'ara', 'arabic' => 'ara',
            'ru' => 'rus', 'russian' => 'rus',
            'fr' => 'fra', 'french' => 'fra',
            'de' => 'deu', 'german' => 'deu',
            'es' => 'spa', 'spanish' => 'spa',
            'pt' => 'por', 'portuguese' => 'por',
            'it' => 'ita', 'italian' => 'ita',
            'hi' => 'hin', 'hindi' => 'hin',
            'th' => 'tha', 'thai' => 'tha',
            'vi' => 'vie', 'vietnamese' => 'vie',
            'id' => 'ind', 'indonesian' => 'ind',
            'tr' => 'tur', 'turkish' => 'tur',
            'pl' => 'pol', 'polish' => 'pol',
            'nl' => 'nld', 'dutch' => 'nld',
            'sv' => 'swe', 'swedish' => 'swe',
            'no' => 'nor', 'nb' => 'nor', 'nn' => 'nor',
            'da' => 'dan', 'danish' => 'dan',
            'fi' => 'fin', 'finnish' => 'fin',
            'cs' => 'ces', 'czech' => 'ces',
            'sk' => 'slk', 'slovak' => 'slk',
            'hu' => 'hun', 'hungarian' => 'hun',
            'ro' => 'ron', 'romanian' => 'ron',
            'el' => 'ell', 'greek' => 'ell',
            'he' => 'heb', 'iw' => 'heb',
            'uk' => 'ukr', 'ukrainian' => 'ukr',
            'bg' => 'bul', 'bulgarian' => 'bul',
            'sr' => 'srp', 'serbian' => 'srp',
            'hr' => 'hrv', 'croatian' => 'hrv',
            'sl' => 'slv', 'slovenian' => 'slv',
            'ms' => 'msa', 'malay' => 'msa',
            'tl' => 'tgl', 'fil' => 'tgl',
            'fa' => 'fas', 'persian' => 'fas',
            'ur' => 'urd', 'urdu' => 'urd',
            'bn' => 'ben', 'bengali' => 'ben',
            'ta' => 'tam', 'tamil' => 'tam',
            'te' => 'tel', 'telugu' => 'tel',
            'kn' => 'kan', 'kannada' => 'kan',
            'ml' => 'mal', 'malayalam' => 'mal',
        ];
        if (isset($aliases[$t])) {
            return $aliases[$t];
        }
        if (preg_match('/^[a-z]{3,}$/', $t)) {
            return $t;
        }
        return $t;
    }

    /**
     * Tesseract lang → Google Vision languageHints (BCP-47).
     *
     * @return string|null null if unknown (caller may skip or pass through)
     */
    public static function tesseractLangToGoogleBcp47(string $tessLang): ?string
    {
        $t = Str::lower(Str::trim($tessLang));
        static $map = [
            'eng' => 'en', 'fra' => 'fr', 'deu' => 'de', 'spa' => 'es', 'por' => 'pt', 'ita' => 'it',
            'nld' => 'nl', 'pol' => 'pl', 'ces' => 'cs', 'slk' => 'sk', 'hun' => 'hu', 'ron' => 'ro',
            'ell' => 'el', 'swe' => 'sv', 'nor' => 'no', 'dan' => 'da', 'fin' => 'fi', 'tur' => 'tr',
            'rus' => 'ru', 'ukr' => 'uk', 'bul' => 'bg', 'srp' => 'sr', 'hrv' => 'hr', 'slv' => 'sl',
            'ara' => 'ar', 'fas' => 'fa', 'urd' => 'ur', 'heb' => 'he', 'hin' => 'hi', 'ben' => 'bn',
            'tam' => 'ta', 'tel' => 'te', 'kan' => 'kn', 'mal' => 'ml', 'tha' => 'th', 'vie' => 'vi',
            'ind' => 'id', 'msa' => 'ms', 'jpn' => 'ja', 'kor' => 'ko', 'chi_sim' => 'zh-Hans',
            'chi_tra' => 'zh-Hant', 'tgl' => 'fil',
        ];
        return $map[$t] ?? null;
    }

    /**
     * Tesseract lang → Azure Computer Vision v3.2 ocr language query value.
     *
     * @return string|null
     */
    public static function tesseractLangToAzure(string $tessLang): ?string
    {
        $g = self::tesseractLangToGoogleBcp47($tessLang);
        if ($g === null) {
            return null;
        }
        static $azureSpecial = [
            'fil' => 'fil', // Azure uses fil for Filipino
        ];
        return $azureSpecial[$g] ?? $g;
    }

    /**
     * Build per-engine language settings from a user-facing language string.
     * Supports multi-language: "en+ja", "eng chi_sim", "zh-CN+fra", etc.
     * Use "auto" or empty for engine defaults (Azure: unk, Google: broad hints, Tesseract: no -l).
     *
     * @return array{tesseract:string,google_hints:list<string>,azure:string,ocrspace:string}
     */
    public static function expandOcrLanguageForEngines(string $language, bool $emojiFriendly = false): array
    {
        $tokens = self::parseOcrLanguageTokens($language);
        if ($tokens === []) {
            // Broad BCP-47 hints improve autodetect for Han / Hangul / Hiragana / Arabic + Latin charts
            $hints = ['en', 'zh-Hans', 'zh-Hant', 'ja', 'ko', 'ar', 'th', 'hi'];
            if ($emojiFriendly) {
                $hints[] = 'und';
            }
            return [
                'tesseract' => '',
                'google_hints' => array_values(array_unique($hints)),
                'azure' => 'unk',
                'ocrspace' => 'auto',
            ];
        }

        $tessParts = [];
        foreach ($tokens as $tok) {
            $norm = self::normalizeTokenToTesseract($tok);
            if ($norm !== '' && $norm !== 'auto' && !in_array($norm, $tessParts, true)) {
                $tessParts[] = $norm;
            }
        }
        if ($tessParts === [] && $tokens !== []) {
            $tessParts = ['eng'];
        }
        $tesseract = implode('+', $tessParts);

        $googleHints = [];
        foreach ($tessParts as $tp) {
            $bcp = self::tesseractLangToGoogleBcp47($tp);
            if ($bcp !== null && !in_array($bcp, $googleHints, true)) {
                $googleHints[] = $bcp;
            }
        }
        if ($emojiFriendly && !in_array('und', $googleHints, true)) {
            $googleHints[] = 'und';
        }

        $azure = 'unk';
        if (!$emojiFriendly && count($tessParts) === 1) {
            $a = self::tesseractLangToAzure($tessParts[0]);
            if ($a !== null) {
                $azure = $a;
            }
        }

        $ocrspace = $tessParts[0] ?? 'eng';
        $ocrspaceMap = [
            'chi_sim' => 'chs', 'chi_tra' => 'cht', 'jpn' => 'jpn', 'kor' => 'kor', 'ara' => 'ara',
            'rus' => 'rus', 'hin' => 'hin', 'ben' => 'ben', 'tam' => 'tam', 'tel' => 'tel',
        ];
        $ocrspace = $ocrspaceMap[$ocrspace] ?? $ocrspace;

        return [
            'tesseract' => $tesseract,
            'google_hints' => array_values(array_unique($googleHints)),
            'azure' => $azure,
            'ocrspace' => $ocrspace,
        ];
    }

}