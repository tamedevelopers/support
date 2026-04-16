<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;
use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Tamedevelopers\Support\ChromePdf\PdfOutput;
use Tamedevelopers\Support\ChromePdf\PdfRebuildOptions;
use Tamedevelopers\Support\Tame;
use Throwable;

/**
 * Merge / rebuild / linearize PDFs using FPDI + TCPDF when those packages are installed (see composer suggest).
 */
final class PdfPipeline
{
    public static function assertDependenciesAvailable(): void
    {
        if (!class_exists(Fpdi::class)) {
            throw new ConversionFailedException(
                'PDF merge, watermark, encryption, and PDF/A need: composer require setasign/fpdi tecnickcom/tcpdf '
                . '(FPDI supplies Tcpdf\\Fpdi but it extends TCPDF, so both packages are required; see '
                . 'https://github.com/Setasign/FPDI and https://manuals.setasign.com/fpdi-manual/).'
            );
        }
    }

    /**
     * @param list<PdfOutput|string> $sources Readable file paths or {@see PdfOutput} instances (binary PDF).
     *
     * @throws ConversionFailedException
     */
    public static function merge(array $sources): PdfOutput
    {
        self::assertDependenciesAvailable();
        if ($sources === []) {
            throw new ConversionFailedException('merge() requires at least one PDF path or PdfOutput instance.');
        }

        try {
            $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false, false);
            self::configureTcpdfDocument($pdf);

            foreach ($sources as $index => $item) {
                $reader = self::openSourceReader($item, $index);
                $pageCount = $pdf->setSourceFile($reader);
                for ($p = 1; $p <= $pageCount; ++$p) {
                    $tpl = $pdf->importPage($p);
                    $size = $pdf->getTemplateSize($tpl);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tpl);
                }
            }

            $binary = $pdf->Output('', 'S');
            if (!is_string($binary) || $binary === '') {
                throw new ConversionFailedException('PDF merge produced empty output.');
            }

