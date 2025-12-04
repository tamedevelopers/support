<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use GdImage;
use RuntimeException;
use InvalidArgumentException;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Traits\ReceiptGeneratorTrait;

class ReceiptGenerator {

    use ReceiptGeneratorTrait;

    private $data = [];
    private $currency = [];
    private $design = 'modern';
    private $fontPath = '';
    private $currentFont = '';
    private $outputFormat = 'png';
    private $width = 600;
    private $height = 800;
    private $heading;
    private $designColors;
    private $paperSizes;
    private $paperSize = 'letter';
    private $dpi = 72; // Default DPI for screen
    private $generate = true;
    private $badge = false;
    
    // Supported designs
    private $supportedDesigns = ['modern', 'classic', 'minimal', 'corporate', 'invoice'];
    
    // Supported output formats
    private $supportedFormats = ['png', 'jpeg', 'jpg', 'gif'];

    // Font settings
    private $fontSizes = [
        'title' => 24,
        'subtitle' => 18,
        'heading' => 14,
        'body' => 12,
        'small' => 10,
        'amount' => 22
    ];

    private $fontFiles = [
        'regular' => '',
        'bold' => '',
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->setDefaultData();
        $this->setDefaultHeading();
        $this->setdesignColors();
        $this->setPaperSizes();
    }
    
    /**
     * Set receipt data
     */
    public function setData(array $data): self 
    {
        $this->data = array_merge($this->data, $data);

        $amount = $this->normalizeForBCMath($this->data['amount']);
        $tax    = $this->normalizeForBCMath($this->data['tax_amount']);

        $this->data['amount'] = $amount;
        $this->data['tax_amount'] = $tax;

        // Auto-calculate total if not provided
        if (!isset($data['total_amount'])) {
            $this->data['total_amount'] = bcadd($amount, $tax, 2);
        } else{
            $this->data['total_amount'] = $this->normalizeForBCMath($data['total_amount']);
        }

        // get country iso
        $iso = NumberToWords::getCurrencyByCode($this->data['currency']);

        if (!is_array($iso)) {
            throw new InvalidArgumentException("[{$this->data['currency']}] is not a valid (iso-4217) currency code.");
        }

        $this->currency = $iso;
        
        return $this;
    }

    /**
     * Set heading text
     *
     * @param  array $config
     * @return self
     */
    public function setHeading($config = []): self
    {
        $this->heading = array_merge($this->heading, $config);

        return $this;
    }
    
    /**
     * Set design
     */
    public function setDesign(string $design): self 
    {
        if (!in_array($design, $this->supportedDesigns)) {
            throw new InvalidArgumentException("Design '$design' is not supported. Supported designs: " . implode(', ', $this->supportedDesigns));
        }
        $this->design = $design;
        return $this;
    }
    
    /**
     * Set image generation
     */
    public function generate($allow = false): self 
    {
        $this->generate = $allow;
        return $this;
    }
    
    /**
     * Set image badge
     */
    public function badge($badge = false): self 
    {
        $this->badge = $badge;
        return $this;
    }

    /**
     * Set font sizes
     */
    public function setFontSizes(array $sizes): self 
    {
        $this->fontSizes = array_merge($this->fontSizes, $sizes);
        return $this;
    }

    /**
     * Set font files
     * 
     * @param array $fonts
     * ['regular' => '', 'bold' => '']
     */
    public function setFontFiles($fonts = []): self 
    {
        if(!$this->isThermal()){
            $this->fontFiles = array_merge($this->fontFiles, $fonts);
    
            $files  = $this->fontFiles;
            $path   = __DIR__ . '/icons/fonts/';
    
            if(!File::exists($files['regular'])){
                $this->fontFiles['regular'] = Server::pathReplacer("{$path}fake-receipt.ttf");
            }
            if(!File::exists($files['bold'])){
                $this->fontFiles['bold'] = Server::pathReplacer("{$path}merchant-copy.ttf");
            }

            $this->setDefaultFonts();
        }

        return $this;
    }

    /**
     * Set DPI (for print quality)
     */
    public function setDPI(int $dpi): self 
    {
        $this->dpi = $dpi;
        return $this;
    }

