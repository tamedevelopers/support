<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

/**
 * Options for {@see Internal\PdfPipeline::rebuild()}: structural work uses FPDI + TCPDF; metadata-only updates use an
 * incremental {@code Info} dictionary append when no watermark/encryption/PDF/A pass is required.
 */
final class PdfRebuildOptions
{
    /**
     * @param list<string>|null $encryptBlockedPermissions Permission names to **block** (TCPDF / PDF semantics — same as
     *        {@see \TCPDF::setProtection()} first argument). {@code null} means use TCPDF default restrictive set when
     *        any password is set; {@code []} means do not block any permission flags (passwords only).
     */
    public function __construct(
        public readonly ?string $textWatermark = null,
        public readonly float $textWatermarkOpacity = 0.12,
        public readonly float $textWatermarkAngleDeg = 45.0,
        public readonly float $textWatermarkFontSizePt = 44.0,
        public readonly WatermarkPosition $textWatermarkPosition = WatermarkPosition::Center,
        public readonly ?string $imageWatermarkPath = null,
        public readonly float $imageWatermarkOpacity = 0.18,
        public readonly ?float $imageWatermarkWidthMm = null,
        public readonly WatermarkPosition $imageWatermarkPosition = WatermarkPosition::Center,
        public readonly ?string $encryptUserPassword = null,
        public readonly ?string $encryptOwnerPassword = null,
        public readonly ?array $encryptBlockedPermissions = null,
        public readonly int $encryptAlgorithm = 3,
        public readonly bool|int $pdfA = false,
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaAuthor = null,
        public readonly ?string $metaSubject = null,
        public readonly ?string $metaKeywords = null,
    ) {
    }

    /**
     * Full FPDI + TCPDF re-import (watermarks, encryption, PDF/A) — Chromium link annotations are not preserved.
     */
    public function needsStructuralRebuild(): bool
    {
        if ($this->textWatermark !== null && $this->textWatermark !== '') {
            return true;
        }
        if ($this->imageWatermarkPath !== null && $this->imageWatermarkPath !== '') {
            return true;
        }
        if ($this->pdfA !== false) {
            return true;
        }
        if ($this->encryptUserPassword !== null && $this->encryptUserPassword !== '') {
            return true;
        }
        if ($this->encryptOwnerPassword !== null && $this->encryptOwnerPassword !== '') {
            return true;
        }

        return false;
    }

    public function needsDocumentMetadataChange(): bool
    {
        return $this->metaTitle !== null
            || $this->metaAuthor !== null
            || $this->metaSubject !== null
            || $this->metaKeywords !== null;
    }

    public function needsTcpdfPass(): bool
    {
        return $this->needsStructuralRebuild() || $this->needsDocumentMetadataChange();
    }
}
