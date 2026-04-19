<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;

/**
 * OCR engine language token parsing and per-vendor expansion (ImageToText, etc.).
 */
trait OcrLanguageTrait
{
    /**
     * True if $text contains at least one Unicode emoji / extended pictographic character.
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
     */
    public static function tesseractLangToAzure(string $tessLang): ?string
    {
        $g = self::tesseractLangToGoogleBcp47($tessLang);
        if ($g === null) {
            return null;
        }
        static $azureSpecial = [
            'fil' => 'fil',
        ];

        return $azureSpecial[$g] ?? $g;
    }

    /**
     * Build per-engine language settings from a user-facing language string.
     * Empty / "auto" → broad autodetect hints for cloud OCR; Tesseract uses empty string (see ImageToText tessdata probe).
     *
     * @param bool $emojiFriendly Ignored (backward compatibility); language hints no longer depend on it.
     * @return array{tesseract:string,google_hints:list<string>,azure:string,ocrspace:string}
     */
    public static function expandOcrLanguageForEngines(string $language, bool $emojiFriendly = false): array
    {
        $tokens = self::parseOcrLanguageTokens($language);
        if ($tokens === []) {
            // Do not add 'und' for emoji_friendly — it biases cloud OCR away from ar/zh/en and garbles mixed text.
            $hints = ['en', 'zh-Hans', 'zh-Hant', 'ja', 'ko', 'ar', 'th', 'hi'];

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
        // Han / CJK scripts need Latin alongside for mixed charts (e.g. 诶 + A).
        static $cjkTess = ['chi_sim', 'chi_tra', 'jpn', 'kor'];
        if (array_intersect($tessParts, $cjkTess) !== [] && !in_array('eng', $tessParts, true)) {
            $tessParts[] = 'eng';
        }
        $tesseract = implode('+', $tessParts);

        $googleHints = [];
        foreach ($tessParts as $tp) {
            $bcp = self::tesseractLangToGoogleBcp47($tp);
            if ($bcp !== null && !in_array($bcp, $googleHints, true)) {
                $googleHints[] = $bcp;
            }
        }
        $azure = 'unk';
        if (count($tessParts) === 1) {
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
