<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\TameTrait;


trait FontPathTrait{

    use TameTrait;

    static private $fontNonLatin = [
        'bold' => 'NotoSansSC-Medium.ttf', // NotoSansSC-Bold.ttf
        'medium' => 'NotoSansSC-Medium.ttf',
    ];

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
        $isBold = $weight === 'bold';

        // If user provided a readable path, use it as-is
        if (is_string($path) && $path !== '' && @is_readable($path)) {
            return $path;
        }

        $isUnicode = self::needsUnicodeFont($textForFont);

        $bundledSc = self::firstReadableBundled(
            $isBold ? self::$fontNonLatin['bold'] : self::$fontNonLatin['medium']
        );
        $bundledLatin = self::firstReadableBundled($isBold ? 'Inter-Bold.ttf' : 'Inter-Medium.ttf');

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
            if (self::textContainsCjkScript($textForFont) && $bundledSc !== null) {
                return $bundledSc;
            }
        }

        if ($bundledLatin !== null) {
            return $bundledLatin;
        }

        foreach (self::systemFallbackOrdered($weight, $isUnicode) as $cand) {
            if (@is_readable($cand)) {
                return $cand;
            }
        }

        // Optional fonts in Traits/icons/fonts/ when the OS has nothing readable
        $bundled = self::firstReadableFont(self::bundledIconsFontFallbacks($weight, $textForFont));
        if ($bundled !== null) {
            return $bundled;
        }

        return null;
    }

    /**
     * Packaged font directories: preferred {@see Traits/icons/fonts}, alternate {@see src/icons/fonts}.
     *
     * @return list<string>
     */
    private static function iconsFontsDirectories(): array
    {
        $primary = self::stringReplacer(__DIR__ . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR);
        $alternate = self::stringReplacer(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR);

        return $primary === $alternate ? [$primary] : [$primary, $alternate];
    }

    /**
     * @return list<string>
     */
    private static function bundledRelativePaths(string $relative): array
    {
        $paths = [];
        foreach (self::iconsFontsDirectories() as $d) {
            $paths[] = $d . $relative;
        }

        return $paths;
    }

    private static function firstReadableBundled(string $relative): ?string
    {
        foreach (self::bundledRelativePaths($relative) as $p) {
            if (@is_readable($p)) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Bundled fallbacks under Traits/icons/fonts/ when no system font is readable.
     *
     * These are NOT OS defaults — ship them with your app if you need consistent results everywhere:
     * - Inter + NotoSansSC (Latin + Simplified Chinese) — typical minimum
     * - Optional: NotoSansCJK-{Regular,Bold}.ttc (Google Noto CJK, one file ~70MB+), NotoNaskhArabic, unifont.ttf
     *
     * @return list<string>
     */
    private static function bundledIconsFontFallbacks(string $weight, string $textForFont): array
    {
        $bold = $weight === 'bold';
        $out = [];

        foreach (self::iconsFontsDirectories() as $d) {
            if (self::textContainsCjkScript($textForFont)) {
                $out[] = $d . ($bold ? 'NotoSansCJK-Bold.ttc' : 'NotoSansCJK-Regular.ttc');
                $out[] = $d . ($bold ? self::$fontNonLatin['bold'] : self::$fontNonLatin['medium']);
            }

            if (self::textContainsArabicScript($textForFont)) {
                foreach (($bold
                    ? ['NotoNaskhArabic-Bold.ttf', 'NotoSansArabic-Bold.ttf']
                    : ['NotoNaskhArabic-Regular.ttf', 'NotoSansArabic-Regular.ttf']) as $f) {
                    $out[] = $d . $f;
                }
            }

            if (self::textContainsHebrewScript($textForFont)) {
                $out[] = $d . ($bold ? 'NotoSansHebrew-Bold.ttf' : 'NotoSansHebrew-Regular.ttf');
            }

            if (self::textContainsThaiScript($textForFont)) {
                $out[] = $d . ($bold ? 'NotoSansThai-Bold.ttf' : 'NotoSansThai-Regular.ttf');
            }

            if (self::textContainsDevanagariScript($textForFont)) {
                $out[] = $d . ($bold ? 'NotoSansDevanagari-Bold.ttf' : 'NotoSansDevanagari-Regular.ttf');
            }

            foreach (['unifont.ttf', 'Unifont.ttf', 'GNUUnifont.ttf'] as $f) {
                $out[] = $d . $f;
            }

            foreach (($bold
                ? ['NotoSans-Bold.ttf', 'NotoSans-SemiBold.ttf', 'NotoSans-Medium.ttf']
                : ['NotoSans-Regular.ttf', 'NotoSans-Medium.ttf']) as $f) {
                $out[] = $d . $f;
            }

            $out[] = $d . ($bold ? 'Inter-Bold.ttf' : 'Inter-Medium.ttf');
        }

        return $out;
    }

    /**
     * OS-installed font paths (optional; may be missing on Docker/minimal images).
     *
     * @return list<string>
     */
    private static function systemFallbackOrdered(string $weight, bool $unicodeFirst): array
    {
        $bold = $weight === 'bold';

        $unicodeBold = [
            'C:\\Windows\\Fonts\\msyhbd.ttf',
            'C:\\Windows\\Fonts\\simhei.ttf',
            'C:\\Windows\\Fonts\\simsun.ttc',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Bold.ttc',
            '/usr/share/fonts/truetype/noto/NotoSansCJK-Bold.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
            '/Library/Fonts/PingFang.ttc',
            '/System/Library/Fonts/PingFang.ttc',
        ];

        $unicodeRegular = [
            'C:\\Windows\\Fonts\\msyh.ttf',
            'C:\\Windows\\Fonts\\simsun.ttc',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
            '/Library/Fonts/PingFang.ttc',
            '/System/Library/Fonts/PingFang.ttc',
        ];

        $winFontsBold = ['C:\\Windows\\Fonts\\arialbd.ttf', 'C:\\Windows\\Fonts\\segoeuib.ttf'];
        $winFontsRegular = ['C:\\Windows\\Fonts\\arial.ttf', 'C:\\Windows\\Fonts\\segoeui.ttf'];
        $unixFontsBold = ['/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', '/Library/Fonts/Arial Bold.ttf'];
        $unixFontsRegular = ['/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', '/Library/Fonts/Arial.ttf'];

        $unicodeOrdered = $bold
            ? array_merge($unicodeBold, $unicodeRegular)
            : array_merge($unicodeRegular, $unicodeBold);

        $latinOrdered = $bold
            ? array_merge($winFontsBold, $unixFontsBold, $winFontsRegular, $unixFontsRegular)
            : array_merge($winFontsRegular, $unixFontsRegular, $winFontsBold, $unixFontsBold);

        return $unicodeFirst
            ? array_merge($unicodeOrdered, $latinOrdered)
            : array_merge($latinOrdered, $unicodeOrdered);
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
        $bundled = $bold
            ? array_merge(
                self::bundledRelativePaths('NotoNaskhArabic-Bold.ttf'),
                self::bundledRelativePaths('NotoSansArabic-Bold.ttf')
            )
            : array_merge(
                self::bundledRelativePaths('NotoNaskhArabic-Regular.ttf'),
                self::bundledRelativePaths('NotoSansArabic-Regular.ttf')
            );

        return $bold ? array_merge($bundled, [
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
        ]) : array_merge($bundled, [
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
        ]);
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

}