<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Traits;

use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Tamedevelopers\Support\ChromePdf\Internal\PdfPipeline;
use Tamedevelopers\Support\ChromePdf\PdfOutput;
use Tamedevelopers\Support\ChromePdf\PdfRebuildOptions;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use TCPDF;
use Throwable;

/**
 * Optional PDF document features for {@see \Tamedevelopers\Support\ChromePdf\ChromePdf}: native Chromium header/footer
 * templates, merge, watermark, encryption, PDF/A, linearization (qpdf), and metadata.
 *
 * {@see \setasign\Fpdi\Tcpdf\Fpdi} is optional and needs **both** {@code setasign/fpdi} and {@code tecnickcom/tcpdf}
 * (see composer {@code suggest}; do not use the abandoned {@code setasign/fpdi-tcpdf} meta package). When missing,
 * merge / post-process throw {@see ConversionFailedException} with an install hint.
 *
 * @phpstan-require-extends \Tamedevelopers\Support\ChromePdf\ChromePdf
 */
trait ChromePdfDocumentTrait
{
    private ?string $pdfDocHeaderHtml = null;

    private ?string $pdfDocFooterHtml = null;
    private ?string $pdfDocHeaderBackgroundColor = null;
    private ?string $pdfDocFooterBackgroundColor = null;
    private string $pdfDocHeaderTextAlign = 'center';
    private string $pdfDocFooterTextAlign = 'center';
    private ?string $pdfDocHeaderTextColor = null;
    private ?string $pdfDocFooterTextColor = null;

    private ?string $pdfDocWatermarkText = null;

    private float $pdfDocWatermarkTextOpacity = 0.12;

    private float $pdfDocWatermarkTextAngleDeg = 45.0;

    private float $pdfDocWatermarkTextFontSizePt = 44.0;

    private ?string $pdfDocWatermarkImagePath = null;

    private float $pdfDocWatermarkImageOpacity = 0.18;

    private ?float $pdfDocWatermarkImageWidthMm = null;

    private ?string $pdfDocEncryptUserPassword = null;

    private ?string $pdfDocEncryptOwnerPassword = null;

    /** @var list<string>|null */
    private ?array $pdfDocEncryptBlockedPermissions = null;

    private int $pdfDocEncryptAlgorithm = 3;

    private bool|int $pdfDocPdfA = false;

    private bool $pdfDocLinearize = false;

    private ?string $pdfDocMetaTitle = null;

    private ?string $pdfDocMetaAuthor = null;

    private ?string $pdfDocMetaSubject = null;

    private ?string $pdfDocMetaKeywords = null;

    /**
     * Chromium {@code Page.printToPDF} header template (HTML). Implies {@code displayHeaderFooter}.
     * Use classes like {@code date}, {@code title}, {@code url}, {@code pageNumber}, {@code totalPages}.
     * Note: Chromium replaces these class placeholders with runtime values. For literal text, avoid those class names
     * (e.g. use {@code <span>Hi</span>} instead of {@code <span class="title">Hi</span>}).
     *
     * @see https://chromedevtools.github.io/devtools-protocol/tot/Page/#method-printToPDF
     */
    public function headerHtml(?string $html = null): self
    {
        if (empty($html)) {
            // Chromium's .title placeholder can be empty when the source document has no title.
            // Use a deterministic literal default so headerHtml() without args always renders.
            $html = $this->chromePdfResolveDefaultHeaderText();
        }

        $this->pdfDocHeaderHtml = $html;

        return $this;
    }

    /**
     * Chromium native footer template (HTML). Same placeholders as {@see headerHtml()}.
     */
    public function footerHtml(?string $html = null): self
    {
        if (empty($html)) {
            $html = '<span class="pageNumber"></span> / <span class="totalPages"></span>';
        }

        $this->pdfDocFooterHtml = $html;

        return $this;
    }

    /**
     * Force a printable background color in the header template area.
     * Pass null/empty to keep it transparent.
     */
    public function headerBackground(?string $cssColor = null): self
    {
        $this->pdfDocHeaderBackgroundColor = !empty($cssColor) ? trim($cssColor) : null;

        return $this;
    }

    /**
     * Set header text color (e.g. #fff, rgb(...), var(...)). Null resets to default.
     */
    public function headerColor(?string $cssColor = null): self
    {
        $this->pdfDocHeaderTextColor = !empty($cssColor) ? trim($cssColor) : null;

        return $this;
    }

