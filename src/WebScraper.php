<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Traits\TameTrait;
use Tamedevelopers\Support\ChromePdf\ChromiumEnvironment;
use Tamedevelopers\Support\WebScraper\ChromiumWebScraperEngine;
use Tamedevelopers\Support\WebScraper\DomWebScraperEngine;
use Tamedevelopers\Support\WebScraper\WebScraperFetchResult;
use Tamedevelopers\Support\WebScraper\WebScraperEngineInterface;

/**
 * Product scraper. By default HTML is fetched with automatic engine fallback: cURL/DOM first (no extra deps,
 * shared-hosting friendly), then headless Chromium when the page is blocked or yields no product data (same stack as
 * {@see \Tamedevelopers\Support\ChromePdf\ChromePdf}). Override with {@see setEngine()} or config key {@code engine}.
 */
class WebScraper
{
    use TameTrait;


    /** 
     * @var string|null The Chromium binary path.
     */
    private ?string $chromiumBinary = null;
    
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
     * @var null|DOMDocument DOM document instance for HTML parsing
     */
    private ?DOMDocument $dom = null;
    
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

    /** @var array<string, bool>|null */
    private static ?array $currencyCodeSet = null;

    /** @var array<string, string>|null */
    private static ?array $currencySymbolToCode = null;

    /** @var array<string, string>|null */
    private static ?array $currencyNameToCode = null;
    
    private WebScraperEngineInterface $engine;
    
    /**
     * Options passed to {@see WebScraperEngineInterface::fetch()}.
     *
     * @var array<string, mixed>
     */
    private array $engineOptions;
    
    private string $lastFetchEngine = 'dom';
    
    private string $lastFetchFinalUrl = '';
    
    private int $lastFetchHttpStatus = 0;

