<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

use GuzzleHttp\Client;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Time;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\GuzzleException;

/**
 * @see \Tamedevelopers\Support\WebScraper
 */
trait WebScraperEngineTrait
{
    
    /**
     * Sets fallback data when page fetching fails completely.
     *
     * @return void
     */
    protected function setDefaultFallbackData(): void
    {
        $fallbackSvg = $this->generateCompanyPlaceholderSvg();

        $this->data = [
            'sku'         => $this->generateSku(),
            'company'     => ucfirst($this->company),
            'url'         => $this->cleanUrl,
            'name'        => null,
            'description' => null,
            'main_image'  => $fallbackSvg,
            'images'      => [$fallbackSvg],
            'colors'      => [],
            'sizes'       => [],
            'qty'         => 1,
            'currency'    => null,
            'amount'      => null,
        ];
    }

    /**
     * Appends execution trace diagnostics and scraping status to the dataset.
     *
     * @return void
     */
    protected function attachFingerprint(): void
    {
        $blockSignals = [];

        if (empty($this->data['name'])) {
            $blockSignals[] = 'data:name_missing';
        }

        if (empty($this->data['description'])) {
            $blockSignals[] = 'data:description_missing';
        }

        if (empty($this->data['currency'])) {
            $blockSignals[] = 'data:currency_missing';
        }

        if (empty($this->data['amount'])) {
            $blockSignals[] = 'data:amount_missing';
        }

        if (empty($this->data['main_image']) && empty($this->data['images'])) {
            $blockSignals[] = 'data:images_missing';
        }

        if ($this->httpStatus === 0) {
            $blockSignals[] = 'http:status=0';
        }

        if ($this->fetchError) {
            $blockSignals[] = 'fetch:error';
        }

        $isBlocked = !empty($blockSignals);

        $this->data['scraped_at'] = (new Time)->format('Y-m-d H:i:s');
        $this->data['raw_data']   = [
            'engine'        => $this->engine,
            'final_url'     => $this->rawUrl,
            'http_status'   => $this->httpStatus,
            'blocked'       => $isBlocked,
            'block_signals' => $blockSignals,
            'block_reason'  => $this->fetchError ?? ($isBlocked ? 'Incomplete product payload extraction' : null),
            'fetch_error'   => $this->fetchError,
        ];
    }

    /**
     * Parses the HTML body using Json-LD, OpenGraph, and native DOM selectors.
     *
     * @param string $html Standard HTML markup string.
     * @return void
     */
    protected function parseHtml(string $html): void
    {
        $crawler = new Crawler($html);

        $jsonLdData = $this->extractJsonLd($crawler);
        $ogData     = $this->extractOpenGraph($crawler);
        $domImages  = $this->getNodeImages($crawler);

        // --- Image Normalization Logic ---
        $rawMainImage  = $jsonLdData['main_image'] ?? $ogData['image'] ?? ($domImages[0] ?? null);
        $rawImagesList = array_merge($jsonLdData['images'] ?? [], $domImages);

        if ($rawMainImage) {
            array_unshift($rawImagesList, $rawMainImage);
        }

        $cleanImages = [];
        $uniquePaths = [];

        foreach ($rawImagesList as $img) {
            $resolved = $this->resolveUrl($img);
            
            if ($resolved && $this->isValidImageUrl($resolved)) {
                // Remove all query parameters completely (&odnBg=..., ?w=..., etc.)
                $parts      = parse_url($resolved);
                $highResUrl = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . ($parts['path'] ?? '');

                // Normalize sizing paths like /fit-in/150x150/ or /500x500/ for deduplication
                $pathKey = preg_replace('/\/fit-in\/\d+x\d+\/|\/\d+x\d+\//i', '/', $parts['path'] ?? '');

                if (!in_array($pathKey, $uniquePaths, true)) {
                    $uniquePaths[] = $pathKey;
                    $cleanImages[] = $highResUrl;
                }
            }
        }

        // --- SVG Fallback if no images are extracted ---
        if (empty($cleanImages)) {
            $fallbackSvg   = $this->generateCompanyPlaceholderSvg();
            $cleanImages[] = $fallbackSvg;
        }

        // --- Amount & Currency Normalization ---
        $priceData = $this->extractPriceAndCurrency($crawler);
        
        $currency = $jsonLdData['currency'] 
            ?? $ogData['currency'] 
            ?? $priceData['currency'];

        $amount = $jsonLdData['price'] 
            ?? $ogData['price'] 
            ?? $priceData['amount'];

        // --- Color & Size Option Extraction ---
        $colors = $jsonLdData['colors'] ?? $this->extractSelectOptions($crawler, ['color', 'colour', 'style']);
        $sizes  = $jsonLdData['sizes']  ?? $this->extractSelectOptions($crawler, ['size', 'capacity']);

        $title       = $jsonLdData['name'] ?? $ogData['title'] ?? $this->getNodeText($crawler, '#productTitle') ?? $this->getNodeText($crawler, 'h1');
        $description = $jsonLdData['description'] ?? $ogData['description'] ?? $this->getNodeMeta($crawler, 'description');

        $this->data = [
            'sku'         => $jsonLdData['sku'] ?? $this->generateSku(),
            'company'     => ucfirst($this->company),
            'url'         => $this->cleanUrl,
            'name'        => trim($title ?? ''),
            'description' => trim(strip_tags($description ?? '')),
            'main_image'  => $cleanImages[0] ?? null,
            'images'      => array_values($cleanImages),
            'colors'      => array_values($colors),
            'sizes'       => array_values($sizes),
            'qty'         => 1,
            'currency'    => $currency,
            'amount'      => $amount,
        ];
    }

