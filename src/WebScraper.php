<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use RuntimeException;


class WebScraper
{
    /**
     * @var string The target URL to scrape
     */
    private string $url;

    /**
     * @var string Base URL for resolving relative image paths
     */
    private string $baseUrl;
    
    /**
     * @var string The HTML content fetched from the URL
     */
    private string $html;
    
    /**
     * @var DOMDocument DOM document instance for HTML parsing
     */
    private DOMDocument $dom;
    
    /**
     * @var DOMXPath XPath instance for querying HTML elements
     */
    private DOMXPath $xpath;
    
    /**
     * @var array The scraped product data
     */
    private array $productData;
    
    /**
     * @var array Custom selectors for flexible scraping
     */
    private array $selectors;
    
    /**
     * @var bool Enable/disable caching
     */
    private bool $cacheEnabled;
    
    /**
     * @var string Cache directory path
     */
    private string $cacheDir;
    
    /**
     * @var int Cache time-to-live in seconds
     */
    private int $cacheTTL;
    
    /**
     * @var array Scraping errors
     */
    private array $errors;
    
    /**
     * Constructor
     * 
     * @param array $config Optional configuration settings
     */
    public function __construct(array $config = [])
    {
        $this->dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $this->errors = [];
        $this->productData = [];
        $this->baseUrl = '';
        
        // Set default selectors (can be customized)
        $this->selectors = $config['selectors'] ?? [
            'name' => 'div h1.-fs20, .title--wrap--UUHae_g h1',
            'price' => 'div span.-prxs, .price-default--current--F8OlYIo, .ux-textspans',
            'description' => '.markup.-mhm.-pvl.-oxa.-sc, .description--wrap--LscZ0He',
            'colors' => '.itm-sel',
            'sizes' => '.vl',
            'images' => 'img.product-image, .product-gallery img, [data-image]'
        ];
        
        // Cache configuration
        $this->cacheEnabled = $config['cache_enabled'] ?? false;
        $this->cacheDir = $config['cache_dir'] ?? __DIR__ . '/cache/scraper/';
        $this->cacheTTL = $config['cache_ttl'] ?? 3600;
        
        // Initialize cache directory if needed
        if ($this->cacheEnabled && !is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Set the URL to scrape
     * 
     * @param string $url The target URL
     * @return self Returns instance for method chaining
     * @throws InvalidArgumentException If URL is invalid
     */
    public function setUrl(string $url): self
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL provided: $url");
        }
        
        $this->url = $url;
        $this->baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);

