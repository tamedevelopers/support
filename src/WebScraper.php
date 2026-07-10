<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use BadMethodCallException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\TameTrait;
use Tamedevelopers\Support\WebScraper\ChromiumWebScraperEngine;
use Tamedevelopers\Support\WebScraper\DomWebScraperEngine;
use Tamedevelopers\Support\WebScraper\WebScraperEngineInterface;

/**
 * **SIMPLIFIED** Product Scraper - Focus only on: IMAGE, DESCRIPTION, PRICE, CURRENCY
 * 
 * Removed over-engineering with colors/sizes. Added:
 * - Cloudflare detection and handling
 * - Proper User-Agent headers
 * - Intelligent timeouts
 * - Image deduplication
 * - Price duplication fixing
 */
class WebScraper
{
    use TameTrait;

    private ?string $chromiumBinary = null;
    private string $url;
    private string $baseUrl;
    private string $html = '';
    private ?DOMDocument $dom = null;
    private DOMXPath $xpath;
    private array $productData = [];
    private array $selectors = [];
    private bool $cacheEnabled = false;
    private string $cacheDir = '';
    private int $cacheTTL = 3600;
    private array $errors = [];

    /** @var array<string, bool>|null */
    private static ?array $currencyCodeSet = null;
    /** @var array<string, string>|null */
    private static ?array $currencySymbolToCode = null;
    /** @var array<string, string>|null */
    private static ?array $currencyNameToCode = null;
    
    private WebScraperEngineInterface $engine;
    private array $engineOptions = [];
    private string $lastFetchEngine = 'dom';
    private string $lastFetchFinalUrl = '';
    private int $lastFetchHttpStatus = 0;
    private bool $engineExplicitlySet = false;

    public function __construct(array $config = [], $url = null, $baseUrl = null)
    {
        if (!empty($url)) {
            $this->setUrl($url);
            if ($baseUrl !== null) {
                $this->baseUrl = $baseUrl;
            }
        }

        $this->dom = new DOMDocument();
        $this->xpath = new DOMXPath($this->dom);
        $this->engine = $this->createEngineFromConfig($config);
        $this->engineOptions = $config['engine_options'] ?? [];
        $this->cacheEnabled = $config['cache_enabled'] ?? false;
        $this->cacheDir = $config['cache_dir'] ?? sys_get_temp_dir() . '/web_scraper_cache';
        $this->cacheTTL = $config['cache_ttl'] ?? 3600;

        $this->initializeDefaultSelectors();
    }

    private function initializeDefaultSelectors(): void
    {
        $this->selectors = [
            'name' => 'h1, .product-title, .product-name, [data-product-title], .title',
            'price' => '.price, [data-price], .product-price, .amount, .cost',
            'description' => '.description, [data-description], .product-description, p.desc',
            'images' => 'img.product-image, [data-image], .gallery img, img[alt*="product"]',
            'currency' => '[data-currency], .currency',
        ];
    }

    public static function __callStatic(string $name, array $arguments): self
    {
        if ($name === 'setUrl' && isset($arguments[0]) && is_string($arguments[0])) {
            $url = Str::trim($arguments[0]);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException("Invalid URL: $url");
            }
            return new self([], $url);
        }

