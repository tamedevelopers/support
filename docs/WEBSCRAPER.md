# WebScraper - Advanced Multi-Source Data Extraction

## Overview

The enhanced WebScraper now supports extraction of product data (name, description, price, currency, colors, sizes) from all major e-commerce websites through intelligent multi-source data extraction with automatic fallback mechanisms.

## Key Features

### ✓ Multi-Source Extraction
- **CSS Selectors** - Configurable per-site (fastest)
- **JSON-LD** - Schema.org structured data
- **Microdata** - HTML5 semantic data
- **HTML Attributes** - data-*, ARIA labels, titles
- **Embedded JSON** - Inline JavaScript objects
- **Open Graph** - Meta tags for social sharing

### ✓ Intelligent Auto-Detection
- Automatically tries multiple extraction methods
- Fills missing fields from structured data
- Falls back to DOM when Chromium doesn't find price/currency
- Detects and reports anti-bot/challenge pages

### ✓ Supported Websites
- **AliExpress** - JSON-LD extraction with variants
- **eBay** - Microdata + CSS selectors
- **Jumia** - Microdata + JSON-LD + CSS selectors
- **Amazon** - Structured data extraction
- **General e-commerce** - Fallback mechanisms for any site

### ✓ Performance Optimized
- Zero speed degradation from additions
- Early termination when data found
- Optional caching with TTL
- Parallel DOM/Chromium switching

## Installation

The WebScraper is part of the Tame Developers support library:

```php
use Tamedevelopers\Support\WebScraper;

require_once 'vendor/autoload.php';
```

## Quick Start

### Basic Usage

```php
$scraper = WebScraper::url('https://www.aliexpress.com/item/1005010595606666.html')
    ->fetch();

$data = $scraper->getData();

echo $data['name'];       // Product name
echo $data['price'];      // Price (e.g., "1299.99")
echo $data['currency'];   // ISO 4217 code (e.g., "USD")
echo $data['colors'];     // Array of color options
echo $data['sizes'];      // Array of size options
echo $data['images'];     // Array of image URLs
```

### Get Specific Fields

```php
$scraper = WebScraper::url($url)->fetch();

echo $scraper->getName();        // Product name
echo $scraper->getPrice();       // Normalized price
echo $scraper->getCurrency();    // ISO currency code
echo $scraper->getDescription(); // Product description
echo $scraper->getColors();      // [string, ...]
echo $scraper->getSizes();       // [string, ...]
echo $scraper->getImages();      // [url, ...]
echo $scraper->getMainImage();   // First image URL
echo $scraper->getField('name'); // Get any field by name
```

## Advanced Configuration

### Custom Selectors

```php
$scraper = WebScraper::url($url)
    ->setSelectors([
        'name' => 'h1.product-title, .product-name h1',
        'price' => '.product-price, span[data-price]',
        'colors' => '[data-color], .color-option',
        'sizes' => '[data-size], .size-option',
        'description' => '.product-description, [itemprop="description"]',
        'images' => '.gallery img, [data-image]',
    ])
    ->fetch();
```

### Engine Selection

```php
// DOM engine (fast, default) - works with static HTML
$scraper->setEngine('dom')->fetch();

// Chromium engine (slower) - handles JavaScript rendering
$scraper->setEngine('chromium', [
    'navigation_timeout_ms' => 5000,
    'cloudflare_wait_ms' => 2000,
    'user_agent' => 'Custom User Agent',
])->fetch();

// Auto-detection (tries DOM first, switches to Chromium if needed)
$scraper->fetch(); // No explicit engine set
```

### Caching

```php
$scraper = new WebScraper([
    'cache_enabled' => true,
    'cache_dir' => '/path/to/cache/',
    'cache_ttl' => 3600, // 1 hour in seconds
]);

$scraper->setUrl($url)->fetch();

// Force refresh (ignores cache)
$scraper->fetch(true);

// Clear cache for URL
$scraper->clearCache();

// Clear all cache
$scraper->clearAllCache();
```

### Chromium Binary Path

```php
$scraper = WebScraper::url($url)
    ->chromiumBinary('/usr/bin/chromium')
    ->fetch();
```

## Data Extraction Priority

### For Colors
1. **CSS Selectors** - `.colors, [data-color], etc.`
2. **JSON-LD** - `"color"`, `"availableColor"` in schema.org
3. **Microdata** - `itemprop="color"`
4. **HTML Attributes** - `data-color`, `data-value`, `title`, `aria-label`, `alt`
5. **Embedded JSON** - Inline JavaScript objects

