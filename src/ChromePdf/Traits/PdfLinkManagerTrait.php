<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Traits;

use HeadlessChromium\Page;
use setasign\Fpdi\Tcpdf\Fpdi;
use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Throwable;

trait PdfLinkManagerTrait
{
    private bool $hasTrackedLinks = false;
    private bool $preserveLinksDuringEncryption = false;

    /**
     * Inject script to track all links with accurate positioning
     */
    private function injectLinkTrackingScript(Page $page): void
    {
        $script = '
            (function() {
                // Clear previous links
                window.__pdfLinks = [];
                
                // Use a Set to track unique URLs to avoid duplicates
                var seenUrls = new Set();
                
                // Get all links
                var links = document.querySelectorAll("a[href]");
                
                // Log the number of links found
                console.log("Found " + links.length + " links in DOM");
                
                links.forEach(function(link, index) {
                    var url = link.href || link.getAttribute("href");
                    
                    // Skip empty or javascript: links
                    if (!url || url === "#" || url.startsWith("javascript:")) {
                        return;
                    }
                    
                    // Get position
                    var rect = link.getBoundingClientRect();
                    
                    // Only process visible links with valid dimensions
                    if (rect.width > 0 && rect.height > 0) {
                        // Create a unique key for this link
                        var key = url + "_" + Math.round(rect.left) + "_" + Math.round(rect.top);
                        
                        // Only add if we haven\'t seen this exact link position before
                        if (!seenUrls.has(key)) {
                            seenUrls.add(key);
                            
                            window.__pdfLinks.push({
                                url: url,
                                x: rect.left,
                                y: rect.top,
                                w: rect.width,
                                h: rect.height,
                                page: 1
                            });
                        }
                    }
                });
                
                console.log("Tracked " + window.__pdfLinks.length + " unique links");
                return window.__pdfLinks.length;
            })();
        ';
        
        $page->evaluate($script)->getReturnValue(3000);
    }

    /**
     * Extract tracked links from the page
     */
    private function extractTrackedLinks(Page $page): array
    {
        $extractScript = '
            (function() {
                if (window.__pdfLinks && window.__pdfLinks.length) {
                    // Return a clean copy with only unique links
                    var unique = [];
                    var seen = new Set();
                    
                    for (var i = 0; i < window.__pdfLinks.length; i++) {
                        var link = window.__pdfLinks[i];
                        var key = link.url + "_" + Math.round(link.x) + "_" + Math.round(link.y);
                        
                        if (!seen.has(key)) {
                            seen.add(key);
                            unique.push({
                                url: link.url,
                                x: link.x,
                                y: link.y,
                                w: link.w,
                                h: link.h,
                                page: 1
                            });
                        }
                    }
                    
                    return unique;
                }
                return [];
            })();
        ';
        
        try {
            $result = $page->evaluate($extractScript)->getReturnValue(3000);
            return is_array($result) ? $result : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Encrypt PDF while automatically preserving links
     */
    private function encryptWithLinks(
        string $pdfContent,
        array $trackedLinks,
        string $userPassword,
        ?string $ownerPassword = null,
        ?array $blockedPermissions = [],
        int $algorithm = 3
    ): string {
        $ownerPassword = $ownerPassword ?? $userPassword;
        
        // Convert blocked permissions to TCPDF format
        $permissions = $this->buildPermissionsArray($blockedPermissions);
        
        try {
            // Create new FPDI instance
            $pdf = new Fpdi();
            
            // Set PDF metadata to preserve original
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set protection BEFORE importing
            $pdf->SetProtection(
                $permissions,
                $userPassword,
                $ownerPassword,
                $algorithm
            );
            
            // Import the existing PDF
            $tempFile = $this->createTempFile($pdfContent);
            $pageCount = $pdf->setSourceFile($tempFile);
            
            // Get PDF page dimensions from the actual PDF
            $firstTemplateId = $pdf->importPage(1);
            $firstSize = $pdf->getTemplateSize($firstTemplateId);
            $pdfPageWidth = $firstSize['width'];
            $pdfPageHeight = $firstSize['height'];
            
            // Scale factor: browser pixels (96 DPI) to PDF points (72 DPI)
            $scaleFactor = 72 / 96;
            
            // Import each page
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                
                // Get page size
                $size = $pdf->getTemplateSize($templateId);
                $pageWidth = $size['width'];
                $pageHeight = $size['height'];
                
                // Add page
                $orientation = ($pageWidth > $pageHeight) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$pageWidth, $pageHeight]);
                
                // Use the imported page
                $pdf->useTemplate($templateId);
                
                // Add links only for page 1 (since our content is single page)
                if ($pageNo === 1 && !empty($trackedLinks)) {
                    foreach ($trackedLinks as $link) {
                        try {
                            // Convert browser coordinates to PDF coordinates
                            // Browser: origin at top-left, PDF: origin at bottom-left
                            $pdfX = $link['x'] * $scaleFactor;
                            $pdfY = $pageHeight - (($link['y'] + $link['h']) * $scaleFactor);
                            $pdfW = max(5, $link['w'] * $scaleFactor);
                            $pdfH = max(5, $link['h'] * $scaleFactor);
                            
                            // Ensure coordinates are within page bounds
                            $pdfX = max(5, min($pageWidth - 10, $pdfX));
                            $pdfY = max(5, min($pageHeight - 10, $pdfY));
                            
                            // Add the link annotation
                            $pdf->Link($pdfX, $pdfY, $pdfW, $pdfH, $link['url']);
                        } catch (Throwable $e) {
                            // Skip problematic links
                            continue;
                        }
                    }
                }
            }
            
            return $pdf->Output('', 'S');
            
        } catch (Throwable $e) {
            throw new ConversionFailedException(
                'Failed to encrypt PDF while preserving links: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Build TCPDF permissions array
     */
    private function buildPermissionsArray(?array $blockedPermissions): array
    {
        $allowed = [
            'print', 'modify', 'copy', 'annot-forms',
            'fill-forms', 'extract', 'assemble', 'print-high'
        ];
        
        if (empty($blockedPermissions)) {
            return $allowed;
        }
        
        $blockedSet = array_flip($blockedPermissions);
        return array_values(array_filter($allowed, function($perm) use ($blockedSet) {
            return !isset($blockedSet[$perm]);
        }));
    }
    
    /**
     * Create temporary file from PDF content
     */
    private function createTempFile(string $content): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'pdf_');
        if ($tempPath === false) {
            throw new ConversionFailedException('Failed to create temporary file');
        }
        
        if (file_put_contents($tempPath, $content) === false) {
            throw new ConversionFailedException('Failed to write PDF to temporary file');
        }
        
        register_shutdown_function(function() use ($tempPath) {
            @unlink($tempPath);
        });
        
        return $tempPath;
    }
}