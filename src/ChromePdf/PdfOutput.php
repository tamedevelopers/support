<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Tamedevelopers\Support\Str;

/**
 * Raw PDF bytes with delivery helpers.
 *
 * Only {@see save()} accepts a full filesystem path. Streaming methods use a suggested filename for the browser only
 * (they do not write to disk — the browser’s download location is controlled by the user agent).
 */
final class PdfOutput
{
    public function __construct(private string $binary)
    {
    }

    public function getContents(): string
    {
        return $this->binary;
    }

    /**
     * Write PDF bytes to a filesystem path (creates parent directories).
     *
     * @param string $path Absolute or relative path, e.g. {@code storage_path('app/out.pdf')}
     *
     * @throws ConversionFailedException
     */
    public function save(string $path): void
    {
        self::writeBinaryToPath($path, $this->binary);
    }

    /**
     * Sends the PDF as a download ({@code Content-Disposition: attachment}). The browser saves to the user’s default
     * download folder; {@code $filename} is only the suggested name (not a path).
     *
     * @throws ConversionFailedException
     */
    public function download(string $filename = 'document.pdf'): void
    {
        $this->sendStreamHeaders(self::sanitizeFilename($filename), true);
        echo $this->binary;
    }

    /**
     * Sends the PDF for inline display in the browser ({@code Content-Disposition: inline}).
     * {@code $filename} is the suggested name only (not a path).
     *
     * @throws ConversionFailedException
     */
    public function inline(string $filename = 'document.pdf'): void
    {
        $this->streamInline(self::sanitizeFilename($filename), false);
    }

    /**
     * Same as {@see inline()} with stricter no-cache headers (handy while iterating in the browser).
     * Alias: one implementation, different cache policy and default filename.
     *
     * @throws ConversionFailedException
     */
    public function view(string $filename = 'preview.pdf'): void
    {
        $this->streamInline(self::sanitizeFilename($filename), true);
    }

    /**
     * @throws ConversionFailedException
     */
    private function streamInline(string $filename, bool $livePreview): void
    {
        $this->sendStreamHeaders($filename, false, $livePreview);
        echo $this->binary;
    }

    /**
     * @throws ConversionFailedException
     */
    private function sendStreamHeaders(string $filename, bool $attachment, bool $livePreview = false): void
    {
        if (headers_sent($file, $line)) {
            throw new ConversionFailedException(
                sprintf('Headers already sent in %s on line %d; cannot stream PDF.', $file, $line)
            );
        }

        $disposition = $attachment ? 'attachment' : 'inline';
        $safeName = preg_replace('/[^-a-zA-Z0-9_.]/', '_', $filename) ?: 'document.pdf';

        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string) strlen($this->binary));
        header(sprintf('Content-Disposition: %s; filename="%s"', $disposition, $safeName));

        if ($livePreview) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        } else {
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }
    }

    private static function sanitizeFilename(string $filename): string
    {
        $t = Str::trim($filename);
        if ($t === '') {
            return 'document.pdf';
        }

        $base = basename(str_replace('\\', '/', $t));

        return $base !== '' ? $base : 'document.pdf';
    }

    /**
     * @throws ConversionFailedException
     */
    private static function writeBinaryToPath(string $path, string $binary): void
    {
        $dir = dirname($path);
        if ($dir !== '' && $dir !== '.' && !is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new ConversionFailedException(sprintf('Could not create directory: %s', $dir));
        }

        if (file_put_contents($path, $binary) === false) {
            throw new ConversionFailedException(sprintf('Could not write PDF file: %s', $path));
        }
    }
}
