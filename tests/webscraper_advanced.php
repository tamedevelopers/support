<?php

use Tamedevelopers\Support\WebScraper;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Advanced WebScraper Tests
 * Tests improved data extraction from major e-commerce websites
 */

// Real URLs for testing
$testUrls = [
    'jumia' => 'https://www.jumia.com.ng/20000-mah-utra-slim-portable-power-bank-ace-elec-mpg11495891.html',
    'jumia_shoes' => 'https://www.jumia.com.ng/ashion-kq-mens-stylish-grey-silver-turf-football-shoes-durable-training-sports-footwear-for-match-casual-419139185.html',
    'ebay' => 'https://www.ebay.com/itm/386936766515',
    'aliexpress' => 'https://www.aliexpress.com/item/1005010595606666.html',
];

/**
 * Test results structure
 */
function testScraper($name, $url)
{
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Testing: $name\n";
    echo "URL: $url\n";
    echo str_repeat('=', 80) . "\n";

    try {
        $scraper = WebScraper::url($url)
            ->fetch();

        $data = $scraper->getData();

        echo "\n✓ SCRAPED DATA:\n";
        echo "─────────────────────────────────\n";
        
        echo "Company:     " . ($data['company'] ?? 'N/A') . "\n";
        echo "Name:        " . substr(($data['name'] ?? 'N/A'), 0, 60) . (strlen($data['name'] ?? '') > 60 ? '...' : '') . "\n";
        echo "Price:       " . ($data['price'] ?? 'N/A') . "\n";
        echo "Currency:    " . ($data['currency'] ?? 'N/A') . "\n";
        echo "Main Image:  " . ((!empty($data['main_image']) ? 'YES' : 'N/A')) . "\n";
        
        // Colors
        $colors = $data['colors'] ?? [];
        echo "Colors:      ";
        if (!empty($colors)) {
            echo implode(', ', array_slice($colors, 0, 5));
            echo (count($colors) > 5 ? " (+" . (count($colors) - 5) . " more)" : "");
        } else {
            echo "N/A";
        }
        echo "\n";
        
        // Sizes
        $sizes = $data['sizes'] ?? [];
        echo "Sizes:       ";
        if (!empty($sizes)) {
            echo implode(', ', array_slice($sizes, 0, 5));
            echo (count($sizes) > 5 ? " (+" . (count($sizes) - 5) . " more)" : "");
        } else {
            echo "N/A";
        }
        echo "\n";
        
        // Description
        $desc = $data['description'] ?? '';
        echo "Description: ";
        if ($desc) {
            echo substr($desc, 0, 60) . (strlen($desc) > 60 ? '...' : '');
        } else {
            echo "N/A";
        }
        echo "\n";
        
        // Images
        $images = $data['images'] ?? [];
        echo "Images:      " . count($images) . " image(s)\n";
        
        // Raw data info
        $raw = $data['raw_data'] ?? [];
        echo "\n─────────────────────────────────\n";
        echo "Engine Used: " . ($raw['engine'] ?? 'unknown') . "\n";
        echo "HTTP Status: " . ($raw['http_status'] ?? 'N/A') . "\n";
        echo "Final URL:   " . ($raw['final_url'] ?? $url) . "\n";
        
        if (!empty($raw['blocked'])) {
            echo "⚠ BLOCKED:   YES\n";
            echo "Reason:      " . ($raw['block_reason'] ?? 'Unknown') . "\n";
        }
        
        // Enrichment info
        $enrichment = $raw['enrichment'] ?? [];
        if (!empty($enrichment)) {
            echo "\nEnrichment Sources:\n";
            foreach ($enrichment as $source) {
                echo "  • $source\n";
            }
        }
        
        echo "\n✓ SUCCESS\n";
        
    } catch (\Exception $e) {
        echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    }
}

/**
 * Run tests
 */
$enableLiveTests = false; // Set to true to test live URLs

if ($enableLiveTests) {
    foreach ($testUrls as $name => $url) {
        testScraper($name, $url);
    }
} else {
    echo "Live URL tests are disabled. Set \$enableLiveTests = true to run.\n";
}

/**
 * Test with mock HTML (JSON-LD structured data from AliExpress)
 */