            return new PdfOutput($binary);
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ConversionFailedException('PDF merge failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Re-imports one PDF and reapplies optional watermarks, metadata, encryption, PDF/A, and optional qpdf linearization.
     *
     * @throws ConversionFailedException
     */
    public static function rebuild(string $binary, PdfRebuildOptions $options): PdfOutput
    {
        if ($binary === '') {
            throw new ConversionFailedException('Cannot rebuild an empty PDF payload.');
        }

        $pdfAArg = $options->pdfA === false ? false : (int) $options->pdfA;
        if ($pdfAArg !== false && $pdfAArg !== 1 && $pdfAArg !== 3) {
            throw new ConversionFailedException('pdfA() level must be false, 1 (PDF/A-1b), or 3 (PDF/A-3).');
        }

        $hasUserPass = $options->encryptUserPassword !== null && $options->encryptUserPassword !== '';
        $hasOwnerPass = $options->encryptOwnerPassword !== null && $options->encryptOwnerPassword !== '';
        $wantsEncryption = $hasUserPass || $hasOwnerPass;

        if ($pdfAArg !== false && $wantsEncryption) {
            throw new ConversionFailedException('TCPDF does not support encryption together with PDF/A; disable one of them.');
        }

        if (!$options->needsTcpdfPass() && !$options->linearize) {
            return new PdfOutput($binary);
        }

        if (!$options->needsTcpdfPass() && $options->linearize) {
            return new PdfOutput(self::linearizeWithQpdf($binary));
        }

        self::assertDependenciesAvailable();

        try {
            $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false, $pdfAArg);
            self::configureTcpdfDocument($pdf);

            self::applyMetadata($pdf, $options);

            $reader = StreamReader::createByString($binary);
            $pageCount = $pdf->setSourceFile($reader);
            for ($p = 1; $p <= $pageCount; ++$p) {
                $tpl = $pdf->importPage($p);
                $size = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);

                if ($options->textWatermark !== null && $options->textWatermark !== '') {
                    self::stampTextWatermark($pdf, $options);
                }
                if ($options->imageWatermarkPath !== null && $options->imageWatermarkPath !== '') {
                    self::stampImageWatermark($pdf, $options);
                }
            }

            if ($wantsEncryption) {
                $blocked = $options->encryptBlockedPermissions;
                if ($blocked === null) {
                    $blocked = ['print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'];
                }
                $pdf->setProtection(
                    $blocked,
                    (string) ($options->encryptUserPassword ?? ''),
                    $options->encryptOwnerPassword !== null && $options->encryptOwnerPassword !== ''
                        ? $options->encryptOwnerPassword
                        : null,
                    $options->encryptAlgorithm
                );
            }

            $out = $pdf->Output('', 'S');
            if (!is_string($out) || $out === '') {
                throw new ConversionFailedException('PDF rebuild produced empty output.');
            }

            if ($options->linearize) {
                $out = self::linearizeWithQpdf($out);
            }

            return new PdfOutput($out);
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ConversionFailedException('PDF post-processing failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private static function openSourceReader(PdfOutput|string $item, int $index): StreamReader
    {
        if ($item instanceof PdfOutput) {
            return StreamReader::createByString($item->getContents());
        }
        if (is_string($item)) {
            $path = Tame::stringReplacer($item);
            if (!is_readable($path)) {
                throw new ConversionFailedException(sprintf('merge(): source #%d is not a readable path: %s', $index, $item));
            }

            return StreamReader::createByFile($path);
        }

        throw new ConversionFailedException(
            sprintf('merge(): source #%d must be a string path or PdfOutput, %s given.', $index, get_debug_type($item))
        );
    }

    private static function applyMetadata(Fpdi $pdf, PdfRebuildOptions $options): void
    {
        if ($options->metaTitle !== null) {
            $pdf->SetTitle($options->metaTitle);
        }
        if ($options->metaAuthor !== null) {
            $pdf->SetAuthor($options->metaAuthor);
        }
        if ($options->metaSubject !== null) {
            $pdf->SetSubject($options->metaSubject);
        }
        if ($options->metaKeywords !== null) {
            $pdf->SetKeywords($options->metaKeywords);
        }
    }

    /**
     * Disable TCPDF's own header/footer chrome so imported Chromium pages stay visually unchanged.
     */
    private static function configureTcpdfDocument(Fpdi $pdf): void
    {
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0.0, 0.0, 0.0, true);
        $pdf->SetHeaderMargin(0.0);
        $pdf->SetFooterMargin(0.0);
        $pdf->SetAutoPageBreak(false, 0.0);
    }

    private static function stampTextWatermark(Fpdi $pdf, PdfRebuildOptions $options): void
    {
        $w = $pdf->getPageWidth();
        $h = $pdf->getPageHeight();
        $cx = $w / 2.0;
        $cy = $h / 2.0;

        $pdf->StartTransform();
        $pdf->SetAlpha(max(0.02, min(1.0, $options->textWatermarkOpacity)));
        $pdf->Rotate($options->textWatermarkAngleDeg, $cx, $cy);
        $pdf->SetFont('helvetica', 'B', $options->textWatermarkFontSizePt);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Text(
            max(5.0, $cx - $pdf->GetStringWidth($options->textWatermark) / 2.0),
            $cy,
            $options->textWatermark
        );
        $pdf->StopTransform();
        $pdf->SetAlpha(1.0);
    }

    private static function stampImageWatermark(Fpdi $pdf, PdfRebuildOptions $options): void
    {
        $path = Tame::stringReplacer($options->imageWatermarkPath ?? '');
        if ($path === '' || !is_readable($path)) {
            throw new ConversionFailedException(sprintf('Image watermark path is not readable: %s', $options->imageWatermarkPath ?? ''));
        }

        $w = $pdf->getPageWidth();
        $h = $pdf->getPageHeight();
        $targetW = $options->imageWatermarkWidthMm;
        if ($targetW === null) {
            $targetW = $w * 0.45;
        }
        $targetW = max(5.0, $targetW);

        $pdf->StartTransform();
        $pdf->SetAlpha(max(0.02, min(1.0, $options->imageWatermarkOpacity)));
        $x = ($w - $targetW) / 2.0;
        $y = $h / 2.0 - $targetW * 0.35;
        $pdf->Image($path, $x, $y, $targetW, 0, '', '', '', false, 300, '', false, false, 0);
        $pdf->StopTransform();
        $pdf->SetAlpha(1.0);
    }

    /**
     * @throws ConversionFailedException
     */
    private static function linearizeWithQpdf(string $binary): string
    {
        $qpdf = self::resolveQpdfBinary();
        if ($qpdf === null) {
            throw new ConversionFailedException(
                'linearize() requires the `qpdf` CLI (https://qpdf.sourceforge.io/) on PATH, or set QPDF_BINARY to the executable.'
            );
        }

        $in = tempnam(sys_get_temp_dir(), 'spdfin');
        $out = tempnam(sys_get_temp_dir(), 'spdfout');
        if ($in === false || $out === false) {
            throw new ConversionFailedException('Could not create temporary files for qpdf linearization.');
        }

        try {
            if (@file_put_contents($in, $binary) === false) {
                throw new ConversionFailedException('Could not write temporary PDF for qpdf.');
            }

            $cmd = sprintf(
                '%s --linearize %s %s',
                escapeshellarg($qpdf),
                escapeshellarg($in),
                escapeshellarg($out)
            );

            $code = 0;
            $output = [];
            @exec($cmd . ' 2>&1', $output, $code);
            $mergedOut = @file_get_contents($out);
            if ($code !== 0 || !is_string($mergedOut) || $mergedOut === '') {
                $detail = $output !== [] ? implode("\n", $output) : 'no output';
                throw new ConversionFailedException('qpdf linearization failed (exit ' . $code . '): ' . $detail);
            }

            return $mergedOut;
        } finally {
            @unlink($in);
            @unlink($out);
        }
    }

    private static function resolveQpdfBinary(): ?string
    {
        $env = getenv('QPDF_BINARY');
        if (is_string($env) && $env !== '') {
            $env = str_replace('/', DIRECTORY_SEPARATOR, $env);
            if (is_file($env) || @is_executable($env)) {
                return $env;
            }
        }

        $o = [];
        if (PHP_OS_FAMILY === 'Windows') {
            @exec('where qpdf 2>nul', $o);
        } else {
            @exec('command -v qpdf 2>/dev/null', $o);
        }
        if ($o !== [] && isset($o[0])) {
            $path = trim((string) $o[0]);
            if ($path !== '' && (is_file($path) || @is_executable($path))) {
                return $path;
            }
        }

        return null;
    }
}