    /**
     * Set paper size
     */
    public function setPaperSize(string $size): self 
    {
        $size = Str::lower($size);

        if (!isset($this->paperSizes[$size])) {
            $sizes = implode(', ', array_keys($this->paperSizes));
            throw new InvalidArgumentException("Paper size '$size' is not supported. Supported sizes: " . $sizes);
        }

        // adjust needed print display
        $forPrint = match ($size) {
            '55mm', '80mm', '55', '80' => true,
            default => false,
        };
        
        $this->paperSize = $size;
        $paperConfig = $this->paperSizes[$size];
        
        if ($forPrint) {
            // For print: use inches with DPI
            $this->dpi = $paperConfig['dpi'];
            $this->width = round($paperConfig['width'] * $this->dpi);
            $this->height = round($paperConfig['height'] * $this->dpi);
        } else {
            // For screen display: use pixels directly
            $this->dpi = 72;
            $this->width = round($paperConfig['width'] * $this->dpi);
            $this->height = round($paperConfig['height'] * $this->dpi);
        }
        
        // Adjust font sizes based on paper size
        $this->adjustFontSizesForPaper($size);
        
        return $this;
    }

    /**
     * Set custom dimensions in pixels
     */
    public function setDimensions(int $width, int $height, int $dpi = 72): self 
    {
        $this->width = $width;
        $this->height = $height;
        $this->dpi = $dpi;
        $this->paperSize = 'custom';
        
        // Adjust font sizes based on custom dimensions
        $this->adjustFontSizesForCustom($width);
        
        return $this;
    }
    
    /**
     * Set output format
     */
    public function setOutputFormat(string $format): self 
    {
        $format = Str::lower($format);
        if (!in_array($format, $this->supportedFormats)) {
            throw new InvalidArgumentException("Format '$format' is not supported. Supported formats: " . implode(', ', $this->supportedFormats));
        }
        $this->outputFormat = $format;
        return $this;
    }

    /**
     * Scaled Padding
     */
    private function getScaledPadding(): int
    {
        // Scale padding based on paper width
        $baseWidth = 600; // Reference width for default padding
        $scaleFactor = $this->width / $baseWidth;
        
        // Minimum padding for very small receipts
        $minPadding = 5;
        
        // For thermal printers, use smaller padding
        if ($this->isThermal()) {
            return (int) max($minPadding, 10); // Reduced padding for thermal
        }
        
        // For other sizes, scale appropriately
        $defaultPadding = 30;
        $scaledPadding = round($defaultPadding * $scaleFactor);
        
        return (int) max($minPadding, $scaledPadding);
    }

    /**
     * Scaled Line Spacing
     */
    private function getScaledLineSpacing(): int
    {
        // Scale line spacing based on paper height
        $scaleFactor = $this->height / 800; // Reference height
        
        // For thermal printers, use tighter spacing
        if ($this->isThermal()) {
            return 25; // Reduced line spacing for thermal
        }
        
        $defaultSpacing = 30;
        $scaledSpacing = round($defaultSpacing * $scaleFactor);
        
        return (int) max(15, $scaledSpacing); // Minimum spacing
    }

    /**
     * Scaled Box Height
     */
    private function getScaledBoxHeight(): int
    {
        // Calculate appropriate box height based on content and paper size
        $minHeight = 200;
        $maxHeight = $this->height - 250; // Leave room for header and footer
        
        // For thermal printers, use more compact height
        if ($this->paperSize === '55mm' || $this->paperSize === '80mm') {
            return (int) min(300, $maxHeight);
        }
        
        // For larger papers, use proportional height
        return (int) min(400, $maxHeight);
    }

    /**
     * isThermal
     */
    private function isThermal(): bool
    {
        return in_array($this->paperSize, ['55mm', '80mm', '55', '80']);
    }

    /**
     * Get thermal height
     */
    private function getTermalHeight(): int
    {
        return $this->isThermal() ? 70 : 100;
    }

    /**
     * Get thermal Start height
     */
    private function getTermalStartHeight(int $boxY): int
    {
        $startY = ($this->isThermal() ? 20 : 40);

        return $startY + $boxY;
    }

    /**
     * Get thermal padding
     */
    private function getTermalPadding(): int
    {
        return ($this->isThermal() ? 15 : 20);
    }

    /**
     * Get title font size
     */
    private function getTitleFontSize(): int
    {
        $value = $this->isThermal() ? $this->fontSizes['title'] - 8 : $this->fontSizes['title'];
        return (int) $value;
    }

    /**
     * Get subtitle font size
     */
    private function getSubtitleFontSize(): int
    {
        $value = $this->isThermal() ? $this->fontSizes['subtitle'] - 6 : $this->fontSizes['subtitle'];
        return (int) $value;
    }

