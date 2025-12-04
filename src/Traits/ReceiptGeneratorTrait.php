<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use GdImage;
use Exception;
use RuntimeException;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\NumberToWords;



trait ReceiptGeneratorTrait
{

    /**
     * Adjust font sizes based on paper size
     */
    private function adjustFontSizesForPaper(string $paperSize): void 
    {
        $sizeFactors = [
            '55mm' => 0.6,  // Reduced from 0.6
            '80mm' => 0.7, // Reduced from 0.7
            'a4' => 1.0,
            'a5' => 0.9,    // Reduced from 0.9
            'letter' => 1.0,
            'legal' => 1.1,
            'square' => 1.0,
        ];
        
        $factor = $sizeFactors[$paperSize] ?? 1.0;
        
        foreach ($this->fontSizes as $key => $size) {
            $this->fontSizes[$key] = max(8, round($size * $factor)); // Minimum font size 8
        }
    }

    /**
     * Adjust font sizes for custom dimensions
     */
    private function adjustFontSizesForCustom(int $width): void 
    {
        $baseWidth = 600; // Default width
        $factor = $width / $baseWidth;
        
        foreach ($this->fontSizes as $key => $size) {
            $this->fontSizes[$key] = round($size * $factor);
        }
    }

    /**
     * Set default fonts
     */
    private function setDefaultFonts(): void 
    {
        $files = $this->fontFiles;
        if(File::exists($files['regular']) || File::exists($files['bold'])){
            return;
        }

        // Will trigger GD font fallback
        $this->fontFiles = [
            'regular' => '',
            'bold' => '',
        ];
    }

    /**
     * Generate receipt image
     */
    private function generateImage(): GdImage 
    {
        // Check memory before creating image
        $this->checkMemoryRequirements();

        // Create image
        $image = imagecreatetruecolor((int) $this->width, (int) $this->height);
        
        // Set image resolution for print (doesn't affect pixel dimensions but metadata)
        imageresolution($image, $this->dpi, $this->dpi);
        
        // Get colors for current design
        $colors = $this->getColors($image);
        
        // Fill background
        imagefill($image, 0, 0, $colors['background']);
        
        // Generate based on design
        $methodName = 'generate' . ucfirst($this->design) . 'Design';

        if (method_exists($this, $methodName)) {
            $this->$methodName($image, $colors);
        } else {
            $this->generateModernDesign($image, $colors);
        }
        
        return $image;
    }

    /**
     * Check memory requirements before creating image
     */
    private function checkMemoryRequirements(): void
    {
        // Calculate required memory (width * height * 4 bytes per pixel)
        $requiredMemory = $this->width * $this->height * 4;
        
        // Add buffer for GD operations
        $requiredMemory *= 1.5;
        
        // Get current memory usage
        $currentUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        
        // Calculate available memory
        $availableMemory = $memoryLimit - $currentUsage;
        
        if ($requiredMemory > $availableMemory) {
            throw new RuntimeException(
                "Insufficient memory to generate image. " .
                "Required: " . round($requiredMemory / 1024 / 1024, 2) . "MB, " .
                "Available: " . round($availableMemory / 1024 / 1024, 2) . "MB. " .
                "Try reducing dimensions or DPI."
            );
        }
    }

