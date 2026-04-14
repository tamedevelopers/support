<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Traits;

use Tamedevelopers\Support\ChromePdf\Exception\FontNotFoundException;
use Tamedevelopers\Support\ChromePdf\Internal\FileUri;
use Tamedevelopers\Support\Traits\FontPathTrait;

/**
 * Builds @font-face CSS from resolved system/bundled fonts when content contains non-ASCII text.
 */
trait FontManagerTrait
{
    use FontPathTrait;

    /**
     * @throws FontNotFoundException
     */
    protected function buildAutoFontFaceCss(string $content, bool $throwIfUnicodeMissingFont): string
    {
        if (!self::needsUnicodeFont($content)) {
            return '';
        }

        $fontPath = self::resolveFontPath(null, 'normal', $content);
        if ($fontPath === null) {
            if ($throwIfUnicodeMissingFont) {
                throw new FontNotFoundException(
                    'No suitable font file found for non-ASCII content. Install Noto CJK, Microsoft YaHei, or bundle fonts under src/Traits/icons/fonts.'
                );
            }

            return '';
        }

        $uri = FileUri::fromPath($fontPath);
        $escaped = self::escapeCssUrl($uri);
        $family = 'SupportPdfUnicode';

        return <<<CSS
            @font-face {
                font-family: "{$family}";
                font-style: normal;
                font-weight: 400;
                src: url("{$escaped}") format("truetype");
            }
            html, body {
                font-family: "{$family}", "PingFang SC", "Microsoft YaHei", "Noto Sans CJK SC", sans-serif;
            }
        CSS;
    }

    private static function escapeCssUrl(string $url): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $url);
    }
}
