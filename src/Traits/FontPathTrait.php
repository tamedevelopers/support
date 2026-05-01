<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\TameTrait;


trait FontPathTrait{

    use TameTrait;

    /**
     * Single bundled Regular/Medium faces for SC (bold request uses faux bold in {@see TextToImage}).
     *
     * @return list<string>
     */
    private static function bundledCjkFilenames(): array
    {
        // DroidSansFallback: ~4MB TTF with broad CJK (GD-friendly). Prefer before huge NotoSansSC.
        return [
            'DroidSansFallback.ttf',
            'NotoSansSC-Medium.ttf',
            'NotoSansSC-Regular.ttf',
        ];
    }

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
        return self::resolveFontPathWithMeta($path, $weight, $textForFont)[0];
    }

    /**
     * Like {@see resolveFontPath} but exposes whether callers should emulate bold when only a Regular/Medium file is bundled.
     *
     * @return array{0: null|string, 1: bool} [fontPath, useSyntheticBold]
     */
    public static function resolveFontPathWithMeta($path = null, $weight = null, $textForFont = null): array
    {
        if (empty($textForFont)) {
            $textForFont = '';
        }

        $weight = Str::lower((string) $weight);
        $weight = in_array($weight, ['normal', 'bold'], true) ? $weight : 'bold';
        $isBoldRequest = $weight === 'bold';

        // If user provided a readable path, use it as-is
        if (is_string($path) && $path !== '' && @is_readable($path)) {
            return [$path, false];
        }

        $isUnicode = self::needsUnicodeFont($textForFont);

        $bundledSc = self::firstReadableBundledCandidates(self::bundledCjkFilenames());
        $bundledLatin = self::firstReadableBundled($isBoldRequest ? 'Inter-Bold.ttf' : 'Inter-Medium.ttf');

        // Script-specific fonts (NotoSansSC does not cover Arabic — bundle NotoSansArabic* etc.)
        if ($isUnicode) {
            if (self::textContainsArabicScript($textForFont) && self::textContainsCjkScript($textForFont)) {
                $resolved = self::firstReadableFont(self::panUnicodeFontCandidates($weight));
                if ($resolved !== null) {
                    return [$resolved, false];
                }
            }
            if (self::textContainsArabicScript($textForFont)) {
                $resolved = self::firstReadableFont(self::arabicFontCandidates($weight));
                if ($resolved !== null) {
                    return [
                        $resolved,
                        self::shouldUseSyntheticBoldForResolvedPath($isBoldRequest, $resolved),
                    ];
                }
            }
            if (self::textContainsHebrewScript($textForFont)) {
                $resolved = self::firstReadableFont(self::hebrewFontCandidates($weight));
                if ($resolved !== null) {
                    return [
                        $resolved,
                        self::shouldUseSyntheticBoldForResolvedPath($isBoldRequest, $resolved),
                    ];
                }
            }
            if (self::textContainsThaiScript($textForFont)) {
                $resolved = self::firstReadableFont(self::thaiFontCandidates($weight));
                if ($resolved !== null) {
                    return [
                        $resolved,
                        self::shouldUseSyntheticBoldForResolvedPath($isBoldRequest, $resolved),
                    ];
                }
            }
            if (self::textContainsDevanagariScript($textForFont)) {
                $resolved = self::firstReadableFont(self::devanagariFontCandidates($weight));
                if ($resolved !== null) {
                    return [
                        $resolved,
                        self::shouldUseSyntheticBoldForResolvedPath($isBoldRequest, $resolved),
                    ];
                }
            }
            if (self::textContainsGeorgianScript($textForFont)) {
                $resolved = self::firstReadableFont(self::georgianFontCandidates());
                if ($resolved !== null) {
                    return [
                        $resolved,
                        self::shouldUseSyntheticBoldForResolvedPath($isBoldRequest, $resolved),
                    ];
                }
            }
            if (self::textContainsCjkScript($textForFont) && $bundledSc !== null) {
                return [
                    $bundledSc,
                    self::shouldUseSyntheticBoldForResolvedPath($isBoldRequest, $bundledSc),
                ];
            }
            // Cyrillic/Greek/other non-Latin — never NotoSans-Regular for CJK (it has no Han / poor CJK coverage).
            if (! self::textContainsCjkScript($textForFont)) {
                $bundledSans = self::firstReadableBundled('NotoSans-Regular.ttf');
                if ($bundledSans !== null) {
                    return [
                        $bundledSans,
                        self::shouldUseSyntheticBoldForResolvedPath($isBoldRequest, $bundledSans),
                    ];
                }
            }
        }

        if ($bundledLatin !== null) {
            return [$bundledLatin, false];
        }

        foreach (self::systemFallbackOrdered($weight, $isUnicode) as $cand) {
            if (@is_readable($cand)) {
                return [$cand, false];
            }
        }

        // Optional fonts in Traits/icons/fonts/ when the OS has nothing readable
        $bundled = self::firstReadableFont(self::bundledIconsFontFallbacks($weight, $textForFont));
        if ($bundled !== null) {
            return [
                $bundled,
                self::shouldUseSyntheticBoldForResolvedPath($isBoldRequest, $bundled),
            ];
        }

        return [null, false];
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

    /**
     * @param list<string> $relativeNames basename order (first match wins)
     */
    private static function firstReadableBundledCandidates(array $relativeNames): ?string
    {
        foreach ($relativeNames as $rel) {
            $found = self::firstReadableBundled($rel);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
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
     * True when bundled face is intentionally single-weight Regular/Medium and bold was requested.
     */
    private static function shouldUseSyntheticBoldForResolvedPath(bool $isBoldRequest, ?string $path): bool
    {
        return $isBoldRequest && $path !== null && self::bundledRegularFaceBasename(Str::lower(basename($path)));
    }

    /**
     * @return list<string> lower-case basenames of bundled Regular/Medium files (not separate Bold.otf).
     */
    private static function syntheticBoldBundledBasenames(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $names = [];
        foreach (array_merge(
            [
                'NotoSansArabicUI-Regular.ttf',
                'NotoSansArabic-Regular.ttf',
                'NotoNaskhArabic-Regular.ttf',
                'NotoSansThaiUI-Regular.ttf',
                'NotoSansThai-Regular.ttf',
                'NotoSansHebrew-Regular.ttf',
                'NotoSansGeorgian-Regular.ttf',
                'NotoSansDevanagari-Regular.ttf',
                'NotoSans-Regular.ttf',
            ],
            self::bundledCjkFilenames(),
        ) as $f) {
            $names[] = Str::lower($f);
        }
        foreach (['NotoSansArabicUI-Regular.otf', 'NotoSansArabic-Regular.otf'] as $f) {
            $names[] = Str::lower($f);
        }

        $cached = $names;

        return $cached;
    }

    private static function bundledRegularFaceBasename(string $lowerBasename): bool
    {
        return in_array($lowerBasename, self::syntheticBoldBundledBasenames(), true)
            || str_contains($lowerBasename, 'unifont');
    }

    /**
     * @return list<string>
     */
    private static function georgianFontCandidates(): array
    {
        $bundled = self::bundledRelativePaths('NotoSansGeorgian-Regular.ttf');

        return array_merge($bundled, [
            'C:\\Windows\\Fonts\\sylfaen.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansGeorgian-Regular.ttf',
        ]);
    }

    /**
     * Bundled fallbacks under Traits/icons/fonts/ when no system font is readable.
     *
     * These are NOT OS defaults — ship them with your app if you need consistent results everywhere:
     * - Inter + DroidSansFallback.ttf (compact CJK) or NotoSansSC + bundled Noto for other scripts; faux bold in renderer when needed
     * - Optional: NotoSansCJK-{Regular,Bold}.ttc (large), separate Bold.ttf per script if you dislike synthetic bold
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
                foreach (($bold ? array_merge(self::bundledCjkFilenames(), ['NotoSansSC-Bold.ttf']) : self::bundledCjkFilenames()) as $cjkFace) {
                    $out[] = $d . $cjkFace;
                }
            }

            if (self::textContainsArabicScript($textForFont)) {
                foreach (($bold ? array_merge(
                    ['NotoSansArabicUI-Regular.ttf', 'NotoSansArabic-Regular.ttf', 'NotoNaskhArabic-Regular.ttf'],
                    ['NotoNaskhArabic-Bold.ttf', 'NotoSansArabic-Bold.ttf']
                ) : [
                    'NotoSansArabicUI-Regular.ttf',
                    'NotoSansArabic-Regular.ttf',
                    'NotoNaskhArabic-Regular.ttf',
                ]) as $f) {
                    $out[] = $d . $f;
                }
            }

            if (self::textContainsHebrewScript($textForFont)) {
                foreach (($bold ? [
                    'NotoSansHebrew-Regular.ttf',
                    'NotoSansHebrew-Bold.ttf',
                ] : ['NotoSansHebrew-Regular.ttf']) as $f) {
                    $out[] = $d . $f;
                }
            }

            if (self::textContainsThaiScript($textForFont)) {
                foreach (($bold ? array_merge(
                    ['NotoSansThaiUI-Regular.ttf', 'NotoSansThai-Regular.ttf'],
                    ['NotoSansThai-Bold.ttf']
                ) : ['NotoSansThaiUI-Regular.ttf', 'NotoSansThai-Regular.ttf']) as $f) {
                    $out[] = $d . $f;
                }
            }

            if (self::textContainsDevanagariScript($textForFont)) {
                foreach (($bold ? [
                    'NotoSansDevanagari-Regular.ttf',
                    'NotoSansDevanagari-Bold.ttf',
                ] : ['NotoSansDevanagari-Regular.ttf']) as $f) {
                    $out[] = $d . $f;
                }
            }

            foreach (['unifont.ttf', 'Unifont.ttf', 'GNUUnifont.ttf'] as $f) {
                $out[] = $d . $f;
            }

            foreach (($bold ? array_merge(
                ['NotoSans-Regular.ttf', 'NotoSans-Medium.ttf'],
                ['NotoSans-Bold.ttf', 'NotoSans-SemiBold.ttf']
            ) : ['NotoSans-Regular.ttf', 'NotoSans-Medium.ttf']) as $f) {
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
        $bundledRegularUi = self::bundledRelativePaths('NotoSansArabicUI-Regular.ttf');
        $bundledRegular = array_merge(
            self::bundledRelativePaths('NotoSansArabic-Regular.ttf'),
            self::bundledRelativePaths('NotoNaskhArabic-Regular.ttf'),
        );
        $bundledBold = array_merge(
            self::bundledRelativePaths('NotoSansArabic-Bold.ttf'),
            self::bundledRelativePaths('NotoNaskhArabic-Bold.ttf'),
        );
        $bundled = $bold
            ? array_merge($bundledRegularUi, $bundledRegular, $bundledBold)
            : array_merge($bundledRegularUi, $bundledRegular);

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
        $bundledReg = self::bundledRelativePaths('NotoSansHebrew-Regular.ttf');

        return $bold ? array_merge($bundledReg, [
            'C:\\Windows\\Fonts\\davidbd.ttf',
            'C:\\Windows\\Fonts\\segoeuib.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansHebrew-Bold.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansHebrew-Regular.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
        ]) : array_merge($bundledReg, [
            'C:\\Windows\\Fonts\\david.ttf',
            'C:\\Windows\\Fonts\\segoeui.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansHebrew-Regular.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
        ]);
    }

    /**
     * @return list<string>
     */
    private static function thaiFontCandidates(string $weight): array
    {
        $bold = $weight === 'bold';
        $bundledUi = self::bundledRelativePaths('NotoSansThaiUI-Regular.ttf');
        $bundledReg = array_merge(
            self::bundledRelativePaths('NotoSansThai-Regular.ttf'),
        );

        return $bold ? array_merge($bundledUi, $bundledReg, [
            'C:\\Windows\\Fonts\\tahomabd.ttf',
            'C:\\Windows\\Fonts\\LeelawUI.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Bold.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
        ]) : array_merge($bundledUi, $bundledReg, [
            'C:\\Windows\\Fonts\\tahoma.ttf',
            'C:\\Windows\\Fonts\\LeelawUI.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
        ]);
    }

    /**
     * @return list<string>
     */
    private static function devanagariFontCandidates(string $weight): array
    {
        $bold = $weight === 'bold';
        $bundledReg = self::bundledRelativePaths('NotoSansDevanagari-Regular.ttf');

        return $bold ? array_merge($bundledReg, [
            'C:\\Windows\\Fonts\\mangalb.ttf',
            'C:\\Windows\\Fonts\\NirmalaB.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansDevanagari-Bold.ttf',
        ]) : array_merge($bundledReg, [
            'C:\\Windows\\Fonts\\mangal.ttf',
            'C:\\Windows\\Fonts\\Nirmala.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansDevanagari-Regular.ttf',
        ]);
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

    public static function textContainsGeorgianScript(string $text): bool
    {
        $text = Str::trim($text);

        return $text !== '' && (bool) preg_match('/\p{Georgian}/u', $text);
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