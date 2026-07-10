<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Traits;

use Tamedevelopers\Support\ChromePdf\Internal\FileUri;
use Tamedevelopers\Support\Traits\FontPathTrait;

/**
 * Builds @font-face CSS chunks keyed by script/range for client-side “smart” injection.
 */
trait FontManagerTrait
{
    use FontPathTrait;

    /**
     * Returns a map of CSS fragments (each: {@code @font-face} + scoped rules) keyed by script bucket.
     * Keys: {@code cjk}, {@code arabic}, {@code cyrillic}. Omitted keys mean no suitable font file was found.
     *
     * @return array<string, string>
     */
    protected function buildAutoFontFaceCssMap(): array
    {
        /** @var array<string, string> $samples */
        $samples = [
            'cjk' => '字',
            'arabic' => "\u{0627}",
            'cyrillic' => "\u{0416}",
        ];

        $map = [];
        foreach ($samples as $key => $sample) {
            if (!self::needsUnicodeFont($sample)) {
                continue;
            }
            $fontPath = self::resolveFontPath(null, 'normal', $sample);
            if ($fontPath === null) {
                continue;
            }
            $family = 'SupportPdf_' . $key;
            $map[$key] = $this->buildFontFaceCssChunk($fontPath, $family);
        }

        return $map;
    }

    private function buildFontFaceCssChunk(string $fontPath, string $family): string
    {
        $uri = FileUri::fromPath($fontPath);
        $escaped = self::escapeCssUrl($uri);

        return <<<CSS
            @font-face {
                font-family: "{$family}";
                font-style: normal;
                font-weight: 400;
                src: url("{$escaped}") format("truetype");
            }
        CSS;
    }

    private static function escapeCssUrl(string $url): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $url);
    }
}
