<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Tamedevelopers\Support\Asset;
use Tamedevelopers\Support\Capsule\CustomException;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\FontPathTrait;

/**
 * Generate initial-based avatar images similar to Google profile placeholders.
 *
 * Features:
 * - Shape: circle or rounded-rectangle ("radius")
 * - Initials: first character(s) from name – supports all languages (English, Chinese, Japanese,
 *   Arabic, etc.); two words → first char of each; one word → first two characters
 * - Font: automatically uses a Unicode/CJK-capable font when the name contains non-ASCII characters
 * - Custom background and text color
 * - Output: save to file, stream to browser (inline or download), or return as data URI
 */
class TextToImage
{
    use FontPathTrait;
    
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
     * - font_weight: string ('normal'|'bold') default 'normal' (applies when auto-resolving system font)
     * - output: string ('save'|'view'|'download'|'data') default 'save'
     * - destination: string (required only when output='save')
     * - generate: boolean (default false). When true, appends a unique suffix to filename to avoid overwriting.
     *
     * @param array $options
     * @return array Returns destination path for 'save', data URI for 'data', null when streaming
     * @throws Exception
     */
    public static function run(array $options = [])
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new CustomException('GD library is required (imagecreatetruecolor missing).');
        }

        $opts = array_merge([
            'name'        => '',
            'size'        => 256,
            'type'        => '', // circle|radius|square
            'radius'      => null,     // default computed: size/6
            'bg_color'    => '',
            'text_color'  => '',
            'font_path'   => null,
            'font_size'   => null,     // auto-fit by default
            'font_weight' => 'normal',   // 'normal' | 'bold' (used when auto-selecting system font)
            'output'      => 'save',   // 'save' | 'view' | 'download' | 'data'
            'destination' => null,     // file path or directory; if directory, slug.png will be appended
            'generate'  => false,    // when true, append a unique suffix to filename
        ], $options);

        // set default data
        if(empty($opts['size'])){
            $opts['size'] = 256;
        }
        if(empty($opts['type'])){
            $opts['type'] = 'square';
        }
        if(empty($opts['font_weight'])){
            $opts['font_weight'] = 'bold';
        }
        if(empty($opts['bg_color'])){
            $opts['bg_color'] = [147, 51, 234];
        }
        if(empty($opts['text_color'])){
            $opts['text_color'] = '#FFFFFF';
        }
        if(empty($opts['output'])){
            $opts['output'] = 'save';
        }

        $name = Str::trim($opts['name']);
        if ($name === '') {
            throw new CustomException('Option "name" is required.');
        }

        // Use first two letters as initials
        $name = self::collectFirstTwoLetters($name);


        $size = max(32, (int)$opts['size']);
        $radius = $opts['radius'] !== null ? (int) $opts['radius'] : max(4, (int)round($size / 6));
        $type = strtolower((string) $opts['type']);
        if (!in_array($type, ['circle', 'radius', 'square', 'gradient', 'diagonal'], true)) {
            $type = 'square';
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
        self::drawBackground($img, $type, $size, $bgCol, $br, $radius);

        // Compute initials (supports all scripts: Latin, CJK, Arabic, etc.)
        $initials = self::computeInitials($name);

        // Render text (TTF preferred); choose font that supports the script (e.g. CJK)
        $fontPath = self::resolveFontPath(
            $opts['font_path'],
            (string) ($opts['font_weight'] ?? 'bold'),
            $initials
        );
        $useTtf = $fontPath !== null && function_exists('imagettftext');

        if ($useTtf) {
            $len = max(1, mb_strlen($initials, 'UTF-8'));
            $fontSize = is_int($opts['font_size']) ? $opts['font_size'] : null;

            if ($fontSize === null) {
                // Circle/rounded shapes need stronger all-side padding due to curved corners.
                if (in_array($type, ['circle', 'radius'], true)) {
                    $contentFactorX = ($type === 'circle') ? 0.62 : 0.68;
                    $contentFactorY = ($type === 'circle') ? 0.62 : 0.68;
                } else {
                    // For square/gradient/diagonal: keep mostly full height, add light side padding only.
                    $contentFactorX = 0.70;
                    $contentFactorY = 0.70;
                }
                $targetWidth = max(8, (int) round($size * $contentFactorX) - 2);
                $targetHeight = max(8, (int) round($size * $contentFactorY) - 2);
                
                $low = 8; 
                $high = $size;
                $best = $low;

                // Binary search for the best fit within the target area
                while ($low <= $high) {
                    $mid = (int)floor(($low + $high) / 2);
                    [$w, $h] = self::measureText($initials, $mid, $fontPath);
                    if ($w <= $targetWidth && $h <= $targetHeight) {
                        $best = $mid;
                        $low = $mid + 1;
                    } else {
                        $high = $mid - 1;
                    }
                }
                $fontSize = $best;
            }

            // 2. Precise Centering Calculation
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $initials);
            if (!$bbox) {
                throw new CustomException('Unable to measure text bounding box.');
            }
            [$textWidth, $textHeight, $minX, $minY] = self::measureBbox($bbox);

            // Calculate X and Y to place the center of the bounding box 
            // exactly at the center of the image ($size / 2)
            $x = (int) round((($size - $textWidth) / 2) - $minX);
            $y = (int) round((($size - $textHeight) / 2) - $minY);

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
        $output     = strtolower((string)$opts['output']);

        $isGenerate = $opts['generate'] ?? false;
        $suffix     = $isGenerate ? ('-' . substr(sha1(uniqid((string) mt_rand(), true)), 0, 15)) : '';
        $fileName   = self::sanitizeFilename($name);
        $fileName   = $isGenerate ? "{$fileName}{$suffix}.png" : "{$fileName}.png";

        switch ($output) {
            case 'view':
            case 'download':
                // Stream to browser
                if (!headers_sent()) {
                    header('Content-Type: image/png');
                    if ($output === 'download') {
                        header("Content-Disposition: attachment; filename={$fileName}");
                    } else {
                        header('Content-Disposition: inline');
                    }
                }
                imagepng($img);
                unset($img);

                return ['path' => null, 'url' => null, 'name' => null, 'storage' => null, 'data' => null];
            case 'data':
                ob_start();
                imagepng($img);
                $bin = ob_get_clean();
                unset($img);

                $data = 'data:image/png;base64,' . base64_encode($bin ?: '');
                
                return ['path' => null, 'url' => null, 'name' => null, 'storage' => null, 'data' => $data];
            default:
                $destination = self::stringReplacer($opts['destination'] ?? 'storage/avatars');
                if(!File::isDirectory($destination)){
                    File::makeDirectory($destination);
                }
                
                // get actual storage path
                $storagePath = Str::replace(Server::getServers('server'), '', $destination);

                // local full path
                $fullPath = self::stringReplacer("$destination/{$fileName}");

                // domain full path
                $domainPath = Asset::asset("{$storagePath}/{$fileName}", true, false);

                imagepng($img, $fullPath);
                unset($img);

                return [
                    'path' => $fullPath, 
                    'url' => $domainPath, 
                    'name' => $fileName, 
                    'storage' => $storagePath, 
                    'data' => null
                ];
        }
    }

    /**
     * Draw the background shape or pattern onto the image.
     * * @param resource $img   GD image resource
     * @param string   $type  The shape/pattern type
     * @param int      $size  Square dimension
     * @param int      $color The allocated background color
     * @param array    $rgb   The raw [r, g, b] array for pattern calculations
     * @param int      $radius Corner radius for 'radius' type
     * @return void
     */
    private static function drawBackground($img, string $type, int $size, int $color, array $rgb, int $radius): void
    {
        switch ($type) {
            case 'circle':
                imagefilledellipse($img, (int)($size / 2), (int)($size / 2), $size, $size, $color);
                break;

            case 'radius':
                self::imageFilledRoundedRect($img, 0, 0, $size - 1, $size - 1, $radius, $color);
                break;

            case 'gradient':
                // Vertical gradient: transitions from original color to 30% darker
                for ($i = 0; $i < $size; $i++) {
                    $factor = 1 - ($i / $size * 0.3);
                    $lineCol = imagecolorallocate(
                        $img, 
                        (int)($rgb[0] * $factor), 
                        (int)($rgb[1] * $factor), 
                        (int)($rgb[2] * $factor)
                    );
                    imageline($img, 0, $i, $size, $i, $lineCol);
                }
                break;

            case 'diagonal':
                // Primary background
                imagefilledrectangle($img, 0, 0, $size, $size, $color);
                // Secondary color (15% lighter) for the diagonal split
                $sCol = imagecolorallocate(
                    $img,
                    min(255, (int)($rgb[0] * 1.15)),
                    min(255, (int)($rgb[1] * 1.15)),
                    min(255, (int)($rgb[2] * 1.15))
                );
                $points = [0, $size, $size, $size, $size, 0];
                imagefilledpolygon($img, $points, 3, $sCol);
                break;

            case 'square':
            default:
                imagefilledrectangle($img, 0, 0, $size, $size, $color);
                break;
        }
    }

    /**
     * Compute initials from a name (all languages):
     * - If 2+ words: first character of first two words (letters or CJK/etc.)
     * - Else: first two characters of the single word
     * Uses Unicode-aware splitting so Chinese, Japanese, Arabic, etc. work.
     */
    private static function computeInitials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'NA';
        }

        // Split on any non-letter/digit as separator; \p{L} includes all Unicode letters (CJK, Arabic, etc.)
        $parts = preg_split('/[^\p{L}\p{N}\p{M}]+/u', $name) ?: [];
        $parts = array_values(array_filter($parts, static fn($p) => $p !== ''));

        if (count($parts) >= 2) {
            $a = self::firstCharUpper($parts[0]);
            $b = self::firstCharUpper($parts[1]);
            return $a . $b;
        }

        $w = $parts[0] ?? $name;
        $c1 = mb_substr($w, 0, 1, 'UTF-8');
        $c2 = mb_substr($w, 1, 1, 'UTF-8');
        // One or two initials; uppercase only for scripts that have case (Latin, etc.); leave CJK as-is
        $init = self::firstCharUpper($c1) . ($c2 !== '' ? self::firstCharUpper($c2) : '');
        return $init !== '' ? $init : 'NA';
    }

    /**
     * Uppercase first character only if script has case (Latin, etc.); leave CJK/others unchanged.
     */
    private static function firstCharUpper(string $char): string
    {
        if ($char === '') {
            return '';
        }
        $upper = mb_strtoupper($char, 'UTF-8');
        // If uppercase changes the char and result is single char, use it; else keep original (CJK, etc.)
        return ($upper !== '' && mb_strlen($upper, 'UTF-8') === 1) ? $upper : $char;
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
     * Measure TTF text size (width, height) using imagettfbbox, handling negative coordinates.
     * @return array{0:int,1:int}
     */
    private static function measureText(string $text, int $fontSize, string $fontPath): array
    {
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        if (!$bbox) return [0, 0];

        [$width, $height] = self::measureBbox($bbox);
        return [$width, $height];
    }

    /**
     * Normalize imagettfbbox() into width/height and top-left offset.
     *
     * @param array<int, int|float> $bbox
     * @return array{0:int,1:int,2:int,3:int}
     */
    private static function measureBbox(array $bbox): array
    {
        $xValues = [(int) $bbox[0], (int) $bbox[2], (int) $bbox[4], (int) $bbox[6]];
        $yValues = [(int) $bbox[1], (int) $bbox[3], (int) $bbox[5], (int) $bbox[7]];
        $minX = min($xValues);
        $maxX = max($xValues);
        $minY = min($yValues);
        $maxY = max($yValues);

        return [max(0, $maxX - $minX), max(0, $maxY - $minY), $minX, $minY];
    }

    /**
     * Collect the first two letters from words after explode(' ', $name).
     *
     * Rules:
     * - Split by spaces
     * - Iterate words in order
     * - Take the first Unicode character of each word
     * - Stop when two characters are collected
     * - Fully UTF-8 safe
     *
     * Examples:
     * - "John Doe"       → "JD"
     * - "Mary Jane Lee" → "MJ"
     * - "张 伟"          → "张伟"
     * - "محمد علي"      → "مع"
     *
     * @param string $name
     * @return string
     */
    private static function collectFirstTwoLetters(string $name): string
    {
        $words = array_values(array_filter(
            explode(' ', trim($name)),
            static fn ($w) => $w !== ''
        ));

        $count = count($words);

        if ($count === 0) {
            return 'NA';
        }

        // First word initial
        $first = mb_substr($words[0], 0, 1, 'UTF-8');

        // Last word initial (same as first if only one word)
        $last = $count > 1
            ? mb_substr($words[$count - 1], 0, 1, 'UTF-8')
            : mb_substr($words[0], 1, 1, 'UTF-8');

        $initials = $first . $last;

        return mb_strtoupper($initials, 'UTF-8');
    }

}