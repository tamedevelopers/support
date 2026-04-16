<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Traits;

use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Tamedevelopers\Support\ChromePdf\Internal\PdfPipeline;
use Tamedevelopers\Support\ChromePdf\PdfOutput;
use Tamedevelopers\Support\ChromePdf\PdfRebuildOptions;
use Tamedevelopers\Support\ChromePdf\WatermarkPosition;
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
    private int $pdfDocHeaderHeightPx = 40;
    private int $pdfDocFooterHeightPx = 30;
    private int $pdfDocHeaderGapPx = 0;
    private int $pdfDocFooterGapPx = 0;
    private float $pdfDocHeaderTopInsetPx = 0.0;
    private float $pdfDocFooterBottomInsetPx = 0.0;
    private float $pdfDocHeaderFooterLeftInsetPx = 0.0;
    private float $pdfDocHeaderFooterRightInsetPx = 0.0;
    private float $pdfDocFooterEdgeOffsetPx = -2.0;

    private ?string $pdfDocWatermarkText = null;

    private float $pdfDocWatermarkTextOpacity = 0.12;

    private float $pdfDocWatermarkTextAngleDeg = 45.0;

    private float $pdfDocWatermarkTextFontSizePt = 44.0;

    private WatermarkPosition $pdfDocWatermarkTextPosition = WatermarkPosition::Center;

    private ?string $pdfDocWatermarkImagePath = null;

    private float $pdfDocWatermarkImageOpacity = 0.18;

    private ?float $pdfDocWatermarkImageWidthMm = null;

    private WatermarkPosition $pdfDocWatermarkImagePosition = WatermarkPosition::Center;

    private ?string $pdfDocEncryptUserPassword = null;

    private ?string $pdfDocEncryptOwnerPassword = null;

    /** @var list<string>|null */
    private ?array $pdfDocEncryptBlockedPermissions = null;

    private int $pdfDocEncryptAlgorithm = 3;

    private bool|int $pdfDocPdfA = false;

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
            $html = 'Page <span class="pageNumber"></span> / <span class="totalPages"></span>';
        }

        $this->pdfDocFooterHtml = $html;

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

    /**
     * Set both header and footer block background color in one call.
     */
    public function headerFooterBackground(string $headerBackground = null, string $footerBackground = null): self
    {
        $this->pdfDocHeaderBackgroundColor = $headerBackground;
        $this->pdfDocFooterBackgroundColor = $footerBackground;

        return $this;
    }

    /**
     * Set both header and footer block text color in one call.
     */
    public function headerFooterColor(string $headerColor = null, string $footerColor = null): self
    {
        $this->pdfDocHeaderTextColor = $headerColor;
        $this->pdfDocFooterTextColor = $footerColor;

        return $this;
    }

    /**
     * Set both header and footer block spacing `body content (px)` in one call.
     */
    public function headerFooterSpacing(int $headerGapPx = 0, int $footerGapPx = 0): self
    {
        $this->pdfDocHeaderGapPx = max(0, $headerGapPx);
        $this->pdfDocFooterGapPx = max(0, $footerGapPx);

        return $this;
    }

    /**
     * Set both header and footer block heights (px) in one call.
     */
    public function headerFooterHeight(int $headerPx = 40, int $footerPx = 30): self
    {
        $this->pdfDocHeaderHeightPx = max(1, $headerPx);
        $this->pdfDocFooterHeightPx = max(1, $footerPx);

        return $this;
    }

    /**
     * Fine-tune footer docking at the page edge.
     * Accepts signed lengths (e.g. -1, '-12px', '-1.5mm', '0.02in').
     * Negative values pull footer down to hide a seam.
     */
    public function footerEdgeOffset(float|int|string $value = -2.0): self
    {
        $this->pdfDocFooterEdgeOffsetPx = $this->chromePdfParseSignedLengthToPx($value);

        return $this;
    }

    public function textWatermark(
        ?string $text,
        float $opacity = 0.14,
        float $angleDegrees = 35.0,
        float $fontSizePt = 42.0,
    ): self {
        $this->pdfDocWatermarkText = !empty($text) ? $text : null;
        $this->pdfDocWatermarkTextOpacity = max(0.02, min(1.0, $opacity));
        $this->pdfDocWatermarkTextAngleDeg = $angleDegrees;
        $this->pdfDocWatermarkTextFontSizePt = max(6.0, $fontSizePt);

        return $this;
    }

    /**
     * Where the text watermark is placed ({@code center} matches the historical default).
     * Pass a {@see WatermarkPosition} or a string ({@code top_left}, {@code top left}, {@code top-left}, etc.).
     */
    public function textWatermarkPosition(WatermarkPosition|string $position): self
    {
        $this->pdfDocWatermarkTextPosition = $position instanceof WatermarkPosition
            ? $position
            : WatermarkPosition::parse($position);

        return $this;
    }

    public function imageWatermark(
        ?string $absoluteOrProjectPath,
        float $opacity = 0.16,
        ?float $widthMm = null,
    ): self {

        $path = Tame::stringReplacer($absoluteOrProjectPath);

        $this->pdfDocWatermarkImagePath = !empty($path)? $path: null;
        $this->pdfDocWatermarkImageOpacity = max(0.02, min(1.0, $opacity));
        $this->pdfDocWatermarkImageWidthMm = $widthMm;

        return $this;
    }

    /**
     * Where the image watermark is placed ({@code center} matches the historical default).
     * Pass a {@see WatermarkPosition} or a string ({@code bottom_right}, {@code bottom right}, {@code bottom-right}, etc.).
     */
    public function imageWatermarkPosition(WatermarkPosition|string $position): self
    {
        $this->pdfDocWatermarkImagePosition = $position instanceof WatermarkPosition
            ? $position
            : WatermarkPosition::parse($position);

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
            // Capture user/base print margins so fixed header/footer honor the same inset.
            $baseTopInches = (float) ($opts['marginTop'] ?? 0.0);
            $baseBottomInches = (float) ($opts['marginBottom'] ?? 0.0);
            $baseLeftInches = (float) ($opts['marginLeft'] ?? 0.0);
            $baseRightInches = (float) ($opts['marginRight'] ?? 0.0);

            $this->pdfDocHeaderTopInsetPx = max(0.0, $baseTopInches * 96.0);
            $this->pdfDocFooterBottomInsetPx = $baseBottomInches * 96.0;
            $this->pdfDocHeaderFooterLeftInsetPx = max(0.0, $baseLeftInches * 96.0);
            $this->pdfDocHeaderFooterRightInsetPx = max(0.0, $baseRightInches * 96.0);

            $opts['displayHeaderFooter'] = true;
            $emptyTemplate = $this->chromePdfEmptyTemplate();

            // Chromium requires templates when displayHeaderFooter is enabled.
            $opts['headerTemplate'] = $hasHeader
                ? $this->chromePdfNormalizeTemplate((string) $header, true)
                : $emptyTemplate;
            $opts['footerTemplate'] = $hasFooter
                ? $this->chromePdfNormalizeTemplate((string) $footer, false)
                : $emptyTemplate;

            // Ensure body content is pushed away from fixed-size header/footer blocks.
            // User-defined ->margin()/->margins() remain effective by being additive.
            if ($hasHeader) {
                $minTopInches = ($this->pdfDocHeaderHeightPx + $this->pdfDocHeaderGapPx) / 96.0;
                $opts['marginTop'] = (float) ($opts['marginTop'] ?? 0.0) + $minTopInches;
            }
            if ($hasFooter) {
                $minBottomInches = ($this->pdfDocFooterHeightPx + $this->pdfDocFooterGapPx) / 96.0;
                $opts['marginBottom'] = (float) ($opts['marginBottom'] ?? 0.0) + $minBottomInches;
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

        if(empty($background)) {
            $background = $this->chromePdfExtractBodyBackgroundColor($this->sourceValue);
        }

        $backgroundCss = $background;
        $textColorCss = !empty($textColor) ? $textColor : '#111';

        $justify = match ($textAlign) {
            'left' => 'flex-start',
            'right' => 'flex-end',
            default => 'center',
        };

        $heightPx = $isHeader ? $this->pdfDocHeaderHeightPx : $this->pdfDocFooterHeightPx;
        $footerBottomPx = $this->pdfDocFooterBottomInsetPx + $this->pdfDocFooterEdgeOffsetPx;
        $edgeInset = $isHeader
            ? ('top:' . $this->chromePdfPx($this->pdfDocHeaderTopInsetPx) . ';')
            : ('bottom:' . $this->chromePdfPx($footerBottomPx) . ';');

        $slotStyle = 'margin:0;padding:0 10px;border:none;outline:0;background:' . $backgroundCss . ';'
            . '-webkit-print-color-adjust:exact;print-color-adjust:exact;color-adjust:exact;'
            . 'position:fixed;left:' . $this->chromePdfPx($this->pdfDocHeaderFooterLeftInsetPx) . ';'
            . 'right:' . $this->chromePdfPx($this->pdfDocHeaderFooterRightInsetPx) . ';'
            . $edgeInset
            . 'display:flex;align-items:center;justify-content:' . $justify . ';'
            . 'text-align:' . $textAlign . ';color:' . $textColorCss . ';'
            . 'width:auto;min-width:0;height:' . $heightPx . 'px;box-sizing:border-box;'
            . 'font-size:12px;line-height:1.35;background-clip:padding-box;'
            . 'white-space:pre-wrap;';

        return '<div style="' . $slotStyle . '">' . $content . '</div>';
    }

    private function chromePdfEmptyTemplate(): string
    {
        return '<span></span>';
    }

    /**
     * Extract background color from body element in HTML
     * 
     * @param string $html HTML content
     * @return string|null Returns color or null if not found
     */
    private function chromePdfExtractBodyBackgroundColor(string $html): ?string
    {
        // Find body tag and get its style attribute
        if (preg_match('/<body[^>]*style=["\']([^"\']*background-color\s*:\s*([^;]+)[^"\']*)["\']/i', $html, $matches)) {
            return trim($matches[2]);
        }
        
        // Find body tag with bgcolor attribute
        if (preg_match('/<body[^>]*bgcolor=["\']([^"\']+)["\']/i', $html, $matches)) {
            return trim($matches[1]);
        }
        
        // Look for body CSS in style tags
        if (preg_match('/<style[^>]*>.*?body\s*{[^}]*background-color\s*:\s*([^;]+).*?<\/style>/is', $html, $matches)) {
            return trim($matches[1]);
        }
        
        return '#ffffff';
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

    private function chromePdfPx(float $value): string
    {
        return rtrim(rtrim(sprintf('%.2f', $value), '0'), '.') . 'px';
    }

    private function chromePdfParseSignedLengthToPx(float|int|string $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = Str::trim($value);
        if ($s === '') {
            return 0.0;
        }

        if (preg_match('/^(-?[\d.]+)\s*px$/i', $s, $m) === 1) {
            return (float) $m[1];
        }
        if (preg_match('/^(-?[\d.]+)\s*mm$/i', $s, $m) === 1) {
            return (float) $m[1] * (96.0 / 25.4);
        }
        if (preg_match('/^(-?[\d.]+)\s*cm$/i', $s, $m) === 1) {
            return (float) $m[1] * (96.0 / 2.54);
        }
        if (preg_match('/^(-?[\d.]+)\s*in$/i', $s, $m) === 1) {
            return (float) $m[1] * 96.0;
        }
        if (preg_match('/^-?[\d.]+$/', $s) === 1) {
            return (float) $s;
        }

        throw new ConversionFailedException(sprintf('Invalid footer edge offset value: %s', $value));
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
            textWatermarkPosition: $this->pdfDocWatermarkTextPosition,
            imageWatermarkPath: $this->pdfDocWatermarkImagePath,
            imageWatermarkOpacity: $this->pdfDocWatermarkImageOpacity,
            imageWatermarkWidthMm: $this->pdfDocWatermarkImageWidthMm,
            imageWatermarkPosition: $this->pdfDocWatermarkImagePosition,
            encryptUserPassword: $this->pdfDocEncryptUserPassword,
            encryptOwnerPassword: $this->pdfDocEncryptOwnerPassword,
            encryptBlockedPermissions: $this->pdfDocEncryptBlockedPermissions,
            encryptAlgorithm: $this->pdfDocEncryptAlgorithm,
            pdfA: $this->pdfDocPdfA,
            metaTitle: $this->pdfDocMetaTitle,
            metaAuthor: $this->pdfDocMetaAuthor,
            metaSubject: $this->pdfDocMetaSubject,
            metaKeywords: $this->pdfDocMetaKeywords,
        );
    }
}