    /**
     * When true (default), {@see fetch()} tries DOM/cURL first and falls back to Chromium when needed.
     */
    private bool $autoEngine = true;
    
    
    /**
     * Constructor
     * 
     * @param array $config {
     *   @var array<string, string>   $selectors
     *   @var 'auto'|'dom'|'chromium'|'chrome'|WebScraperEngineInterface  $engine  Fetch backend (default: auto)
     *   @var array<string, mixed>   $engine_options  Passed to the engine (e.g. navigation_timeout_ms, binary, user_agent, verify_ssl)
     *   @var bool   $cache_enabled
     *   @var string $cache_dir
     *   @var int    $cache_ttl
     * }
     * @param string|null $url Optional URL to set immediately
     * @param string|null $baseUrl Optional base URL for resolving relative image paths (defaults to URL's domain)
     */
    public function __construct(array $config = [], $url = null, $baseUrl = null)
    {
        // If URL is provided in constructor, set it and derive base URL if not explicitly provided
        if(!empty($url)){
            $this->url = $url;
            $this->baseUrl = $baseUrl ?? parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
        }

        // Initialize properties and engine
        if(!($this->dom instanceof DOMDocument)) {
            $this->dom = new DOMDocument();
            libxml_use_internal_errors(true);
    
            $this->errors = [];
            $this->productData = [];
    
            // Human behavior defaults: Updated to modern Chrome version with complete anti-fingerprinting headers
            $this->engineOptions = $config['engine_options'] ?? [
                'navigation_timeout_ms' => 12000,
                'post_navigation_settle_ms' => 395,
                'timeout' => 6,
                'connect_timeout' => 4,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'verify_ssl' => true,
                'proxy' => null,
                'http_headers' => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                    'Accept-Language: en-US,en;q=0.9',
                    'Cache-Control: max-age=0',
                    'Sec-Ch-Ua: "Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"',
                    'Sec-Ch-Ua-Mobile: ?0',
                    'Sec-Ch-Ua-Platform: "Windows"',
                    'Sec-Fetch-Dest: document',
                    'Sec-Fetch-Mode: navigate',
                    'Sec-Fetch-Site: none',
                    'Sec-Fetch-User: ?1',
                    'Upgrade-Insecure-Requests: 1'
                ]
            ];
    
            $engineConfig = $config['engine'] ?? 'auto';
            if ($this->isAutoEngineConfig($engineConfig)) {
                $this->autoEngine = true;
                $this->engine = new DomWebScraperEngine();
            } else {
                $this->autoEngine = false;
                $this->engine = $this->createEngineFromConfig($config);
            }
            
            // Set default selectors (can be customized)
            $this->selectors = [
                'name' => 'div h1.-fs20, .title--wrap--UUHae_g h1, h1.dark-gray, h1[itemprop="name"]',
                'price' => 'div span.-prxs, .price-default--current--F8OlYIo, .ux-textspans, .price, span.price, [itemprop="price"]',
                'description' => '.markup.-mhm.-pvl.-oxa.-sc, .description--wrap--LscZ0He, [itemprop="description"], p.description',
                'colors' => '.itm-sel',
                'sizes' => '.vl, [data-testid="variant-group-0"] .ld_A0 span, .pl_selectiontile-text100 label font',
                'images' => 'img.product-image, .product-gallery img, [data-image]'
            ];
            
            // Cache configuration
            $this->cacheEnabled = $config['cache_enabled'] ?? false;
            $this->cacheDir = $config['cache_dir'] ?? __DIR__ . '/cache/scraper/';
            $this->cacheTTL = $config['cache_ttl'] ?? 86400;
            
            // Initialize cache directory if needed
            if ($this->cacheEnabled && !is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0755, true);
            }
        }
    }
    
    /**
     * Set the URL to scrape
     * 
     * @param string $url The target URL
     * @return self Returns instance for method chaining
     * @throws InvalidArgumentException If URL is invalid
     */
    public static function setUrl(string $url): self
    {
        $url = Str::trim($url);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL provided: $url");
        }

        return new self(
            [], 
            $url, 
            parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST)
        );
    }

    /**
     * Alias for {@see setUrl()} to allow fluent static calls.
     *
     * @param string $url
     * @return self
     */
    public static function url(string $url): self
    {
        return self::setUrl($url);
    }

    /**
     * Set the Chromium binary path.
     *
     * @param string|null $absolutePath
     * @return self
     */
    public function chromiumBinary(?string $absolutePath): self
    {
        $env = new ChromiumEnvironment();
        if($env->isWindowAndNotDocker()) {
            $absolutePath = self::stringReplacer($absolutePath);
        }
        
        $this->chromiumBinary = $absolutePath;

        return $this;
    }
    
    /**
     * Use automatic DOM→Chromium fallback (default), a single engine, or a custom {@see WebScraperEngineInterface}.
     *
     * @param 'auto'|'dom'|'chromium'|'chrome'|WebScraperEngineInterface $engine
     * @param array<string, mixed> $options Replaces engine options for subsequent {@see fetch()} calls
     */
    public function setEngine(string|WebScraperEngineInterface $engine, array $options = []): self
    {
        if (is_string($engine) && $this->isAutoEngineConfig($engine)) {
            $this->autoEngine = true;
            $this->engine = new DomWebScraperEngine();
        } else {
            $this->autoEngine = false;
            if (is_string($engine)) {
                $this->engine = $this->createEngineByName($engine);
            } else {
                $this->engine = $engine;
            }
        }
        if ($options !== []) {
            $this->engineOptions = $options;
        }

        return $this;
    }
    
    /**
     * Get the engine.
     *
     * @return WebScraperEngineInterface
     */
    public function getEngine(): WebScraperEngineInterface
    {
        return $this->engine;
    }
    
    /**
     * Get the engine options.
     * 
     * @return array<string, mixed>
     */
    public function getEngineOptions(): array
    {
        return $this->engineOptions;
    }
    
    /**
     * @param array<string, mixed> $config
     */
    private function createEngineFromConfig(array $config): WebScraperEngineInterface
    {
        $e = $config['engine'] ?? 'auto';
        if ($e instanceof WebScraperEngineInterface) {
            return $e;
        }
        if (is_string($e) && !$this->isAutoEngineConfig($e)) {
            return $this->createEngineByName($e);
        }
        return new DomWebScraperEngine();
    }

    /**
     * @param mixed $engine
     */
    private function isAutoEngineConfig(mixed $engine): bool
    {
        if (!is_string($engine)) {
            return false;
        }

        return in_array(strtolower(trim($engine)), ['auto', ''], true);
    }
    
    /**
     * Create an engine by name.
     *
     * @param string $name
     * @return WebScraperEngineInterface
     */
    private function createEngineByName(string $name): WebScraperEngineInterface
    {
        $n = strtolower(trim($name));
        if (in_array($n, ['chromium', 'chrome', 'headless-chromium'], true)) {
            return new ChromiumWebScraperEngine($this->chromiumBinary);
        }

        return new DomWebScraperEngine();
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
            
            if ($this->autoEngine) {
                $this->fetchWithAutoEngine();
            } else {
                $this->processFetchResult($this->engine->fetch($this->url, $this->engineOptions));
            }
            
            // Save to cache
            if ($this->cacheEnabled) {
                $this->saveToCache();
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->setFetchFailureResult($e->getMessage());
        }
        
        return $this;
    }

    /**
     * DOM/cURL first (fast probe), then Chromium via the same path as {@see \Tamedevelopers\Support\ChromePdf\ChromePdf}.
     */
    private function fetchWithAutoEngine(): void
    {
        $enginesTried = [];
        $domResult = null;
        $chromiumAvailable = class_exists(\HeadlessChromium\BrowserFactory::class);

        try {
            $enginesTried[] = 'dom';
            $domResult = (new DomWebScraperEngine())->fetch($this->url, $this->domEngineOptions());

            if (!$this->htmlIndicatesBotChallenge($domResult->html)) {
                $this->processFetchResult($domResult, $enginesTried);

                if (!$this->shouldRetryWithChromium()) {
                    return;
                }
            }
        } catch (Exception $e) {
            $this->errors[] = 'dom: ' . $e->getMessage();
        }

        if (!$chromiumAvailable) {
            if ($domResult !== null) {
                $this->processFetchResult($domResult, $enginesTried);
            } elseif ($domResult === null) {
                throw new RuntimeException(
                    'DOM fetch failed and Chromium fallback requires chrome-php/chrome (composer require chrome-php/chrome).'
                );
            }

            return;
        }

        try {
            $enginesTried[] = 'chromium';
            $chromiumResult = (new ChromiumWebScraperEngine($this->chromiumBinary))
                ->fetch($this->url, $this->chromiumEngineOptions());
            $this->processFetchResult($chromiumResult, $enginesTried);
        } catch (Exception $e) {
            $this->errors[] = 'chromium: ' . $e->getMessage();
            if ($domResult !== null) {
                $this->processFetchResult($domResult, $enginesTried);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function domEngineOptions(): array
    {
        $opts = $this->engineOptions;
        $opts['timeout'] = min(max(3, (int) ($opts['timeout'] ?? 6)), 6);
        $opts['connect_timeout'] = min(max(2, (int) ($opts['connect_timeout'] ?? 4)), 4);

        return $opts;
    }

    /**
     * @return array<string, mixed>
     */
    private function chromiumEngineOptions(): array
    {
        $opts = $this->engineOptions;
        $opts['navigation_timeout_ms'] = min(max(5000, (int) ($opts['navigation_timeout_ms'] ?? 12000)), 12000);

        return $opts;
    }

    /**
     * Load fetched HTML into the DOM, scrape fields, and record fetch metadata.
     *
     * @param list<string> $enginesTried
     */
    private function processFetchResult(WebScraperFetchResult $result, array $enginesTried = []): void
    {
        $this->html = $result->html;
        $this->lastFetchEngine = $result->engineName;
        $this->lastFetchFinalUrl = $result->finalUrl;
        $this->lastFetchHttpStatus = $result->httpStatus;
        if ($this->lastFetchFinalUrl !== '') {
            $pu = @parse_url($this->lastFetchFinalUrl);
            if (!empty($pu['scheme']) && !empty($pu['host'])) {
                $this->baseUrl = $pu['scheme'] . '://' . $pu['host'];
            }
        }

        $this->dom->loadHTML(mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
        $this->xpath = new DOMXPath($this->dom);
        $this->scrape($enginesTried);
    }

    /**
     * Decide whether Chromium should be attempted after a DOM fetch + scrape pass.
     */
    private function shouldRetryWithChromium(): bool
    {
        if ($this->htmlIndicatesBotChallenge($this->html)) {
            return true;
        }

        $signals = $this->detectBlockSignals();
        if ($signals['is_blocked']) {
            return true;
        }

        $emptyCore = 0;
        foreach (['name', 'price', 'description'] as $field) {
            if (trim((string) ($this->productData[$field] ?? '')) === '') {
                $emptyCore++;
            }
        }

        return $emptyCore >= 2 && empty($this->productData['images']);
    }

    /**
     * Fast HTML-only check for anti-bot interstitials before paying for a Chromium launch.
     */
    private function htmlIndicatesBotChallenge(string $html): bool
    {
        $htmlLower = strtolower($html);
        $needles = [
            'cf-challenge',
            'cloudflare',
            '/cdn-cgi/challenge-platform',
            'just a moment',
            'please wait',
            'checking your browser',
            'verify you are human',
            'attention required',
            'captcha',
            'g-recaptcha',
            'hcaptcha',
            'robot check',
            'access denied',
            'request blocked',
        ];

        foreach ($needles as $needle) {
            if (str_contains($htmlLower, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a safe empty payload when fetch is blocked/fails, instead of throwing.
     */
    private function setFetchFailureResult(string $errorMessage): void
    {
        $status = 0;
        if (preg_match('/HTTP Error:\s*(\d{3})/i', $errorMessage, $m) === 1) {
            $status = (int) $m[1];
        }

        $this->lastFetchHttpStatus = $status;
        if ($this->lastFetchFinalUrl === '') {
            $this->lastFetchFinalUrl = $this->url ?? '';
        }

        $this->html = '';
        $this->dom->loadHTML('<!doctype html><html><head></head><body></body></html>', LIBXML_NOERROR);
        $this->xpath = new DOMXPath($this->dom);
        $this->scrape();
        
        $this->productData['raw_data']['fetch_error'] = $errorMessage;
        $this->productData['raw_data']['blocked'] = true;
        $this->productData['raw_data']['block_signals'] = array_values(array_unique(array_merge(
            (array) ($this->productData['raw_data']['block_signals'] ?? []),
            ['fetch:error']
        )));
        $this->productData['raw_data']['block_reason'] = $errorMessage;
    }
    
    /**
     * Scrape product information from the HTML
     *
     * @param list<string> $enginesTried
     * @return self Returns instance for method chaining
     */
    private function scrape(array $enginesTried = []): self
    {
        $this->productData = [
            'name' => $this->extractName(),
            'company' => $this->extractCompany(),
            'price' => $this->extractPrice(),
            'currency' => '',
            'description' => $this->extractDescription(),
            'colors' => $this->extractColors(),
            'sizes' => $this->extractSizes(),
            'images' => $this->extractImages(),
            'main_image' => '',
            'url' => $this->url,
            'scraped_at' => date('Y-m-d H:i:s'),
            'raw_data' => [
                'engine' => $this->lastFetchEngine,
                'engines_tried' => $enginesTried,
                'final_url' => $this->lastFetchFinalUrl,
                'http_status' => $this->lastFetchHttpStatus,
            ],
        ];
        
        // Set main image as first image if available
        if (!empty($this->productData['images'])) {
            $this->productData['main_image'] = $this->productData['images'][0];
        }
        
        $this->applyOpenGraphTitleFallback();
        $this->applyStructuredDataEnrichment();
        $this->applyBlockSignals();
        
        return $this;
    }

    /**
     * Detects anti-bot/challenge pages and stores signals in raw_data for easier debugging.
     */
    private function applyBlockSignals(): void
    {
        $signals = $this->detectBlockSignals();

        $this->productData['raw_data']['blocked'] = $signals['is_blocked'];
        $this->productData['raw_data']['block_signals'] = $signals['signals'];
        $this->productData['raw_data']['block_reason'] = $signals['reason'];
    }

    /**
     * @return array{is_blocked: bool, signals: list<string>, reason: string}
     */
    private function detectBlockSignals(): array
    {
        $signals = [];
        $htmlLower = strtolower($this->html ?? '');

        $title = '';
        $titleNodes = $this->xpath->query('//title');
        if ($titleNodes && $titleNodes->length > 0) {
            $title = trim((string) $titleNodes->item(0)?->textContent);
        }
        $titleLower = strtolower($title);

        $titleMatches = [
            'captcha',
            'access denied',
            'attention required',
            'verify you are human',
            'bot challenge',
            'robot check',
            'just a moment',
            'please wait',
            'loading please wait',
            'request blocked',
            'temporarily blocked',
        ];
        foreach ($titleMatches as $needle) {
            if ($titleLower !== '' && str_contains($titleLower, $needle)) {
                $signals[] = "title:{$needle}";
            }
        }

        $htmlMatches = [
            'cf-challenge',
            'cloudflare',
            '/cdn-cgi/challenge-platform',
            'captcha',
            'g-recaptcha',
            'hcaptcha',
            'perimeterx',
            'px-captcha',
            'datadome',
            'distil_r_captcha',
            'access denied',
            'verify you are human',
            'automated queries',
            'unusual traffic',
            'robot check',
            'service unavailable',
            'request blocked',
            'blocked due to unusual activity',
            'please wait',
            'loading please wait',
            'checking your browser',
        ];
        foreach ($htmlMatches as $needle) {
            if (str_contains($htmlLower, $needle)) {
                $signals[] = "html:{$needle}";
            }
        }

        $emptyCoreCount = 0;
        foreach (['name', 'price', 'description'] as $field) {
            $val = trim((string) ($this->productData[$field] ?? ''));
            if ($val === '') {
                $emptyCoreCount++;
            }
        }
        if ($emptyCoreCount >= 2) {
            $signals[] = 'data:core_fields_missing';
        }
        if (empty($this->productData['images'])) {
            $signals[] = 'data:images_missing';
        }
        if (($this->lastFetchHttpStatus >= 400) || $this->lastFetchHttpStatus === 0) {
            $signals[] = 'http:status=' . (string) $this->lastFetchHttpStatus;
        }

        $signals = array_values(array_unique($signals));
        $isBlocked = false;
        foreach ($signals as $signal) {
            if (str_starts_with($signal, 'title:') || str_starts_with($signal, 'html:') || str_starts_with($signal, 'http:')) {
                $isBlocked = true;
                break;
            }
        }
        if (!$isBlocked && in_array('data:core_fields_missing', $signals, true) && in_array('data:images_missing', $signals, true)) {
            $isBlocked = true;
        }

        $reason = $isBlocked ? implode(', ', array_slice($signals, 0, 5)) : '';

        return [
            'is_blocked' => $isBlocked,
            'signals' => $signals,
            'reason' => $reason,
        ];
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
    
    /**
     * Fills name, description, price, and ISO 4217 currency from JSON-LD, microdata, and Open Graph product tags
     * when selection/CSS misses them.
     */
    private function applyStructuredDataEnrichment(): void
    {
        $sources = [];
        $ld = $this->extractProductFieldsFromJsonLd();
        if ($ld['name'] !== '' || $ld['description'] !== '' || $ld['price'] !== '' || $ld['currency'] !== '') {
            $sources[] = 'json-ld';
            if (($this->productData['name'] ?? '') === '' && $ld['name'] !== '') {
                $this->productData['name'] = $this->decodeHtmlText($ld['name']);
            }
            if (($this->productData['description'] ?? '') === '' && $ld['description'] !== '') {
                $this->productData['description'] = $this->cleanDescriptionPlain($ld['description']);
            }
            if (($this->productData['price'] ?? '') === '' && $ld['price'] !== '') {
                $this->productData['price'] = $this->formatNormalizedPrice($ld['price']);
            }
            if (($this->productData['currency'] ?? '') === '' && $ld['currency'] !== '') {
                $this->productData['currency'] = $this->normalizeToIso4217($ld['currency']);
            }
        }
        $md = $this->extractProductFieldsFromMicrodata();
        if ($md['price'] !== '' || $md['description'] !== '' || $md['currency'] !== '') {
            $sources[] = 'microdata';
            if (($this->productData['description'] ?? '') === '' && $md['description'] !== '') {
                $this->productData['description'] = $this->cleanDescriptionPlain($md['description']);
            }
            if (($this->productData['price'] ?? '') === '' && $md['price'] !== '') {
                $this->productData['price'] = $this->formatNormalizedPrice($md['price']);
            }
            if (($this->productData['currency'] ?? '') === '' && $md['currency'] !== '') {
                $this->productData['currency'] = $this->normalizeToIso4217($md['currency']);
            }
        }
        if (($this->productData['price'] ?? '') === '') {
            $ogp = $this->getMetaByProperty('og:price:amount');
            if ($ogp === '') {
                $ogp = $this->getMetaByProperty('product:price:amount');
            }
            if ($ogp !== '') {
                $sources[] = 'og:price';
                $this->productData['price'] = $this->formatNormalizedPrice($ogp);
            }
        }
        if (($this->productData['currency'] ?? '') === '') {
            $ogc = $this->getMetaByProperty('og:price:currency');
            if ($ogc === '') {
                $ogc = $this->getMetaByProperty('product:price:currency');
            }
            if ($ogc === '') {
                $ogc = $this->getMetaItemprop('priceCurrency');
            }
            if ($ogc !== '') {
                $sources[] = 'og:meta-currency';
                $this->productData['currency'] = $this->normalizeToIso4217($ogc);
            }
        }
        if (($this->productData['currency'] ?? '') === '' && isset($this->selectors['currency'])
            && is_string($this->selectors['currency']) && $this->selectors['currency'] !== '') {
            $cs = $this->extractText($this->selectors['currency']);
            if ($cs !== '') {
                $sources[] = 'selector:currency';
                $this->productData['currency'] = $this->normalizeToIso4217($cs);
            }
        }
        if (($this->productData['currency'] ?? '') === '') {
            $rawPrice = $this->extractText($this->selectors['price']);
            $cc = $this->inferCurrencyFromPriceText($rawPrice);
            if ($cc !== '') {
                $sources[] = 'price-text';
                $this->productData['currency'] = $cc;
            }
        }
        if ($sources !== []) {
            $ex = $this->productData['raw_data']['enrichment'] ?? [];
            if (!is_array($ex)) {
                $ex = [];
            }
            $this->productData['raw_data']['enrichment'] = array_values(array_unique(array_merge($ex, $sources)));
        }
    }
    
    /**
     * @return array{name: string, description: string, price: string, currency: string}
     */
    private function extractProductFieldsFromJsonLd(): array
    {
        $out = ['name' => '', 'description' => '', 'price' => '', 'currency' => ''];
        $scripts = $this->xpath->query('//script[@type="application/ld+json"]');
        if ($scripts === false) {
            return $out;
        }
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
            $this->mergeJsonLdProductFields($data, $out);
        }
        return $out;
    }
    
    /**
     * @param array<string, mixed> $data
     * @param array{name: string, description: string, price: string, currency: string} $out
     */
    private function mergeJsonLdProductFields(mixed $data, array &$out): void
    {
        if (!is_array($data)) {
            return;
        }
        if ($this->jsonLdIsProduct($data)) {
            if ($out['name'] === '' && !empty($data['name']) && is_string($data['name'])) {
                $out['name'] = $data['name'];
            }
            if (!empty($data['description']) && is_string($data['description'])) {
                $d = $data['description'];
                if (strlen($d) > strlen($out['description']) || $out['description'] === '') {
                    $out['description'] = $d;
                }
            }
            if ($out['price'] === '' && !empty($data['offers'])) {
                $p = $this->extractPriceFromJsonLdOffer($data['offers']);
                if ($p !== '') {
                    $out['price'] = $p;
                }
            }
            if ($out['currency'] === '' && !empty($data['offers'])) {
                $c = $this->jsonLdExtractOfferCurrency($data['offers']);
                if ($c !== '') {
                    $out['currency'] = $c;
                }
            }
        }
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $g) {
                $this->mergeJsonLdProductFields($g, $out);
            }
        }
        if (isset($data['mainEntity']) && is_array($data['mainEntity'])) {
            $this->mergeJsonLdProductFields($data['mainEntity'], $out);
        }
        if (isset($data['itemListElement']) && is_array($data['itemListElement'])) {
            foreach ($data['itemListElement'] as $el) {
                if (is_array($el) && isset($el['item'])) {
                    $this->mergeJsonLdProductFields($el['item'], $out);
                } else {
                    $this->mergeJsonLdProductFields($el, $out);
                }
            }
        }
    }
    
    /**
     * @param array<string, mixed> $data
     */
    private function jsonLdIsProduct(array $data): bool
    {
        if (!isset($data['@type'])) {
            return false;
        }
        $t = $data['@type'];
        $list = is_string($t) ? [$t] : (is_array($t) ? $t : []);
        foreach ($list as $one) {
            if (!is_string($one)) {
                continue;
            }
            if ($one === 'Product' || str_ends_with($one, 'Product') || $one === 'http://schema.org/Product' || $one === 'https://schema.org/Product') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Extract price from JSON-LD offer.
     *
     * @param mixed $offers
     * @return string
     */
    private function extractPriceFromJsonLdOffer(mixed $offers): string
    {
        if ($offers === null) {
            return '';
        }
        if (is_array($offers) && array_is_list($offers)) {
            foreach ($offers as $o) {
                $p = $this->extractPriceFromJsonLdOffer($o);
                if ($p !== '') {
                    return $p;
                }
            }
            return '';
        }
        if (!is_array($offers)) {
            return is_scalar($offers) ? (string) $offers : '';
        }
        if (isset($offers['lowPrice']) && (is_string($offers['lowPrice']) || is_int($offers['lowPrice']) || is_float($offers['lowPrice']))) {
            return (string) $offers['lowPrice'];
        }
        if (isset($offers['price'])) {
            $price = $offers['price'];
            if (is_string($price) || is_int($price) || is_float($price)) {
                return (string) $price;
            }
            if (is_array($price) && isset($price['value'])) {
                return (string) $price['value'];
            }
        }
        if (isset($offers['@type']) && is_string($offers['@type']) && $offers['@type'] === 'AggregateOffer' && isset($offers['lowPrice'])) {
            return (string) $offers['lowPrice'];
        }
        if (isset($offers['offers'])) {
            return $this->extractPriceFromJsonLdOffer($offers['offers']);
        }
        return '';
    }
    
    /**
     * Extract currency from JSON-LD offer.
     *
     * @param mixed $offers
     * @return string
     */
    private function jsonLdExtractOfferCurrency(mixed $offers): string
    {
        if ($offers === null) {
            return '';
        }
        if (is_array($offers) && array_is_list($offers)) {
            foreach ($offers as $o) {
                $c = $this->jsonLdExtractOfferCurrency($o);
                if ($c !== '') {
                    return $c;
                }
            }
            return '';
        }
        if (!is_array($offers)) {
            return '';
        }
        if (isset($offers['priceCurrency']) && (is_string($offers['priceCurrency']) || is_int($offers['priceCurrency']))) {
            return (string) $offers['priceCurrency'];
        }
        if (isset($offers['offers'])) {
            return $this->jsonLdExtractOfferCurrency($offers['offers']);
        }
        return '';
    }
    
    /**
     * @return array{price: string, description: string, currency: string}
     */
    private function extractProductFieldsFromMicrodata(): array
    {
        $out = ['price' => '', 'description' => '', 'currency' => ''];
        $n = $this->xpath->query("//*[contains(concat(' ', @itemprop, ' '), ' price ') or @itemprop='price']");
        if ($n && $n->length > 0) {
            for ($i = 0; $i < $n->length; $i++) {
                $node = $n->item($i);
                if ($node instanceof \DOMElement) {
                    $c = $node->getAttribute('content');
                    if ($c !== '') {
                        $out['price'] = $c;
                        break;
                    }
                    $c = trim($node->textContent);
                    if ($c !== '' && preg_match('/[0-9]/', $c)) {
                        $out['price'] = $c;
                        break;
                    }
                }
            }
        }
        if ($out['description'] === '') {
            $n = $this->xpath->query("//*[@itemprop='description']");
            if ($n && $n->length > 0) {
                $t = $n->item(0);
                if ($t !== null) {
                    $d = $t instanceof \DOMElement
                        ? (trim($t->getAttribute('content')) !== '' ? $t->getAttribute('content') : $t->textContent)
                        : $t->textContent;
                    $d = is_string($d) ? $d : '';
                    if ($d !== '') {
                        $out['description'] = $d;
                    }
                }
            }
        }
        $n = $this->xpath->query("//*[@itemprop='priceCurrency']");
        if ($n && $n->length > 0) {
            for ($i = 0; $i < $n->length; $i++) {
                $node = $n->item($i);
                if ($node instanceof \DOMElement) {
                    $c = $node->getAttribute('content');
                    if ($c === '') {
                        $c = trim($node->getAttribute('value'));
                    }
                    if ($c === '') {
                        $c = trim($node->textContent);
                    }
                    if ($c !== '') {
                        $out['currency'] = $c;
                        break;
                    }
                }
            }
        }
        return $out;
    }
    
    /**
     * Normalize to ISO 4217 currency code.
     *
     * @param string $raw
     * @return string
     */
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
    
    /**
     * Infer currency from price text.
     *
     * @param string $raw
     * @return string
     */
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

    /**
     * Build ISO code, symbol, and name maps from NumberToWords::allCurrency().
     */
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
                if ($symbol !== '') {
                    // Keep first mapping to avoid unstable overrides on shared symbols like "$".
                    if (!isset($symbolMap[$symbol])) {
                        $symbolMap[$symbol] = $iso;
                    }
                }
            }
        }

        uksort($symbolMap, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        self::$currencyCodeSet = $codeSet;
        self::$currencySymbolToCode = $symbolMap;
        self::$currencyNameToCode = $nameMap;
    }
    
    /**
     * Clean description plain.
     *
     * @param string $d
     * @return string
     */
    private function cleanDescriptionPlain(string $d): string
    {
        $d = strip_tags($d);
        $d = html_entity_decode($d, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $d = preg_replace('/\s+/', ' ', $d) ?? $d;
        return trim($d);
    }
    
    /**
     * @see extractPrice() uses the same normalization for numeric price strings
     */
    private function formatNormalizedPrice(string $price): string
    {
        if ($price === '') {
            return '';
        }
        $price = preg_replace('/[^0-9.,]/', '', $price) ?? '';
        if ($price === '') {
            return '';
        }
        if (strpos($price, ',') !== false && strpos($price, '.') === false) {
            $price = str_replace(',', '', $price);
        } elseif (strpos($price, ',') !== false && strpos($price, '.') !== false) {
            $price = str_replace(',', '', $price);
        } elseif (preg_match('/^\d+\.\d{3}$/', $price)) {
            $price = str_replace('.', '', $price);
        } elseif (strpos($price, '.') !== false) {
            $parts = explode('.', $price);
            if (count($parts) == 2) {
                $price = $parts[0] . '.' . str_pad($parts[1], 2, '0', STR_PAD_RIGHT);
            }
        }
        if (is_numeric($price) && strpos((string) $price, '.') === false) {
            $price = $price . '.00';
        }
        return trim((string) $price);
    }
    
    /**
     * Get meta itemprop.
     *
     * @param string $name
     * @return string
     */
    private function getMetaItemprop(string $name): string
    {
        $n = $this->xpath->query(
            "//meta[translate(@itemprop,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='"
            . strtolower($name) . "']/@content"
        );
        if ($n && $n->length > 0) {
            return trim($n->item(0)->nodeValue ?? '');
        }
        return '';
    }
    
    /**
     * Get meta by property.
     *
     * @param string $property
     * @return string
     */
    private function getMetaByProperty(string $property): string
    {
        $n = $this->xpath->query("//meta[translate(@property,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='" . strtolower($property) . "']/@content");
        if ($n && $n->length > 0) {
            $v = trim($n->item(0)->nodeValue ?? '');
            return $v;
        }
        
        return '';
    }
    
    /**
     * Decode HTML text.
     *
     * @param string $s
     * @return string
     */
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
     * Extract company name from the URL domain
     * @return string Company name or empty string if not found
     */
    private function extractCompany(): string
    {
        if (empty($this->url)) {
            return ''; 
        }

        // Parse host from URL (e.g., "www.aliexpress.com" or "shop.ebay.co.uk")
        $host = parse_url($this->url, PHP_URL_HOST);
        if (empty($host)) {
            return ''; 
        }

        // Remove "www." prefix if it exists
        $host = preg_replace('/^www\./i', '', $host);
        
        // Isolate the primary domain name part before the first dot
        $domainPart = explode('.', $host)[0] ?? '';

        // Fallback to capitalizing the first letter (e.g., "aliexpress" -> "Aliexpress")
        return ucfirst($domainPart);
    }
    
    /**
     * Extract product price from HTML
     * 
     * @return string Sanitized price or empty string if not found
     */
    private function extractPrice(): string
    {
        $price = $this->extractText($this->selectors['price']);
        if ($price === '') {
            return '';
        }
        return $this->formatNormalizedPrice($price);
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
     * ISO 4217 code when detected (e.g. USD, EUR), or empty string.
     */
    public function getCurrency(): string
    {
        return $this->productData['currency'] ?? '';
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