    /**
     * Get title Height
     */
    private function getTitleHeight(): int
    {
        $headerHeight = $this->getTermalHeight();
        $value = $this->isThermal() ? $headerHeight/2 - 15 : $headerHeight/2 - 5;

        return (int) $value;
    }

    /**
     * Get subtitle Height
     */
    private function getSubtitleHeight(): int
    {
        $headerHeight = $this->getTermalHeight();
        $value = $this->isThermal() ? $headerHeight - 25 : $headerHeight - 25;

        return (int) $value;
    }
    
    /**
     * Modern design
     */
    private function generateModernDesign(GdImage $image, array $colors): void 
    {
        $width      = (int) $this->width;
        $height     = (int) $this->height;
        $data       = $this->data;
        $heading    = $this->heading;

        // Use scaled values
        $boxPadding = $this->getScaledPadding();
        $lineSpacing = $this->getScaledLineSpacing();
        $boxHeight = $this->getScaledBoxHeight();
        $headerHeight = $this->getTermalHeight();
        $titleFontSize = $this->getTitleFontSize();
        $subtitleFontSize = $this->getSubtitleFontSize();
        
        // Draw header
        imagefilledrectangle($image, 0, 0, $width, $headerHeight, $colors['primary']);
        
        // Company name
        $this->addText(
            $image, $data['company_name'], $width/2, $this->getTitleHeight(), 
            $titleFontSize, $colors['white'], 'center', true
        );
        
        // Receipt title
        $this->addText(
            $image, $heading['payment_receipt'], $width/2, $this->getSubtitleHeight(), 
            $subtitleFontSize, $colors['white'], 'center'
        );

        // Draw receipt body
        $boxY = $headerHeight + 10; // Add some space after header

        // For thermal printers, use simpler rectangle without rounded corners
        if($this->isThermal()){
            imagefilledrectangle(
                $image, $boxPadding, $boxY, $width - $boxPadding, 
                $boxY + $boxHeight, $colors['white']
            );

            // Simple border for thermal
            imagerectangle(
                $image, $boxPadding, $boxY, $width - $boxPadding, 
                $boxY + $boxHeight, $colors['white']
            );
        } else{
            // Draw rounded rectangle for receipt details
            $this->drawRoundedRect(
                $image, 
                $boxPadding, 
                $boxY, 
                $width - ($boxPadding * 2), 
                $boxHeight, 
                8, 
                $colors['white']
            );

            // Add shadow effect for larger receipts
            if ($width > 300) {
                imagesetthickness($image, 1);
                imagerectangle(
                    $image, 
                    $boxPadding + 1, 
                    $boxY + 1, 
                    $width - $boxPadding + 1, 
                    $boxY + $boxHeight + 1, 
                    $colors['light_gray']
                );
            }
        }

        // Receipt details
        $details = [
            ["{$heading['receipt_number']}:", $data['receipt_number']],
            ["{$heading['date']}:", $data['date']],
            ["{$heading['transaction_id']}:", $data['transaction_id']],
            ["{$heading['customer_name']}:", $data['customer_name']],
            ["{$heading['email']}:", $data['customer_email']],
            ["{$heading['payment_method']}:", $data['payment_method']],
            ["{$heading['description']}:", $data['description']],
        ];

        // Adjust starting Y position based on paper size
        $startY = $this->getTermalStartHeight($boxY);
        $y = $startY;

        foreach ($details as $detail) {
            // Only show if there's a value
            if($detail[1]){
                $leftPadding = $boxPadding + $this->getTermalPadding();
                $rightPadding = $width - $boxPadding - $this->getTermalPadding();

                $this->addText(
                    $image, $detail[0], $leftPadding, $y, 
                    $this->fontSizes['body'], $colors['text'], 'left'
                );
                $this->addText(
                    $image, $detail[1], $rightPadding, $y, 
                    $this->fontSizes['body'], $colors['text'], 'right'
                );
                $y += $lineSpacing;
            }
        }
        
        // Amount section
        $y += ($this->isThermal() ? 5 : 10);
        $leftPadding = $boxPadding + $this->getTermalPadding();
        $rightPadding = $width - $boxPadding - $this->getTermalPadding();

        $this->addText(
            $image, "{$heading['amount']}:", $leftPadding, $y, 
            $this->fontSizes['heading'], $colors['text'], 'left'
        );

        $this->addText(
            $image, $this->format($data['amount']), $rightPadding, $y, 
            $this->fontSizes['heading'] + 2, $colors['primary'], 'right'
        );
        
        // tax section
        $y += $lineSpacing;
        $this->addText(
            $image, "{$heading['tax']}:", $leftPadding, $y, 
            $this->fontSizes['heading'], $colors['text'], 'left'
        );
        $this->addText(
            $image, $this->format($data['tax_amount']), $rightPadding, $y, 
            $this->fontSizes['heading'] + 2, $colors['primary'], 'right'
        );

        // image line
        $y += $lineSpacing;
        imageline($image, $leftPadding, $y, $rightPadding, $y, $colors['gray']);
        $y += $this->getTermalPadding();

        // total_amount section
        $totalSize = $this->isThermal() ? 0 : 2;
        $this->addText(
            $image, "{$heading['total']}:", $leftPadding, $y, 
            $this->fontSizes['heading'] + $totalSize, $colors['text'], 'left', true
        );
        $this->addText(
            $image, $this->format($data['total_amount']), $rightPadding, $y, 
            $this->fontSizes['amount'] - $totalSize, $colors['accent'], 'right', true
        );

        // Status badge - only show for larger receipts, not for thermal
        if($this->badge === true && $width > 300 && !$this->isThermal()){
            $this->drawStatusBadge(
                $image, 
                $width - 120, 
                $boxY + $boxHeight + 30, 
                $data['status'], 
                $colors
            );
        }
        
        $this->footer($image, $colors);
    }
    