    /**
     * Get memory limit in bytes
     */
    private function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
            if ($matches[2] == 'G') {
                $memoryLimit = $matches[1] * 1024 * 1024 * 1024;
            } elseif ($matches[2] == 'M') {
                $memoryLimit = $matches[1] * 1024 * 1024;
            } elseif ($matches[2] == 'K') {
                $memoryLimit = $matches[1] * 1024;
            }
        }
        
        return (int) $memoryLimit;
    }

    /**
     * Get color resources for image
     */
    private function getColors(GdImage $image): array 
    {
        $designColor = $this->designColors[$this->design];
        $colors = [];
        
        foreach ($designColor as $name => $rgb) {
            $colors[$name] = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        }
        
        // Additional colors
        $colors['white'] = imagecolorallocate($image, 255, 255, 255);
        $colors['black'] = imagecolorallocate($image, 0, 0, 0);
        $colors['gray'] = imagecolorallocate($image, 150, 150, 150);
        $colors['light_gray'] = imagecolorallocate($image, 240, 240, 240);
        $colors['dark_gray'] = imagecolorallocate($image, 100, 100, 100);
        $colors['red'] = imagecolorallocate($image, 231, 76, 60);
        $colors['green'] = imagecolorallocate($image, 46, 204, 113);
        $colors['yellow'] = imagecolorallocate($image, 241, 196, 15);
        
        return $colors;
    }

    /**
     * Format amount
     *
     * @param  mixed $amount
     * @param  mixed $decimals
     * @return string
     */
    private function format($amount = null, $decimals = 2)
    {
        // Convert to string
        $amountStr = (string) $amount;
        
        // Split into whole and decimal parts
        $parts = explode('.', $amountStr);
        $whole = $parts[0] ?? '0';
        $fraction = $parts[1] ?? '';
        
        // Handle negative numbers
        $isNegative = str_starts_with($whole, '-');
        if ($isNegative) {
            $whole = substr($whole, 1);
        }
        
        // Format whole part with commas
        $formattedWhole = '';
        $length = strlen($whole);
        
        for ($i = 0; $i < $length; $i++) {
            if ($i > 0 && ($length - $i) % 3 === 0) {
                $formattedWhole .= ',';
            }
            $formattedWhole .= $whole[$i];
        }
        
        // Handle decimals
        if ($decimals > 0) {
            if (strlen($fraction) > $decimals) {
                $fraction = substr($fraction, 0, $decimals);
            } else {
                $fraction = str_pad($fraction, $decimals, '0');
            }
            
            $result = ($isNegative ? '-' : '') . $formattedWhole . '.' . $fraction;
        } else {
            $result = ($isNegative ? '-' : '') . $formattedWhole;
        }
        
        return "{$this->currency['symbol']}{$result}";
    }

    /**
     * Create Footer
     *
     * @param  mixed $image
     * @param  mixed $colors
     * @return void
     */
    private function footer(GdImage $image, array $colors)
    {
        $width      = $this->width;
        $height     = $this->height;
        $data       = $this->data;
        
        $footerY = $this->getFooterY();
        $footerFontSize = $this->getFooterFontSize();

        // For thermal printers, use simpler footer
        if ($this->isThermal()) {
            $this->addText(
                $image, 
                $data['footer'],
                $width/2, 
                $footerY, 
                $footerFontSize, 
                $colors['text'], 
                'center'
            );
            
            // Only show address/phone if there's space
            if ($height > 400) {
                $this->addText(
                    $image, 
                    "{$data['company_address']} | {$data['company_phone']}", 
                    $width/2,
                    $footerY + 15, 
                    max(4, $footerFontSize - 2), 
                    $colors['gray'], 
                    'center'
                );
            }
        } else {
            // Regular footer for larger papers
            $this->addText(
                $image, 
                $data['footer'],
                $width/2, 
                $footerY, 
                $footerFontSize, 
                $colors['text'], 
                'center'
            );

            $this->addText(
                $image, 
                "{$data['company_address']} | {$data['company_phone']}", 
                $width/2,
                $footerY + 20, 
                max(8, $footerFontSize - 2), 
                $colors['gray'], 
                'center'
            );
        }
    }

    /**
     * Get footer Y position
     */
    private function getFooterY(): int
    {
        $baseFooterY = $this->height - 50;
        
        if ($this->isThermal()) {
            // For thermal, leave less space at bottom
            return (int) $this->height - 60;
        }
        
        return (int) $baseFooterY;
    }

    /**
     * Get footer font size
     */
    private function getFooterFontSize(): int
    {
        $baseSize = $this->fontSizes['small'];
        
        if ($this->isThermal()) {
            return (int) max(6, $baseSize - 3);
        }
        
        return (int) $baseSize;
    }

    /**
     * Set default data
     */
    private function setDefaultData(): void
    {
        $this->data = [
            'receipt_number' => 'RCPT-' . date('Ymd') . '-' . rand(1000, 9999),
            'date' => date('Y-m-d H:i:s'),
            'amount' => 0.00,
            'currency' => 'USD',
            'payment_method' => 'Credit Card',
            'customer_name' => null,
            'customer_email' => null,
            'transaction_id' => $this->generateTransactionId(),
            'description' => 'Payment for services',
            'company_name' => 'Tech Solutions Inc.',
            'company_logo' => null,
            'company_address' => 'Example address - Lagos, Nigeria.',
            'company_phone' => '+234 9XXXX XXXX',
            'tax_amount' => 0.00,
            'total_amount' => 0.00,
            'footer' => 'Thank you for using our services!',
            'status' => 'PAID'
        ];
    }

    /**
     * Set default headings
     */
    private function setDefaultHeading(): void
    {
        $this->heading = [
            'invoice_receipt' => 'INVOICE/RECEIPT DETAILS',
            'receipt_number' => 'Receipt No',
            'transaction_id' => 'Transaction ID',
            'payment_method' => 'Payment Method',
            'payment_receipt' => 'Payment Receipt',
            'customer_name' => 'Customer',
            'description' => 'Description',
            'details' => 'Details',
            'date' => 'Date',
            'email' => 'Email',
            'amount' => 'Amount',
            'tax' => 'Tax',
            'total' => 'Total',
            'paid' => 'Paid',
            'unpaid' => 'Unpaid',
            'pending' => 'Pending',
        ];
    }
        
    /**
     * Generate Transaction ID
     *
     * @return string
     */
    private function generateTransactionId(): string
    {
        $prefix = 'TXN'; // or 'PMT', 'TRX', 'INV' depending on context
        $random = random_int(1000000000, 9999999999); // 6-digit random
        $unique = uniqid('', true); // More uniqueness
        
        // Format: TXN-B8AB001C5652036769
        return sprintf(
            '%s-%s',
            $prefix,
            substr(Str::upper(hash('crc32', $unique)) . $random, 0, 16)
        );
    }

    /**
     * Normalize Value For BCMath
     *
     * @param  mixed $value
     * @return string
     */
    private function normalizeForBCMath($value): string
    {
        // Convert to string if not already
        if (!is_string($value)) {
            $value = (string) $value;
        }
        
        // Handle scientific notation
        if (stripos($value, 'E') !== false) {
            $value = sprintf('%.2f', (float)$value);
        }
        
        // Remove any non-numeric characters except minus sign and decimal point
        $value = preg_replace('/[^0-9.-]/', '', $value);
        
        // Ensure it has at most 2 decimal places
        if (strpos($value, '.') !== false) {
            $parts = explode('.', $value);
            if (count($parts) === 2) {
                $value = $parts[0] . '.' . substr($parts[1], 0, 2);
            }
        }
        
        // Check if it's a valid number
        if (!is_numeric($value)) {
            return '0.00';
        }
        
        return $value;
    }
    
    /**
     * setdesignColors
     */
    private function setdesignColors(): void
    {
        $this->designColors = [
            'modern' => [
                'primary' => [41, 128, 185],
                'secondary' => [52, 152, 219],
                'background' => [245, 245, 245],
                'text' => [44, 62, 80],
                'accent' => [46, 204, 113]
            ],
            'classic' => [
                'primary' => [123, 36, 28],
                'secondary' => [179, 57, 57],
                'background' => [255, 253, 231],
                'text' => [51, 51, 51],
                'accent' => [26, 82, 118]
            ],
            'minimal' => [
                'primary' => [68, 68, 68],
                'secondary' => [136, 136, 136],
                'background' => [255, 255, 255],
                'text' => [34, 34, 34],
                'accent' => [68, 68, 68]
            ],
            'corporate' => [
                'primary' => [0, 82, 155],
                'secondary' => [30, 113, 184],
                'background' => [255, 255, 255],
                'text' => [34, 34, 34],
                'accent' => [0, 82, 155]
            ],
            'invoice' => [
                'primary' => [0, 0, 0],
                'secondary' => [100, 100, 100],
                'background' => [255, 255, 255],
                'text' => [0, 0, 0],
                'accent' => [0, 0, 0]
            ]
        ];
    }
    
    /**
     * setPaperSizes
     */
    private function setPaperSizes(): void
    {
        $this->paperSizes = [
            // Common receipt/printer sizes
            '55mm' => [
                'width' => 2.165, // 55mm in inches
                'height' => 4.0, // Continuous paper
                'dpi' => 203, // Thermal printer DPI
                'type' => 'thermal'
            ],
            '80mm' => [
                'width' => 3.15, // 80mm in inches
                'height' => 6.0,
                'dpi' => 203,
                'type' => 'thermal'
            ],
            
            // Standard paper sizes
            'a4' => [
                'width' => 8.27,
                'height' => 11.69,
                'dpi' => 300,
                'type' => 'standard'
            ],
            'a5' => [
                'width' => 5.83,
                'height' => 8.27,
                'dpi' => 300,
                'type' => 'standard'
            ],
            'legal' => [
                'width' => 8.5,
                'height' => 14,
                'dpi' => 300,
                'type' => 'standard'
            ],
            'letter' => [
                'width' => 8.5,
                'height' => 11,
                'dpi' => 300,
                'type' => 'standard'
            ],
            'square' => [
                'width' => 8,
                'height' => 8,
                'dpi' => 300,
                'type' => 'custom'
            ]
        ];
    }

    /**
     * Simplify special characters for font compatibility
     */
    private function simplifySpecialCharacters(string $text): string
    {
        $replacements = [
            // African Currencies
            '₦' => 'N',      // Nigerian Naira
            '؋' => 'AFN',    // Afghan Afghani
            '֏' => 'AMD',    // Armenian Dram
            '৳' => 'BDT',    // Bangladeshi Taka
            '៛' => 'KHR',    // Cambodian Riel
            '₡' => 'CRC',    // Costa Rican Colón
            '¢' => 'c',      // Cent
            '₵' => 'GHS',    // Ghanaian Cedi
            '₲' => 'PYG',    // Paraguayan Guarani
            '₴' => 'UAH',    // Ukrainian Hryvnia
            '₭' => 'LAK',    // Lao Kip
            '₮' => 'MNT',    // Mongolian Tugrik
            '₱' => 'PHP',    // Philippine Peso
            '₺' => 'TRY',    // Turkish Lira
            '₼' => 'AZN',    // Azerbaijani Manat
            '฿' => 'THB',    // Thai Baht
            
            // Asian Currencies
            '₹' => 'Rs',     // Indian Rupee
            '₨' => 'Rs',     // Rupee (alternative)
            '₩' => 'KRW',    // South Korean Won
            '¥' => '¥',      // Japanese Yen/Chinese Yuan (usually well supported)
            '圓' => 'HK$',    // Hong Kong Dollar (traditional)
            '元' => 'CN¥',    // Chinese Yuan (simplified)
            '円' => 'JP¥',    // Japanese Yen (character)
            
            // Middle Eastern Currencies
            '﷼' => 'Rial',   // Iranian Rial/Saudi Riyal
            '₪' => 'NIS',    // Israeli Shekel
            'د.إ' => 'AED',   // UAE Dirham
            'د.ا' => 'JOD',   // Jordanian Dinar
            'د.ب' => 'BHD',   // Bahraini Dinar
            'ر.س' => 'SAR',   // Saudi Riyal
            'ر.ق' => 'QAR',   // Qatari Riyal
            'ر.ع' => 'OMR',   // Omani Rial
            'د.ك' => 'KWD',   // Kuwaiti Dinar
            
            // European Currencies
            '€' => '€',      // Euro (usually well supported)
            '£' => '£',      // British Pound (usually well supported)
            '₽' => 'RUB',    // Russian Ruble
            '₸' => 'KZT',    // Kazakhstani Tenge
            '₾' => 'GEL',    // Georgian Lari
            'ƒ' => 'ANG',    // Netherlands Antillean Guilder
            '₣' => 'FRF',    // French Franc (historic)
            '₤' => 'Lira',   // Lira (historic)
            '₶' => 'Livres', // Livre Tournois (historic)
            '₷' => 'Spesmilo', // Spesmilo (historic)
            
            // American Currencies
            '$' => '$',      // Dollar (well supported)
            '₡' => 'CRC',    // Costa Rican Colón (duplicate for clarity)
            '₲' => 'PYG',    // Paraguayan Guarani (duplicate)
            
            // Cryptocurrencies
            '₿' => 'BTC',    // Bitcoin
            'Ξ' => 'ETH',    // Ethereum
            'Ł' => 'LTC',    // Litecoin
            'Ƀ' => 'BCH',    // Bitcoin Cash
            
            // Other Symbols
            '¤' => 'Cur',    // Currency sign (generic)
            '₰' => 'Penny',  // German Penny (historic)
            '₯' => 'Drachma', // Greek Drachma (historic)
            '₠' => 'ECU',    // European Currency Unit (historic)
            '₢' => 'BRR',    // Brazilian Real (historic)
            '₳' => 'ARA',    // Argentine Austral (historic)
            '₻' => 'Nordic Mark', // Nordic Mark (historic)
            '₼' => 'Manat',  // Azerbaijani Manat (alternative)
            '₽' => 'RUR',    // Russian Ruble (alternative)
            
            // Currency subunit symbols
            '₥' => 'M',      // Mill
            '₰' => 'Pf',     // Pfennig
            '₣' => 'F',      // Franc
            '₤' => 'L',      // Lira
            'ℳ' => 'M',      // German Mark (historic)
            
            // Special formatting characters that might cause issues
            '٫' => '.',      // Arabic decimal separator
            '٬' => ',',      // Arabic thousands separator
            '৹' => '/',      // Bengali currency numerator
            '૱' => '/',      // Gujarati fraction
            '௹' => 'Rs',     // Tamil Rupee sign
            '꠸' => '/',      // North Indic fraction
            
            // Additional regional currency symbols
            '₫' => 'VND',    // Vietnamese Dong
            '₸' => 'KZT',    // Kazakhstani Tenge
            '₾' => 'GEL',    // Georgian Lari
            '₼' => 'AZN',    // Azerbaijani Manat
            '₺' => 'TRY',    // Turkish Lira
            '₻' => 'NMR',    // Nordic Mark (proposed)
            '₼' => 'MANAT',  // Azerbaijani Manat (full)
            '₽' => 'RUBLE',  // Russian Ruble (full)
            
            // Currency abbreviations for unsupported symbols
            'Kč' => 'CZK',   // Czech Koruna
            'zł' => 'PLN',   // Polish Zloty
            'lei' => 'RON',  // Romanian Leu
            'ден' => 'MKD',  // Macedonian Denar
            'лв' => 'BGN',   // Bulgarian Lev
            'kn' => 'HRK',   // Croatian Kuna
            'р' => 'RUB',    // Russian Ruble (Cyrillic)
            'сом' => 'KGS',  // Kyrgyzstani Som
            'сомони' => 'TJS', // Tajikistani Somoni
            
            // African currency symbols
            'Le' => 'SLL',   // Sierra Leonean Leone
            'Nfa' => 'GNF',  // Guinean Franc
            'T$' => 'TOP',   // Tongan Pa'anga
            'WS$' => 'WST',  // Samoan Tala
            'ZK' => 'ZMW',   // Zambian Kwacha
            'Z$' => 'ZWL',   // Zimbabwean Dollar
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

}
