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
 * - Clip `type`: circle | radius | square | reuleaux3 | reuleaux | hexagon | decagram | octagram
 *   (hexagon = flat-top regular hex; octagram = 8-point compound of two squares; decagram = regular {10/3}, nonzero fill)
 * - Optional overlay `shape`: diagonal | stripe | ring | gloss | corner | split — fills only. After fill/gradient, before initials.
 * - Optional `gradient` presets (null = solid `bg_color` only)
 * - Initials: first character(s) from name – supports all languages (English, Chinese, Japanese,
 *   Arabic, etc.); two words → first char of each; one word → first two characters
 * - Font: automatically uses a Unicode/CJK-capable font when the name contains non-ASCII characters
 * - Custom background and text color
 * - Option `transparent`: when true, pixels outside the clip `type` are full PNG transparency (initials stay solid `text_color`)
 * - Output: save to file, stream to browser (inline or download), or return as data URI
 */
class TextToImage
{
    use FontPathTrait;

    /**
     * Reuleaux pentagon: disk-intersection body stays much smaller than vertex radius; scale Rc so max
     * distance from center to the clip (matches circle’s size/2) like triangle with Rc = size/2.
     */
    private const REULEAUX_PENTAGON_RC_FACTOR = 2.3892892185171;
    
    
    /**
     * Create an avatar image based on a name or text.
     *
     * Options keys:
     * - name: string (required)
     * - size: int (square dimension in px, default 256)
     * - type: string clip shape — circle | radius | square | reuleaux3 | reuleaux | hexagon | decagram | octagram (default square)
     * - shape: string|null … see shapeTypePresets() (nested fills; no extra edge pixels). null = no overlay.
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
     * - transparent: boolean (default false). When true, area outside the clip shape is fully transparent in the PNG.
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
            'type'        => 'square', // see normalizeClipType()
            'shape'       => null, // diagonal|stripe|ring|gloss|corner|split
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
            'transparent' => false, // when true, trim square canvas to clip (transparent outside shape)
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
        $type = self::normalizeClipType($type);

        $gradientType = self::normalizeGradientType($opts['gradient'] ?? null);
        $shapeType = self::normalizeShapeType($opts['shape'] ?? null);
        $trimOutsideClip = filter_var($opts['transparent'] ?? false, FILTER_VALIDATE_BOOLEAN);

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
        imagealphablending($img, false);

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
                if ($type === 'radius') {
                    $contentFactorX = 0.68;
                    $contentFactorY = 0.68;
                } elseif (in_array($type, ['circle', 'reuleaux3', 'reuleaux', 'hexagon', 'decagram', 'octagram'], true)) {
                    $contentFactorX = 0.62;
                    $contentFactorY = 0.62;
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
                $needShapeFit = $type !== 'square';
                while ($low <= $high) {
                    $mid = (int)floor(($low + $high) / 2);
                    [$w, $h] = self::measureText($initials, $mid, $fontPath);
                    $fitsBox = $w <= $targetWidth && $h <= $targetHeight;
                    $fitsShape = !$needShapeFit || self::initialsFitClipShape($type, $size, $radius, $mid, $fontPath, $initials);
                    if ($fitsBox && $fitsShape) {
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
            imagealphablending($img, true);
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
            imagealphablending($img, true);
            imagestring($img, $font, $x, $y, $initials, $txCol);
        }

        if ($trimOutsideClip) {
            self::applyTransparencyOutsideClip($img, $type, $size, $radius);
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
                imagesavealpha($img, true);
                imagepng($img);
                unset($img);

                return ['path' => null, 'url' => null, 'name' => null, 'storage' => null, 'data' => null];
            case 'data':
                ob_start();
                imagesavealpha($img, true);
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

                imagesavealpha($img, true);
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
            'stripe',
            'ring',
            'gloss',
            'corner',
            'split',
        ];
    }

    /**
     * @return list<string>
     */
    private static function clipTypePresets(): array
    {
        return [
            'square',
            'circle',
            'radius',
            'reuleaux3',
            'reuleaux',
            'hexagon',
            'decagram',
            'octagram',
        ];
    }