        throw new BadMethodCallException("Method {$name} does not exist.");
    }

    public static function url(string $url): self
    {
        return self::__callStatic('setUrl', [$url]);
    }

    public function setUrl(string $url): self
    {
        $url = Str::trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL: $url");
        }
        $this->url = $url;
        if (!isset($this->baseUrl) || $this->baseUrl === '') {
            $parsed = parse_url($url);
            $this->baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        }
        return $this;
    }

    public function chromiumBinary(?string $absolutePath): self
    {
        $this->chromiumBinary = $absolutePath;
        return $this;
    }

    public function setEngine(string|WebScraperEngineInterface $engine, array $options = []): self
    {
        $this->engineExplicitlySet = true;
        if (is_string($engine)) {
            $this->engine = $this->createEngineByName($engine);
        } else {
            $this->engine = $engine;
        }
        if ($options !== []) {
            $this->engineOptions = $options;
        }
        return $this;
    }

    public function setSelectors(array $selectors): self
    {
        foreach ($selectors as $key => $value) {
            if (is_string($value) && $value !== '') {
                $k = $key === 'title' ? 'name' : $key;
                $this->selectors[$k] = $this->normalizeCssSelectorQuotes($value);
            }
        }
        return $this;
    }

    public function fetch(bool $forceRefresh = false): self
    {
        if (empty($this->url)) {
            throw new RuntimeException('URL not set. Call setUrl() first.');
        }

        if (!$forceRefresh && $this->cacheEnabled) {
            $cached = $this->getFromCache();
            if ($cached !== null) {
                $this->productData = $cached;
                return $this;
            }
        }

        $attempts = 0;
        $maxAttempts = 2;

        while ($attempts < $maxAttempts) {
            try {
                $this->fetchWithEngine($this->engine);
                $this->autoSwitchEngineIfCloudflareDetected();
                break;
            } catch (Exception $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    $this->setFetchFailureResult($e->getMessage());
                    $this->errors[] = $e->getMessage();
                    break;
                }

                if (!($this->engine instanceof ChromiumWebScraperEngine)) {
                    $this->engine = new ChromiumWebScraperEngine();
                }
                usleep(500000);
            }
        }

        if ($this->cacheEnabled && !empty($this->productData['name']) && !empty($this->productData['price'])) {
            $this->saveToCache();
        }

        return $this;
    }

    /**
     * Detect Cloudflare and other anti-bot systems, automatically switch to Chromium
     */
    private function autoSwitchEngineIfCloudflareDetected(): void
    {
        if ($this->engineExplicitlySet || $this->lastFetchEngine === 'chromium') {
            return;
        }

        $htmlLower = strtolower($this->html);
        $isCloudflare = str_contains($htmlLower, 'cloudflare') || 
                       str_contains($htmlLower, 'challenge') ||
                       str_contains($htmlLower, 'just a moment');

        $isMissingData = empty($this->productData['images']) || 
                        empty($this->productData['price']) ||
                        empty($this->productData['description']);

        if ($isCloudflare || $isMissingData) {
            try {
                $chromiumEngine = new ChromiumWebScraperEngine();
                $this->fetchWithEngine($chromiumEngine);
            } catch (Exception) {
                // Fallback already handled
            }
        }
    }

    private function fetchWithEngine(WebScraperEngineInterface $engine): void
    {
        $engineOpts = $this->engineOptions;
        
        // Add Cloudflare-busting headers
        $engineOpts['user_agent'] = $engineOpts['user_agent'] ?? 
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $engineOpts['headers'] = $engineOpts['headers'] ?? [];
        $engineOpts['headers']['Accept'] = $engineOpts['headers']['Accept'] ?? 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8';
        $engineOpts['headers']['Accept-Language'] = $engineOpts['headers']['Accept-Language'] ?? 'en-US,en;q=0.9';
        $engineOpts['headers']['Accept-Encoding'] = $engineOpts['headers']['Accept-Encoding'] ?? 'gzip, deflate, br';
        $engineOpts['headers']['Referer'] = $engineOpts['headers']['Referer'] ?? $this->baseUrl;
        $engineOpts['headers']['Sec-Fetch-Site'] = $engineOpts['headers']['Sec-Fetch-Site'] ?? 'none';
        $engineOpts['headers']['Sec-Fetch-Mode'] = $engineOpts['headers']['Sec-Fetch-Mode'] ?? 'navigate';
        $engineOpts['headers']['Sec-Fetch-User'] = $engineOpts['headers']['Sec-Fetch-User'] ?? '?1';
        
        $engineOpts['navigation_timeout_ms'] = $engineOpts['navigation_timeout_ms'] ?? 12000;
        $engineOpts['timeout'] = $engineOpts['timeout'] ?? 20;
        $engineOpts['connect_timeout'] = $engineOpts['connect_timeout'] ?? 8;

        $result = $engine->fetch($this->url, $engineOpts);
        $this->html = $result->html;
        $this->lastFetchEngine = $result->engineName;
        $this->lastFetchFinalUrl = $result->finalUrl;
        $this->lastFetchHttpStatus = $result->httpStatus;

        if ($this->lastFetchFinalUrl !== '') {
            $this->baseUrl = $this->lastFetchFinalUrl;
        }

        $this->dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $this->dom->loadHTML(mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
        libxml_use_internal_errors(false);
        
        $this->xpath = new DOMXPath($this->dom);
        $this->scrape();
    }

    private function createEngineFromConfig(array $config): WebScraperEngineInterface
    {
        $this->engineExplicitlySet = array_key_exists('engine', $config);
        $e = $config['engine'] ?? null;
        if ($e instanceof WebScraperEngineInterface) {
            return $e;
        }
        if (is_string($e)) {
            return $this->createEngineByName($e);
        }
        return new DomWebScraperEngine();
    }

    private function createEngineByName(string $name): WebScraperEngineInterface
    {
        $n = strtolower(trim($name));
        if (in_array($n, ['chromium', 'chrome', 'headless-chromium'], true)) {
            return new ChromiumWebScraperEngine();
        }
        return new DomWebScraperEngine();
    }

    private function scrape(): self
    {
        $this->productData = [
            'name' => '',
            'company' => $this->extractCompany(),
            'price' => '',
            'currency' => '',
            'description' => '',
            'images' => [],
            'main_image' => '',
            'url' => $this->url,
            'scraped_at' => date('Y-m-d H:i:s'),
            'raw_data' => [
                'engine' => $this->lastFetchEngine,
                'final_url' => $this->lastFetchFinalUrl,
                'http_status' => $this->lastFetchHttpStatus,
                'blocked' => false,
                'block_signals' => [],
                'block_reason' => '',
            ],
        ];

        // Extract essential fields only
        $this->productData['name'] = $this->extractName();
        $this->productData['price'] = $this->extractPrice();
        $this->productData['currency'] = $this->extractCurrency();
        $this->productData['description'] = $this->extractDescription();
        $this->productData['images'] = $this->extractImages();

        if (!empty($this->productData['images'])) {
            $this->productData['main_image'] = $this->productData['images'][0];
        }

        // Fallback to structured data if CSS selectors failed
        if (empty($this->productData['price']) || empty($this->productData['currency'])) {
            $this->enrichFromStructuredData();
        }

        $this->detectBlockSignals();

        return $this;
    }

    /**
     * Try JSON-LD, microdata, Open Graph for missing fields
     */
    private function enrichFromStructuredData(): void
    {
        // JSON-LD
        $scripts = $this->xpath->query('//script[@type="application/ld+json"]');
        if ($scripts && $scripts->length > 0) {
            for ($i = 0; $i < $scripts->length; $i++) {
                $item = $scripts->item($i);
                if ($item && $item->textContent) {
                    $data = json_decode($item->textContent, true);
                    if (is_array($data) && isset($data['offers'])) {
                        $offers = is_array($data['offers']) ? $data['offers'] : [$data['offers']];
                        foreach ($offers as $offer) {
                            if (is_array($offer)) {
                                if (empty($this->productData['price']) && isset($offer['price'])) {
                                    $this->productData['price'] = (string) $offer['price'];
                                }
                                if (empty($this->productData['currency']) && isset($offer['priceCurrency'])) {
                                    $this->productData['currency'] = (string) $offer['priceCurrency'];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Open Graph fallback
        if (empty($this->productData['price'])) {
            $price = $this->getMetaByProperty('og:price:amount');
            if ($price) {
                $this->productData['price'] = $price;
            }
        }
        if (empty($this->productData['currency'])) {
            $curr = $this->getMetaByProperty('og:price:currency');
            if ($curr) {
                $this->productData['currency'] = $curr;
            }
        }
    }

    private function detectBlockSignals(): void
    {
        $signals = [];
        $htmlLower = strtolower($this->html);

        if (str_contains($htmlLower, 'cloudflare') || str_contains($htmlLower, 'just a moment')) {
            $signals[] = 'cloudflare:detected';
        }
        if (str_contains($htmlLower, 'captcha') || str_contains($htmlLower, 'challenge')) {
            $signals[] = 'captcha:detected';
        }
        if (empty($this->productData['name']) || empty($this->productData['price'])) {
            $signals[] = 'data:incomplete';
        }
        if (empty($this->productData['images'])) {
            $signals[] = 'images:missing';
        }
        if ($this->lastFetchHttpStatus >= 400) {
            $signals[] = 'http:' . $this->lastFetchHttpStatus;
        }

        $this->productData['raw_data']['blocked'] = !empty($signals);
        $this->productData['raw_data']['block_signals'] = array_values(array_unique($signals));
        $this->productData['raw_data']['block_reason'] = implode(', ', array_slice($signals, 0, 3));
    }

    private function extractName(): string
    {
        return htmlspecialchars_decode(trim($this->extractText($this->selectors['name'])));
    }

    private function extractCompany(): string
    {
        if (empty($this->url)) {
            return '';
        }
        $host = parse_url($this->url, PHP_URL_HOST) ?? '';
        $host = preg_replace('/^www\./i', '', $host);
        $domainPart = explode('.', $host)[0] ?? '';
        return ucfirst($domainPart);
    }

    /**
     * Extract price - normalized and cleaned to avoid duplicated or noisy values.
     */
    private function extractPrice(): string
    {
        $price = $this->extractText($this->selectors['price']);
        if ($price === '') {
            return '';
        }

        return $this->formatNormalizedPrice($price);
    }

    private function formatNormalizedPrice(string $price): string
    {
        if ($price === '') {
            return '';
        }

        $price = html_entity_decode($price, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $price = preg_replace('/\s+/', ' ', $price) ?? $price;
        $price = preg_replace('/[^0-9,.-]/', '', $price) ?? '';
        if ($price === '') {
            return '';
        }

        $price = str_replace(',', '', $price);
        $price = trim($price, '.');
        if ($price === '') {
            return '';
        }

        $parts = array_values(array_filter(explode('.', $price), static fn ($part): bool => trim((string) $part) !== ''));
        if ($parts === []) {
            return '';
        }

        $normalizedParts = [];
        foreach ($parts as $part) {
            $normalizedParts[] = $this->collapseRepeatedPrefix((string) $part);
        }

        $normalized = implode('.', array_filter($normalizedParts, static fn ($part): bool => $part !== ''));
        if ($normalized === '') {
            return '';
        }

        if (!str_contains($normalized, '.')) {
            return $normalized . '.00';
        }

        return $normalized;
    }

    private function normalizePriceValue(string $value): string
    {
        return $this->formatNormalizedPrice($value);
    }

    private function collapseRepeatedPrefix(string $value): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) < 3) {
            return $value;
        }

        $value = preg_replace('/[^0-9]/', '', $value) ?? $value;
        if ($value === '') {
            return '';
        }

        $length = strlen($value);
        for ($i = 1; $i <= intdiv($length, 2); $i++) {
            $prefix = substr($value, 0, $i);
            $repeated = str_repeat($prefix, intdiv($length, $i));
            if ($repeated === $value) {
                return $prefix;
            }
        }

        return $value;
    }

    private function extractCurrency(): string
    {
        $combined = trim((string) $this->extractText($this->selectors['currency']));
        $priceText = trim((string) $this->extractText($this->selectors['price']));
        if ($priceText !== '') {
            $combined = $combined !== '' ? $combined . ' ' . $priceText : $priceText;
        }

        $normalized = $this->normalizeToIso4217($combined);
        if ($normalized !== '') {
            return $normalized;
        }

        return $this->inferCurrencyFromPriceText($combined);
    }

    private function normalizeToIso4217(string $raw): string
    {
        $t = strtoupper(trim($raw));
        $this->buildCurrencyLookups();
        if (preg_match('/^[A-Z]{3}$/', $t) && isset(self::$currencyCodeSet[$t])) {
            return $t;
        }

        $t = (string) preg_replace('/\s+/', ' ', $t);
        if (preg_match('/\b([A-Z]{3})\b/u', $t, $m)) {
            $code = $m[1];
            if (isset(self::$currencyCodeSet[$code])) {
                return $code;
            }
        }

        if (isset(self::$currencyNameToCode[$t])) {
            return self::$currencyNameToCode[$t];
        }

        return '';
    }

    private function inferCurrencyFromPriceText(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        $this->buildCurrencyLookups();
        if (preg_match('/\b([A-Z]{3})\b/iu', $raw, $m)) {
            $code = strtoupper($m[1]);
            if (isset(self::$currencyCodeSet[$code])) {
                return $code;
            }
        }

        if (str_contains($raw, 'A$') || str_contains($raw, 'AU$') || str_contains($raw, 'AUD')) {
            return 'AUD';
        }
        if (str_contains($raw, 'C$') || str_contains($raw, 'CA$') || str_contains($raw, 'CAD')) {
            return 'CAD';
        }
        if (preg_match('/^\s*R\$/u', $raw)) {
            return 'BRL';
        }
        if (str_contains($raw, '¥')) {
            if (str_contains($raw, '元') || str_contains($raw, 'CNY') || str_contains($raw, 'RMB') || str_contains($raw, '人民币')) {
                return 'CNY';
            }
            return 'JPY';
        }
        if (preg_match('/^\s*(US\$\s*|\$)(?![A-Za-z])/u', $raw) && !str_contains($raw, 'A$') && !str_contains($raw, 'AU$')) {
            return 'USD';
        }
        if (str_starts_with(ltrim($raw), '€') || str_contains($raw, ' €')) {
            return 'EUR';
        }
        if (preg_match('/^\s*£/u', $raw)) {
            return 'GBP';
        }
        if (preg_match('/^\s*₹/u', $raw) || (str_contains($raw, '₹') && !preg_match('/\b(USD|EUR|GBP)\b/i', $raw))) {
            return 'INR';
        }
        if (preg_match('/\b₦/u', $raw)) {
            return 'NGN';
        }
        if (is_array(self::$currencySymbolToCode)) {
            foreach (self::$currencySymbolToCode as $symbol => $code) {
                if ($symbol !== '' && str_contains($raw, $symbol)) {
                    return $code;
                }
            }
        }

        return '';
    }

    private function buildCurrencyLookups(): void
    {
        if (self::$currencyCodeSet !== null && self::$currencySymbolToCode !== null && self::$currencyNameToCode !== null) {
            return;
        }

        $catalog = NumberToWords::allCurrency();
        if (!is_array($catalog)) {
            self::$currencyCodeSet = [];
            self::$currencySymbolToCode = [];
            self::$currencyNameToCode = [];
            return;
        }

        $codeSet = [];
        $symbolMap = [];
        $nameMap = [];
        foreach ($catalog as $code => $meta) {
            if (!is_string($code) || $code === '') {
                continue;
            }

            $iso = strtoupper(trim($code));
            $codeSet[$iso] = true;

            if (is_array($meta)) {
                $name = trim((string) ($meta['name'] ?? ''));
                if ($name !== '') {
                    $nameMap[strtoupper($name)] = $iso;
                }
                $symbol = trim((string) ($meta['symbol'] ?? ''));
                if ($symbol !== '' && !isset($symbolMap[$symbol])) {
                    $symbolMap[$symbol] = $iso;
                }
            }
        }

        uksort($symbolMap, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        self::$currencyCodeSet = $codeSet;
        self::$currencySymbolToCode = $symbolMap;
        self::$currencyNameToCode = $nameMap;
    }

    private function extractDescription(): string
    {
        $desc = $this->extractText($this->selectors['description']);
        if ($desc === '') {
            return '';
        }
        $desc = strip_tags($desc);
        $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $desc = preg_replace('/\s+/', ' ', $desc) ?? $desc;
        return trim($desc);
    }

    /**
     * Extract images - FIXED: prevents duplication
     */
    private function extractImages(): array
    {
        $images = [];
        
        // From CSS selectors
        $selectorList = array_map('trim', explode(',', $this->selectors['images']));
        foreach ($selectorList as $sel) {
            try {
                $xpath = $this->cssToXPath($sel);
                $nodes = $this->xpath->query($xpath);
                if ($nodes && $nodes->length > 0) {
                    for ($i = 0; $i < $nodes->length; $i++) {
                        $node = $nodes->item($i);
                        if ($node instanceof DOMElement) {
                            foreach (['src', 'data-src', 'data-lazy', 'data-lazy-src', 'data-original'] as $attr) {
                                $url = $node->getAttribute($attr);
                                if ($url !== '') {
                                    $images[] = $this->resolveUrl($url);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception) {
                // Skip invalid selectors
            }
        }

        // From JSON-LD
        $scripts = $this->xpath->query('//script[@type="application/ld+json"]');
        if ($scripts && $scripts->length > 0) {
            for ($i = 0; $i < $scripts->length; $i++) {
                $item = $scripts->item($i);
                if ($item && $item->textContent) {
                    $data = json_decode($item->textContent, true);
                    if (is_array($data) && isset($data['image'])) {
                        $imageData = $data['image'];
                        if (is_string($imageData)) {
                            $images[] = $this->resolveUrl($imageData);
                        } elseif (is_array($imageData)) {
                            foreach ($imageData as $img) {
                                if (is_string($img)) {
                                    $images[] = $this->resolveUrl($img);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Deduplicate and clean up
        $images = array_filter($images, fn($u) => is_string($u) && !empty($u));
        $normalized = [];
        $seen = [];

        foreach ($images as $img) {
            $img = $this->normalizeImageUrl($img);
            if ($img === '') {
                continue;
            }

            $base = strtok($img, '?');
            $base = preg_replace('#/+#', '/', $base) ?? $base;
            if (!isset($seen[$base])) {
                $seen[$base] = true;
                $normalized[] = $img;
            }
        }

        return array_values($normalized);
    }

    private function resolveUrl(string $url): string
    {
        $url = trim($url);
        if (empty($url)) {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $this->normalizeImageUrl($url);
        }

        if (str_starts_with($url, '//')) {
            $protocol = parse_url($this->baseUrl, PHP_URL_SCHEME) ?? 'https';
            return $this->normalizeImageUrl($protocol . ':' . $url);
        }

        if (str_starts_with($url, '/')) {
            return $this->normalizeImageUrl(rtrim($this->baseUrl, '/') . $url);
        }

        return $this->normalizeImageUrl(rtrim($this->baseUrl, '/') . '/' . $url);
    }

    private function normalizeImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $path = $parts['path'] ?? '';
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        $normalized = $parts['scheme'] . '://' . $parts['host'] . $path;

        if (isset($parts['query']) && $parts['query'] !== '') {
            $normalized .= '?' . $parts['query'];
        }
        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $normalized .= '#' . $parts['fragment'];
        }

        return $normalized;
    }

    private function extractText(string $selector, ?DOMNode $context = null): string
    {
        $selectors = array_values(array_filter(array_map('trim', explode(',', $selector)), static fn (string $value): bool => $value !== ''));
        foreach ($selectors as $sel) {
            $xpathQuery = $this->cssToXPath($sel);
            $nodes = $this->xpath->query($xpathQuery, $context);
            if ($nodes && $nodes->length > 0) {
                for ($i = 0; $i < $nodes->length; $i++) {
                    $value = trim((string) ($nodes->item($i)?->textContent ?? ''));
                    if ($value !== '') {
                        return $value;
                    }
                }
            }
        }

        return '';
    }

    private function cssToXPath(string $selector): string
    {
        $selector = $this->normalizeCssSelectorQuotes(trim($selector));

        if ($selector === '') {
            return '// *';
        }

        $parts = preg_split('/\s+/', $selector, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return '// *';
        }

        $xpath = '';
        foreach ($parts as $index => $part) {
            $converted = $this->convertSimpleSelector($part);
            if ($index === 0) {
                $xpath = '//' . $converted;
            } else {
                $xpath .= '//' . $converted;
            }
        }

        return $xpath === '' ? '//*' : $xpath;
    }

    private function convertSimpleSelector(string $selector): string
    {
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)(\.[a-zA-Z_-][a-zA-Z0-9_-]*)+$/', $selector, $matches)) {
            $tag = $matches[1];
            $classPart = substr($selector, strlen($tag));
            $classes = array_values(array_filter(explode('.', ltrim($classPart, '.')), static fn (string $value): bool => $value !== ''));
            $conditions = [];
            foreach ($classes as $className) {
                $conditions[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$className} ')";
            }
            return $tag . '[' . implode(' and ', $conditions) . ']';
        }

        if (str_starts_with($selector, '.') && substr_count($selector, '.') > 1) {
            $tokens = array_values(array_filter(explode('.', $selector), static fn (string $token): bool => $token !== ''));
            if (count($tokens) >= 2) {
                $conditions = [];
                foreach ($tokens as $className) {
                    $conditions[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$className} ')";
                }
                return '*[' . implode(' and ', $conditions) . ']';
            }
        }

        if (preg_match('/^\.([a-zA-Z_-][a-zA-Z0-9_-]*)$/', $selector, $matches)) {
            return "*[contains(concat(' ', @class, ' '), ' {$matches[1]} ')]";
        }

        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)#([a-zA-Z][a-zA-Z0-9_-]*)$/', $selector, $matches)) {
            return "{$matches[1]}[@id='{$matches[2]}']";
        }

        if (preg_match('/^#([a-zA-Z][a-zA-Z0-9_-]*)$/', $selector, $matches)) {
            return "*[@id='{$matches[1]}']";
        }

        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\[([A-Za-z_][A-Za-z0-9_:\-]*)\]$/u', $selector, $m)) {
            return "{$m[1]}[@{$m[2]}]";
        }
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\[([A-Za-z_][A-Za-z0-9_:\-]*)="((?:\\.|[^"\\])*)"\]$/u', $selector, $m)) {
            $v = $this->escapeXpathStringLiteral($m[3]);
            return "{$m[1]}[@{$m[2]}={$v}]";
        }
        if (preg_match("/^([a-zA-Z][a-zA-Z0-9]*)\\[([A-Za-z_][A-Za-z0-9_:\\-]*)='((?:\\\\'|[^'])*)'\\]$/u", $selector, $m)) {
            $v = $this->escapeXpathStringLiteral(stripslashes($m[3]));
            return "{$m[1]}[@{$m[2]}={$v}]";
        }
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\[([A-Za-z_][A-Za-z0-9_:\-]*)=([^]\s\]]+)\]$/u', $selector, $m)) {
            $v = $this->escapeXpathStringLiteral($m[3]);
            return "{$m[1]}[@{$m[2]}={$v}]";
        }

        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $selector)) {
            return $selector;
        }

        if (preg_match('/^\[([a-zA-Z_][A-Za-z0-9_:\-]*)(?:=([\'\"]?)([^\'\"]+)\2)?\]$/u', $selector, $matches)) {
            $attr = $matches[1];
            if (isset($matches[3]) && $matches[3] !== '') {
                $v = $this->escapeXpathStringLiteral($matches[3]);
                return "*[@{$attr}={$v}]";
            }
            return "*[@{$attr}]";
        }

        return "*[contains(concat(' ', @class, ' '), ' {$selector} ')]";
    }

    private function escapeXpathStringLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    private function getMetaByProperty(string $property): string
    {
        $n = $this->xpath->query("//meta[@property='" . strtolower($property) . "']/@content");
        if ($n && $n->length > 0) {
            return trim((string) ($n->item(0)?->nodeValue ?? ''));
        }
        return '';
    }

    private function normalizeCssSelectorQuotes(string $selector): string
    {
        return str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", "\u{00AB}", "\u{00BB}"],
            ['"', '"', "'", "'", '"', '"'],
            $selector
        );
    }

    private function setFetchFailureResult(string $errorMessage): void
    {
        $this->html = '';
        $this->dom = new DOMDocument();
        $this->dom->loadHTML('<!doctype html><html><head></head><body></body></html>', LIBXML_NOERROR);
        $this->xpath = new DOMXPath($this->dom);

        $this->productData = [
            'name' => '',
            'company' => $this->extractCompany(),
            'price' => '',
            'currency' => '',
            'description' => '',
            'images' => [],
            'main_image' => '',
            'url' => $this->url,
            'scraped_at' => date('Y-m-d H:i:s'),
            'raw_data' => [
                'engine' => $this->lastFetchEngine,
                'final_url' => $this->lastFetchFinalUrl,
                'http_status' => $this->lastFetchHttpStatus,
                'blocked' => true,
                'block_signals' => ['fetch:failed'],
                'block_reason' => $errorMessage,
                'fetch_error' => $errorMessage,
            ],
        ];
    }

    private function getFromCache(): ?array
    {
        if (!$this->cacheEnabled) {
            return null;
        }

        $key = md5($this->url);
        $file = $this->cacheDir . '/' . $key . '.json';

        if (!file_exists($file)) {
            return null;
        }

        if (time() - filemtime($file) > $this->cacheTTL) {
            @unlink($file);
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    private function saveToCache(): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        @mkdir($this->cacheDir, 0755, true);
        $key = md5($this->url);
        $file = $this->cacheDir . '/' . $key . '.json';

        return (bool) file_put_contents($file, json_encode($this->productData));
    }

    public function getData(): array
    {
        return $this->productData;
    }

    public function getField(string $field)
    {
        return $this->productData[$field] ?? null;
    }

    public function getName(): string
    {
        return $this->productData['name'] ?? '';
    }

    public function getPrice(): string
    {
        return $this->productData['price'] ?? '';
    }

    public function getCurrency(): string
    {
        return $this->productData['currency'] ?? '';
    }

    public function getDescription(): string
    {
        return $this->productData['description'] ?? '';
    }

    public function getImages(): array
    {
        return $this->productData['images'] ?? [];
    }

    public function getMainImage(): string
    {
        return $this->productData['main_image'] ?? '';
    }

    public function toJson(bool $prettyPrint = true): string
    {
        return json_encode($this->productData, $prettyPrint ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : JSON_UNESCAPED_SLASHES);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getLastFetchEngine(): string
    {
        return $this->lastFetchEngine;
    }

    public function getLastFetchFinalUrl(): string
    {
        return $this->lastFetchFinalUrl;
    }

    public function getLastFetchHttpStatus(): int
    {
        return $this->lastFetchHttpStatus;
    }

    public function clearCache(): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        $key = md5($this->url);
        $file = $this->cacheDir . '/' . $key . '.json';
        return @unlink($file);
    }

    public function clearAllCache(): bool
    {
        if (!is_dir($this->cacheDir)) {
            return false;
        }
        $files = glob($this->cacheDir . '/*.json');
        foreach ($files ?? [] as $file) {
            @unlink($file);
        }
        return true;
    }

    public function __destruct()
    {
        // Cleanup if needed
    }
}