        return $this;
    }
    
    /**
     * Set custom selectors for scraping
     * 
     * @param array $selectors Associative array of CSS selectors (use "name" for the product title; "title" is an alias and maps to "name")
     * @return self Returns instance for method chaining
     */
    public function setSelectors(array $selectors): self
    {
        foreach ($selectors as $key => $value) {
            if (isset($this->selectors[$key])) {
                // Append to existing selector with OR condition
                $this->selectors[$key] .= ', ' . $value;
            } else {
                $this->selectors[$key] = $value;
            }
        }

        return $this;
    }
    
    /**
     * Fetch HTML content from the URL
     * 
     * @param bool $forceRefresh Force refresh ignoring cache
     * @return self Returns instance for method chaining
     * @throws RuntimeException If fetching fails
     */
    public function fetch(bool $forceRefresh = false): self
    {
        try {
            // Check cache first
            if (!$forceRefresh && $this->cacheEnabled) {
                $cachedData = $this->getFromCache();
                if ($cachedData) {
                    $this->productData = $cachedData;
                    return $this;
                }
            }
            
            // Initialize cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10
            ]);
            
            $this->html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            unset($ch);
            
            // Handle errors
            if ($curlError) {
                throw new RuntimeException("cURL Error: " . $curlError);
            }
            
            if ($httpCode !== 200) {
                throw new RuntimeException("HTTP Error: $httpCode - Failed to fetch $this->url");
            }
            
            if (empty($this->html)) {
                throw new RuntimeException("No content received from $this->url");
            }
            
            // Load HTML
            $this->dom->loadHTML(mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
            $this->xpath = new DOMXPath($this->dom);
            
            // Scrape the content
            $this->scrape();
            
            // Save to cache
            if ($this->cacheEnabled) {
                $this->saveToCache();
            }
            
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            throw new RuntimeException("Failed to fetch content: " . $e->getMessage());
        }
        
        return $this;
    }
    
    /**
     * Scrape product information from the HTML
     * 
     * @return self Returns instance for method chaining
     */
    private function scrape(): self
    {
        $this->productData = [
            'name' => $this->extractName(),
            'price' => $this->extractPrice(),
            'description' => $this->extractDescription(),
            'colors' => $this->extractColors(),
            'sizes' => $this->extractSizes(),
            'images' => $this->extractImages(),
            'main_image' => '',
            'url' => $this->url,
            'scraped_at' => date('Y-m-d H:i:s'),
            'raw_data' => []
        ];
        
        // Set main image as first image if available
        if (!empty($this->productData['images'])) {
            $this->productData['main_image'] = $this->productData['images'][0];
        }
        
        $this->applyOpenGraphTitleFallback();
        
        return $this;
    }
    
    /**
     * When CSS selectors find no title, use Open Graph or Twitter card title if present.
     */
    private function applyOpenGraphTitleFallback(): void
    {
        if (empty($this->productData['name'])) {
            $name = $this->getMetaByProperty('og:title') 
                ?? $this->getMetaByProperty('twitter:title');

            if (!empty($name)) {
                $this->productData['name'] = $this->decodeHtmlText($name);
            }
        }

        if (empty($this->productData['description'])) {
            $description = $this->getMetaByProperty('og:description') 
                ?? $this->getMetaByProperty('twitter:description');

            if (!empty($description)) {
                $this->productData['description'] = $this->decodeHtmlText($description);
            }
        }

    }
    
    private function getMetaByProperty(string $property): string
    {
        $n = $this->xpath->query("//meta[translate(@property,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='" . strtolower($property) . "']/@content");
        if ($n && $n->length > 0) {
            $v = trim($n->item(0)->nodeValue ?? '');
            return $v;
        }
        
        return '';
    }
    
    private function decodeHtmlText(string $s): string
    {
        return trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Extract product name from HTML
     * 
     * @return string Product name or empty string if not found
     */
    private function extractName(): string
    {
        $name = $this->extractText($this->selectors['name']);
        return $name ? htmlspecialchars_decode(trim($name)) : '';
    }
    
    /**
     * Extract product price from HTML
     * 
     * @return string Sanitized price or empty string if not found
     */
    private function extractPrice(): string
    {
        $price = $this->extractText($this->selectors['price']);
        
        if (!$price) {
            return '';
        }
        
        // Remove currency symbols and non-numeric characters except decimal points and commas
        $price = preg_replace('/[^0-9.,]/', '', $price);
        
        // Handle Nigerian Naira format (₦ 7,200)
        // Check if it has comma as thousand separator
        if (strpos($price, ',') !== false && strpos($price, '.') === false) {
            // Remove commas (thousand separators)
            $price = str_replace(',', '', $price);
        } 
        // Handle format like "7,200.50"
        else if (strpos($price, ',') !== false && strpos($price, '.') !== false) {
            // Remove commas (thousand separators) but keep decimal point
            $price = str_replace(',', '', $price);
        }
        // Handle format like "7.200" (dot as thousand separator - European format)
        else if (preg_match('/^\d+\.\d{3}$/', $price)) {
            // This is likely thousand separator, not decimal
            $price = str_replace('.', '', $price);
        }
        // Handle format with decimal like "7200.50"
        else if (strpos($price, '.') !== false) {
            // Keep as is, but ensure 2 decimal places
            $parts = explode('.', $price);
            if (count($parts) == 2) {
                $price = $parts[0] . '.' . str_pad($parts[1], 2, '0');
            }
        }
        
        // Add .00 if no decimal part
        if (is_numeric($price) && strpos($price, '.') === false) {
            $price = $price . '.00';
        }
        
        return trim($price);
    }
    
    /**
     * Extract product description from HTML
     * 
     * @return string Cleaned description or empty string if not found
     */
    private function extractDescription(): string
    {
        $description = $this->extractText($this->selectors['description']);
        
        if (!$description) {
            return '';
        }
        
        // Clean the description
        $description = strip_tags($description);
        $description = html_entity_decode($description);
        $description = preg_replace('/\s+/', ' ', $description);
        
        return trim($description);
    }
    
    /**
     * Extract product colors from HTML
     * 
     * @return array List of colors or empty array if not found
     */
    private function extractColors(): array
    {
        $colors = $this->extractArray($this->selectors['colors']);
        
        // Also try to extract from common attributes
        if (empty($colors)) {
            $colors = $this->extractColorsFromAttributes();
        }
        
        return array_unique(array_filter(array_map('trim', $colors)));
    }
    
    /**
     * Extract product sizes from HTML
     * 
     * @return array List of sizes or empty array if not found
     */
    private function extractSizes(): array
    {
        $sizes = $this->extractArray($this->selectors['sizes']);
        
        // Also try to extract from common attributes
        if (empty($sizes)) {
            $sizes = $this->extractSizesFromAttributes();
        }
        
        return array_unique(array_filter(array_map('trim', $sizes)));
    }

    /**
     * Extract product images from HTML
     * 
     * @return array List of image URLs
     */
    private function extractImages(): array
    {
        $images = [];
        $images = array_merge($images, $this->extractImageElements($this->selectors['images']));
        $images = array_merge($images, $this->extractImagesFromJsonLd());
        
        if ($images === []) {
            $images = array_merge(
                $images,
                $this->extractImageElements('img[class*="product"], img[class*="gallery"], img[class*="main"]')
            );
        }
        
        if ($images === []) {
            $ogImage = $this->extractMetaImage();
            if ($ogImage) {
                $images[] = $ogImage;
            }
        }
        
        $images = array_values(array_filter($images, static fn ($u): bool => is_string($u) && $u !== ''));
        $images = array_map([$this, 'resolveUrl'], $images);
        $images = array_values(array_unique($images));
        
        return $images;
    }
    
    /**
     * Extract image elements using CSS selector
     * 
     * @param string $selector CSS selector
     * @return array List of image URLs
     */
    private function extractImageElements(string $selector): array
    {
        $images = [];
        $selectorList = array_map('trim', explode(',', $selector));
        foreach ($selectorList as $sel) {
            if ($sel === '') {
                continue;
            }
            $xpathQuery = $this->cssToXPath($sel);
            $nodes = $this->xpath->query($xpathQuery);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    if (!$node instanceof \DOMElement) {
                        continue;
                    }
                    $images = array_merge($images, $this->collectImageUrlsFromElement($node));
                }
            }
        }
        
        return $images;
    }
    
    /**
     * @return list<string>
     */
    private function collectImageUrlsFromElement(\DOMElement $node): array
    {
        $urls = [];
        foreach (['src', 'data-src', 'data-lazy', 'data-lazy-src', 'data-lazyload', 'data-original'] as $attr) {
            $v = $node->getAttribute($attr);
            if ($v !== '') {
                $urls[] = $v;
            }
        }
        $srcset = $node->getAttribute('srcset');
        if ($srcset !== '') {
            $urls = array_merge($urls, $this->parseSrcsetToUrls($srcset));
        }
        
        return $urls;
    }
    
    /**
     * @return list<string>
     */
    private function parseSrcsetToUrls(string $srcset): array
    {
        $out = [];
        if ($srcset === '') {
            return $out;
        }
        $candidates = preg_split('/\s*,\s*/', $srcset) ?: [];
        foreach ($candidates as $cand) {
            $cand = trim($cand);
            if ($cand === '') {
                continue;
            }
            $parts = preg_split('/\s+/', $cand, 2, PREG_SPLIT_NO_EMPTY);
            if ($parts === false || $parts === []) {
                continue;
            }
            $url = $parts[0] ?? '';
            if ($url === '') {
                continue;
            }
            if (str_starts_with($url, '//')) {
                $url = 'https:' . $url;
            }
            $out[] = $url;
        }
        
        return $out;
    }
    
    /**
     * @return list<string>
     */
    private function extractImagesFromJsonLd(): array
    {
        $scripts = $this->xpath->query('//script[@type="application/ld+json"]');
        if ($scripts === false || $scripts->length === 0) {
            return [];
        }
        $all = [];
        for ($i = 0; $i < $scripts->length; $i++) {
            $item = $scripts->item($i);
            if ($item === null) {
                continue;
            }
            $text = trim($item->textContent ?? '');
            if ($text === '') {
                continue;
            }
            $data = json_decode($text, true);
            if (!is_array($data)) {
                continue;
            }
            $all = array_merge($all, $this->collectProductImagesFromJsonLd($data));
        }
        
        return $all;
    }
    
    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private function collectProductImagesFromJsonLd(array $data): array
    {
        $images = [];
        if ($this->jsonLdTypeIs($data, 'Product') && !empty($data['image'])) {
            $images = array_merge($images, $this->normalizeJsonLdImageValue($data['image']));
        }
        foreach ($data as $v) {
            if (is_array($v)) {
                $images = array_merge($images, $this->collectProductImagesFromJsonLd($v));
            }
        }
        
        return $images;
    }
    
    /**
     * @param array<string, mixed> $data
     */
    private function jsonLdTypeIs(array $data, string $type): bool
    {
        if (!isset($data['@type'])) {
            return false;
        }
        $t = $data['@type'];
        if (is_string($t)) {
            return $t === $type;
        }
        if (is_array($t)) {
            return in_array($type, $t, true);
        }
        
        return false;
    }
    
    /**
     * @return list<string>
     */
    private function normalizeJsonLdImageValue(mixed $image): array
    {
        if (is_string($image)) {
            if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                return [$image];
            }
            return [];
        }
        if (is_int($image) || is_float($image)) {
            return [];
        }
        if (!is_array($image)) {
            return [];
        }
        if (array_is_list($image)) {
            $out = [];
            foreach ($image as $it) {
                $out = array_merge($out, $this->normalizeJsonLdImageValue($it));
            }
            return $out;
        }
        if ($this->jsonLdTypeIs($image, 'ImageObject') || isset($image['contentUrl']) || isset($image['url'])) {
            if (isset($image['contentUrl'])) {
                return $this->normalizeJsonLdImageValue($image['contentUrl']);
            }
            if (isset($image['url'])) {
                return $this->normalizeJsonLdImageValue($image['url']);
            }
            if (isset($image['thumbnailUrl'])) {
                return $this->normalizeJsonLdImageValue($image['thumbnailUrl']);
            }
        }
    
        return [];
    }
    
    /**
     * Extract image from Open Graph meta tag
     * 
     * @return string|null Image URL or null if not found
     */
    private function extractMetaImage(): ?string
    {
        $metaNodes = $this->xpath->query("//meta[@property='og:image']/@content");
        if ($metaNodes && $metaNodes->length > 0) {
            return $metaNodes->item(0)->nodeValue;
        }
        
        return null;
    }

    /**
     * Resolve relative URL to absolute
     * 
     * @param string $url URL to resolve
     * @return string Absolute URL
     */
    private function resolveUrl(string $url): string
    {
        // Already absolute URL
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }
        
        // Relative URL starting with /
        if (strpos($url, '/') === 0) {
            return $this->baseUrl . $url;
        }
        
        // Relative URL
        return $this->baseUrl . '/' . ltrim($url, '/');
    }
    
    /**
     * Extract colors from HTML attributes
     * 
     * @return array List of colors found in attributes
     */
    private function extractColorsFromAttributes(): array
    {
        $colors = [];
        
        // Try to extract from data-color attributes
        $colorAttrs = $this->extractArrayFromAttribute('//*[@data-color]/@data-color');
        $colors = array_merge($colors, $colorAttrs);
        
        // Try to extract from color classes
        $colorClasses = $this->extractArray('//*[contains(@class, "color")]/text()');
        $colors = array_merge($colors, $colorClasses);
        
        return $colors;
    }
    
    /**
     * Extract sizes from HTML attributes
     * 
     * @return array List of sizes found in attributes
     */
    private function extractSizesFromAttributes(): array
    {
        $sizes = [];
        
        // Try to extract from data-size attributes
        $sizeAttrs = $this->extractArrayFromAttribute('//*[@data-size]/@data-size');
        $sizes = array_merge($sizes, $sizeAttrs);
        
        // Try to extract from size classes
        $sizeClasses = $this->extractArray('//*[contains(@class, "size")]/text()');
        $sizes = array_merge($sizes, $sizeClasses);
        
        return $sizes;
    }
    
    /**
     * Extract text content using CSS selector
     * 
     * @param string $selector CSS selector(s) separated by commas
     * @param DOMNode|null $context Optional context node
     * @return string Extracted text or empty string
     */
    private function extractText(string $selector, ?DOMNode $context = null): string
    {
        $selectors = explode(',', $selector);
        
        foreach ($selectors as $sel) {
            $xpathQuery = $this->cssToXPath(trim($sel));
            $nodes = $this->xpath->query($xpathQuery, $context);
            
            if ($nodes && $nodes->length > 0) {
                $text = trim($nodes->item(0)->textContent);
                if (!empty($text)) {
                    return $text;
                }
            }
        }
        
        return '';
    }
    
    /**
     * Extract array of text content using CSS selector
     * 
     * @param string $selector CSS selector(s) separated by commas
     * @param DOMNode|null $context Optional context node
     * @return array Array of extracted texts
     */
    private function extractArray(string $selector, ?DOMNode $context = null): array
    {
        $selectors = explode(',', $selector);
        $results = [];
        
        foreach ($selectors as $sel) {
            $xpathQuery = $this->cssToXPath(trim($sel));
            $nodes = $this->xpath->query($xpathQuery, $context);
            
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if (!empty($text)) {
                        $results[] = $text;
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Extract array of attribute values using CSS selector
     * 
     * @param string $xpathQuery XPath query
     * @param DOMNode|null $context Optional context node
     * @return array Array of extracted attribute values
     */
    private function extractArrayFromAttribute(string $xpathQuery, ?DOMNode $context = null): array
    {
        $nodes = $this->xpath->query($xpathQuery, $context);
        $results = [];
        
        if ($nodes && $nodes->length > 0) {
            foreach ($nodes as $node) {
                $value = trim($node->nodeValue);
                if (!empty($value)) {
                    $results[] = $value;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Convert CSS selector to XPath query
     * 
     * @param string $selector CSS selector
     * @return string XPath query
     */
    private function cssToXPath(string $selector): string
    {
        $selector = $this->normalizeCssSelectorQuotes(trim($selector));
        
        // Split by descendant combinator spaces
        $parts = preg_split('/\s+/', $selector, -1, PREG_SPLIT_NO_EMPTY);
        $xpath = '';
        
        foreach ($parts as $index => $part) {
            $converted = $this->convertSimpleSelector($part);
            
            if ($index === 0) {
                // Always search descendants from document/context root.
                $xpath = '//' . $converted;
            } else {
                // Descendant: add //
                $xpath .= '//' . $converted;
            }
        }
        
        // If no spaces, just return the converted selector
        if (empty($xpath)) {
            $xpath = '//' . $this->convertSimpleSelector($selector);
        }
        
        return $xpath;
    }

    /**
     * Convert simple CSS selector to XPath
     * 
     * @param string $selector Simple CSS selector (no spaces)
     * @return string XPath expression
     */
    private function convertSimpleSelector(string $selector): string
    {
        // Element with class(es): div.class or div.class1.class2
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)(\.[a-zA-Z_-][a-zA-Z0-9_-]*)+$/', $selector, $matches)) {
            $tag = $matches[1];
            $classPart = substr($selector, strlen($tag)); // .class1.class2
            $classes = array_filter(explode('.', ltrim($classPart, '.')));
            $conditions = [];

            foreach ($classes as $className) {
                $conditions[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$className} ')";
            }

            return $tag . '[' . implode(' and ', $conditions) . ']';
        }
        
        // Chained classes only: .a.b, .markup.-mhm.-pvl.-oxa.-sc (no tag name)
        if (str_starts_with($selector, '.') && substr_count($selector, '.') > 1) {
            $tokens = array_values(array_filter(
                explode('.', $selector),
                static fn (string $t): bool => $t !== ''
            ));
            if (count($tokens) >= 2) {
                $conditions = [];
                foreach ($tokens as $className) {
                    $conditions[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$className} ')";
                }

                return '*[' . implode(' and ', $conditions) . ']';
            }
        }
        
        // Just class: .class
        if (preg_match('/^\.([a-zA-Z_-][a-zA-Z0-9_-]*)$/', $selector, $matches)) {
            return "*[contains(concat(' ', @class, ' '), ' {$matches[1]} ')]";
        }
        
        // Element with ID: div#id
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)#([a-zA-Z][a-zA-Z0-9_-]*)$/', $selector, $matches)) {
            return "{$matches[1]}[@id='{$matches[2]}']";
        }
        
        // Just ID: #id
        if (preg_match('/^#([a-zA-Z][a-zA-Z0-9_-]*)$/', $selector, $matches)) {
            return "*[@id='{$matches[1]}']";
        }
        
        // Element with attribute: h1[data-pl="x"], a[href="..."], h1[title] (one [...] only)
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\[([A-Za-z_][A-Za-z0-9_:\-]*)\]$/u', $selector, $m)) {
            return "{$m[1]}[@{$m[2]}]";
        }
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\[([A-Za-z_][A-Za-z0-9_:\-]*)="((?:\\\\.|[^"\\\\])*)"\]$/u', $selector, $m)) {
            $v = $this->escapeXpathStringLiteral($m[3]);

            return "{$m[1]}[@{$m[2]}={$v}]";
        }
        if (preg_match("/^([a-zA-Z][a-zA-Z0-9]*)\[([A-Za-z_][A-Za-z0-9_:\-]*)='((?:\\\\'|[^'])*)'\]$/u", $selector, $m)) {
            $v = $this->escapeXpathStringLiteral(stripslashes($m[3]));

            return "{$m[1]}[@{$m[2]}={$v}]";
        }
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\[([A-Za-z_][A-Za-z0-9_:\-]*)=([^]\s\]]+)\]$/u', $selector, $m)) {
            $v = $this->escapeXpathStringLiteral($m[3]);

            return "{$m[1]}[@{$m[2]}={$v}]";
        }
        
        // Just element: div
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $selector)) {
            return $selector;
        }
        
        // Attribute selectors: [attr] or [attr=value]
        if (preg_match('/^\[([a-zA-Z_][A-Za-z0-9_:\-]*)(?:=([\'"]?)([^\'\"]+)\2)?\]$/u', $selector, $matches)) {
            $attr = $matches[1];
            if (isset($matches[3]) && $matches[3] !== '') {
                $v = $this->escapeXpathStringLiteral($matches[3]);

                return "*[@{$attr}={$v}]";
            }
            return "*[@{$attr}]";
        }
        
        // Default: return as is
        return "*[contains(concat(' ', @class, ' '), ' {$selector} ')]";
    }
    
    /**
     * Escape a value for XPath string literals in predicates.
     */
    private function escapeXpathStringLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
    
    /**
     * Replace Unicode “smart” quotes with ASCII so attribute selectors parse reliably.
     */
    private function normalizeCssSelectorQuotes(string $selector): string
    {
        return str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", "\u{00AB}", "\u{00BB}"],
            ['"', '"', "'", "'", '"', '"'],
            $selector
        );
    }
    
    /**
     * Get cache key for current URL
     * 
     * @return string Cache key
     */
    private function getCacheKey(): string
    {
        return md5($this->url);
    }
    
    /**
     * Get cached data
     * 
     * @return array|null Cached data or null if expired/not found
     */
    private function getFromCache(): ?array
    {
        $cacheFile = $this->cacheDir . $this->getCacheKey() . '.json';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTTL) {
            $data = file_get_contents($cacheFile);
            return json_decode($data, true);
        }
        
        return null;
    }
    
    /**
     * Save data to cache
     * 
     * @return bool True on success, false on failure
     */
    private function saveToCache(): bool
    {
        $cacheFile = $this->cacheDir . $this->getCacheKey() . '.json';

        return file_put_contents(
            $cacheFile, 
            json_encode($this->productData, JSON_PRETTY_PRINT)
        ) !== false;
    }
    
    /**
     * Get the scraped product data as array
     * 
     * @return array Associative array containing product information
     */
    public function getData(): array
    {
        return $this->productData;
    }
    
    /**
     * Get specific field from scraped data
     * 
     * @param string $field Field name (name, price, description, colors, sizes)
     * @return mixed Field value or null if not found
     */
    public function getField(string $field)
    {
        return $this->productData[$field] ?? null;
    }
    
    /**
     * Get product name
     * 
     * @return string Product name
     */
    public function getName(): string
    {
        return $this->productData['name'] ?? '';
    }
    
    /**
     * Get product price
     * 
     * @return string Product price
     */
    public function getPrice(): string
    {
        return $this->productData['price'] ?? '';
    }
    
    /**
     * Get product description
     * 
     * @return string Product description
     */
    public function getDescription(): string
    {
        return $this->productData['description'] ?? '';
    }
    
    /**
     * Get product colors
     * 
     * @return array List of colors
     */
    public function getColors(): array
    {
        return $this->productData['colors'] ?? [];
    }
    
    /**
     * Get product sizes
     * 
     * @return array List of sizes
     */
    public function getSizes(): array
    {
        return $this->productData['sizes'] ?? [];
    }

    /**
     * Get product images
     * 
     * @return array List of image URLs
     */
    public function getImages(): array
    {
        return $this->productData['images'] ?? [];
    }
    
    /**
     * Get main product image
     * 
     * @return string Main image URL
     */
    public function getMainImage(): string
    {
        return $this->productData['main_image'] ?? '';
    }
    
    /**
     * Get scraped data as JSON string
     * 
     * @param bool $prettyPrint Pretty print JSON
     * @return string JSON encoded data
     */
    public function toJson(bool $prettyPrint = true): string
    {
        $flags = $prettyPrint ? JSON_PRETTY_PRINT : 0;
        return json_encode($this->productData, $flags);
    }
    
    /**
     * Get scraping errors
     * 
     * @return array List of errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Clear cache for current URL
     * 
     * @return bool True on success
     */
    public function clearCache(): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        $cacheFile = $this->cacheDir . $this->getCacheKey() . '.json';
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Clear all cache files
     * 
     * @return bool True on success
     */
    public function clearAllCache(): bool
    {
        if (!$this->cacheEnabled || !is_dir($this->cacheDir)) {
            return false;
        }
        
        $files = glob($this->cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Destructor - Clean up
     */
    public function __destruct()
    {
        libxml_use_internal_errors(false);
    }
}
