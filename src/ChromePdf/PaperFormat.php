<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Tamedevelopers\Support\Str;

/**
 * Named paper sizes for Chromium printToPDF (dimensions in millimetres).
 * * Includes standard ISO A and B series, and common North American formats.
 */
enum PaperFormat: string
{
    // ISO A-Series (International Standard)
    case A0 = 'A0';
    case A1 = 'A1';
    case A2 = 'A2';
    case A3 = 'A3';
    case A4 = 'A4';
    case A5 = 'A5';
    case A6 = 'A6';

    // ISO B-Series (Common for posters/books)
    case B4 = 'B4';
    case B5 = 'B5';

    // North American Standards
    case Letter    = 'Letter';
    case Legal     = 'Legal';
    case Ledger    = 'Ledger';
    case Tabloid   = 'Tabloid';
    case Executive = 'Executive';

    /**
     * Resolves a paper name (case-insensitive; also accepts exact enum values).
     *
     * @param string $value
     * @return self
     * * @throws ConversionFailedException
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
            'a0' => self::A0,
            'a1' => self::A1,
            'a2' => self::A2,
            'a3' => self::A3,
            'a4' => self::A4,
            'a5' => self::A5,
            'a6' => self::A6,
            'b4' => self::B4,
            'b5' => self::B5,
            'letter'    => self::Letter,
            'legal'     => self::Legal,
            'ledger'    => self::Ledger,
            'tabloid'   => self::Tabloid,
            'executive' => self::Executive,
            default => throw new ConversionFailedException(sprintf('Unknown paper format: %s', $value)),
        };
    }

    /**
     * Get width in millimetres.
     * * @return float
     */
    public function widthMm(): float
    {
        return match ($this) {
            self::A0 => 841.0,
            self::A1 => 594.0,
            self::A2 => 420.0,
            self::A3 => 297.0,
            self::A4 => 210.0,
            self::A5 => 148.0,
            self::A6 => 105.0,
            self::B4 => 250.0,
            self::B5 => 176.0,
            self::Letter => 215.9,
            self::Legal  => 215.9,
            self::Ledger, self::Tabloid => 279.4,
            self::Executive => 184.1,
        };
    }

    /**
     * Get height in millimetres.
     * * @return float
     */
    public function heightMm(): float
    {
        return match ($this) {
            self::A0 => 1189.0,
            self::A1 => 841.0,
            self::A2 => 594.0,
            self::A3 => 420.0,
            self::A4 => 297.0,
            self::A5 => 210.0,
            self::A6 => 148.0,
            self::B4 => 353.0,
            self::B5 => 250.0,
            self::Letter => 279.4,
            self::Legal  => 355.6,
            self::Ledger => 431.8,
            self::Tabloid => 431.8,
            self::Executive => 266.7,
        };
    }

    /**
     * Chromium expects inches for paperWidth / paperHeight.
     * * @return float
     */
    public function widthInches(): float
    {
        return $this->widthMm() / 25.4;
    }

    /**
     * Chromium expects inches for paperWidth / paperHeight.
     * * @return float
     */
    public function heightInches(): float
    {
        return $this->heightMm() / 25.4;
    }
}