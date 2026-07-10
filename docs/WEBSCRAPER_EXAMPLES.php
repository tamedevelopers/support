#!/usr/bin/env php
<?php
/**
 * WebScraper Advanced Usage Examples
 * 
 * Demonstrates all the enhanced extraction capabilities
 * for name, description, price, currency, colors, and sizes
 */

use Tamedevelopers\Support\WebScraper;

// Example 1: Basic usage with auto-detection
// The scraper will automatically try multiple extraction methods
$scraper = WebScraper::url('https://www.aliexpress.com/item/1005010595606666.html')
    ->fetch();

// Get all data at once
$data = $scraper->getData();

echo "Product: " . $data['name'] . "\n";
echo "Price: " . $data['price'] . " " . $data['currency'] . "\n";
echo "Colors: " . implode(', ', $data['colors']) . "\n";
echo "Sizes: " . implode(', ', $data['sizes']) . "\n";

// Example 2: Access specific fields
echo "\n--- Individual Fields ---\n";
echo "Name: " . $scraper->getName() . "\n";
echo "Price: " . $scraper->getPrice() . "\n";
echo "Currency: " . $scraper->getCurrency() . "\n";
echo "Description: " . substr($scraper->getDescription(), 0, 100) . "...\n";
echo "Colors: " . count($scraper->getColors()) . " available\n";
echo "Sizes: " . count($scraper->getSizes()) . " available\n";
echo "Images: " . count($scraper->getImages()) . " total\n";
echo "Main Image: " . $scraper->getMainImage() . "\n";

// Example 3: Custom selectors for specific websites
$customScraper = WebScraper::url('https://www.ebay.com/itm/386936766515')
    ->setSelectors([
        'name' => 'h1.it-title, h1[data-testid="title"]',
        'price' => 'span.vi-VR-cvipPrice, div[data-testid="ds_div"] span',
        'colors' => 'span[aria-label*="Color"], div[data-color]',
        'sizes' => 'span[aria-label*="Size"], div[data-size]',
        'description' => 'div.ds_div, span.vi_acc_del_range',
    ])
    ->fetch();

echo "\n--- eBay Custom Extraction ---\n";
echo "Data: " . json_encode($customScraper->getData(), JSON_PRETTY_PRINT) . "\n";

// Example 4: Using specific engine (DOM or Chromium)
// DOM engine: Fast, works with static HTML (default)
// Chromium engine: Slower but handles JavaScript-rendered content
$scraperWithDom = WebScraper::url('https://www.jumia.com.ng/ashion-kq-mens-stylish-grey-silver-turf-football-shoes-durable-training-sports-footwear-for-match-casual-419139185.html')
    ->setEngine('dom')
    ->fetch();

echo "\n--- Fetch Engine Used ---\n";
echo "Engine: " . $scraperWithDom->getLastFetchEngine() . "\n";
echo "Final URL: " . $scraperWithDom->getLastFetchFinalUrl() . "\n";
echo "HTTP Status: " . $scraperWithDom->getLastFetchHttpStatus() . "\n";

// Example 5: Caching for repeated requests
$scraperWithCache = new WebScraper([
    'cache_enabled' => true,
    'cache_dir' => __DIR__ . '/cache/',
    'cache_ttl' => 3600, // 1 hour
]);

$scraperWithCache->setUrl('https://www.aliexpress.com/item/1005010595606666.html')
    ->fetch(); // First call - fetches from internet

// Subsequent calls within 1 hour will use cache
$scraperWithCache->fetch();

// To force refresh:
$scraperWithCache->fetch(true); // forceRefresh = true

// Example 6: Handling blocked/challenge pages
$scraper = WebScraper::url('https://www.protected-site.example')
    ->fetch();

$data = $scraper->getData();
$rawData = $data['raw_data'];

if ($rawData['blocked'] ?? false) {
    echo "\n--- Site Detection ---\n";
    echo "Status: BLOCKED\n";
    echo "Reason: " . $rawData['block_reason'] . "\n";
    echo "Signals:\n";
    foreach ($rawData['block_signals'] as $signal) {
        echo "  - $signal\n";
    }
} else {
    echo "Status: OK\n";
}

// Example 7: Enrichment information
echo "\n--- Data Enrichment ---\n";
$enrichment = $data['raw_data']['enrichment'] ?? [];
if (!empty($enrichment)) {
    echo "Data was enriched from these sources:\n";
    foreach ($enrichment as $source) {
        echo "  • $source\n";
    }
}

// Example 8: JSON output
echo "\n--- JSON Export ---\n";
echo $scraper->toJson(true); // Pretty print

// Example 9: Getting errors
$errors = $scraper->getErrors();
if (!empty($errors)) {
    echo "\n--- Scraping Errors ---\n";
    foreach ($errors as $error) {
        echo "Error: $error\n";
    }
}

// Example 10: Data source extraction priority
/*
 * The scraper uses this priority for colors and sizes:
 * 1. CSS Selectors (fastest)
 * 2. JSON-LD structured data
 * 3. HTML5 Microdata
 * 4. HTML Attributes (data-*, ARIA, title)
 * 5. Embedded JSON in page
 * 
 * For name and description:
 * 1. CSS Selectors
 * 2. JSON-LD
 * 3. Open Graph meta tags
 * 
 * For price and currency:
 * 1. CSS Selectors
 * 2. JSON-LD offers
 * 3. Microdata with itemprop
 * 4. Open Graph meta tags
 * 5. Embedded JSON patterns
 */

// Example 11: Advanced - Direct method access
$scraper = WebScraper::setUrl('https://example.com/product')
    ->fetch();

// Get specific fields
$colors = $scraper->getColors();      // Array of color options
$sizes = $scraper->getSizes();        // Array of size options
$images = $scraper->getImages();      // Array of image URLs
$field = $scraper->getField('price'); // Get any field by name

// Example 12: Cache management
$scraper->clearCache();      // Clear cache for current URL
$scraper->clearAllCache();   // Clear all cache files

echo "\n✓ All examples completed successfully!\n";
