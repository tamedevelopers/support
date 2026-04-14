<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\TameTrait;


trait FontPathTrait{

    use TameTrait;
    

    /**
     * Try to resolve a readable TTF/TTC font path. Use provided path if valid; otherwise try system fonts.
     * When $textForFont contains non-ASCII (CJK, Arabic, etc.), Unicode/CJK-capable fonts are tried first.
     * Returns null if none found.
     * 
     * @param string $path
     * @param string $weight (Default is 'bold')
     * @param string $textForFont (Default is '')
     * @return null|string
     */
    public static function resolveFontPath($path = null, $weight = null, $textForFont = null)
    {
        if(empty($textForFont)){
            $textForFont = '';
        }

        $weight = Str::lower($weight);
        $weight = in_array($weight, ['normal', 'bold'], true) ? $weight : 'bold';

        // If user provided a readable path, use it as-is
        if (is_string($path) && $path !== '' && @is_readable($path)) {
            return $path;
        }
        
        // 2. Check if text contains CJK or other non-ASCII characters
        $isUnicode = self::needsUnicodeFont($textForFont);
        
        // creating 
        $path = self::stringReplacer( __DIR__  . DIRECTORY_SEPARATOR);

        // Define your bundled paths
        $bundledUnicode = "{$path}icons/fonts/" . ($weight === 'bold' ? 'NotoSansSC-Bold.ttf' : 'NotoSansSC-Medium.ttf');
        $bundledLatin = "{$path}icons/fonts/" . ($weight === 'bold' ? 'Inter-Bold.ttf' : 'Inter-Medium.ttf');
        
        if ($isUnicode && file_exists($bundledUnicode)) {
            return $bundledUnicode;
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