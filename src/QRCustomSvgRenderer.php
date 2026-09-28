<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputAbstract;

/**
 * Custom SVG Renderer for styled QR Codes.
 *
 * @package Tamedevelopers\Support
 */
class QRCustomSvgRenderer extends QROutputAbstract
{
    /**
     * Map or process input module values.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function prepareModuleValue($value): mixed
    {
        return $value;
    }

    /**
     * Get default fallback module value.
     *
     * @param bool $isDark
     * @return mixed
     */
    protected function getDefaultModuleValue(bool $isDark): mixed
    {
        return $isDark ? '#000000' : '#FFFFFF';
    }

    /**
     * Validate whether a module value format is acceptable.
     *
     * @param mixed $value
     * @return bool
     */
    public static function moduleValueIsValid($value): bool
    {
        return is_string($value) && !empty($value);
    }

    /**
     * Dump raw matrix output to stream or return generated string.
     *
     * @param string|null $file Optional output path or stream
     * @return string
     */
    public function dump($file = null): string
    {
        $data = $this->render();

        if ($file !== null) {
            $this->saveToFile($data, $file);
        }

        return $data;
    }

    /**
     * Render the QR code matrix into custom SVG markup.
     *
     * @param string|null $file Optional output destination file
     * @return string Generated SVG XML string
     */
    public function render($file = null): string
    {
        $matrixSize = $this->matrix->getSize();
        $scale      = (int) ($this->options->scale ?? 10);
        $svgSize    = $matrixSize * $scale;

        $palette = $this->options->paletteColors;

        // Extract shape arrays or normalize string fallbacks
        $shapeConfig      = $this->options->shape;
        $outerShapeConfig = $this->options->outerShape;
        $innerShapeConfig = $this->options->innerShape;

        $shape      = $shapeConfig['shape'];
        $outerShape = $outerShapeConfig['shape'];
        $innerShape = $innerShapeConfig['shape'];

        // Resolve colors with priority: Specific Shape Color > Palette Preset > Default Fallback
        $bgColor     = $palette['bg'] ?? '#FFFFFF';
        $moduleColor = $shapeConfig['color'] ?? ($palette['module'] ?? '#000000');
        $outerColor  = $outerShapeConfig['color'] ?? ($palette['finder'] ?? '#000000');
        $innerColor  = $innerShapeConfig['color'] ?? ($palette['finder'] ?? '#000000');

        $finderOrigins    = [];
        $finderOriginsMap = [];

        // 1. Locate Finder Patterns (7x7 corners)
        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($this->matrix->checkType($x, $y, QRMatrix::M_FINDER)) {
                    if (!isset($finderOriginsMap["{$x},{$y}"]) 
                        && $x + 6 < $matrixSize 
                        && $y + 6 < $matrixSize 
                        && $this->matrix->checkType($x + 6, $y + 6, QRMatrix::M_FINDER)
                    ) {
                        $finderOrigins[] = [$x, $y];
                        for ($fy = $y; $fy < $y + 7; $fy++) {
                            for ($fx = $x; $fx < $x + 7; $fx++) {
                                $finderOriginsMap["{$fx},{$fy}"] = true;
                            }
                        }
                    }
                }
            }
        }

        $svg   = [];
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d">',
            $svgSize, $svgSize, $svgSize, $svgSize
        );
        $svg[] = sprintf('<rect width="100%%" height="100%%" fill="%s"/>', $bgColor);

        // Helper lambda to safely check matrix modules (excluding finder patterns)
        $isModuleDark = function(int $mx, int $my) use ($matrixSize, $finderOriginsMap): bool {
            if ($mx < 0 || $mx >= $matrixSize || $my < 0 || $my >= $matrixSize) {
                return false;
            }
            if (isset($finderOriginsMap["{$mx},{$my}"])) {
                return false;
            }
            return $this->matrix->check($mx, $my);
        };

        // 2. Render Dark Data Modules according to $shape
        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if (isset($finderOriginsMap["{$x},{$y}"])) {
                    continue;
                }

                if ($this->matrix->check($x, $y)) {
                    $px = $x * $scale;
                    $py = $y * $scale;

                    switch ($shape) {
                        // --- CIRCLE / DOTS ---
                        case 'circle':
                            $r = $scale / 2;
                            $svg[] = sprintf(
                                '<circle cx="%.2f" cy="%.2f" r="%.2f" fill="%s"/>',
                                $px + $r, $py + $r, $r * 0.85, $moduleColor
                            );
                            break;

                        // --- THIN (Smaller centered square) ---
                        case 'thin':
                            $inset = $scale * 0.2;
                            $dim   = $scale * 0.75;
                            $svg[] = sprintf(
                                '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="%s"/>',
                                $px + $inset, $py + $inset, $dim, $dim, $moduleColor
                            );
                            break;

                        // --- SMOOTH (Connected modules with adaptive outer-corner rounding) ---
                        case 'smooth':
                            $top    = $isModuleDark($x, $y - 1);
                            $bottom = $isModuleDark($x, $y + 1);
                            $left   = $isModuleDark($x - 1, $y);
                            $right  = $isModuleDark($x + 1, $y);

                            $r = $scale * 0.45; // Corner radius

                            // Compute SVG path using d commands
                            $path  = sprintf('M %.2f,%.2f ', $px + $r, $py);

                            // Top edge & Top-Right corner
                            if (!$top && !$right) {
                                $path .= sprintf('H %.2f a %.2f,%.2f 0 0 1 %.2f,%.2f ', $px + $scale - $r, $r, $r, $r, $r);
                            } else {
                                $path .= sprintf('H %.2f V %.2f ', $px + $scale, $py + ($top ? 0 : $r));
                            }

                            // Right edge & Bottom-Right corner
                            if (!$bottom && !$right) {
                                $path .= sprintf('V %.2f a %.2f,%.2f 0 0 1 -%.2f,%.2f ', $py + $scale - $r, $r, $r, $r, $r);
                            } else {
                                $path .= sprintf('V %.2f H %.2f ', $py + $scale, $px + $scale - ($right ? 0 : $r));
                            }

                            // Bottom edge & Bottom-Left corner
                            if (!$bottom && !$left) {
                                $path .= sprintf('H %.2f a %.2f,%.2f 0 0 1 -%.2f,-%.2f ', $px + $r, $r, $r, $r, $r);
                            } else {
                                $path .= sprintf('H %.2f V %.2f ', $px, $py + $scale - ($bottom ? 0 : $r));
                            }

                            // Left edge & Top-Left corner
                            if (!$top && !$left) {
                                $path .= sprintf('V %.2f a %.2f,%.2f 0 0 1 %.2f,-%.2f ', $py + $r, $r, $r, $r, $r);
                            } else {
                                $path .= sprintf('V %.2f H %.2f ', $py, $px + ($left ? 0 : $r));
                            }

                            $path .= 'Z';

                            $svg[] = sprintf('<path d="%s" fill="%s"/>', $path, $moduleColor);
                            break;

                        // --- ROUNDED (Slightly rounded corners) ---
                        case 'rounded':
                            $rx = $scale * 0.25;
                            $svg[] = sprintf(
                                '<rect x="%d" y="%d" width="%d" height="%d" rx="%.2f" ry="%.2f" fill="%s"/>',
                                $px, $py, $scale, $scale, $rx, $rx, $moduleColor
                            );
                            break;

                        // --- CLASSIC / SQUARE (Default) ---
                        case 'square':
                        default:
                            $svg[] = sprintf(
                                '<rect x="%d" y="%d" width="%d" height="%d" fill="%s"/>',
                                $px, $py, $scale, $scale, $moduleColor
                            );
                            break;
                    }
                }
            }
        }

        // 3. Render Finder Corner Patterns
        foreach ($finderOrigins as [$fx, $fy]) {
            $x           = $fx * $scale;
            $y           = $fy * $scale;
            $outerDim    = 7 * $scale;
            $middleDim   = 5 * $scale;
            $innerDim    = 3 * $scale;

            $midOffset   = 1 * $scale;
            $innerOffset = 2 * $scale;

            // Helper to generate path for Leaf (TR + BL rounded) or Inverted Leaf (TL + BR rounded)
            $getLeafPath = function($px, $py, $dim, $r, $isInverted = false) {
                if (!$isInverted) {
                    // Leaf: Top-Right & Bottom-Left rounded[cite: 3, 4]
                    return sprintf(
                        'M %.2f,%.2f H %.2f a %.2f,%.2f 0 0 1 %.2f,%.2f V %.2f H %.2f a %.2f,%.2f 0 0 1 -%.2f,-%.2f V %.2f Z',
                        $px, $py,
                        $px + $dim - $r, $r, $r, $r, $r,
                        $py + $dim,
                        $px + $r, $r, $r, $r, $r,
                        $py
                    );
                } else {
                    // Inverted Leaf: Top-Left & Bottom-Right rounded[cite: 5]
                    return sprintf(
                        'M %.2f,%.2f H %.2f V %.2f a %.2f,%.2f 0 0 1 -%.2f,%.2f H %.2f V %.2f a %.2f,%.2f 0 0 1 %.2f,-%.2f Z',
                        $px + $r, $py,
                        $px + $dim,
                        $py + $dim - $r, $r, $r, $r, $r,
                        $px,
                        $py + $r, $r, $r, $r, $r
                    );
                }
            };

            // Helper to generate path for Pillow (4 outer rounded corners)[cite: 6, 7]
            $getPillowPath = function($px, $py, $dim, $r) {
                return sprintf(
                    'M %.2f,%.2f H %.2f a %.2f,%.2f 0 0 1 %.2f,%.2f V %.2f a %.2f,%.2f 0 0 1 -%.2f,%.2f H %.2f a %.2f,%.2f 0 0 1 -%.2f,-%.2f V %.2f a %.2f,%.2f 0 0 1 %.2f,-%.2f Z',
                    $px + $r, $py,
                    $px + $dim - $r, $r, $r, $r, $r,
                    $py + $dim - $r, $r, $r, $r, $r,
                    $px + $r, $r, $r, $r, $r,
                    $py + $r, $r, $r, $r, $r
                );
            };

            // Helper to generate path for Scallop / Notched corners[cite: 9]
            $getScallopPath = function($px, $py, $dim, $cut) {
                return sprintf(
                    'M %.2f,%.2f H %.2f a %.2f,%.2f 0 0 0 %.2f,%.2f V %.2f a %.2f,%.2f 0 0 0 -%.2f,%.2f H %.2f a %.2f,%.2f 0 0 0 -%.2f,-%.2f V %.2f a %.2f,%.2f 0 0 0 %.2f,-%.2f Z',
                    $px + $cut, $py,
                    $px + $dim - $cut, $cut, $cut, $cut, $cut,
                    $py + $dim - $cut, $cut, $cut, $cut, $cut,
                    $px + $cut, $cut, $cut, $cut, $cut,
                    $py + $cut, $cut, $cut, $cut, $cut
                );
            };

            // --- Outer Frame & Middle Cutout ---
            if (in_array($outerShape, ['circle', 'dots', 'dot'], true)) {
                $rOuter = $outerDim / 2;
                $rMid   = $middleDim / 2;
                $cx     = $x + $rOuter;
                $cy     = $y + $rOuter;

                $svg[] = sprintf('<circle cx="%.2f" cy="%.2f" r="%.2f" fill="%s"/>', $cx, $cy, $rOuter, $outerColor);
                $svg[] = sprintf('<circle cx="%.2f" cy="%.2f" r="%.2f" fill="%s"/>', $cx, $cy, $rMid, $bgColor);
            } elseif (in_array($outerShape, ['leaf', 'teardrop'], true)) {
                // TR & BL rounded[cite: 3, 4]
                $rOuter = $scale * 2.5;
                $rMid   = $scale * 1.5;

                $svg[] = sprintf('<path d="%s" fill="%s"/>', $getLeafPath($x, $y, $outerDim, $rOuter, false), $outerColor);
                $svg[] = sprintf('<path d="%s" fill="%s"/>', $getLeafPath($x + $midOffset, $y + $midOffset, $middleDim, $rMid, false), $bgColor);
            } elseif (in_array($outerShape, ['inverted', 'leaf-inverted'], true)) {
                // TL & BR rounded[cite: 5]
                $rOuter = $scale * 2.5;
                $rMid   = $scale * 1.5;

                $svg[] = sprintf('<path d="%s" fill="%s"/>', $getLeafPath($x, $y, $outerDim, $rOuter, true), $outerColor);
                $svg[] = sprintf('<path d="%s" fill="%s"/>', $getLeafPath($x + $midOffset, $y + $midOffset, $middleDim, $rMid, true), $bgColor);
            } elseif (in_array($outerShape, ['pillow', 'diamond-finder'], true)) {
                // Rounded corner frame[cite: 6, 7]
                $rOuter = $scale * 2.2;
                $rMid   = $scale * 1.2;

                $svg[] = sprintf('<path d="%s" fill="%s"/>', $getPillowPath($x, $y, $outerDim, $rOuter), $outerColor);
                $svg[] = sprintf('<path d="%s" fill="%s"/>', $getPillowPath($x + $midOffset, $y + $midOffset, $middleDim, $rMid), $bgColor);
            } elseif (in_array($outerShape, ['scallop', 'gear'], true)) {
                // Inset corner cuts[cite: 9]
                $cutOuter = $scale * 1.2;
                $cutMid   = $scale * 0.8;

                $svg[] = sprintf('<path d="%s" fill="%s"/>', $getScallopPath($x, $y, $outerDim, $cutOuter), $outerColor);
                $svg[] = sprintf('<path d="%s" fill="%s"/>', $getScallopPath($x + $midOffset, $y + $midOffset, $middleDim, $cutMid), $bgColor);
            } elseif (in_array($outerShape, ['rounded', 'smooth'], true)) {
                $rxOuter = $scale * 2.0;
                $rxMid   = $scale * 1.2;

                $svg[] = sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="%.2f" ry="%.2f" fill="%s"/>', $x, $y, $outerDim, $outerDim, $rxOuter, $rxOuter, $outerColor);
                $svg[] = sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="%.2f" ry="%.2f" fill="%s"/>', $x + $midOffset, $y + $midOffset, $middleDim, $middleDim, $rxMid, $rxMid, $bgColor);
            } else {
                // Square / Classic
                $svg[] = sprintf('<rect x="%d" y="%d" width="%d" height="%d" fill="%s"/>', $x, $y, $outerDim, $outerDim, $outerColor);
                $svg[] = sprintf('<rect x="%d" y="%d" width="%d" height="%d" fill="%s"/>', $x + $midOffset, $y + $midOffset, $middleDim, $middleDim, $bgColor);
            }

            // --- Inner Center Shape ---
            $ix = $x + $innerOffset;
            $iy = $y + $innerOffset;

            if (in_array($innerShape, ['circle', 'dots', 'dot'], true)) {
                $r = $innerDim / 2;
                $svg[] = sprintf('<circle cx="%.2f" cy="%.2f" r="%.2f" fill="%s"/>', $ix + $r, $iy + $r, $r, $innerColor);
            } elseif (in_array($innerShape, ['rounded', 'smooth'], true)) {
                $rxInner = $scale * 1.0;
                $svg[] = sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="%.2f" ry="%.2f" fill="%s"/>', $ix, $iy, $innerDim, $innerDim, $rxInner, $rxInner, $innerColor);
            } else {
                // Square / Classic
                $svg[] = sprintf('<rect x="%d" y="%d" width="%d" height="%d" fill="%s"/>', $ix, $iy, $innerDim, $innerDim, $innerColor);
            }
        }

        $svg[] = '</svg>';

        $output = implode('', $svg);

        if ($file !== null && method_exists($this, 'saveToFile')) {
            $this->saveToFile($output, $file);
        }

        return $output;
    }
    
}