    /**
     * Classic design
     */
    private function generateClassicDesign(GdImage $image, array $colors): void 
    {
        // Implementation for classic design
        $width      = (int) $this->width;
        $height     = (int) $this->height;
        $data       = $this->data;
        $heading    = $this->heading;

        // Use scaled values
        $boxPadding = $this->getScaledPadding();
        $headerHeight = $this->getTermalHeight();
        $titleFontSize = $this->getTitleFontSize();

        // Ornate header
        imagefilledrectangle($image, 0, 0, $width, $headerHeight, $colors['primary']);
        
        // Decorative border for non-thermal
        if (!$this->isThermal()) {
            imagesetthickness($image, 2);
            imagerectangle($image, 10, 10, $width - 10, $height - 10, $colors['secondary']);
        }
        
        // Company name with decorative font
        $this->addText(
            $image, $data['company_name'], $width/2, $this->getTitleHeight(), 
            $titleFontSize, $colors['white'], 'center', true
        );
        
        // Classic receipt styling
        $receiptTitleY = $this->isThermal() ? $headerHeight + 20 : $headerHeight + 40;
        $this->addText(
            $image, $heading['payment_receipt'], $width/2, $receiptTitleY, 
            $this->getSubtitleFontSize(), $colors['text'], 'center'
        );
        
        // Draw decorative line
        $linePadding = $this->isThermal() ? 30 : 50;
        imageline(
            $image, $linePadding, $receiptTitleY + 20, $width - $linePadding, 
            $receiptTitleY + 20, $colors['secondary']
        );
        
        // Receipt details in table format
        $y = $receiptTitleY + 40;
        $leftPadding = $this->isThermal() ? $boxPadding + 10 : 50;
        $rightPadding = $this->isThermal() ? $width - $boxPadding - 10 : $width - 50;
        
        $details = [
            ["{$heading['receipt_number']}", $data['receipt_number']],
            ["{$heading['date']}", $data['date']],
            ["{$heading['transaction_id']}", $data['transaction_id']],
            ["{$heading['customer_name']}", $data['customer_name']],
            ["{$heading['payment_method']}", $data['payment_method']],
            ["{$heading['description']}:", $data['description']],
            ["{$heading['amount']}", $this->format($data['amount'])],
            ["{$heading['tax']}", $this->format($data['tax_amount'])],
        ];
        
        foreach ($details as $detail) {
            $this->addText($image, $detail[0], $leftPadding, $y, $this->fontSizes['body'], $colors['dark_gray'], 'left');
            $this->addText($image, $detail[1], $rightPadding, $y, $this->fontSizes['body'], $colors['text'], 'right');
            $y += $this->isThermal() ? 25 : 35;
        }

        // Total with emphasis
        $y += $this->isThermal() ? 5 : 10;
        imageline($image, $leftPadding, $y, $rightPadding, $y, $colors['secondary']);
        $y += $this->isThermal() ? 10 : 20;
        $amount = $this->format($data['total_amount']);

        $this->addText(
            $image, 
            "{$heading['total']}:", 
            $leftPadding, 
            $y, 
            $this->fontSizes['heading'] + ($this->isThermal() ? 0 : 2), 
            $colors['text'], 
            'left', 
            true
        );
        $this->addText(
            $image, 
            $amount, 
            $rightPadding,
            $y, 
            $this->fontSizes['amount'] - ($this->isThermal() ? 2 : 0), 
            $colors['accent'], 
            'right', 
            true
        );

        // Status badge - only show for larger receipts, not for thermal
        if($this->badge === true && $width > 300 && !$this->isThermal()){
            $this->drawStatusBadge(
                $image, 
                $width - $rightPadding, 
                $rightPadding + $headerHeight + 30, 
                $data['status'], 
                $colors
            );
        }
        
        $this->footer($image, $colors);
    }
    
