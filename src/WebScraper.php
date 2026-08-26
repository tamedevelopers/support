<?php

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Collections\Collection;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\WebScraper\WebScraperApi;
use Tamedevelopers\Support\WebScraper\WebScraperEngineTrait;

/**
 * Class WebScraper
 *
 * High-performance, anti-bot resilient web scraper for e-commerce product pages.
 * Supports HTML parsing via DOMCrawler and JS/Cloudflare bypass via Headless engines and proxies.
 *
 * @package App\Services
 */
class WebScraper extends WebScraperApi
{
    use WebScraperEngineTrait;

    /**
     * Target raw URL requested by the user.
     *
     * @var string|null
     */
    protected ?string $rawUrl = null;

    /**
     * Cleaned target URL stripped of query string parameters.
     *
     * @var string|null
     */
    protected ?string $cleanUrl = null;

    /**
     * Extracted brand/company name derived from domain.
     *
     * @var string
     */
    protected string $company;

    /**
     * Scraping engine strategy ('dom' or 'chromium').
     *
     * @var string
     */
    protected string $engine = 'dom';

    /**
     * Parsed e-commerce product payload.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Proxy connection configuration.
     *
     * @var string|null
     */
    protected ?string $proxy = null;

    /**
     * Last HTTP response status code.
     *
     * @var int
     */
    protected int $httpStatus = 0;

    /**
     * Error message captured during HTTP requests or anti-bot fetch.
     *
     * @var string|null
     */
    protected ?string $fetchError = null;

    /**
     * Third-party scraping API configuration.
     *
     * @var array|null
     */
    protected ?array $apiConfig = null;

    /**
     * List of realistic user-agent strings for browser impersonation.
     *
     * @var array<int, string>
     */
    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
    ];

    /**
     * WebScraper Constructor.
     *
     * @param string|null $url The target page URL to scrape.
     */
    public function __construct($url = null)
    {
        if (!$this->rawUrl && !empty($url)) {
            $this->rawUrl   = Str::trim($url);
            $this->cleanUrl = strtok($this->rawUrl, '?') ?: $this->rawUrl;
            $this->company  = $this->extractCompanyDomain($this->rawUrl);
            $this->proxy    = Server::config('services.scraper.proxy_url');
        }
    }

    /**
     * Instantiates the WebScraper instance fluently.
     *
     * @param string $url Target product URL.
     * @return static
     */
    public static function url(string $url)
    {
        return new static($url);
    }

    /**
     * Set the scraping execution engine.
     *
     * @param 'dom'|'chromium' $engine
     * @return $this
     */
    public function setEngine(string $engine)
    {
        $this->engine = strtolower($engine);
        return $this;
    }

    /**
     * Explicitly pass a proxy URL for anti-bot bypass.
     *
     * @param string $proxy Example: "http://user:pass@123.45.67.89:8080"
     * @return $this
     */
    public function setProxy(string $proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * Executes the HTTP request, handles fallbacks, and triggers DOM extraction.
     *
     * @return $this
     */
    public function fetch()
    {
        try {
            if (!empty($this->apiConfig)) {
                $html = $this->fetchViaApiDriver($this->rawUrl);
            } elseif (in_array($this->engine, ['headless', 'chrome', 'chromium'])) {
                $html = $this->fetchViaHeadlessBrowser($this->rawUrl);
            } else {
                $html = $this->fetchViaGuzzle($this->rawUrl);
            }
        } catch (\Throwable $e) {
            $this->fetchError = $e->getMessage();
            $html = '';
        }

        if (!empty($html)) {
            $this->parseHtml($html);
        } else {
            $this->setDefaultFallbackData();
        }

        // Attach audit fingerprint metadata payload
        $this->attachFingerprint();

        return $this;
    }

    /**
     * Retrieves extracted product dataset array.
     * 
     * @return \Tamedevelopers\Support\Collections\Collection
     */
    public function getData()
    {
        return new Collection($this->data);
    }
    
    /**
     * Get specific field from scraped data
     * 
     * @param string $field Field name (name, price, description, colors, sizes)
     * @return mixed Field value or null if not found
     */
    public function getField(string $field)
    {
        return $this->data[$field] ?? null;
    }
    
    /**
     * Get product name
     */
    public function getName(): string
    {
        return $this->data['name'] ?? '';
    }
    
    /**
     * Get product price
     */
    public function getPrice(): string
    {
        return $this->data['price'] ?? '';
    }
    
    /**
     * ISO 4217 code when detected (e.g. USD, EUR), or empty string.
     */
    public function getCurrency(): string
    {
        return $this->data['currency'] ?? '';
    }
    
    /**
     * Get product description
     */
    public function getDescription(): string
    {
        return $this->data['description'] ?? '';
    }
    
    /**
     * Get product colors
     */
    public function getColors(): array
    {
        return $this->data['colors'] ?? [];
    }
    
    /**
     * Get product sizes
     */
    public function getSizes(): array
    {
        return $this->v['sizes'] ?? [];
    }

    /**
     * Get product images
     * 
     * @return array List of image URLs
     */
    public function getImages(): array
    {
        return $this->data['images'] ?? [];
    }
    
    /**
     * Get main product image
     */
    public function getMainImage(): string
    {
        return $this->data['main_image'] ?? '';
    }
    
    /**
     * Get scraped data as JSON string
     * 
     * @param bool $prettyPrint Pretty print JSON
     * @return string JSON encoded data
     */
    public function toJson(bool $prettyPrint = true)
    {
        $flags = $prettyPrint ? JSON_PRETTY_PRINT : 0;

        return json_encode($this->data, $flags);
    }

}