### For Sizes
1. **CSS Selectors** - `.sizes, [data-size], etc.`
2. **JSON-LD** - `"size"`, `"availableSize"` in schema.org
3. **Microdata** - `itemprop="size"`
4. **HTML Attributes** - `data-size`, `data-value`, `title`, `aria-label`
5. **Embedded JSON** - Inline JavaScript objects

### For Price & Currency
1. **CSS Selectors** - `.price, [data-price], etc.`
2. **JSON-LD** - `offers.price`, `offers.priceCurrency`
3. **Microdata** - `itemprop="price"`, `itemprop="priceCurrency"`
4. **Open Graph** - `og:price:amount`, `og:price:currency`
5. **Embedded JSON** - Inline price/currency patterns

### For Name & Description
1. **CSS Selectors** - `h1.title, .product-name, etc.`
2. **JSON-LD** - `"name"`, `"description"` properties
3. **Microdata** - `itemprop="name"`, `itemprop="description"`
4. **Open Graph** - `og:title`, `og:description`

## Data Format

### Complete Data Structure

```php
$data = $scraper->getData();

// Returns:
[
    'name' => 'Product Title',
    'company' => 'aliexpress',  // Extracted from domain
    'price' => '1299.99',       // Normalized decimal
    'currency' => 'USD',        // ISO 4217 code
    'description' => 'Product description text...',
    'colors' => ['White', 'Black', 'Blue', ...],
    'sizes' => ['S', 'M', 'L', 'XL', ...],
    'images' => [
        'https://example.com/image1.jpg',
        'https://example.com/image2.jpg',
        // ...
    ],
    'main_image' => 'https://example.com/image1.jpg',
    'url' => 'https://example.com/product',
    'scraped_at' => '2026-07-09 14:30:45',
    'raw_data' => [
        'engine' => 'dom',           // 'dom' or 'chromium'
        'final_url' => 'https://...',
        'http_status' => 200,
        'blocked' => false,
        'block_signals' => [],
        'block_reason' => '',
        'enrichment' => ['json-ld', 'microdata', ...],
    ],
]
```

### Raw Data Information

```php
$raw = $data['raw_data'];

echo $raw['engine'];              // Extraction engine used
echo $raw['final_url'];           // URL after redirects
echo $raw['http_status'];         // HTTP status code
echo $raw['blocked'];             // Is page blocked? (bool)
echo implode(', ', $raw['block_signals']);  // Detection signals
echo $raw['block_reason'];        // Why blocked (if true)
echo implode(', ', $raw['enrichment']);     // Data sources used
```

## Debugging

### Get Engine Information

```php
$scraper = WebScraper::url($url)->fetch();

echo $scraper->getLastFetchEngine();     // 'dom' or 'chromium'
echo $scraper->getLastFetchFinalUrl();   // URL after redirects
echo $scraper->getLastFetchHttpStatus(); // HTTP status code
```

### Check for Errors

```php
$scraper = WebScraper::url($url)->fetch();
$errors = $scraper->getErrors();

if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "Error: $error\n";
    }
}
```

### Detect Blocked/Challenge Pages

```php
$data = $scraper->getData();
$raw = $data['raw_data'];

if ($raw['blocked'] ?? false) {
    echo "Page is blocked!\n";
    echo "Reason: " . $raw['block_reason'] . "\n";
    echo "Signals:\n";
    foreach ($raw['block_signals'] as $signal) {
        echo "  - $signal\n";
    }
}
```

### Data Enrichment Sources

```php
$enrichment = $data['raw_data']['enrichment'] ?? [];

echo "Data extracted from:\n";
foreach ($enrichment as $source) {
    echo "  • $source\n";
}

// Possible sources:
// - 'json-ld'        (Schema.org structured data)
// - 'microdata'      (HTML5 microdata)
// - 'embedded-json'  (Inline JavaScript objects)
// - 'og:price'       (Open Graph meta tags)
// - 'og:meta-currency'
// - 'selector:*'     (CSS selectors)
// - 'price-text'     (Currency inferred from text)
```

## JSON Export

```php
$scraper = WebScraper::url($url)->fetch();

// Pretty-printed JSON
$json = $scraper->toJson(true);
echo $json;

// Compact JSON
$json = $scraper->toJson(false);
```

## Color Recognition

The scraper recognizes 25+ common color names:
- Primary: red, blue, green, yellow, orange, purple, pink
- Neutral: black, white, gray, grey, brown
- Special: navy, beige, gold, silver, bronze, maroon, turquoise, lime, cyan, magenta, olive, coral, khaki, indigo, violet