    /**
     * Minimal design
     */
    private function generateMinimalDesign(GdImage $image, array $colors): void 
    {
        // Minimalistic implementation
        $width      = (int) $this->width;
        $height     = (int) $this->height;
        $data       = $this->data;
        $heading    = $this->heading;

        // Very minimal header
        $headerY = $this->isThermal() ? 40 : 60;
        $this->addText(
            $image, 
            $data['company_name'], 
            $width/2, 
            $headerY, 
            $this->fontSizes['title'] - 2, 
            $colors['text'], 
            'center'
        );
        
        // Simple line separator
        $linePadding = $this->isThermal() ? 30 : 50;
        $linePaddingTop = $this->isThermal() ? 40 : 30;
        imageline(
            $image, $linePadding, $headerY + $linePaddingTop, $width - $linePadding, 
            $headerY + $linePaddingTop, $colors['light_gray']
        );
        
        // Receipt title
        $receiptTitleY = $this->isThermal() ? $headerY + 55 : $headerY + 60;
        $this->addText(
            $image, 
            $heading['payment_receipt'], 
            $width/2, 
            $receiptTitleY, 
            $this->fontSizes['subtitle'], 
            $colors['gray'], 
            'center'
        );

        // Very compact receipt details
        $y = $receiptTitleY + ($this->isThermal() ? 45 : 40);
        $lineSpacing = $this->isThermal() ? 30 : 25;
        $leftPadding = $this->isThermal() ? 30 : 50;
        $rightPadding = $this->isThermal() ? $width - 30 : $width - 50;
        
        $details = [
            ["{$heading['receipt_number']}", $data['receipt_number']],
            ["{$heading['date']}", $data['date']],
            ["{$heading['transaction_id']}:", $data['transaction_id']],
            ["{$heading['customer_name']}", $data['customer_name']],
            ["{$heading['payment_method']}:", $data['payment_method']],
            ["{$heading['description']}:", $data['description']],
            ["{$heading['amount']}", $this->format($data['amount'])],
            ["{$heading['tax']}:", $this->format($data['tax_amount'])],
        ];
        
        foreach ($details as $detail) {
            if(isset($detail[1]) && !empty($detail[1])){
                $this->addText(
                    $image, 
                    $detail[0], 
                    $leftPadding, 
                    $y, 
                    $this->fontSizes['body'], 
                    $colors['dark_gray'], 
                    'left'
                );
                $this->addText(
                    $image, 
                    $detail[1], 
                    $rightPadding, 
                    $y, 
                    $this->fontSizes['body'], 
                    $colors['text'], 
                    'right'
                );
                $y += $lineSpacing;
            }
        }
        
        // Total amount emphasized
        $y += $this->isThermal() ? 5 : 10;
        $this->addText(
            $image, 
            "{$heading['total']}", 
            $width/2, 
            $y, 
            $this->fontSizes['heading'], 
            $colors['text'], 
            'center', 
            true
        );
        $y += $this->isThermal() ? 20 : 25;
        $this->addText(
            $image, 
            $this->format($data['total_amount']), 
            $width/2, 
            $y, 
            $this->fontSizes['amount'], 
            $colors['accent'], 
            'center', 
            true
        );
        
        $this->footer($image, $colors);
    }

