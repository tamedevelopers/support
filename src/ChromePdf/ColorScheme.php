<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

/**
 * Maps to Chromium {@see https://chromedevtools.github.io/devtools-protocol/tot/Emulation/#method-setEmulatedMedia Emulation.setEmulatedMedia}
 * for {@code prefers-color-scheme}.
 *
 * Headless Chrome does not follow your installed Chrome or OS theme; use {@see self::Light} for typical “printed” / light pages.
 */
enum ColorScheme: string
{
    case Light = 'light';
    case Dark = 'dark';

    /**
     * Do not override media features (Chromium headless default; often resolves like dark for sites such as Google).
     */
    case NoPreference = 'no-preference';

    /**
     * Accepts plain strings (e.g. {@code "dark"}, {@code "light"}, {@code "no-preference"}).
     * Unknown or empty values fall back to {@see self::NoPreference}.
     */
    public static function parse(?string $value): self
    {
        if ($value === null) {
            return self::NoPreference;
        }

        $n = strtolower(str_replace('_', '-', trim($value)));
        if ($n === '') {
            return self::NoPreference;
        }

        return match ($n) {
            'light' => self::Light,
            'dark' => self::Dark,
            default => self::NoPreference,
        };
    }
}