    /**
     * Normalized clip `type` for the avatar canvas (not `shape` overlays).
     */
    private static function normalizeClipType(string $type): string
    {
        $t = strtolower(trim($type));
        $aliases = [
            'reuleaux_triangle' => 'reuleaux3',
            'reuleaux_tri' => 'reuleaux3',
            'rtri' => 'reuleaux3',
            'reuleaux_polygon' => 'reuleaux',
            'reuleaux5' => 'reuleaux',
            'reuleaux_pentagon' => 'reuleaux',
            'polygon' => 'reuleaux3',
            'hex' => 'hexagon',
            'ngon' => 'hexagon',
            'star10' => 'decagram',
            'star_10' => 'decagram',
            'star8' => 'octagram',
            'star_8' => 'octagram',
        ];
        if (isset($aliases[$t])) {
            $t = $aliases[$t];
        }
        if (!in_array($t, self::clipTypePresets(), true)) {
            return 'square';
        }

        return $t;
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
            'h_stripe' => 'stripe',
            'v_stripe' => 'stripe',
            'stripe_vertical' => 'stripe',
            'split_vertical' => 'split',
            'split_v' => 'split',
            'vsplit' => 'split',
            'corner_tl' => 'corner',
            'corner_br' => 'corner',
            'corner_tr' => 'corner',
            'corners_tl' => 'corner',
            'corners_br' => 'corner',
            'diagonal_flip' => 'diagonal',
            'diagonal-opposite' => 'diagonal',
            'diagonal_opposite' => 'diagonal',
            'flip' => 'diagonal',
            'corner_bl' => 'corner',
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
     * @param string   $type  clip preset (see clipTypePresets)
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

            case 'reuleaux3':
            case 'reuleaux':
            case 'hexagon':
            case 'decagram':
            case 'octagram':
                self::fillClipScanlines($img, $type, $size, $color, $radius);
                break;

            case 'square':
            default:
                imagefilledrectangle($img, 0, 0, $size, $size, $color);
                break;
        }
    }

    /**
     * Solid fill for clip types defined by per-pixel geometry tests.
     *
     * @param resource|\GdImage $img
     */
    private static function fillClipScanlines($img, string $type, int $size, int $color, int $radius): void
    {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (self::pixelInClipType($type, $x, $y, $size, $radius)) {
                    imagesetpixel($img, $x, $y, $color);
                }
            }
        }
    }

    /**
     * @param resource|\GdImage      $img
     * @param array{0:int,1:int,2:int} $rgb
     */
    private static function shapeAllocateMultiply($img, array $rgb, float $mul): int
    {
        $r = max(0, min(255, (int) round($rgb[0] * $mul)));
        $g = max(0, min(255, (int) round($rgb[1] * $mul)));
        $b = max(0, min(255, (int) round($rgb[2] * $mul)));

        return (int) imagecolorallocate($img, $r, $g, $b);
    }

    /** Legacy diagonal secondary (~15% lift per channel). */
    private static function shapeAllocateLighten15($img, array $rgb): int
    {
        return self::shapeAllocateMultiply($img, $rgb, 1.15);
    }

    /**
     * @param resource|\GdImage $img
     * @param callable(int,int,int):bool $predicate
     */
    private static function applySolidWhere($img, string $type, int $size, int $radius, callable $predicate, int $color): void
    {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!self::pixelInClipType($type, $x, $y, $size, $radius)) {
                    continue;
                }
                if (!$predicate($x, $y, $size)) {
                    continue;
                }
                imagesetpixel($img, $x, $y, $color);
            }
        }
    }

    /**
     * @param resource|\GdImage $img
     */
    private static function applySolidInTriangle(
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
        int $color
    ): void {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!self::pixelInClipType($type, $x, $y, $size, $radius)) {
                    continue;
                }
                if (!self::pointInTriangle((float) $x, (float) $y, $ax, $ay, $bx, $by, $cx, $cy)) {
                    continue;
                }
                imagesetpixel($img, $x, $y, $color);
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
     * Shape overlays: `diagonal` is one lighter BR triangle. Others use nested fills only — edges match diagonal
     * (clean transitions between filled colours, no drawn stroke or double outline).
     *
     * @param resource|\GdImage $img
     * @param array{0:int,1:int,2:int} $rgb bg_color seed for flat overlay fills
     */
    private static function applyShapeOverlay($img, string $type, int $size, array $rgb, int $radius, string $shapeType): void
    {
        $s = (float) $size;
        $sMax = max(0, $size - 1);
        switch ($shapeType) {
            case 'diagonal':
                // Reference: one lighter BR triangle (unchanged).
                $fill = self::shapeAllocateLighten15($img, $rgb);
                self::applySolidInTriangle($img, $type, $size, $radius, 0.0, $s, $s, $s, $s, 0.0, $fill);
                break;

            case 'stripe': {
                $halfH = $size * 0.11;
                $cy = ($size - 1) / 2.0;
                $fillBand = self::shapeAllocateMultiply($img, $rgb, 1.18);
                self::applySolidWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) use ($halfH, $cy) {
                        return abs($y - $cy) < $halfH;
                    },
                    $fillBand
                );
                $innerH = $halfH * 0.42;
                $fillSpine = self::shapeAllocateMultiply($img, $rgb, 1.26);
                self::applySolidWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) use ($innerH, $cy) {
                        return abs($y - $cy) < $innerH;
                    },
                    $fillSpine
                );
                break;
            }

            case 'ring': {
                $cx = ($size - 1) / 2.0;
                $cy = ($size - 1) / 2.0;
                $rr = $size / 2.0;
                $rIn = $rr * 0.72;
                $rOut = $rr * 0.94;
                $fillRing = self::shapeAllocateMultiply($img, $rgb, 0.72);
                self::applySolidWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) use ($cx, $cy, $rIn, $rOut) {
                        $d = hypot($x - $cx, $y - $cy);

                        return $d > $rIn && $d < $rOut;
                    },
                    $fillRing
                );
                $rMid = ($rIn + $rOut) / 2.0;
                $wSpine = ($rOut - $rIn) * 0.28;
                $fillSpine = self::shapeAllocateMultiply($img, $rgb, 0.62);
                self::applySolidWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) use ($cx, $cy, $rMid, $wSpine, $rIn, $rOut) {
                        $d = hypot($x - $cx, $y - $cy);

                        return $d > $rIn && $d < $rOut && abs($d - $rMid) <= $wSpine / 2.0;
                    },
                    $fillSpine
                );
                break;
            }

            case 'gloss': {
                $r = min(255, (int) round($rgb[0] * 0.78 + 255 * 0.22));
                $g = min(255, (int) round($rgb[1] * 0.78 + 255 * 0.22));
                $b = min(255, (int) round($rgb[2] * 0.78 + 255 * 0.22));
                $fillTop = (int) imagecolorallocate($img, $r, $g, $b);
                self::applySolidWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        return $y < $sz * 0.36;
                    },
                    $fillTop
                );
                $r2 = min(255, (int) round($rgb[0] * 0.85 + 255 * 0.15));
                $g2 = min(255, (int) round($rgb[1] * 0.85 + 255 * 0.15));
                $b2 = min(255, (int) round($rgb[2] * 0.85 + 255 * 0.15));
                $fillCap = (int) imagecolorallocate($img, $r2, $g2, $b2);
                self::applySolidWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) {
                        return $y < $sz * 0.2;
                    },
                    $fillCap
                );
                break;
            }

            case 'corner':
                if ($sMax < 2) {
                    break;
                }
                $k = (float) max(2, (int) round($sMax * 0.52));
                $fillOuter = self::shapeAllocateMultiply($img, $rgb, 1.18);
                self::applySolidInTriangle($img, $type, $size, $radius, 0.0, 0.0, $k, 0.0, 0.0, $k, $fillOuter);
                $ki = $k * 0.74;
                $fillInner = self::shapeAllocateMultiply($img, $rgb, 1.08);
                self::applySolidInTriangle($img, $type, $size, $radius, 0.0, 0.0, $ki, 0.0, 0.0, $ki, $fillInner);
                break;

            case 'split': {
                $mid = (int) floor($size / 2);
                $halfW = max(1, (int) round($size * 0.028));
                $xL = max(0, $mid - $halfW);
                $xR = min($sMax, $mid + $halfW);
                $left = self::shapeAllocateMultiply($img, $rgb, 0.88);
                $midFill = self::shapeAllocateMultiply($img, $rgb, 0.985);
                $right = self::shapeAllocateMultiply($img, $rgb, 1.1);
                self::applySolidWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) use ($xL) {
                        return $x < $xL;
                    },
                    $left
                );
                self::applySolidWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) use ($xL, $xR) {
                        return $x >= $xL && $x <= $xR;
                    },
                    $midFill
                );
                self::applySolidWhere(
                    $img,
                    $type,
                    $size,
                    $radius,
                    static function (int $x, int $y, int $sz) use ($xR) {
                        return $x > $xR;
                    },
                    $right
                );
                break;
            }
        }
    }

    /**
     * Same radial extent as {@see pixelInClipType} circle test: `r = size/2` (center ((size-1)/2,(size-1)/2)).
     */
    private static function clipRadiusHalf(int $size): float
    {
        return $size / 2.0;
    }

    /**
     * Regular hexagon with flat horizontal top and bottom edges (vertex at 3 o’clock), circumradius = size/2.
     */
    private static function pointInHexagonFlatTop(float $px, float $py, float $cx, float $cy, int $size): bool
    {
        $R = self::clipRadiusHalf($size);
        $poly = [];
        for ($k = 0; $k < 6; $k++) {
            $t = 2 * M_PI * $k / 6.0;
            $poly[] = [$cx + $R * cos($t), $cy + $R * sin($t)];
        }

        return self::pointInPolygonEvenOdd($px, $py, $poly);
    }

    /**
     * Octagram: 8-point / 8-edge compound — union of two equal squares (axis-aligned + 45°), same center.
     */
    private static function pointInOctagramTwoSquares(float $px, float $py, float $cx, float $cy, int $size): bool
    {
        $R = self::clipRadiusHalf($size);
        $dx = $px - $cx;
        $dy = $py - $cy;
        $ax = abs($dx);
        $ay = abs($dy);
        $half = $R / sqrt(2.0);
        $inAxisAligned = $ax <= $half && $ay <= $half;
        $inDiamond = ($ax + $ay) <= $R;

        return $inAxisAligned || $inDiamond;
    }

    /**
     * Reuleaux triangle / odd Reuleaux n-gon: intersection of disks of radius a (side length) at each vertex.
     * Same orientation for n=3 and n=5: first vertex at the top (no extra rotation).
     */
    private static function pointInReuleauxOdd(float $px, float $py, float $cx, float $cy, int $size, int $n): bool
    {
        // Triangle: vertices on circle r = size/2 so the shape reaches the canvas like `type=circle`.
        // Pentagon: same max extent via empirical factor (intersection is not vertex-centered).
        $Rc = $n === 3 ? self::clipRadiusHalf($size) : self::REULEAUX_PENTAGON_RC_FACTOR * $size;
        $a = 2.0 * $Rc * sin(M_PI / (float) $n);
        $base = -M_PI / 2;
        for ($k = 0; $k < $n; $k++) {
            $t = $base + 2 * M_PI * $k / $n;
            $vx = $cx + $Rc * cos($t);
            $vy = $cy + $Rc * sin($t);
            if (hypot($px - $vx, $py - $vy) > $a + 1e-4) {
                return false;
            }
        }

        return true;
    }

    /**
     * Even–odd ray test for a closed polygon (supports star polygons).
     *
     * @param list<array{0:float,1:float}> $poly
     */
    private static function pointInPolygonEvenOdd(float $px, float $py, array $poly): bool
    {
        $n = count($poly);
        if ($n < 3) {
            return false;
        }
        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $poly[$i][0];
            $yi = $poly[$i][1];
            $xj = $poly[$j][0];
            $yj = $poly[$j][1];
            $yn = $yj - $yi;
            if ((($yi > $py) !== ($yj > $py)) && ($yn !== 0.0)) {
                $xInt = $xi + ($py - $yi) * ($xj - $xi) / $yn;
                if ($px < $xInt) {
                    $inside = !$inside;
                }
            }
        }

        return $inside;
    }

    /**
     * Non-zero winding rule (SVG fill-rule="nonzero"). Fills self-intersecting stars as one solid region
     * without even-odd “holes” or moats between spikes and centre.
     *
     * @param list<array{0:float,1:float}> $poly
     */
    private static function pointInPolygonNonZero(float $px, float $py, array $poly): bool
    {
        $n = count($poly);
        if ($n < 3) {
            return false;
        }
        $w = 0;
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $xi = $poly[$i][0];
            $yi = $poly[$i][1];
            $xj = $poly[$j][0];
            $yj = $poly[$j][1];
            if ($yi <= $py) {
                if ($yj > $py) {
                    $cross = ($xj - $xi) * ($py - $yi) - ($px - $xi) * ($yj - $yi);
                    if ($cross > 0) {
                        $w++;
                    }
                }
            } else {
                if ($yj <= $py) {
                    $cross = ($xj - $xi) * ($py - $yi) - ($px - $xi) * ($yj - $yi);
                    if ($cross < 0) {
                        $w--;
                    }
                }
            }
        }

        return $w !== 0;
    }

    /**
     * Regular decagram {10/3}: 10 edges (step 3 on a decagon), all vertices on the circumcircle, one at the top.
     * This is the true decagram star — not the alternating-radius polygon that reads like a rounded decagon.
     *
     * @return list<array{0:float,1:float}>
     */
    private static function decagramSchlafli10_3Polygon(float $cx, float $cy, int $size): array
    {
        $R = self::clipRadiusHalf($size);
        $n = 10;
        $step = 3;
        $onCircle = [];
        for ($k = 0; $k < $n; $k++) {
            $t = -M_PI / 2 + 2 * M_PI * $k / $n;
            $onCircle[] = [$cx + $R * cos($t), $cy + $R * sin($t)];
        }
        $poly = [];
        $idx = 0;
        for ($i = 0; $i < $n; $i++) {
            $poly[] = $onCircle[$idx];
            $idx = ($idx + $step) % $n;
        }

        return $poly;
    }

    /**
     * Decagram: {10/3} path + non-zero winding so the fill is one solid colour (no even-odd holes / moats).
     */
    private static function pointInDecagram(float $px, float $py, float $cx, float $cy, int $size): bool
    {
        $R = self::clipRadiusHalf($size);
        $dx = $px - $cx;
        $dy = $py - $cy;
        if (hypot($dx, $dy) > $R + 1e-6) {
            return false;
        }
        $poly = self::decagramSchlafli10_3Polygon($cx, $cy, $size);

        return self::pointInPolygonNonZero($px, $py, $poly);
    }

    private static function pixelInClipGeometry(string $type, float $px, float $py, int $size): bool
    {
        $cx = ($size - 1) / 2.0;
        $cy = ($size - 1) / 2.0;
        switch ($type) {
            case 'reuleaux3':
                return self::pointInReuleauxOdd($px, $py, $cx, $cy, $size, 3);

            case 'reuleaux':
                return self::pointInReuleauxOdd($px, $py, $cx, $cy, $size, 5);

            case 'hexagon':
                return self::pointInHexagonFlatTop($px, $py, $cx, $cy, $size);

            case 'octagram':
                return self::pointInOctagramTwoSquares($px, $py, $cx, $cy, $size);

            case 'decagram':
                return self::pointInDecagram($px, $py, $cx, $cy, $size);
        }

        return false;
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
        if (in_array($type, ['reuleaux3', 'reuleaux', 'hexagon', 'decagram', 'octagram'], true)) {
            return self::pixelInClipGeometry($type, (float) $x, (float) $y, $size);
        }

        return false;
    }

    /**
     * Subpixel clip test for fitting text (same geometry as {@see pixelInClipType}).
     */
    private static function pointInClipFloat(string $type, float $px, float $py, int $size, int $radius): bool
    {
        if ($px < 0.0 || $py < 0.0 || $px > $size - 1 || $py > $size - 1) {
            return false;
        }
        if ($type === 'square') {
            return true;
        }
        $cx = ($size - 1) / 2.0;
        $cy = ($size - 1) / 2.0;
        $r = $size / 2.0;
        if ($type === 'circle') {
            return hypot($px - $cx, $py - $cy) <= $r + 0.25;
        }
        if ($type === 'radius') {
            return self::pixelInFilledRoundRect((int) round($px), (int) round($py), $size, $radius);
        }
        if (in_array($type, ['reuleaux3', 'reuleaux', 'hexagon', 'decagram', 'octagram'], true)) {
            return self::pixelInClipGeometry($type, $px, $py, $size);
        }

        return false;
    }

    /**
     * True if a dense grid over the glyph bounding box lies inside the clip (avoids concave clipping on stars / Reuleaux).
     */
    private static function initialsFitClipShape(
        string $type,
        int $size,
        int $radius,
        int $fontSize,
        string $fontPath,
        string $initials
    ): bool {
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $initials);
        if (!$bbox) {
            return false;
        }
        $xs = [(float) $bbox[0], (float) $bbox[2], (float) $bbox[4], (float) $bbox[6]];
        $ys = [(float) $bbox[1], (float) $bbox[3], (float) $bbox[5], (float) $bbox[7]];
        $minBx = min($xs);
        $maxBx = max($xs);
        $minBy = min($ys);
        $maxBy = max($ys);

        [$textWidth, $textHeight, $minX, $minY] = self::measureBbox($bbox);
        $x = (($size - $textWidth) / 2.0) - (float) $minX;
        $y = (($size - $textHeight) / 2.0) - (float) $minY;

        $steps = 7;
        for ($iy = 0; $iy <= $steps; $iy++) {
            for ($ix = 0; $ix <= $steps; $ix++) {
                $bx = $minBx + ($maxBx - $minBx) * $ix / $steps;
                $by = $minBy + ($maxBy - $minBy) * $iy / $steps;
                $imgX = $x + $bx;
                $imgY = $y + $by;
                if (!self::pointInClipFloat($type, $imgX, $imgY, $size, $radius)) {
                    return false;
                }
            }
        }

        return true;
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
     * Force pixels outside the clip `type` to full transparency (PNG alpha outside the shape).
     *
     * @param resource|\GdImage $img
     */
    private static function applyTransparencyOutsideClip($img, string $type, int $size, int $radius): void
    {
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $clear = imagecolorallocatealpha($img, 0, 0, 0, 127);
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!self::pixelInClipType($type, $x, $y, $size, $radius)) {
                    imagesetpixel($img, $x, $y, $clear);
                }
            }
        }
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