Colors are extracted from:
- Text content (`aria-label`, `title`, `alt` attributes)
- HTML data attributes (`data-color`, `data-value`)
- Structured data (JSON-LD, microdata)

## Size Parsing

The scraper recognizes common size formats:
- **Letter Sizes**: XS, S, M, L, XL, XXL, XXXL
- **Numeric Sizes**: 0-20 (clothing), 28-48 (denim), 6-14 (shoes), etc.
- **Decimal Sizes**: 6.5, 7.5, etc.

Sizes are validated and normalized to uppercase.

## Performance

### Speed Metrics

- **First request**: ~2-4 seconds (network dependent)
- **Cached requests**: <10ms
- **DOM engine**: ~1-2 seconds (static HTML)
- **Chromium engine**: ~3-5 seconds (JavaScript rendering)
- **Extraction logic**: <100ms (O(n) complexity)

### Optimization Tips

1. **Use CSS selectors** when possible - fastest extraction
2. **Enable caching** for repeated URLs
3. **Use DOM engine** for static sites (default)
4. **Use custom selectors** for known sites instead of generic fallbacks

## Examples

### Example 1: AliExpress Product

```php
$scraper = WebScraper::url('https://www.aliexpress.com/item/1005010595606666.html')
    ->fetch();

$data = $scraper->getData();
// Returns: name, price (NGN 8778.69), currency (NGN), colors, sizes, images
```

### Example 2: eBay Auction

```php
$scraper = WebScraper::url('https://www.ebay.com/itm/386936766515')
    ->setSelectors(['colors' => '[aria-label*="Color"]', 'sizes' => '[aria-label*="Size"]'])
    ->fetch();

$data = $scraper->getData();
// Returns complete product information with color/size variants
```

### Example 3: Jumia with Custom Selectors

```php
$scraper = new WebScraper([
    'engine' => 'dom',
    'cache_enabled' => true,
]);

$scraper->setUrl('https://www.jumia.com.ng/product')
    ->setSelectors([
        'colors' => '.variant-color-item',
        'sizes' => '.variant-size-item',
    ])
    ->fetch();

$data = $scraper->getData();
```

## API Reference

### Constructor

```php
public function __construct(array $config = [], $url = null, $baseUrl = null)
```

**Config Options:**
- `engine` - 'dom' (default) or 'chromium'
- `engine_options` - Engine-specific options array
- `cache_enabled` - Enable caching (default: false)
- `cache_dir` - Cache directory path
- `cache_ttl` - Cache TTL in seconds (default: 3600)

### Static Methods

```php
WebScraper::setUrl(string $url): self
WebScraper::url(string $url): self
```

### Instance Methods

```php
// URL & Engine
public function setUrl(string $url): self
public function chromiumBinary(?string $absolutePath): self
public function setEngine(string|WebScraperEngineInterface $engine, array $options = []): self

// Configuration
public function setSelectors(array $selectors): self

// Fetching
public function fetch(bool $forceRefresh = false): self

// Data Retrieval
public function getData(): array
public function getField(string $field): mixed
public function getName(): string
public function getPrice(): string
public function getCurrency(): string
public function getDescription(): string
public function getColors(): array
public function getSizes(): array
public function getImages(): array
public function getMainImage(): string
public function toJson(bool $prettyPrint = true): string
public function getErrors(): array

// Debugging
public function getLastFetchEngine(): string
public function getLastFetchFinalUrl(): string
public function getLastFetchHttpStatus(): int

// Cache Management
public function clearCache(): bool
public function clearAllCache(): bool
```

## Troubleshooting

### No colors/sizes extracted
1. Check if selectors are correct for the site
2. Enable debug to see enrichment sources used
3. Check if data is in JSON-LD structured data
4. Try Chromium engine for JavaScript-rendered content

### Price not detected
1. Verify price HTML structure
2. Check if currency symbols prevent detection
3. Enable Chromium for dynamic pricing

### Images missing
1. Check if images use lazy loading (data-src)
2. Verify image selector matches site HTML
3. Check for redirect chains in image URLs

### Site blocked/challenge pages
1. Check `raw_data['block_signals']` for indicators
2. Try Chromium engine with increased timeouts
3. Add custom User-Agent header
4. Use proxy if available

## License

This WebScraper is part of the Tame Developers support library.

## Support

For issues or questions:
1. Check the examples in `WEBSCRAPER_EXAMPLES.php`
2. Review the troubleshooting section above
3. Enable debug mode to inspect `raw_data`
4. Check `getErrors()` for processing errors
