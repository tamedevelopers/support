<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;
use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Tamedevelopers\Support\ChromePdf\PdfOutput;
use Tamedevelopers\Support\ChromePdf\PdfRebuildOptions;
use Tamedevelopers\Support\ChromePdf\WatermarkPosition;
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
                // Move TCPDF's page mark to after the imported Chromium page so watermarks are not spliced "under"
                // earlier content (see {@see \TCPDF::setPageMark()}).
                $pdf->setPageMark();

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

        $pdf->SetFont('helvetica', 'B', $options->textWatermarkFontSizePt);
        $sw = $pdf->GetStringWidth($options->textWatermark);
        $fsMm = max(1.0, $options->textWatermarkFontSizePt * 0.352778);
        $pad = 8.0;

        [$xText, $yText, $rx, $ry] = match ($options->textWatermarkPosition) {
            WatermarkPosition::Center => [
                max(5.0, $w / 2.0 - $sw / 2.0),
                $h / 2.0,
                $w / 2.0,
                $h / 2.0,
            ],
            WatermarkPosition::TopLeft => [
                $pad,
                $pad + $fsMm * 0.85,
                $pad + $sw / 2.0,
                $pad + $fsMm * 0.85 - $fsMm * 0.35,
            ],
            WatermarkPosition::TopCenter => [
                max(5.0, $w / 2.0 - $sw / 2.0),
                $pad + $fsMm * 0.85,
                $w / 2.0,
                $pad + $fsMm * 0.85 - $fsMm * 0.35,
            ],
            WatermarkPosition::TopRight => [
                max($pad, $w - $pad - $sw),
                $pad + $fsMm * 0.85,
                max($pad, $w - $pad - $sw) + $sw / 2.0,
                $pad + $fsMm * 0.85 - $fsMm * 0.35,
            ],
            WatermarkPosition::CenterLeft => [
                $pad,
                $h / 2.0 + $fsMm * 0.25,
                $pad + $sw / 2.0,
                $h / 2.0 + $fsMm * 0.25 - $fsMm * 0.35,
            ],
            WatermarkPosition::CenterRight => [
                max($pad, $w - $pad - $sw),
                $h / 2.0 + $fsMm * 0.25,
                max($pad, $w - $pad - $sw) + $sw / 2.0,
                $h / 2.0 + $fsMm * 0.25 - $fsMm * 0.35,
            ],
            WatermarkPosition::BottomLeft => [
                $pad,
                $h - $pad,
                $pad + $sw / 2.0,
                ($h - $pad) - $fsMm * 0.35,
            ],
            WatermarkPosition::BottomCenter => [
                max(5.0, $w / 2.0 - $sw / 2.0),
                $h - $pad,
                $w / 2.0,
                ($h - $pad) - $fsMm * 0.35,
            ],
            WatermarkPosition::BottomRight => [
                max($pad, $w - $pad - $sw),
                $h - $pad,
                max($pad, $w - $pad - $sw) + $sw / 2.0,
                ($h - $pad) - $fsMm * 0.35,
            ],
        };

        $opacity = max(0.02, min(1.0, $options->textWatermarkOpacity));
        $angle = $options->textWatermarkAngleDeg;
        if (abs($angle) < 0.0001) {
            $pdf->setAlpha($opacity, 'Normal');
            $pdf->SetTextColor(128, 128, 128);
            $pdf->Text($xText, $yText, $options->textWatermark);
            $pdf->setAlpha(1.0, 'Normal');
        } else {
            $pdf->StartTransform();
            $pdf->setAlpha($opacity, 'Normal');
            $pdf->Rotate($angle, $rx, $ry);
            $pdf->SetTextColor(128, 128, 128);
            $pdf->Text($xText, $yText, $options->textWatermark);
            $pdf->StopTransform();
            $pdf->setAlpha(1.0, 'Normal');
        }
        $pdf->setPageMark();
    }

    private static function stampImageWatermark(Fpdi $pdf, PdfRebuildOptions $options): void
    {
        $path = Tame::stringReplacer($options->imageWatermarkPath ?? '');
        if ($path === '' || !is_readable($path)) {
            throw new ConversionFailedException(sprintf('Image watermark path is not readable: %s', $options->imageWatermarkPath ?? ''));
        }

        $info = @getimagesize($path);
        if ($info === false || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
            throw new ConversionFailedException(sprintf('Image watermark could not be measured: %s', $options->imageWatermarkPath ?? ''));
        }

        $w = $pdf->getPageWidth();
        $h = $pdf->getPageHeight();
        $targetW = $options->imageWatermarkWidthMm;
        if ($targetW === null) {
            $targetW = $w * 0.45;
        }
        $targetW = max(5.0, $targetW);

        $iw = (float) $info[0];
        $ih = (float) $info[1];
        $targetH = max(1.0, $targetW * ($ih / $iw));

        $pad = 8.0;
        [$x, $y] = match ($options->imageWatermarkPosition) {
            WatermarkPosition::Center => [
                ($w - $targetW) / 2.0,
                ($h - $targetH) / 2.0,
            ],
            WatermarkPosition::TopLeft => [$pad, $pad],
            WatermarkPosition::TopCenter => [($w - $targetW) / 2.0, $pad],
            WatermarkPosition::TopRight => [max($pad, $w - $pad - $targetW), $pad],
            WatermarkPosition::CenterLeft => [$pad, ($h - $targetH) / 2.0],
            WatermarkPosition::CenterRight => [max($pad, $w - $pad - $targetW), ($h - $targetH) / 2.0],
            WatermarkPosition::BottomLeft => [$pad, max($pad, $h - $pad - $targetH)],
            WatermarkPosition::BottomCenter => [($w - $targetW) / 2.0, max($pad, $h - $pad - $targetH)],
            WatermarkPosition::BottomRight => [max($pad, $w - $pad - $targetW), max($pad, $h - $pad - $targetH)],
        };

        $pdf->setAlpha(max(0.02, min(1.0, $options->imageWatermarkOpacity)), 'Normal');
        $pdf->Image($path, $x, $y, $targetW, 0, '', '', '', false, 300, '', false, false, 0);
        $pdf->setAlpha(1.0, 'Normal');
        $pdf->setPageMark();
    }

}