    /**
     * Extracts Schema.org Product data from embedded JSON-LD script elements.
     *
     * @param Crawler $crawler DomCrawler Instance.
     * @return array
     */
    protected function extractJsonLd(Crawler $crawler): array
    {
        $data = [];
        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$data) {
            $json = json_decode($node->text(), true);
            if (!$json) {
                return;
            }

            $items = isset($json['@graph']) ? $json['@graph'] : [$json];

            foreach ($items as $item) {
                if (isset($item['@type']) && (is_string($item['@type']) ? $item['@type'] === 'Product' : in_array('Product', (array)$item['@type'], true))) {
                    $data['name']        = $item['name'] ?? null;
                    $data['description'] = $item['description'] ?? null;
                    $data['sku']         = $item['sku'] ?? $item['productID'] ?? null;

                    if (isset($item['image'])) {
                        $imgs = is_array($item['image']) ? $item['image'] : [$item['image']];
                        $extractedImages = [];

                        foreach ($imgs as $img) {
                            $url = is_array($img) ? ($img['contentUrl'] ?? $img['url'] ?? null) : $img;
                            if (is_string($url) && $this->isValidImageUrl($url)) {
                                $extractedImages[] = $url;
                            }
                        }

                        if (!empty($extractedImages)) {
                            $data['main_image'] = $extractedImages[0];
                            $data['images']     = $extractedImages;
                        }
                    }

                    if (isset($item['offers'])) {
                        $offers           = isset($item['offers'][0]) ? $item['offers'][0] : $item['offers'];
                        $data['price']    = $offers['price'] ?? $offers['lowPrice'] ?? null;
                        $data['currency'] = $offers['priceCurrency'] ?? null;
                    }
                    break;
                }
            }
        });

        return $data;
    }

    /**
     * Extracts meta property tags from HTML header (OpenGraph protocol).
     *
     * @param Crawler $crawler DomCrawler Instance.
     * @return array
     */
    protected function extractOpenGraph(Crawler $crawler): array
    {
        return [
            'title'       => $this->getNodeMeta($crawler, 'og:title', 'property'),
            'description' => $this->getNodeMeta($crawler, 'og:description', 'property'),
            'image'       => $this->getNodeMeta($crawler, 'og:image', 'property'),
            'price'       => $this->getNodeMeta($crawler, 'product:price:amount', 'property'),
            'currency'    => $this->getNodeMeta($crawler, 'product:price:currency', 'property'),
        ];
    }

    /**
     * Executes HTTP request with Guzzle client using custom Headers & Proxy support.
     *
     * @param string $url Target URL.
     * @return string Raw HTML payload.
     * @throws GuzzleException
     */
    protected function fetchViaGuzzle(string $url): string
    {
        $options = [
            'timeout' => 15,
            'verify'  => false,
            'headers' => [
                'User-Agent'                => $this->userAgents[array_rand($this->userAgents)],
                'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language'           => 'en-US,en;q=0.9',
                'Cache-Control'             => 'no-cache',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest'            => 'document',
                'Sec-Fetch-Mode'            => 'navigate',
                'Sec-Fetch-Site'            => 'none',
                'Sec-Fetch-User'            => '?1',
            ],
        ];

        if ($this->proxy) {
            $options['proxy'] = $this->proxy;
        }

        $client   = new Client();
        $response = $client->get($url, $options);
        
        $this->httpStatus = $response->getStatusCode();

        return (string) $response->getBody();
    }

    /**
     * Unified HTTP Handler for anti-bot scraping APIs.
     *
     * @param string $url Target product URL.
     * @return string Raw HTML payload.
     */
    protected function fetchViaApiDriver(string $url): string
    {
        $provider = $this->apiConfig['provider'] ?? 'generic';
        $endpoint = $this->apiConfig['endpoint'];
        $queryParams = $this->apiConfig['params'] ?? [];

        // Attach requested target URL
        $queryParams['url'] = $url;

        $client = new Client([
            'verify'          => false,
            'timeout'         => 15,
            'connect_timeout' => 15,
        ]);

        $response = $client->get($endpoint, [
            'query' => $queryParams,
        ]);

        $this->httpStatus = $response->getStatusCode();

        return (string) $response->getBody();
    }

    /**
     * Executes headless browser fallback if configured manually.
     *
     * @param string $url Target URL.
     * @return mixed Raw HTML payload.
     */
    protected function fetchViaHeadlessBrowser(string $url)
    {
        throw new \RuntimeException('Local headless browser strategy is not configured.');
    }

    /**
     * Parses company host from raw URL string.
     *
     * @param string $url Target URL.
     * @return string Parsed brand name.
     */
    protected function extractCompanyDomain(string $url)
    {
        $host  = parse_url($url, PHP_URL_HOST) ?? '';
        $host  = preg_replace('/^www\./', '', $host);
        $parts = explode('.', $host);
        return $parts[0] ?? 'unknown';
    }

    /**
     * Scrapes form options/buttons corresponding to select attributes (colors, sizes).
     *
     * @param Crawler $crawler DomCrawler instance.
     * @param array<int, string> $keywords Matching field attributes.
     * @return array
     */
    protected function extractSelectOptions(Crawler $crawler, array $keywords): array
    {
        $options = [];
        
        foreach ($keywords as $keyword) {
            $lower   = strtolower($keyword);
            $ucfirst = ucfirst($lower);

            // 1. Direct <select> elements matching keywords
            $selectSelector = "select[name*='{$lower}'], select[name*='{$ucfirst}'], select[id*='{$lower}'], select[id*='{$ucfirst}']";
            $crawler->filter($selectSelector)->each(function (Crawler $node) use (&$options) {
                $node->filter('option')->each(function (Crawler $opt) use (&$options) {
                    $text = trim($opt->text());
                    if ($text !== '' && !preg_match('/(select|choose|pick)/i', $text)) {
                        $options[] = $text;
                    }
                });
            });

            // 2. Amazon & E-commerce Swatches (excluding carousel pagination & arrow controls)
            $swatchSelector = implode(', ', [
                "[id*='variation_{$lower}'] li span.a-button-text",
                "[id*='variation_{$ucfirst}'] li span.a-button-text",
                "[id*='twister'] [id*='{$lower}'] li span.a-button-text",
                "[id*='twister'] [id*='{$ucfirst}'] li span.a-button-text",
                "[class*='swatch'][class*='{$lower}']",
                "[class*='swatch'][class*='{$ucfirst}']",
            ]);

            // Fallback selector if span.a-button-text isn't found
            $nodes = $crawler->filter($swatchSelector);
            if ($nodes->count() === 0) {
                $swatchSelector = "[id*='variation_{$lower}'] li, [id*='twister'] [id*='{$lower}'] li, [class*='swatch'][class*='{$lower}']";
                $nodes = $crawler->filter($swatchSelector);
            }

            $nodes->each(function (Crawler $node) use (&$options) {
                // Prefer title or data-value attribute if present, else fallback to text
                $rawText = $node->attr('title') ?? $node->attr('data-value') ?? $node->text();

                $cleanText = trim(preg_replace('/\s+/', ' ', strtok($rawText, "\n")));
                
                // Filter out pagination arrows, single digits, and standard carousel noise
                if (
                    $cleanText !== '' 
                    && strlen($cleanText) > 1 
                    && strlen($cleanText) < 30 
                    && !preg_match('/[←→‹›«»]|^(select|choose|click|\d+)$/iu', $cleanText)
                ) {
                    $options[] = $cleanText;
                }
            });
        }

        return array_values(array_unique(array_filter($options)));
    }

    /**
     * Extracts currency and price from DOM when JSON-LD and OpenGraph fail.
     *
     * @param Crawler $crawler
     * @return array{currency: ?string, amount: ?float}
     */
    protected function extractPriceAndCurrency(Crawler $crawler): array
    {
        $price = null;
        $currency = null;

        // 1. Check Meta tags (e.g., twitter:data1, product:price:amount)
        $metaPrice = $this->getNodeMeta($crawler, 'product:price:amount') 
            ?? $this->getNodeMeta($crawler, 'twitter:data1');
        
        $metaCurrency = $this->getNodeMeta($crawler, 'product:price:currency') 
            ?? $this->getNodeMeta($crawler, 'og:price:currency');

        if ($metaPrice) {
            $price = (float) preg_replace('/[^\d.]/', '', $metaPrice);
        }

        if ($metaCurrency) {
            $currency = strtoupper(trim($metaCurrency));
        }

        // 2. Fallback: Parse common Amazon/E-commerce price DOM selectors
        if (!$price) {
            $priceSelectors = [
                // Amazon & Global
                '.a-price .a-offscreen',
                '#priceblock_ourprice',
                '#priceblock_dealprice',
                '.priceToPay .a-offscreen',
                '#price_inside_buybox',
                '[data-asin-price]',
                // Jumia, Jiji & AliExpress
                '.-b.-ltr.-nn.-pxs',
                '.-b.-primary.-fs24',
                '.qa-advert-price',
                '.product-price-current',
                '[data-pl="product-price"]',
                // Generic E-commerce
                '.product-price',
                '.price',
                '[itemprop="price"]'
            ];

            foreach ($priceSelectors as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $rawPrice = $crawler->filter($selector)->first()->text();

                    // Infer currency if meta tags didn't provide one
                    if (!$currency) {
                        $currency = $this->inferCurrencyFromPriceText($rawPrice);
                    }
                    
                    // Parse numerical float (handles both "1,299.99" and "1.299,99" formats)
                    if (preg_match('/[\d,.]+/u', $rawPrice, $matches)) {
                        $cleanNum = $matches[0];

                        // If European format (e.g. 1.299,99), convert to standard float (1299.99)
                        if (preg_match('/^\d{1,3}(\.\d{3})*,\d{2}$/', $cleanNum)) {
                            $cleanNum = str_replace(['.', ','], ['', '.'], $cleanNum);
                        } else {
                            $cleanNum = str_replace(',', '', $cleanNum);
                        }

                        $price = (float) $cleanNum;
                        if ($price > 0) {
                            break;
                        }
                    }
                }
            }
        }

        return [
            'currency' => $currency, // Defaults to null if not detected
            'amount'   => $price,
        ];
    }

    /**
     * Infer currency code from raw price text.
     *
     * @param string $raw
     * @return ?string Returns currency code or null if undetected
     */
    private function inferCurrencyFromPriceText(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        // Specific Multi-Character Codes / Symbols
        if (str_contains($raw, 'A$') || str_contains($raw, 'AU$') || str_contains($raw, 'AUD')) {
            return 'AUD';
        }
        if (str_contains($raw, 'C$') || str_contains($raw, 'CA$') || str_contains($raw, 'CAD')) {
            return 'CAD';
        }
        if (preg_match('/^\s*R\$/u', $raw) || str_contains($raw, 'BRL')) {
            return 'BRL';
        }
        if (str_contains($raw, '¥')) {
            if (str_contains($raw, '元') || str_contains($raw, 'CNY') || str_contains($raw, 'RMB') || str_contains($raw, '人民币')) {
                return 'CNY';
            }
            return 'JPY';
        }

        // Nigerian Naira (Jumia / Jiji)
        if (preg_match('/\b(NGN|₦)\b/u', $raw) || str_contains($raw, '₦')) {
            return 'NGN';
        }

        // Indian Rupee
        if (preg_match('/^\s*₹/u', $raw) || (str_contains($raw, '₹') && !preg_match('/\b(USD|EUR|GBP)\b/i', $raw))) {
            return 'INR';
        }

        // Euro
        if (str_contains($raw, '€') || preg_match('/\bEUR\b/i', $raw)) {
            return 'EUR';
        }

        // British Pound
        if (str_contains($raw, '£') || preg_match('/\bGBP\b/i', $raw)) {
            return 'GBP';
        }

        // US Dollar (matches $, US$, USD while preventing false positives from AUD/CAD)
        if ((str_contains($raw, '$') || preg_match('/\bUSD\b/i', $raw)) && !str_contains($raw, 'A$') && !str_contains($raw, 'C$')) {
            return 'USD';
        }

        return null;
    }

    /**
     * Safely reads plain text from a CSS selector.
     *
     * @param Crawler $crawler DomCrawler instance.
     * @param string $selector CSS Selector string.
     * @return string|null
     */
    protected function getNodeText(Crawler $crawler, string $selector): ?string
    {
        try {
            return $crawler->filter($selector)->first()->text();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Safely reads content attribute from a `<meta>` tag.
     *
     * @param Crawler $crawler DomCrawler instance.
     * @param string $value Meta tag key value.
     * @param string $attr Attribute name ('name' or 'property').
     * @return string|null
     */
    protected function getNodeMeta(Crawler $crawler, string $value, string $attr = 'name'): ?string
    {
        try {
            return $crawler->filter("meta[{$attr}='{$value}']")->first()->attr('content');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Safely reads an attribute from an HTML node.
     *
     * @param Crawler $crawler DomCrawler instance.
     * @param string $selector CSS Selector string.
     * @param string $attr Attribute name (e.g., 'src', 'href').
     * @return string|null
     */
    protected function getNodeAttribute(Crawler $crawler, string $selector, string $attr): ?string
    {
        try {
            return $crawler->filter($selector)->first()->attr($attr);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extracts array of product image URLs found in the DOM.
     *
     * @param Crawler $crawler DomCrawler instance.
     * @return array<int, string>
     */
    protected function getNodeImages(Crawler $crawler): array
    {
        $images = [];

        // Scoped and platform-specific selectors (Jumia, Jiji, AliExpress, Shopify, Amazon)
        $selectors = [
            '#slide-container img', '#slide-container a',
            '.-gallery img', '.-gallery a', '.image-gallery img',
            '[data-testid="product-images"] img', '[data-testid="media-thumbnail"] img',
            '.qa-image-block img', '.b-slider img', '.b-slider__item img',
            '.images-view-item img', '.pdp-mod-product-badge-wrapper img',
            '.product-single__photos img', '.product-gallery img',
            '#slider img', '#product-gallery img', 'main img', '.b-slider-image__wrapper'
        ];

        foreach ($selectors as $selector) {
            try {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$images) {
                    $candidates = [
                        $node->attr('data-full-src'),
                        $node->attr('data-zoom-image'),
                        $node->attr('data-large-image'),
                        $node->attr('data-image-src'),
                        $node->attr('data-original'),
                        $node->attr('data-original-src'),
                        $node->attr('data-lazy-src'),
                        $node->attr('data-fsrc'),
                        $node->attr('data-image'),
                        $node->attr('data-src'),
                        $node->attr('data-lazy'),
                        $node->attr('srcset'),
                        $node->attr('data-srcset'),
                        $node->attr('src'),
                        $node->attr('href'),
                    ];

                    foreach ($candidates as $src) {
                        if (!$src) {
                            continue;
                        }

                        // Extract primary URL from srcset if present
                        if (str_contains($src, ',')) {
                            $parts = explode(',', $src);
                            $src = trim(explode(' ', trim($parts[0]))[0]);
                        }

                        $resolved = $this->resolveUrl($src);
                        if ($resolved && $this->isValidImageUrl($resolved)) {
                            $images[] = $resolved;
                            break;
                        }
                    }
                });
            } catch (\Throwable $e) {
                continue;
            }

            if (count($images) >= 8) {
                break;
            }
        }

        return array_values(array_unique($images));
    }

    /**
     * Strict validator to ensure strings are actual image URLs.
     *
     * @param string|null $url
     * @return bool
     */
    protected function isValidImageUrl(?string $url): bool
    {
        if (!$url || !is_string($url)) {
            return false;
        }

        // 1. Must start with http, relative protocol //, or root-relative path /
        if (!preg_match('/^(https?:\/\/|\/\/|\/)/i', $url)) {
            return false;
        }

        // 2. Reject non-image strings or objects embedded in schema text
        if (str_contains($url, 'ImageObject') || str_contains($url, ' ')) {
            return false;
        }

        // 3. Blacklist pattern filtering for icons, UI badges, security seals, and cross-sell keyword markers
        $blacklistPattern = '/(privacy-choices|logo|icon|badge|avatar|banner|payment|trust|sprite|loader|spinner|placeholder|apple-tv|subscription|star-rating)/i';
        if (preg_match($blacklistPattern, $url)) {
            return false;
        }

        // 4. Single-pass host check using regex instead of looping
        $parsedHost = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (!empty($parsedHost)) {
            $company = strtolower($this->company ?? '');
            
            // Single regex matching company name OR trusted multi-tenant CDNs
            $cdnPattern = '/(scene7|cloudinary|imgix|shopify|ssl-images-amazon|media-amazon|akamaized|cloudfront|fastly|fbcdn)/i';

            $isCompanyHost = !empty($company) && str_contains($parsedHost, $company);
            
            if (!$isCompanyHost && !preg_match($cdnPattern, $parsedHost)) {
                return false;
            }
        }

        // 5. Must have valid image extension or e-commerce image path signatures
        return (bool) preg_match('/\.(jpg|jpeg|png|webp)(\?.*)?$/i', $url) 
            || Str::contains($url, ['/seo/', '/asr/', '/product/', '/fit-in/']);
    }

    /**
     * Generate a standalone SVG placeholder string using the company or brand name.
     *
     * @param string|null $text
     * @return string
     */
    protected function generateCompanyPlaceholderSvg(?string $text = null): string
    {
        return Tame::svgPlaceholder($text ?? $this->company ?? 'Store');
    }

    /**
     * Converts relative image URLs into absolute fully-qualified URLs.
     *
     * @param string|null $url Rel or Abs URL.
     * @return string|null Absolute URL string.
     */
    protected function resolveUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $parse = parse_url($this->rawUrl);
            $base = ($parse['scheme'] ?? 'https') . '://' . ($parse['host'] ?? '');
            return rtrim($base, '/') . '/' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Generates a fallback SKU code based on company name and a random integer.
     *
     * @return string
     */
    protected function generateSku(): string
    {
        return strtoupper(substr($this->company, 0, 3)) . '-' . rand(100000, 999999);
    }
    
}