    /**
     * Force a printable background color in the footer template area.
     * Pass null/empty to keep it transparent.
     */
    public function footerBackground(?string $cssColor = null): self
    {
        $this->pdfDocFooterBackgroundColor = !empty($cssColor) ? trim($cssColor) : null;

        return $this;
    }

    /**
     * Set footer text color (e.g. #fff, rgb(...), var(...)). Null resets to default.
     */
    public function footerColor(?string $cssColor = null): self
    {
        $this->pdfDocFooterTextColor = !empty($cssColor) ? trim($cssColor) : null;

        return $this;
    }

    public function headerLeft(): self
    {
        $this->pdfDocHeaderTextAlign = 'left';

        return $this;
    }

    public function headerRight(): self
    {
        $this->pdfDocHeaderTextAlign = 'right';

        return $this;
    }

    public function footerLeft(): self
    {
        $this->pdfDocFooterTextAlign = 'left';

        return $this;
    }

    public function footerRight(): self
    {
        $this->pdfDocFooterTextAlign = 'right';

        return $this;
    }

    public function textWatermark(
        ?string $text,
        float $opacity = 0.12,
        float $angleDegrees = 45.0,
        float $fontSizePt = 44.0,
    ): self {
        $this->pdfDocWatermarkText = !empty($text) ? $text : null;
        $this->pdfDocWatermarkTextOpacity = max(0.02, min(1.0, $opacity));
        $this->pdfDocWatermarkTextAngleDeg = $angleDegrees;
        $this->pdfDocWatermarkTextFontSizePt = max(6.0, $fontSizePt);

        return $this;
    }

    public function imageWatermark(
        ?string $absoluteOrProjectPath,
        float $opacity = 0.18,
        ?float $widthMm = null,
    ): self {
        $this->pdfDocWatermarkImagePath = ($absoluteOrProjectPath !== null && $absoluteOrProjectPath !== '')
            ? $absoluteOrProjectPath
            : null;
        $this->pdfDocWatermarkImageOpacity = max(0.02, min(1.0, $opacity));
        $this->pdfDocWatermarkImageWidthMm = $widthMm;

        return $this;
    }

    /**
     * Password protection and permission flags (requires FPDI + TCPDF). At least one password must be non-empty.
     *
     * @param list<string>|null $blockedPermissions Names of permissions to **block** (TCPDF convention — same as
     *        {@see TCPDF::setProtection()}): {@code print}, {@code modify}, {@code copy}, {@code annot-forms},
     *        {@code fill-forms}, {@code extract}, {@code assemble}, {@code print-high}. {@code null} = TCPDF default
     *        restrictive set when encrypting.
     * @param int $algorithm {@code 0}=RC4 40-bit, {@code 1}=RC4 128, {@code 2}=AES 128, {@code 3}=AES 256 (recommended).
     */
    public function encrypt(
        ?string $userPassword = null,
        ?string $ownerPassword = null,
        ?array $blockedPermissions = null,
        int $algorithm = 3,
    ): self {
        $this->pdfDocEncryptUserPassword = $userPassword;
        $this->pdfDocEncryptOwnerPassword = $ownerPassword;
        $this->pdfDocEncryptBlockedPermissions = $blockedPermissions;
        $this->pdfDocEncryptAlgorithm = max(0, min(3, $algorithm));

        return $this;
    }

    /**
     * PDF/A output via TCPDF when rebuilding ({@code 1} = PDF/A-1b style, {@code 3} = PDF/A-3). Cannot be combined
     * with {@see encrypt()}.
     */
    public function pdfA(bool|int $level): self
    {
        $this->pdfDocPdfA = $level;

        return $this;
    }

    /**
     * Fast Web View via external {@code qpdf --linearize}. Requires {@code qpdf} on PATH or {@code QPDF_BINARY}.
     */
    public function linearize(bool $enable = true): self
    {
        $this->pdfDocLinearize = $enable;

        return $this;
    }

    public function documentMetadata(
        ?string $title = null,
        ?string $author = null,
        ?string $subject = null,
        ?string $keywords = null,
    ): self {
        $this->pdfDocMetaTitle = $title;
        $this->pdfDocMetaAuthor = $author;
        $this->pdfDocMetaSubject = $subject;
        $this->pdfDocMetaKeywords = $keywords;

        return $this;
    }

