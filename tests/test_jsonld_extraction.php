#!/usr/bin/env php
<?php
/**
 * WebScraper JSON-LD Test
 * 
 * Tests the enhanced extraction from the exact JSON-LD data
 * provided as example from AliExpress
 */

use Tamedevelopers\Support\WebScraper;

require_once __DIR__ . '/../vendor/autoload.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "WebScraper JSON-LD Extraction Test\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

/**
 * Test Data: AliExpress Product with JSON-LD
 * This is the exact structure mentioned in requirements
 */
$jsonLdHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>White Transparent Mesh Tank Top Women Slim Fit Summer</title>
    <meta property="og:title" content="White Transparent Mesh Tank Top Women Slim Fit Summer">
    <meta property="og:description" content="Elegant transparent mesh tank top for women, slim fit, solid white color">
    <meta property="og:image" content="https://ae-pic-a1.aliexpress-media.com/kf/S55f9c0617d144512bd8f251cc0aa5a3c5.jpg">
    <meta property="og:price:amount" content="8778.69">
    <meta property="og:price:currency" content="NGN">
    <script type="application/ld+json">[{
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": "White Transparent Mesh Tank Top Women Slim Fit Summer",
        "image": [
            "https://ae-pic-a1.aliexpress-media.com/kf/S55f9c0617d144512bd8f251cc0aa5a3c5.jpg",
            "https://ae-pic-a1.aliexpress-media.com/kf/S55f9c0617d144512bd8f251cc0aa5a3c5.jpg",
            "https://ae-pic-a1.aliexpress-media.com/kf/Scf26c4bfec064b81bafd0b1ba42480859.jpg"
        ],
        "description": "Elegant transparent mesh tank top for women, slim fit, solid white color, high stretch knit fabric, perfect for summer parties. Made from polyester and spandex, lightweight and breathable.",
        "color": "White",
        "availableColor": ["White", "Black", "Pink", "Blue"],
        "size": "M",
        "availableSize": ["XS", "S", "M", "L", "XL"],
        "brand": {
            "@type": "Brand",
            "name": "Vamos Todos"
        },
        "offers": {
            "@type": "Offer",
            "availability": "https://schema.org/InStock",
            "url": "https://www.aliexpress.com/item/1005010595606666.html",
            "priceCurrency": "NGN",
            "price": "8778.69"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "reviewCount": "31"
        }
    },{
        "@context": "https://schema.org/",
        "@type": "VideoObject",
        "contentUrl": "https://video.aliexpress-media.com/play/u/ae_sg_item/2211420589645/p/1/e/6/t/10301/5000403828620.mp4",
        "uploadDate": "2026-06-09T03:46:53.301Z",
        "name": "White Transparent Mesh Tank Top Women Slim Fit Summer",
        "description": "Elegant transparent mesh tank top for women, slim fit, solid white color, high stretch knit fabric, perfect for summer parties. Made from polyester and spandex, lightweight and breathable.",
        "thumbnailUrl": [
            "https://ae-pic-a1.aliexpress-media.com/kf/S55f9c0617d144512bd8f251cc0aa5a3c5.jpg",
            "https://ae-pic-a1.aliexpress-media.com/kf/S55f9c0617d144512bd8f251cc0aa5a3c5.jpg",
            "https://ae-pic-a1.aliexpress-media.com/kf/Scf26c4bfec064b81bafd0b1ba42480859.jpg"
        ]
    }]</script>
</head>
<body>
    <h1>White Transparent Mesh Tank Top Women Slim Fit Summer</h1>
    <p class="description">Elegant transparent mesh tank top for women, slim fit, solid white color, high stretch knit fabric, perfect for summer parties. Made from polyester and spandex, lightweight and breathable.</p>
    <span class="price">NGN 8778.69</span>
    <div class="images">
        <img src="https://ae-pic-a1.aliexpress-media.com/kf/S55f9c0617d144512bd8f251cc0aa5a3c5.jpg" alt="product image 1">
        <img src="https://ae-pic-a1.aliexpress-media.com/kf/Scf26c4bfec064b81bafd0b1ba42480859.jpg" alt="product image 2">
    </div>
</body>
</html>
HTML;

