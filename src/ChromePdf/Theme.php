<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

use InvalidArgumentException;

/**
 * Collects custom CSS to inject into the headless document before PDF capture.
 * For typical use, prefer {@see ChromePdf::css()} and {@see ChromePdf::cssFile()}; use this class when building a reusable style bundle.
 */
final class Theme
{
    /** @var list<string> */
    private array $cssChunks = [];

    public static function create(): self
    {
        return new self();
    }

    public function addCssString(string $css): self
    {
        $this->cssChunks[] = $css;

        return $this;
    }

    public function addCssFile(string $path): self
    {
        if (!is_readable($path)) {
            throw new InvalidArgumentException(sprintf('CSS file is not readable: %s', $path));
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new InvalidArgumentException(sprintf('Could not read CSS file: %s', $path));
        }
        $this->cssChunks[] = $contents;

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->cssChunks === [];
    }

    public function toCssString(): string
    {
        return implode("\n\n", $this->cssChunks);
    }
}