    /**
     * Concatenate existing PDFs (paths or {@see PdfOutput}). Requires suggested FPDI + TCPDF packages.
     *
     * @param list<PdfOutput|string> $files
     *
     * @throws ConversionFailedException
     */
    public static function merge(array $files): PdfOutput
    {
        try {
            return PdfPipeline::merge($files);
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ConversionFailedException('PDF merge failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Re-run FPDI/TCPDF (and optional qpdf) on an existing PDF using the options configured on this builder instance.
     * Does not invoke Chromium.
     *
     * @throws ConversionFailedException
     */
    public function reprocessPdf(PdfOutput|string $input): PdfOutput
    {
        if ($input instanceof PdfOutput) {
            $binary = $input->getContents();
        } else {
            $path = Tame::stringReplacer($input);
            if (!is_readable($path)) {
                throw new ConversionFailedException(sprintf('reprocessPdf(): file is not readable: %s', $input));
            }
            $read = @file_get_contents($path);
            $binary = is_string($read) ? $read : '';
        }
        if ($binary === '') {
            throw new ConversionFailedException('reprocessPdf(): input PDF is empty or unreadable.');
        }

        try {
            return PdfPipeline::rebuild($binary, $this->pdfDocBuildRebuildOptions());
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ConversionFailedException('PDF reprocess failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param array<string, mixed> $opts
     *
     * @return array<string, mixed>
     */
    protected function chromePdfDocumentMergePrintOptions(array $opts): array
    {
        $header = $this->pdfDocHeaderHtml;
        $footer = $this->pdfDocFooterHtml;
        $hasHeader = !empty($header);
        $hasFooter = !empty($footer);

        if ($hasHeader || $hasFooter) {
            $opts['displayHeaderFooter'] = true;
            // Chromium requires templates when displayHeaderFooter is enabled.
            $opts['headerTemplate'] = $hasHeader
                ? $this->chromePdfNormalizeTemplate((string) $header, true)
                : $this->chromePdfEmptyTemplate();
            $opts['footerTemplate'] = $hasFooter
                ? $this->chromePdfNormalizeTemplate((string) $footer, false)
                : $this->chromePdfEmptyTemplate();
            
            if ($hasHeader) {
                $opts['marginTop'] = max((float) ($opts['marginTop'] ?? 0.0), 0.65);
            }
            if ($hasFooter) {
                $opts['marginBottom'] = max((float) ($opts['marginBottom'] ?? 0.0), 0.35);
            }
        }

        return $opts;
    }

    private function chromePdfNormalizeTemplate(string $template, bool $isHeader): string
    {
        $trimmed = Str::trim($template);
        if (empty($trimmed)) {
            return $this->chromePdfEmptyTemplate();
        }

        $containsHtml = preg_match('/<[^>]+>/', $trimmed) === 1;
        $content = $containsHtml 
            ? $trimmed 
            : htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $background = $isHeader 
            ? $this->pdfDocHeaderBackgroundColor 
            : $this->pdfDocFooterBackgroundColor;
        $textAlign = $isHeader
            ? $this->pdfDocHeaderTextAlign
            : $this->pdfDocFooterTextAlign;
        $textColor = $isHeader
            ? $this->pdfDocHeaderTextColor
            : $this->pdfDocFooterTextColor;

        $backgroundCss =  !empty($background) ? $background : 'transparent';
        $textColorCss = !empty($textColor) ? $textColor : '#111';
        
        $wrapStyle = 'margin:0;padding:0;border:none;outline:0;background-color:' . $backgroundCss . ';'
            . '-webkit-print-color-adjust:exact;print-color-adjust:exact;color-adjust:exact;'
            . 'width:100%;height:28px;display:flex;align-items:center;justify-content:center;';

        $slotStyle = 'margin:0;padding:0 2px;border:none;outline:0;background-color:transparent;'
            . 'width:100%;text-align:' . $textAlign . ';color:' . $textColorCss . ';font-size:12px;line-height:1.35;';

        return '<div style="' . $wrapStyle . '"><div style="' . $slotStyle . '">' . $content . '</div></div>';
    }

    private function chromePdfEmptyTemplate(): string
    {
        return '<div style="margin:0;padding:0;border:none;outline:0;background:transparent;'
            . 'width:100%;height:0;font-size:0;line-height:0;"></div>';
    }

    private function chromePdfResolveDefaultHeaderText(): string
    {
        $fromTitle = $this->chromePdfExtractDocumentTitleFromSource();
        if ($fromTitle !== null) {
            return $fromTitle;
        }

        if (property_exists($this, 'sourceMode') && property_exists($this, 'sourceValue')) {
            /** @var mixed $mode */
            $mode = $this->sourceMode;
            /** @var mixed $value */
            $value = $this->sourceValue;

            if ($mode === 'url' && is_string($value) && $value !== '') {
                $host = parse_url($value, PHP_URL_HOST);
                if (is_string($host) && $host !== '') {
                    return $host;
                }
            }

            if ($mode === 'file' && is_string($value) && $value !== '') {
                $base = basename(str_replace('\\', '/', $value));
                if ($base !== '') {
                    return $base;
                }
            }
        }

        return 'Document';
    }

    private function chromePdfExtractDocumentTitleFromSource(): ?string
    {
        if (!property_exists($this, 'sourceMode') || !property_exists($this, 'sourceValue')) {
            return null;
        }

        /** @var mixed $mode */
        $mode = $this->sourceMode;
        /** @var mixed $value */
        $value = $this->sourceValue;

        if ($mode === 'html' && is_string($value) && $value !== '') {
            return $this->chromePdfExtractTitleTagValue($value);
        }

        if ($mode === 'file' && is_string($value) && $value !== '' && is_readable($value)) {
            $raw = @file_get_contents($value);
            if (is_string($raw) && $raw !== '') {
                return $this->chromePdfExtractTitleTagValue($raw);
            }
        }

        return null;
    }

    private function chromePdfExtractTitleTagValue(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m) !== 1) {
            return null;
        }

        $title = trim(strip_tags((string) ($m[1] ?? '')));
        if ($title === '') {
            return null;
        }

        $decoded = html_entity_decode($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $decoded = trim($decoded);

        return $decoded !== '' ? $decoded : null;
    }

    protected function chromePdfDocumentAfterGenerate(string $rawPdf): PdfOutput
    {
        $hasUser = $this->pdfDocEncryptUserPassword !== null && $this->pdfDocEncryptUserPassword !== '';
        $hasOwner = $this->pdfDocEncryptOwnerPassword !== null && $this->pdfDocEncryptOwnerPassword !== '';
        if ($this->pdfDocPdfA !== false && ($hasUser || $hasOwner)) {
            throw new ConversionFailedException('Choose either pdfA() or encrypt(); TCPDF cannot apply both.');
        }

        try {
            $options = $this->pdfDocBuildRebuildOptions();

            return PdfPipeline::rebuild($rawPdf, $options);
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ConversionFailedException('PDF post-processing failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function pdfDocBuildRebuildOptions(): PdfRebuildOptions
    {
        return new PdfRebuildOptions(
            textWatermark: $this->pdfDocWatermarkText,
            textWatermarkOpacity: $this->pdfDocWatermarkTextOpacity,
            textWatermarkAngleDeg: $this->pdfDocWatermarkTextAngleDeg,
            textWatermarkFontSizePt: $this->pdfDocWatermarkTextFontSizePt,
            imageWatermarkPath: $this->pdfDocWatermarkImagePath,
            imageWatermarkOpacity: $this->pdfDocWatermarkImageOpacity,
            imageWatermarkWidthMm: $this->pdfDocWatermarkImageWidthMm,
            encryptUserPassword: $this->pdfDocEncryptUserPassword,
            encryptOwnerPassword: $this->pdfDocEncryptOwnerPassword,
            encryptBlockedPermissions: $this->pdfDocEncryptBlockedPermissions,
            encryptAlgorithm: $this->pdfDocEncryptAlgorithm,
            pdfA: $this->pdfDocPdfA,
            linearize: $this->pdfDocLinearize,
            metaTitle: $this->pdfDocMetaTitle,
            metaAuthor: $this->pdfDocMetaAuthor,
            metaSubject: $this->pdfDocMetaSubject,
            metaKeywords: $this->pdfDocMetaKeywords,
        );
    }
}
