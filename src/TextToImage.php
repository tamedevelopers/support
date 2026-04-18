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
 * - Clip type: circle | radius | square (`type`)
 * - Optional overlay `shape` (`diagonal` = top-right corner wedge, `diagonal_flip` = bottom-left; stripes, corners, ring, gloss, …) blended on top of fill/gradient, before initials
 * - Optional `gradient` presets (null = solid `bg_color` only)
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
     * - type: string ('circle' | 'radius' | 'square') default 'square' — clip/mask for the avatar
     * - shape: string|null Overlay preset (see shapeTypePresets). null = no overlay. Applied after fill/gradient, before initials.
     * - gradient: string|null Preset gradient fill. null = solid bg_color only.
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
            'type'        => 'square', // circle|radius|square
            'shape'       => null, // overlay: diagonal (TR wedge), diagonal_flip (BL), stripe, …
            'gradient'    => null, // preset name or null for solid bg_color
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
        if ($type === 'gradient') {
            $type = 'square';
            if (($opts['gradient'] ?? null) === null || $opts['gradient'] === '') {
                $opts['gradient'] = 'vertical';
            }
        }
        if ($type === 'diagonal') {
            $type = 'square';
            if (($opts['shape'] ?? null) === null || $opts['shape'] === '') {
                $opts['shape'] = 'diagonal';
            }
        }
        if (!in_array($type, ['circle', 'radius', 'square'], true)) {
            $type = 'square';
        }

        $gradientType = self::normalizeGradientType($opts['gradient'] ?? null);
        $shapeType = self::normalizeShapeType($opts['shape'] ?? null);

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

        // Fill clip (solid or gradient only). Shape overlays run later — immediately before painting initials.
        self::drawBackground($img, $type, $size, $bgCol, $br, $radius, $gradientType);

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
                    // Square (and overlays like diagonal): keep mostly full height, light side padding.
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

            if ($shapeType !== null) {
                self::applyShapeOverlay($img, $type, $size, $br, $radius, $shapeType);
            }
            imagettftext($img, $fontSize, 0, $x, $y, $txCol, $fontPath, $initials);
        } else {
            // Fallback: built-in font
            $font = 5; // largest built-in font
            $textWidth = imagefontwidth($font) * strlen($initials);
            $textHeight = imagefontheight($font);
            $x = (int)(($size - $textWidth) / 2);
            $y = (int)(($size - $textHeight) / 2);
            if ($shapeType !== null) {
                self::applyShapeOverlay($img, $type, $size, $br, $radius, $shapeType);
            }
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
     * @return list<string>
     */
    private static function gradientTypePresets(): array
    {
        return [
            'vertical',
            'horizontal',
            'radial',
            'spotlight',
            'vignette',
            'sunset',
            'ocean',
            'aurora',
            'forest',
            'ember',
            'cosmic',
            'mesh',
            'noir',
            'candy',
        ];
    }

    /**
     * @return list<string>
     */
    private static function shapeTypePresets(): array
    {
        return [
            'diagonal',
            'diagonal_flip',
            'stripe',
            'stripe_vertical',
            'corner_tl',
            'corner_br',
            'split_vertical',
            'ring',
            'gloss',
        ];
    }

    private static function normalizeShapeType($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $s = strtolower(trim((string) $value));
        if ($s === '') {
            return null;
        }
        $aliases = [
            'diag' => 'diagonal',
            'diagonal-opposite' => 'diagonal_flip',
            'diagonal_opposite' => 'diagonal_flip',
            'flip' => 'diagonal_flip',
            'h_stripe' => 'stripe',
            'v_stripe' => 'stripe_vertical',
            'corners_tl' => 'corner_tl',
            'corners_br' => 'corner_br',
            'corner_tr' => 'diagonal',
            'corner_bl' => 'diagonal_flip',
            'split_v' => 'split_vertical',
            'vsplit' => 'split_vertical',
        ];
        if (isset($aliases[$s])) {
            $s = $aliases[$s];
        }
        if (!in_array($s, self::shapeTypePresets(), true)) {
            return null;
        }

        return $s;
    }

    private static function normalizeGradientType($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $g = strtolower(trim((string) $value));
        if ($g === '') {
            return null;
        }
        $aliases = [
            'v' => 'vertical',
            'linear-down' => 'vertical',
            'h' => 'horizontal',
            'linear-right' => 'horizontal',
            'r' => 'radial',
            'radial-soft' => 'radial',
            'center-glow' => 'spotlight',
            'edge-dark' => 'vignette',
        ];
        if (isset($aliases[$g])) {
            $g = $aliases[$g];
        }
        if (!in_array($g, self::gradientTypePresets(), true)) {
            return null;
        }

        return $g;
    }

    /**
     * Draw the background shape or pattern onto the image.
     *
     * @param resource $img   GD image resource
     * @param string   $type  circle|radius|square
     * @param int      $size  Square dimension
     * @param int      $color The allocated background color (solid path)
     * @param array    $rgb   The raw [r, g, b] array for pattern calculations
     * @param int      $radius Corner radius for 'radius' type
     * @param string|null $gradientType normalized preset or null
     */
    private static function drawBackground($img, string $type, int $size, int $color, array $rgb, int $radius, ?string $gradientType): void
    {
        if ($gradientType !== null) {
            self::fillShapeWithGradient($img, $type, $size, $rgb, $radius, $gradientType);
            return;
        }

        switch ($type) {
            case 'circle':
                imagefilledellipse($img, (int)($size / 2), (int)($size / 2), $size, $size, $color);
                break;

            case 'radius':
                self::imageFilledRoundedRect($img, 0, 0, $size - 1, $size - 1, $radius, $color);
                break;

            case 'square':
            default:
                imagefilledrectangle($img, 0, 0, $size, $size, $color);
                break;
        }
    }

    /**
     * Decorative overlay blended onto existing pixels (keeps gradients visible). Clipped to `type`.
     *
     * @param resource|\GdImage $img
     */
    private static function applyShapeOverlay($img, string $type, int $size, array $rgb, int $radius, string $shapeType): void
    {
        switch ($shapeType) {
            case 'diagonal':
                // Top-right corner wedge (classic avatar diagonal): triangle TR → down right edge → in along top.
                $sMax = max(0, $size - 1);
                if ($sMax < 2) {
                    break;
                }
                $xr = (float) min($sMax - 1, max(1, (int) round($sMax * 0.58)));
                $yr = (float) min($sMax, max(1, (int) round($sMax * 0.40)));
                if ($xr >= $sMax || $yr < 1) {
                    break;
                }
                self::applyBlendInTriangle(
                    $img,
                    $type,
                    $size,
                    $radius,
                    (float) $sMax,
                    0.0,
                    (float) $sMax,
                    $yr,
                    $xr,
                    0.0,
                    self::accentLighterRgb($rgb, 1.28),
                    0.58
                );
                break;
            case 'diagonal_flip':
                // Bottom-left corner wedge (mirror of `diagonal`).
                $sMax = max(0, $size - 1);
                if ($sMax < 2) {
                    break;
                }
                $xr = (float) min($sMax - 1, max(1, (int) round($sMax * 0.58)));
                $yr = (float) min($sMax, max(1, (int) round($sMax * 0.40)));
                if ($xr >= $sMax || $yr < 1) {
                    break;
                }
                $xh = (float) ($sMax - $xr);
                $yv = (float) ($sMax - $yr);
                self::applyBlendInTriangle(
                    $img,
                    $type,
                    $size,
                    $radius,
                    0.0,
                    (float) $sMax,
                    $xh,
                    (float) $sMax,
                    0.0,
                    $yv,
                    self::accentLighterRgb($rgb, 1.26),
                    0.56
                );
                break;
            case 'stripe':
                self::applyBlendWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        $cy = ($sz - 1) / 2.0;

                        return abs($y - $cy) < $sz * 0.11;
                    },
                    self::accentLighterRgb($rgb, 1.18),
                    0.32
                );
                break;
            case 'stripe_vertical':
                self::applyBlendWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        $cx = ($sz - 1) / 2.0;

                        return abs($x - $cx) < $sz * 0.09;
                    },
                    self::accentLighterRgb($rgb, 1.15),
                    0.3
                );
                break;
            case 'corner_tl':
                self::applyBlendWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        return $x < $sz * 0.48 && $y < $sz * 0.48;
                    },
                    self::accentLighterRgb($rgb, 1.22),
                    0.35
                );
                break;
            case 'corner_br':
                self::applyBlendWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        return $x > $sz * 0.52 && $y > $sz * 0.52;
                    },
                    self::accentLighterRgb($rgb, 1.2),
                    0.35
                );
                break;
            case 'split_vertical':
                self::applyBlendWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        return $x < (int) floor($sz / 2);
                    },
                    self::accentDarkerRgb($rgb, 0.88),
                    0.22
                );
                self::applyBlendWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        return $x >= (int) floor($sz / 2);
                    },
                    self::accentLighterRgb($rgb, 1.08),
                    0.18
                );
                break;
            case 'ring':
                self::applyBlendWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        $cx = ($sz - 1) / 2.0;
                        $cy = ($sz - 1) / 2.0;
                        $rr = $sz / 2.0;
                        $d = hypot($x - $cx, $y - $cy);

                        return $d > $rr * 0.72 && $d < $rr * 0.94;
                    },
                    self::accentDarkerRgb($rgb, 0.75),
                    0.45
                );
                break;
            case 'gloss':
                self::applyBlendWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        return $y < $sz * 0.36;
                    },
                    [255.0, 255.0, 255.0],
                    0.18
                );
                break;
        }
    }

    /**
     * @param resource|\GdImage $img
     * @param callable(int,int,int):bool $predicate
     */
    private static function applyBlendWhere($img, string $type, int $size, int $radius, callable $predicate, array $accentRgb, float $strength): void
    {
        $cache = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!self::pixelInClipType($type, $x, $y, $size, $radius)) {
                    continue;
                }
                if (!$predicate($x, $y, $size)) {
                    continue;
                }
                self::blendPixelToward($img, $x, $y, $accentRgb, $strength, $cache);
            }
        }
    }

    /**
     * Blend accent inside a triangle (e.g. corner wedges). Clipped to `type`.
     *
     * @param resource|\GdImage $img
     */
    private static function applyBlendInTriangle(
        $img,
        string $type,
        int $size,
        int $radius,
        float $ax,
        float $ay,
        float $bx,
        float $by,
        float $cx,
        float $cy,
        array $accentRgb,
        float $strength
    ): void {
        $cache = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!self::pixelInClipType($type, $x, $y, $size, $radius)) {
                    continue;
                }
                if (!self::pointInTriangle((float) $x, (float) $y, $ax, $ay, $bx, $by, $cx, $cy)) {
                    continue;
                }
                self::blendPixelToward($img, $x, $y, $accentRgb, $strength, $cache);
            }
        }
    }

    private static function pointInTriangle(
        float $px,
        float $py,
        float $ax,
        float $ay,
        float $bx,
        float $by,
        float $cx,
        float $cy
    ): bool {
        $sign = static function (float $p1x, float $p1y, float $p2x, float $p2y, float $p3x, float $p3y): float {
            return ($p1x - $p3x) * ($p2y - $p3y) - ($p2x - $p3x) * ($p1y - $p3y);
        };

        $d1 = $sign($px, $py, $ax, $ay, $bx, $by);
        $d2 = $sign($px, $py, $bx, $by, $cx, $cy);
        $d3 = $sign($px, $py, $cx, $cy, $ax, $ay);
        $hasNeg = ($d1 < 0) || ($d2 < 0) || ($d3 < 0);
        $hasPos = ($d1 > 0) || ($d2 > 0) || ($d3 > 0);

        return !($hasNeg && $hasPos);
    }

    /**
     * @param array{0:int,1:int,2:int} $rgb
     * @return array{0:float,1:float,2:float}
     */
    private static function accentLighterRgb(array $rgb, float $mul): array
    {
        return [
            min(255.0, $rgb[0] * $mul),
            min(255.0, $rgb[1] * $mul),
            min(255.0, $rgb[2] * $mul),
        ];
    }

    /**
     * @param array{0:int,1:int,2:int} $rgb
     * @return array{0:float,1:float,2:float}
     */
    private static function accentDarkerRgb(array $rgb, float $mul): array
    {
        return [
            max(0.0, $rgb[0] * $mul),
            max(0.0, $rgb[1] * $mul),
            max(0.0, $rgb[2] * $mul),
        ];
    }

    /**
     * @param array<string,int> $cache
     * @param resource|\GdImage $img
     */
    private static function blendPixelToward($img, int $x, int $y, array $targetRgb, float $strength, array &$cache): void
    {
        $base = self::readTruecolorRgb($img, $x, $y);
        if ($base === null) {
            return;
        }
        $s = max(0.0, min(1.0, $strength));
        $out = [
            $base[0] + ($targetRgb[0] - $base[0]) * $s,
            $base[1] + ($targetRgb[1] - $base[1]) * $s,
            $base[2] + ($targetRgb[2] - $base[2]) * $s,
        ];
        $r = max(0, min(255, (int) round($out[0])));
        $g = max(0, min(255, (int) round($out[1])));
        $b = max(0, min(255, (int) round($out[2])));
        $k = $r . ',' . $g . ',' . $b;
        if (!isset($cache[$k])) {
            $cache[$k] = imagecolorallocate($img, $r, $g, $b);
        }
        imagesetpixel($img, $x, $y, $cache[$k]);
    }

    /**
     * @param resource|\GdImage $img
     * @return array{0:float,1:float,2:float}|null
     */
    private static function readTruecolorRgb($img, int $x, int $y): ?array
    {
        $c = @imagecolorat($img, $x, $y);
        if ($c === false) {
            return null;
        }
        $a = ($c >> 24) & 127;
        if ($a >= 127) {
            return null;
        }

        return [
            (float) (($c >> 16) & 0xFF),
            (float) (($c >> 8) & 0xFF),
            (float) ($c & 0xFF),
        ];
    }

    private static function pixelInClipType(string $type, int $x, int $y, int $size, int $radius): bool
    {
        if ($x < 0 || $y < 0 || $x >= $size || $y >= $size) {
            return false;
        }
        if ($type === 'square') {
            return true;
        }
        $cx = ($size - 1) / 2.0;
        $cy = ($size - 1) / 2.0;
        $r = $size / 2.0;
        if ($type === 'circle') {
            return hypot($x - $cx, $y - $cy) <= $r + 0.5;
        }
        if ($type === 'radius') {
            return self::pixelInFilledRoundRect($x, $y, $size, $radius);
        }

        return false;
    }

    /**
     * @param resource $img
     */
    private static function fillShapeWithGradient($img, string $type, int $size, array $rgb, int $radius, string $gradientType): void
    {
        $cx = ($size - 1) / 2.0;
        $cy = ($size - 1) / 2.0;
        $r = $size / 2.0;
        $colorCache = [];

        $alloc = static function ($img, array $c) use (&$colorCache): int {
            $c[0] = max(0, min(255, (int) round($c[0])));
            $c[1] = max(0, min(255, (int) round($c[1])));
            $c[2] = max(0, min(255, (int) round($c[2])));
            $k = $c[0] . ',' . $c[1] . ',' . $c[2];
            if (!isset($colorCache[$k])) {
                $colorCache[$k] = imagecolorallocate($img, $c[0], $c[1], $c[2]);
            }

            return $colorCache[$k];
        };

        $inShape = function (int $x, int $y) use ($type, $size, $radius): bool {
            return self::pixelInClipType($type, $x, $y, $size, $radius);
        };

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!$inShape($x, $y)) {
                    continue;
                }
                $c = self::gradientRgbAt($x, $y, $size, $rgb, $gradientType, $cx, $cy, $r);
                imagesetpixel($img, $x, $y, $alloc($img, $c));
            }
        }
    }

    private static function pixelInFilledRoundRect(int $x, int $y, int $size, int $radius): bool
    {
        $x2 = $size - 1;
        $y2 = $size - 1;
        $r = max(0, min((int) floor(min($size, $size) / 2), $radius));

        if ($x >= $r && $x <= $x2 - $r) {
            return true;
        }
        if ($y >= $r && $y <= $y2 - $r) {
            return true;
        }
        if (hypot($x - $r, $y - $r) <= $r) {
            return true;
        }
        if (hypot($x - ($x2 - $r), $y - $r) <= $r) {
            return true;
        }
        if (hypot($x - $r, $y - ($y2 - $r)) <= $r) {
            return true;
        }
        if (hypot($x - ($x2 - $r), $y - ($y2 - $r)) <= $r) {
            return true;
        }

        return false;
    }

    /**
     * @return array{0:float,1:float,2:float}
     */
    private static function gradientRgbAt(
        int $x,
        int $y,
        int $size,
        array $rgb,
        string $gradientType,
        float $cx,
        float $cy,
        float $r
    ): array {
        $br = [(float) $rgb[0], (float) $rgb[1], (float) $rgb[2]];
        $nx = $size > 1 ? $x / ($size - 1) : 0.0;
        $ny = $size > 1 ? $y / ($size - 1) : 0.0;
        $d = $r > 0 ? hypot($x - $cx, $y - $cy) / $r : 0.0;
        $d = max(0.0, min(1.0, $d));

        switch ($gradientType) {
            case 'vertical':
                return self::rgbLerp($br, self::rgbDarken($br, 0.32), $ny);

            case 'horizontal':
                return self::rgbLerp($br, self::rgbDarken($br, 0.28), $nx);

            case 'radial':
                return self::rgbLerp(self::rgbLighten($br, 0.18), self::rgbDarken($br, 0.35), $d);

            case 'spotlight':
                return self::rgbLerp(self::rgbLighten($br, 0.35), self::rgbDarken($br, 0.25), $d);

            case 'vignette':
                return self::rgbLerp($br, self::rgbDarken($br, 0.55), $d * $d);

            case 'sunset':
                return self::rgbLerp3($br, [255.0, 118.0, 92.0], [168.0, 85.0, 247.0], $ny);

            case 'ocean':
                return self::rgbLerp3($br, [56.0, 189.0, 248.0], [30.0, 58.0, 138.0], $ny);

            case 'aurora':
                return self::rgbLerp3($br, [52.0, 211.0, 153.0], [129.0, 140.0, 248.0], $ny);

            case 'forest':
                return self::rgbLerp3($br, [74.0, 222.0, 128.0], [22.0, 101.0, 52.0], $ny);

            case 'ember':
                return self::rgbLerp3($br, [251.0, 191.0, 36.0], [127.0, 29.0, 29.0], $ny);

            case 'cosmic':
                $t = ($nx + $ny) / 2;

                return self::rgbLerp3($br, [147.0, 51.0, 234.0], [6.0, 182.0, 212.0], $t);

            case 'mesh':
                $t = ($nx + (1.0 - $ny)) / 2;

                return self::rgbLerp($br, self::rgbDarken($br, 0.45), $t);

            case 'noir':
                $g = 0.299 * $br[0] + 0.587 * $br[1] + 0.114 * $br[2];
                $g0 = [$g, $g, $g];
                $g1 = [max(0.0, $g * 0.35), max(0.0, $g * 0.35), max(0.0, $g * 0.38)];

                return self::rgbLerp($g0, $g1, $ny);

            case 'candy':
                return self::rgbLerp3($br, [244.0, 114.0, 182.0], [192.0, 132.0, 252.0], $nx);

            default:
                return $br;
        }
    }

    /**
     * @param array{0:float,1:float,2:float} $a
     * @param array{0:float,1:float,2:float} $b
     * @return array{0:float,1:float,2:float}
     */
    private static function rgbLerp(array $a, array $b, float $t): array
    {
        $t = max(0.0, min(1.0, $t));

        return [
            $a[0] + ($b[0] - $a[0]) * $t,
            $a[1] + ($b[1] - $a[1]) * $t,
            $a[2] + ($b[2] - $a[2]) * $t,
        ];
    }

    /**
     * @param array{0:float,1:float,2:float} $a
     * @param array{0:float,1:float,2:float} $b
     * @param array{0:float,1:float,2:float} $c
     */
    private static function rgbLerp3(array $a, array $b, array $c, float $t): array
    {
        $t = max(0.0, min(1.0, $t));
        if ($t <= 0.5) {
            return self::rgbLerp($a, $b, $t * 2.0);
        }

        return self::rgbLerp($b, $c, ($t - 0.5) * 2.0);
    }

    /**
     * @param array{0:float,1:float,2:float} $rgb
     * @return array{0:float,1:float,2:float}
     */
    private static function rgbDarken(array $rgb, float $amount): array
    {
        $f = max(0.0, min(1.0, 1.0 - $amount));

        return [$rgb[0] * $f, $rgb[1] * $f, $rgb[2] * $f];
    }

    /**
     * @param array{0:float,1:float,2:float} $rgb
     * @return array{0:float,1:float,2:float}
     */
    private static function rgbLighten(array $rgb, float $amount): array
    {
        return [
            $rgb[0] + (255.0 - $rgb[0]) * $amount,
            $rgb[1] + (255.0 - $rgb[1]) * $amount,
            $rgb[2] + (255.0 - $rgb[2]) * $amount,
        ];
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