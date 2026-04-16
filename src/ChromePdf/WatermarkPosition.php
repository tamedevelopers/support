<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;

/**
 * Placement of text or image watermarks on each PDF page (FPDI/TCPDF pass).
 *
 * {@see self::parse()} accepts fluent spellings such as {@code top_left}, {@code top left}, {@code top-left}
 * (case-insensitive).
 */
enum WatermarkPosition: string
{
    /** Page center (previous default). */
    case Center = 'center';

    case TopLeft = 'top_left';
    case TopCenter = 'top_center';
    case TopRight = 'top_right';

    /** Vertical center, horizontal left (same row as {@code top_center} / {@code bottom_center}). */
    case CenterLeft = 'center_left';
    /** Vertical center, horizontal right. */
    case CenterRight = 'center_right';

    case BottomLeft = 'bottom_left';
    case BottomCenter = 'bottom_center';
    case BottomRight = 'bottom_right';

    /**
     * Resolves a position from a string: spaces and hyphens act like underscores; matching is case-insensitive.
     *
     * @throws ConversionFailedException
     */
    public static function parse(string $value): self
    {
        $normalized = strtolower(
            (string) preg_replace('/_+/u', '_', (string) preg_replace('/[\s\-]+/u', '_', trim($value)))
        );
        $normalized = trim($normalized, '_');
        if ($normalized === '') {
            throw new ConversionFailedException('Watermark position cannot be empty.');
        }

        $fromBacking = self::tryFrom($normalized);
        if ($fromBacking !== null) {
            return $fromBacking;
        }

        // Legacy names (middle_*); keep in sync with {@see CenterLeft} / {@see CenterRight}.
        $legacy = match ($normalized) {
            'middle_left' => self::CenterLeft,
            'middle_right' => self::CenterRight,
            default => null,
        };
        if ($legacy !== null) {
            return $legacy;
        }

        throw new ConversionFailedException(sprintf('Unknown watermark position: %s', $value));
    }
}
