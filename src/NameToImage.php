<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\CustomException;

/**
 * Generate initial-based avatar images similar to Google profile placeholders.
 *
 * Features:
 * - Shape: circle or rounded-rectangle ("radius")
 * - Initials: first letters from two words; or first two letters if only one word
 * - Custom background and text color
 * - Output: save to file, stream to browser (inline or download), or return as data URI
 */
class NameToImage
{
    /**
     * Create an avatar image based on a name or text.
     *
     * Options keys:
     * - name: string (required)
     * - size: int (square dimension in px, default 256)
     * - type: string ('circle' | 'radius') default 'circle'
     * - radius: int (corner radius for 'radius' shape) default size/6
     * - bg_color: string|array (hex '#RRGGBB'|'#RGB'|'rgb(r,g,b)'|[r,g,b]) default '#4A5568'
     * - text_color: string|array default '#FFFFFF'
     * - font_path: string|null (path to a TTF font). If null/unreadable, falls back to GD built-in font.
     * - font_size: int|null (auto-calculated when using TTF)
     * - output: string ('save'|'view'|'download'|'data') default 'save'
     * - destination: string (required only when output='save')
     *
     * @param array $options
     * @return string|null  Returns destination path for 'save', data URI for 'data', null when streaming
     * @throws Exception
     */
    public static function create(array $options = [])
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new CustomException('GD library is required (imagecreatetruecolor missing).');
        }

        $opts = array_merge([
            'name'        => '',
            'size'        => 256,
            'type'        => 'circle', // 'circle' | 'radius'
            'radius'      => null,     // default computed: size/6
            'bg_color'    => [147, 51, 234],
            'text_color'  => '#FFFFFF',
            'font_path'   => null,
            'font_size'   => null,     // auto-fit by default
            'output'      => 'save',   // 'save' | 'view' | 'download' | 'data'
            'destination' => null,     // file path or directory; if directory, slug.png will be appended
            'regenerate'  => false,    // when true, append a unique suffix to filename
        ], $options);

        $name = trim((string)$opts['name']);
        if ($name === '') {
            throw new CustomException('Option "name" is required.');
        }

        $size = max(32, (int)$opts['size']);
        $radius = $opts['radius'] !== null ? (int)$opts['radius'] : max(4, (int)round($size / 6));
        $type = strtolower((string)$opts['type']);
        if (!in_array($type, ['circle', 'radius'], true)) {
            $type = 'circle';
        }

        [$br, $bg, $bt] = [
            self::normalizeColor($opts['bg_color']),
            null, // GD color allocate later
            self::normalizeColor($opts['text_color'])
        ];

        // Parse possible 'px' in font_size and normalize to int when provided
        if ($opts['font_size'] !== null && is_string($opts['font_size'])) {
            if (preg_match('/^(\d+)\s*px$/i', trim($opts['font_size']), $m)) {
                $opts['font_size'] = (int)$m[1];
            } elseif (is_numeric($opts['font_size'])) {
                $opts['font_size'] = (int)$opts['font_size'];
            } else {
                $opts['font_size'] = null; // fallback to auto-fit
            }
        }

        // Prepare canvas with transparency
        $img = imagecreatetruecolor($size, $size);
        if (!$img) {
            throw new CustomException('Unable to create image resource.');
        }
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);

        // Allocate colors
        $bgCol = imagecolorallocate($img, $br[0], $br[1], $br[2]);
        $txCol = imagecolorallocate($img, $bt[0], $bt[1], $bt[2]);

        // Draw background shape
        if ($type === 'circle') {
            imagefilledellipse($img, (int)($size / 2), (int)($size / 2), $size, $size, $bgCol);
        } else {
            self::imageFilledRoundedRect($img, 0, 0, $size - 1, $size - 1, $radius, $bgCol);
        }

        // Compute initials
        $initials = self::computeInitials($name);

        // Render text (TTF preferred)
        $fontPath = self::resolveFontPath($opts['font_path']);
        $useTtf = $fontPath !== null && function_exists('imagettftext');

        if ($useTtf) {
            // Auto-fit font size if not provided to fill with padding
            $len = max(1, mb_strlen($initials, 'UTF-8'));
            $fontSize = is_int($opts['font_size']) ? $opts['font_size'] : null;
            if ($fontSize === null) {
                // Target area with padding (10% each side) for a fuller fit
                $padding = (int)round($size * 0.10);
                $targetW = $size - 2 * $padding;
                $targetH = $size - 2 * $padding;
                // Binary search a font size that fits both width and height
                $low = 8; $high = (int)round($size * ($len === 1 ? 1.2 : 1.0));
                $best = $low;
                while ($low <= $high) {
                    $mid = (int)floor(($low + $high) / 2);
                    [$w, $h] = self::measureText($initials, $mid, $fontPath);
                    if ($w <= $targetW && $h <= $targetH) {
                        $best = $mid;
                        $low = $mid + 1; // try larger
                    } else {
                        $high = $mid - 1; // try smaller
                    }
                }
                $fontSize = max(8, $best);
            }

            // Calculate bounding box to center text precisely (including negative offsets)
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $initials);
            $textWidth  = abs($bbox[2] - $bbox[0]);
            $textHeight = abs($bbox[7] - $bbox[1]);
            // Centering with bbox offsets
            $x = (int)round(($size - $textWidth) / 2 - min($bbox[0], $bbox[2]));
            $y = (int)round(($size - $textHeight) / 2 + $textHeight - max($bbox[1], $bbox[7]));

            imagettftext($img, $fontSize, 0, $x, $y, $txCol, $fontPath, $initials);
        } else {
            // Fallback: built-in font
            $font = 5; // largest built-in font
            $textWidth = imagefontwidth($font) * strlen($initials);
            $textHeight = imagefontheight($font);
            $x = (int)(($size - $textWidth) / 2);
            $y = (int)(($size - $textHeight) / 2);
            imagestring($img, $font, $x, $y, $initials, $txCol);
        }

        // Output handling
        $output = strtolower((string)$opts['output']);
        switch ($output) {
            case 'view':
            case 'download':
                // Stream to browser
                if (!headers_sent()) {
                    header('Content-Type: image/png');
                    if ($output === 'download') {
                        $fname = self::sanitizeFilename($name) . '.png';
                        header('Content-Disposition: attachment; filename="' . $fname . '"');
                    } else {
                        header('Content-Disposition: inline');
                    }
                }
                imagepng($img);
                imagedestroy($img);
                return null;

            case 'data':
                ob_start();
                imagepng($img);
                $bin = ob_get_clean();
                imagedestroy($img);
                return 'data:image/png;base64,' . base64_encode($bin ?: '');

            case 'save':
            default:
                $dest = (string)($opts['destination'] ?? '');
                $slug = self::sanitizeFilename($name);
                // If destination is empty, or a directory, or ends without .png, build the final path
                if ($dest === '' || is_dir($dest) || !preg_match('/\.png$/i', $dest)) {
                    $baseDir = $dest !== '' ? rtrim($dest, "\\/") : (__DIR__ . '/../storage/avatars');
                    // Append slug with optional regenerate suffix
                    $suffix = !empty($opts['regenerate']) ? ('-' . substr(sha1(uniqid((string)mt_rand(), true)), 0, 8)) : '';
                    $dest = $baseDir . '/' . $slug . $suffix . '.png';
                } else {
                    // If regenerate requested for a full file path, inject suffix before extension
                    if (!empty($opts['regenerate'])) {
                        $dest = preg_replace('/\.png$/i', '-' . substr(sha1(uniqid((string)mt_rand(), true)), 0, 8) . '.png', $dest);
                    }
                }

                // Ensure directory exists
                File::makeDirectory(dirname($dest));

                imagepng($img, $dest);
                imagedestroy($img);
                return $dest;
        }
    }

    /**
     * Compute initials from a name:
     * - If 2+ words: take first letter of the first two words
     * - Else: take first two letters of the single word
     */
    private static function computeInitials(string $name): string
    {
        // Split on any non-letter/digit as separator, keep unicode letters
        $parts = preg_split('/[^\p{L}\p{N}]+/u', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, static fn($p) => $p !== ''));

        if (count($parts) >= 2) {
            $a = mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'), 'UTF-8');
            $b = mb_strtoupper(mb_substr($parts[1], 0, 1, 'UTF-8'), 'UTF-8');
            return $a . $b;
        }

        $w = $parts[0] ?? '';
        $firstTwo = mb_strtoupper(mb_substr($w, 0, 2, 'UTF-8'), 'UTF-8');
        return $firstTwo !== '' ? $firstTwo : 'NA';
    }

    /**
     * Convert color input to [r,g,b]. Accepts '#RRGGBB', '#RRGGBBAA', '#RGB', 'rgb(r,g,b)', 'rgba(r,g,b,a)', or [r,g,b].
     * Alpha is ignored (GD fill uses full opacity for shapes).
     * @param string|array $color
     * @return array{0:int,1:int,2:int}
     */
    private static function normalizeColor($color): array
    {
        if (is_array($color) && count($color) >= 3) {
            return [
                max(0, min(255, (int)$color[0])),
                max(0, min(255, (int)$color[1])),
                max(0, min(255, (int)$color[2])),
            ];
        }

        if (is_string($color)) {
            $c = trim($color);
            // #RGB
            if (preg_match('/^#([0-9a-f]{3})$/i', $c, $m)) {
                $hex = $m[1];
                $r = hexdec(str_repeat($hex[0], 2));
                $g = hexdec(str_repeat($hex[1], 2));
                $b = hexdec(str_repeat($hex[2], 2));
                return [$r, $g, $b];
            }
            // #RRGGBB
            if (preg_match('/^#([0-9a-f]{6})$/i', $c, $m)) {
                $hex = $m[1];
                return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
            }
            // #RRGGBBAA (ignore AA)
            if (preg_match('/^#([0-9a-f]{8})$/i', $c, $m)) {
                $hex = $m[1];
                return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
            }
            // rgb(r,g,b)
            if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $c, $m)) {
                return [
                    max(0, min(255, (int)$m[1])),
                    max(0, min(255, (int)$m[2])),
                    max(0, min(255, (int)$m[3])),
                ];
            }
            // rgba(r,g,b,a) -> ignore a
            if (preg_match('/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(?:\d*\.?\d+)\s*\)$/i', $c, $m)) {
                return [
                    max(0, min(255, (int)$m[1])),
                    max(0, min(255, (int)$m[2])),
                    max(0, min(255, (int)$m[3])),
                ];
            }
        }

        // Fallback to dark gray
        return [74, 85, 104];
    }

    /**
     * Draw a filled rounded rectangle using basic GD primitives.
     */
    private static function imageFilledRoundedRect($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        $w = $x2 - $x1 + 1;
        $h = $y2 - $y1 + 1;
        $r = max(0, min((int)floor(min($w, $h) / 2), $radius));

        // Center rectangle
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);

        // Corners as filled quarters of ellipses
        imagefilledellipse($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color); // TL
        imagefilledellipse($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color); // TR
        imagefilledellipse($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color); // BL
        imagefilledellipse($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color); // BR
    }

    /**
     * Basic filename sanitizer for default destination names.
     */
    private static function sanitizeFilename(string $name): string
    {
        $n = preg_replace('/[^\p{L}\p{N}\-_.]+/u', '-', trim($name));
        $n = preg_replace('/-+/', '-', (string)$n);
        $n = trim((string)$n, '-');
        return $n !== '' ? $n : 'avatar';
    }

    /**
     * Try to resolve a readable TTF font path. Use provided path if valid; otherwise try common system fonts.
     * Returns null if none found.
     */
    private static function resolveFontPath(?string $path): ?string
    {
        $candidates = [];
        if (is_string($path) && $path !== '' && is_readable($path)) {
            $candidates[] = $path;
        }
        // Common Windows fonts
        $winFonts = [
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\segoeuib.ttf',
            'C:\\Windows\\Fonts\\segoeui.ttf',
        ];
        $unixFonts = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/Library/Fonts/Arial Bold.ttf',
            '/Library/Fonts/Arial.ttf',
        ];
        $candidates = array_merge($candidates, $winFonts, $unixFonts);
        foreach ($candidates as $cand) {
            if (is_readable($cand)) {
                return $cand;
            }
        }
        return null;
    }

    /**
     * Measure TTF text size (width, height) using imagettfbbox, handling negative coordinates.
     * @return array{0:int,1:int}
     */
    private static function measureText(string $text, int $fontSize, string $fontPath): array
    {
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $width  = (int)abs($bbox[2] - $bbox[0]);
        $height = (int)abs($bbox[7] - $bbox[1]);
        return [$width, $height];
    }
}