<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Tamedevelopers\Support\Str;

/**
 * Named paper sizes for Chromium printToPDF (dimensions in millimetres).
 */
enum PaperFormat: string
{
    case A4 = 'A4';
    case A3 = 'A3';
    case Letter = 'Letter';
    case Legal = 'Legal';
    case Tabloid = 'Tabloid';

    /**
     * Resolves a paper name (case-insensitive; also accepts exact enum values like {@code A4}, {@code Letter}).
     *
     * @throws ConversionFailedException
     */
    public static function parse(string $value): self
    {
        $trimmed = Str::trim($value);
        if ($trimmed === '') {
            throw new ConversionFailedException('Paper format cannot be empty.');
        }

        $fromBacking = self::tryFrom($trimmed);
        if ($fromBacking !== null) {
            return $fromBacking;
        }

        return match (strtolower($trimmed)) {
            'a4' => self::A4,
            'a3' => self::A3,
            'letter' => self::Letter,
            'legal' => self::Legal,
            'tabloid' => self::Tabloid,
            default => throw new ConversionFailedException(sprintf('Unknown paper format: %s', $value)),
        };
    }

    public function widthMm(): float
    {
        return match ($this) {
            self::A4 => 210.0,
            self::A3 => 297.0,
            self::Letter => 215.9,
            self::Legal => 215.9,
            self::Tabloid => 279.4,
        };
    }

    public function heightMm(): float
    {
        return match ($this) {
            self::A4 => 297.0,
            self::A3 => 420.0,
            self::Letter => 279.4,
            self::Legal => 355.6,
            self::Tabloid => 431.8,
        };
    }

    /**
     * Chromium expects inches for paperWidth / paperHeight.
     */
    public function widthInches(): float
    {
        return $this->widthMm() / 25.4;
    }

    public function heightInches(): float
    {
        return $this->heightMm() / 25.4;
    }
}
