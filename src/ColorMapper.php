<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;


class ColorMapper
{

    /**
     * Map a scraped color string to an existing standard color name.
     *
     * @param string|null $scrapedColor
     * @param string $default
     * @return string
     */
    public static function match(?string $scrapedColor, string $default = 'Neutral'): string
    {
        if (empty($scrapedColor)) {
            return $default;
        }

        // Clean up the string (lowercase, strip extra spaces)
        $scrapedColor = strtolower(trim($scrapedColor));

        // 1. Direct Match Check
        foreach (self::colorMaps() as $standardColor => $variants) {
            foreach ($variants as $variant) {
                if (str_contains($scrapedColor, $variant)) {
                    return ucfirst($standardColor); // Returns 'Red', 'Black', etc.
                }
            }
        }

        // 2. Optional: Basic Hex Code handling (e.g., #FFFFFF -> White)
        if (preg_match('/^#?([a-f0-9]{6}|[a-f0-9]{3})$/i', $scrapedColor)) {
            return self::matchHexToName($scrapedColor) ?? $default;
        }

        // Fallback if no rules matched, return the cleaned raw string capitalized
        // or just return your default 'Neutral'
        return ucfirst($scrapedColor); 
    }

    /**
     * Very basic hex approximation (optional expansion)
     */
    protected static function matchHexToName(string $hex): ?string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Simple threshold rules
        if ($r > 220 && $g > 220 && $b > 220) return 'White';
        if ($r < 45 && $g < 45 && $b < 45) return 'Black';
        if ($r > 150 && $g < 50 && $b < 50) return 'Red';
        if ($b > 150 && $r < 50 && $g < 50) return 'Blue';
        if ($g > 150 && $r < 50 && $b < 50) return 'Green';

        return null;
    }

    /**
     * A mapping dictionary of common variant keywords to standard existing color names
     */
    protected static function colorMaps(): array
    {
        return [
            'black'  => ['black', 'midnight', 'obsidian', 'onyx', 'charcoal'],
            'white'  => ['white', 'ivory', 'cream', 'snow', 'alabaster'],
            'red'    => ['red', 'crimson', 'scarlet', 'ruby', 'wine', 'maroon'],
            'blue'   => ['blue', 'navy', 'cyan', 'indigo', 'azure', 'sapphire'],
            'green'  => ['green', 'emerald', 'olive', 'mint', 'lime', 'sage'],
            'yellow' => ['yellow', 'gold', 'lemon', 'mustard'],
            'orange' => ['orange', 'tangerine', 'coral', 'peach'],
            'purple' => ['purple', 'violet', 'plum', 'lavender', 'magenta'],
            'pink'   => ['pink', 'rose', 'fuchsia'],
            'brown'  => ['brown', 'chocolate', 'tan', 'beige', 'khaki'],
            'grey'   => ['grey', 'gray', 'silver', 'slate'],
        ];
    }

}