    /**
     * Corporate design
     */
    private function generateCorporateDesign(GdImage $image, array $colors): void 
    {
        // Corporate/formal styling
        $width      = (int) $this->width;
        $height     = (int) $this->height;
        $data       = $this->data;
        $heading    = $this->heading;
        
        // Clean header with logo area
        $headerHeight = $this->getTermalHeight();
        
        // Clean header with logo area
        imagefilledrectangle($image, 0, 0, $width, $headerHeight, $colors['primary']);
        
        // Company name with formal styling
        $this->addText(
            $image, $data['company_name'], $width/2, $this->getTitleHeight(), 
            $this->getTitleFontSize(), $colors['white'], 'center', true
        );
        
        // Receipt details in formal table
        $y = $headerHeight + ($this->isThermal() ? 30 : 30);
        $tablePadding = $this->isThermal() ? 25 : 50;
        
        // Table header
        $this->addText(
            $image, $heading['invoice_receipt'], $width/2, $y, 
            $this->getSubtitleFontSize(), $colors['primary'], 'center', true
        );
        $y += $this->isThermal() ? 35 : 40;
        
        // Details table
        $details = [
            [$heading['description'], $heading['details']],
            ["{$heading['receipt_number']}", $data['receipt_number']],
            ["{$heading['date']}", $data['date']],
            ["{$heading['transaction_id']}", $data['transaction_id']],
            ["{$heading['customer_name']}", $data['customer_name']],
            ["{$heading['payment_method']}", $data['payment_method']],
            ["{$heading['description']}", $data['description']],
            ['', ''],
            ["{$heading['amount']}", $this->format($data['amount'])],
            ["{$heading['tax']}", $this->format($data['tax_amount'])],
        ];
        
        $headingTrack = 0;
        foreach ($details as $detail) {
            // Table header
            if ($headingTrack === 0) {
                $this->addText(
                    $image, $detail[0], $tablePadding, $y, 
                    $this->fontSizes['heading'], $colors['primary'], 'left', true
                );

                $this->addText(
                    $image, $detail[1], $width - $tablePadding, $y, 
                    $this->fontSizes['heading'], $colors['primary'], 'right', true
                );

                $y += $this->isThermal() ? 25 : 30;
                imageline($image, $tablePadding, $y, $width - $tablePadding, $y, $colors['primary']);
                $y += $this->isThermal() ? 10 : 10;
            } elseif ($detail[0] === '' && $detail[1] === '') {
                // Separator
                $y += $this->isThermal() ? 5 : 10;
            } else {
                // Table row
                $this->addText(
                    $image, $detail[0], $tablePadding, $y, 
                    $this->fontSizes['body'], $colors['text'], 'left'
                );
                $this->addText(
                    $image,  $detail[1], $width - $tablePadding, $y, 
                    $this->fontSizes['body'], $colors['text'], 'right'
                );
                $y += $this->isThermal() ? 25 : 25;
            }

            $headingTrack++;
        }
        
        // Total with double underline
        $y += $this->isThermal() ? 10 : 10;
        $totalLeft = $this->isThermal() ? $width - 125 : $width - 200;
        $totalRight = $this->isThermal() ? $width - 25 : $width - 50;
        $amount = $this->format($data['total_amount']);

        // divide 
        $whole = intdiv(strlen($amount), 5);
        for ($i=0; $i < $whole; $i++) {
            if($whole === 1){
                $totalLeft = $totalLeft + 20;
            } elseif($whole > 1 && $whole <= 3){
                $totalLeft = $totalLeft - 15;
            } else{
                $totalLeft = $totalLeft - 26;
            }
        }

        imageline($image, $totalLeft, $y, $totalRight, $y, $colors['dark_gray']);
        $y += 2;
        imageline($image, $totalLeft, $y, $totalRight, $y, $colors['dark_gray']);
        $y += $this->isThermal() ? 10 : 15;
        
        $this->addText(
            $image, 
            "{$heading['total']}:", 
            $tablePadding, 
            $y, 
            $this->fontSizes['heading'] + ($this->isThermal() ? 0 : 2), 
            $colors['text'], 
            'left', 
            true
        );

        $this->addText(
            $image, 
            $amount, 
            $width - $tablePadding, 
            $y, 
            $this->fontSizes['amount'] - ($this->isThermal() ? 2 : 0), 
            $colors['accent'], 
            'right', 
            true
        );
        
        $this->footer($image, $colors);
    }
    
    /**
     * Invoice design
     */
    private function generateInvoiceDesign(GdImage $image, array $colors): void 
    {
        // For thermal printers, use modern design instead of corporate
        if ($this->isThermal()) {
            $this->generateModernDesign($image, $colors);
        } else {
            $this->generateCorporateDesign($image, $colors);
        }
    }