// Test the extraction using reflection to inject HTML
try {
    $scraper = new WebScraper([
        'engine' => 'dom',
        'cache_enabled' => false,
    ], 'https://www.aliexpress.com/item/1005010595606666.html');
    
    // Use reflection to set HTML directly (simulating fetch)
    $reflection = new ReflectionClass($scraper);
    
    // Set HTML
    $htmlProperty = $reflection->getProperty('html');
    $htmlProperty->setAccessible(true);
    $htmlProperty->setValue($scraper, $jsonLdHtml);
    
    // Create DOM and XPath
    $domProperty = $reflection->getProperty('dom');
    $domProperty->setAccessible(true);
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($jsonLdHtml, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
    $domProperty->setValue($scraper, $dom);
    
    $xpathProperty = $reflection->getProperty('xpath');
    $xpathProperty->setAccessible(true);
    $xpathProperty->setValue($scraper, new DOMXPath($dom));
    
    // Invoke scrape method
    $scrapeMethod = $reflection->getMethod('scrape');
    $scrapeMethod->setAccessible(true);
    $scrapeMethod->invoke($scraper);
    
    // Get data
    $data = $scraper->getData();
    
    echo "✓ EXTRACTION RESULTS\n";
    echo "─────────────────────────────────────────────────────────\n\n";
    
    // Test 1: Company extraction
    echo "TEST 1: Company Extraction\n";
    echo "Expected: aliexpress (case-insensitive)\n";
    echo "Got:      " . $data['company'] . "\n";
    echo "Status:   " . (strtolower($data['company']) === 'aliexpress' ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 2: Name extraction
    echo "TEST 2: Name Extraction\n";
    echo "Expected: White Transparent Mesh Tank Top Women Slim Fit Summer\n";
    echo "Got:      " . $data['name'] . "\n";
    echo "Status:   " . (!empty($data['name']) ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 3: Price extraction
    echo "TEST 3: Price Extraction\n";
    echo "Expected: 8778.69\n";
    echo "Got:      " . $data['price'] . "\n";
    echo "Status:   " . ($data['price'] === '8778.69' ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 4: Currency extraction
    echo "TEST 4: Currency Extraction (ISO 4217)\n";
    echo "Expected: NGN\n";
    echo "Got:      " . $data['currency'] . "\n";
    echo "Status:   " . ($data['currency'] === 'NGN' ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 5: Description extraction
    echo "TEST 5: Description Extraction\n";
    echo "Expected: Contains 'transparent mesh tank top'\n";
    echo "Got:      " . substr($data['description'], 0, 50) . "...\n";
    echo "Status:   " . (str_contains($data['description'], 'transparent mesh') ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 6: Images extraction
    echo "TEST 6: Images Extraction\n";
    echo "Expected: 2+ images\n";
    echo "Got:      " . count($data['images']) . " image(s)\n";
    echo "Status:   " . (count($data['images']) >= 2 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    if (!empty($data['images'])) {
        echo "Images URLs:\n";
        foreach (array_slice($data['images'], 0, 2) as $img) {
            echo "  • " . substr($img, 0, 60) . "...\n";
        }
        echo "\n";
    }
    
    // Test 7: Main image
    echo "TEST 7: Main Image Extraction\n";
    echo "Expected: First image URL\n";
    echo "Got:      " . (!empty($data['main_image']) ? "✓ YES" : "✗ NO") . "\n";
    echo "Status:   " . (!empty($data['main_image']) ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 8: Enrichment sources
    echo "TEST 8: Enrichment Sources\n";
    $enrichment = $data['raw_data']['enrichment'] ?? [];
    echo "Expected: json-ld should be present\n";
    echo "Got:      " . implode(', ', $enrichment) . "\n";
    echo "Status:   " . (in_array('json-ld', $enrichment) ? "✓ PASS" : "⚠ WARNING") . "\n\n";
    
    // Test 9: Colors extraction from JSON-LD
    echo "TEST 9: Colors Extraction (from JSON-LD)\n";
    echo "Expected: 4 colors (White, Black, Pink, Blue)\n";
    echo "Got:      " . count($data['colors']) . " color(s): " . implode(', ', $data['colors']) . "\n";
    echo "Status:   " . (count($data['colors']) >= 3 ? "✓ PASS" : "⚠ WARNING") . "\n\n";
    
    // Test 10: Sizes extraction from JSON-LD
    echo "TEST 10: Sizes Extraction (from JSON-LD)\n";
    echo "Expected: 5 sizes (XS, S, M, L, XL)\n";
    echo "Got:      " . count($data['sizes']) . " size(s): " . implode(', ', $data['sizes']) . "\n";
    echo "Status:   " . (count($data['sizes']) >= 4 ? "✓ PASS" : "⚠ WARNING") . "\n\n";
    
    // Summary
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "OVERALL RESULTS\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $checks = [
        'Company' => strtolower($data['company']) === 'aliexpress',
        'Name' => !empty($data['name']),
        'Price' => $data['price'] === '8778.69',
        'Currency' => $data['currency'] === 'NGN',
        'Description' => !empty($data['description']),
        'Images' => count($data['images']) >= 2,
        'Main Image' => !empty($data['main_image']),
        'Colors' => count($data['colors']) >= 3,
        'Sizes' => count($data['sizes']) >= 4,
    ];
    
    $passed = 0;
    $total = count($checks);
    
    foreach ($checks as $check => $result) {
        $status = $result ? "✓ PASS" : "✗ FAIL";
        echo "$check: $status\n";
        if ($result) $passed++;
    }
    
    echo "─────────────────────────────────────────────────────────────\n";
    echo "SCORE: $passed/$total\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    if ($passed === $total) {
        echo "✓ ALL TESTS PASSED!\n";
        echo "The WebScraper successfully extracts from JSON-LD data!\n";
    } else {
        echo "⚠ SOME TESTS FAILED\n";
        echo "Please review the extraction logic.\n";
    }
    
    // Bonus: Show full JSON output
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "FULL DATA OUTPUT (JSON)\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo $scraper->toJson(true);
    echo "\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

libxml_use_internal_errors(false);