echo "\n" . str_repeat('=', 80) . "\n";
echo "Testing: Mock AliExpress with JSON-LD\n";
echo str_repeat('=', 80) . "\n";

$mockHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta property="og:title" content="White Transparent Mesh Tank Top Women">
    <meta property="og:description" content="Elegant transparent mesh tank top for women">
    <meta property="og:image" content="https://ae-pic-a1.aliexpress-media.com/kf/S55f9c0617d144512bd8f251cc0aa5a3c5.jpg">
    <script type="application/ld+json">[{
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": "White Transparent Mesh Tank Top Women Slim Fit Summer",
        "image": [
            "https://ae-pic-a1.aliexpress-media.com/kf/S55f9c0617d144512bd8f251cc0aa5a3c5.jpg",
            "https://ae-pic-a1.aliexpress-media.com/kf/Scf26c4bfec064b81bafd0b1ba42480859.jpg"
        ],
        "description": "Elegant transparent mesh tank top for women, slim fit, solid white color, high stretch knit fabric, perfect for summer parties.",
        "color": "White",
        "availableColor": ["White", "Black", "Pink"],
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
    }]</script>
</head>
<body>
    <h1 class="product-title">White Transparent Mesh Tank Top Women Slim Fit Summer</h1>
    <span class="price">NGN 8778.69</span>
    <div class="description">Elegant transparent mesh tank top for women, slim fit, solid white color, high stretch knit fabric, perfect for summer parties. Made from polyester and spandex, lightweight and breathable.</div>
    <img class="product-image" src="https://ae-pic-a1.aliexpress-media.com/kf/S55f9c0617d144512bd8f251cc0aa5a3c5.jpg" alt="product image">
</body>
</html>
HTML;

// Create temporary file with mock HTML
$tempFile = tempnam(sys_get_temp_dir(), 'scraper_test_');
file_put_contents($tempFile, $mockHtml);

try {
    // Use DOM engine for mock testing (since file: URLs work better)
    $scraper = new WebScraper([
        'engine' => 'dom',
        'cache_enabled' => false,
    ], 'https://www.aliexpress.com/item/1005010595606666.html');
    
    // Manually set HTML for testing
    $reflection = new ReflectionClass($scraper);
    $htmlProperty = $reflection->getProperty('html');
    $htmlProperty->setAccessible(true);
    $htmlProperty->setValue($scraper, $mockHtml);
    
    // Run scrape directly
    $scrapeMethod = $reflection->getMethod('scrape');
    $scrapeMethod->setAccessible(true);
    $scrapeMethod->invoke($scraper);
    
    $data = $scraper->getData();
    
    echo "\n✓ MOCK DATA RESULTS:\n";
    echo "─────────────────────────────────\n";
    echo "Company:     " . ($data['company'] ?? 'N/A') . "\n";
    echo "Name:        " . substr(($data['name'] ?? 'N/A'), 0, 60) . "\n";
    echo "Price:       " . ($data['price'] ?? 'N/A') . "\n";
    echo "Currency:    " . ($data['currency'] ?? 'N/A') . "\n";
    
    $colors = $data['colors'] ?? [];
    echo "Colors:      " . (count($colors) > 0 ? implode(', ', $colors) : 'N/A') . "\n";
    
    $sizes = $data['sizes'] ?? [];
    echo "Sizes:       " . (count($sizes) > 0 ? implode(', ', $sizes) : 'N/A') . "\n";
    
    echo "Description: " . substr(($data['description'] ?? 'N/A'), 0, 60) . "...\n";
    echo "Main Image:  " . (!empty($data['main_image']) ? '✓ YES' : 'N/A') . "\n";
    
    // Enrichment info
    $raw = $data['raw_data'] ?? [];
    $enrichment = $raw['enrichment'] ?? [];
    if (!empty($enrichment)) {
        echo "\nEnrichment Sources:\n";
        foreach ($enrichment as $source) {
            echo "  • $source\n";
        }
    }
    
    echo "\n✓ MOCK TEST SUCCESS\n";
    
} catch (\Exception $e) {
    echo "\n✗ MOCK TEST ERROR: " . $e->getMessage() . "\n";
} finally {
    @unlink($tempFile);
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "Testing completed!\n";
echo str_repeat('=', 80) . "\n";