    /**
     * Add UTF-8 text to image with font support
     *
     * @param  GdImage $image
     * @param  string $text
     * @param  int $x
     * @param  int $y
     * @param  int $size
     * @param  int $color
     * @param  string $align
     * @param  bool $bold
     * @param  string $fontStyle
     * @return void
     */
    private function addText($image, $text, $x, $y, $size, $color, $align = 'left', $bold = false, $fontStyle = 'regular'): void 
    {
        // Ensure text is UTF-8 encoded
        $text = $this->ensureUtf8($text);

        $fontStyle  = $bold ? 'bold' : $fontStyle;
        $fontPath   = $this->fontFiles[$fontStyle] ?? $this->fontFiles['regular'];
        $this->currentFont = $fontPath;

        // Adjust size based on DPI for print
        $adjustedSize = round($size * ($this->dpi / 72));

        if (!File::exists($fontPath)) {
            // Use default GD font if custom font not available
            $gdFont = $bold ? 5 : 3;
            $textWidth = imagefontwidth($gdFont) * strlen($text);
            
            if ($align === 'center') {
                $x = $x - ($textWidth / 2);
            } elseif ($align === 'right') {
                $x = $x - $textWidth;
            }
            
            imagestring($image, $gdFont, (int) $x, (int) $y, $text, $color);
        } else {
            // Use TrueType font
            $bbox = imagettfbbox($adjustedSize, 0, $fontPath, $text);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            
            if ($align === 'center') {
                $x = $x - ($textWidth / 2);
            } elseif ($align === 'right') {
                $x = $x - $textWidth;
            }
            
            // Adjust y position for proper baseline
            $y = $y + ($textHeight * 0.75);
            
            imagettftext($image, $adjustedSize, 0, (int) $x, (int) $y, $color, $fontPath, $text);
        }
    }

    /**
     * Ensure string is UTF-8 encoded
     */
    private function ensureUtf8(string $text): string
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8');
        }

        return $this->simplifySpecialCharacters($text);
    }
      
    /**
     * Draw rounded rectangle
     *
     * @param  GdImage $image
     * @param  int $x
     * @param  int $y
     * @param  int $width
     * @param  int $height
     * @param  int $radius
     * @param  int $color
     * @return void
     */
    private function drawRoundedRect($image, $x, $y, $width, $height, $radius, $color): void 
    {
        imagefilledrectangle($image, $x + $radius, $y, $x + $width - $radius, $y + $height, $color);
        imagefilledrectangle($image, $x, $y + $radius, $x + $width, $y + $height - $radius, $color);
        
        imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $width - $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $width - $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
    }
    
    /**
     * Draw status badge
     *
     * @param  GdImage $image
     * @param  int $x
     * @param  int $y
     * @param  string $status
     * @param  array $colors
     * @return void
     */
    private function drawStatusBadge($image, $x, $y, $status, $colors): void 
    {
        $status = Str::lower($status);
        $statusColor = ($status === 'paid') ? $colors['green'] : 
                      (($status === 'pending') ? $colors['yellow'] : $colors['red']);

        $textWidth = 70;
        $textHeight = 28;

        // Draw rounded badge
        $this->drawRoundedRect($image, $x, $y, $textWidth, $textHeight, 5, $statusColor);

        // Add border
        imagerectangle($image, $x, $y, $x + $textWidth, $y + $textHeight, $colors['light_gray']);

        $this->addText(
            $image, 
            $status, 
            $x + ($textWidth/2), 
            $y + 7, 
            10, 
            $colors['white'], 
            'center', 
            true
        );
        
        $this->addText($image, $status, $x + ($textWidth/2), $y + 6, 10, $colors['white'], 'center', true);
    }
    
    /**
     * Output image to browser for download
     */
    public function outputToBrowser(string $filename = 'receipt'): void 
    {
        $image = $this->generateImage();
        
        header('Content-Type: image/' . $this->outputFormat);
        header('Content-Disposition: attachment; filename="' . $filename . '.' . $this->outputFormat . '"');
        
        $this->outputImage($image);
        unset($image);
    }
    
    /**
     * Save image to file
     */
    public function saveToFile(string $filepath): array
    {
        $image  = $this->generateImage();
        $data   = $this->getFileName($filepath);
        
        $fullPath = $data['filepath'];
        $response = $this->outputImage($image, $fullPath);
        
        unset($image);

        $pathToImage = Str::replace(base_path(), '', $fullPath);

        return [
            'image' => $data['basename'],
            'urlpath' => domain($pathToImage),
            'fullpath' => $data['filepath'],
            'response' => $response,
        ];
    }
    
    /**
     * Output image in specified format
     */
    private function outputImage(GdImage $image, ?string $filepath = null): bool 
    {
        $format     = $this->outputFormat === 'jpg' ? 'jpeg' : $this->outputFormat;
        $function   = 'image' . $format;
        $dirPath    = dirname($filepath);
        
        if (!function_exists($function)) {
            throw new RuntimeException("Function $function is not available. Check GD library support.");
        }

        if(!File::isDirectory($dirPath)){
            File::makeDirectory($dirPath);
        }
        
        if ($filepath) {
            return $function($image, $filepath);
        } else {
            return $function($image);
        }
    }

    /**
     * Get file information with unique filename generation.
     *
     * @param string $filepath Original file path
     * @return array File information array
     */
    private function getFileName($filepath)
    {
        // Helper function to get file info
        $getFileInfo = function($path) {
            $path = rtrim($path, '/');
            return [
                'filepath' => $path,
                'filename' => pathinfo($path, PATHINFO_FILENAME),
                'extension' => pathinfo($path, PATHINFO_EXTENSION),
                'basename' => pathinfo($path, PATHINFO_BASENAME),
                'dirname' => pathinfo($path, PATHINFO_DIRNAME),
            ];
        };
        
        // Check if the original path is a directory
        if (File::isDirectory($filepath)) {
            // Generate a random filename with default extension
            $filename = bin2hex(random_bytes(10));
            $newFilepath = $filepath . '/' . $filename . '.' . $this->outputFormat;
            $filepath = $newFilepath; // Update filepath for existence check

            $data = $getFileInfo($filepath);
        } else{
            $extension = pathinfo($filepath, PATHINFO_EXTENSION);
            
            if(!empty($extension)){
                $filepath = Str::replace(".{$extension}", ".{$this->outputFormat}", $filepath);
            } else{
                $filepath = $filepath . '.' . $this->outputFormat;
            }

            $data = $getFileInfo($filepath);
        }

        if($this->generate == false){
            return $getFileInfo($filepath);
        }
        
        // If file already exists, generate a unique filename
        while (File::exists($filepath)) {
            $append = random_int(10, 99);
            
            // Generate new filename with random suffix
            $newBasename = $data['filename'] . '_' . $append . '.' . $data['extension'];
            $filepath = $data['dirname'] . '/' . $newBasename;
            
            // Update data with new filepath
            $data = $getFileInfo($filepath);
        }
        
        return $data;
    }
    
    /**
     * Get image as base64 string
     */
    public function getBase64(): string 
    {
        $image = $this->generateImage();
        
        ob_start();
        $this->outputImage($image);
        $imageData = ob_get_clean();
        unset($image);
        
        return 'data:image/' . $this->outputFormat . ';base64,' . base64_encode($imageData);
    }

    /**
     * Get supported paper sizes
     */
    public function getSupportedPaperSizes(): array 
    {
        return array_keys($this->paperSizes);
    }

    /**
     * Get paper size info
     */
    public function getPaperSizeInfo(string $size): ?array 
    {
        return $this->paperSizes[$size] ?? null;
    }
    
    /**
     * Get supported designs
     */
    public function getSupportedDesigns(): array 
    {
        return $this->supportedDesigns;
    }
    
    /**
     * Get supported formats
     */
    public function getSupportedFormats(): array 
    {
        return $this->supportedFormats;
    }

    /**
     * Get current settings
     */
    public function getSettings(): array 
    {
        return [
            'paper_size' => $this->paperSize,
            'dimensions' => [
                'width' => $this->width,
                'height' => $this->height,
                'dpi' => $this->dpi
            ],
            'font_sizes' => $this->fontSizes,
            'design' => $this->design,
            'format' => $this->outputFormat
        ];
    }
    
    /**
     * Magic: customize what is displayed during var-dump/dd().
     * Provides a pretty, safe snapshot of the current class.
     *
     * - data: the data value
     * - heading: the heading translation text 
     * - paperSize: Paper size
     * - currentFont: Current font used
     * - design: Design delected
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'data'   => $this->data,
            'heading'   => $this->heading,
            'paperSize'     => $this->paperSize,
            'currentFont'     => $this->currentFont,
            'design' => $this->design,
        ];
    }
}