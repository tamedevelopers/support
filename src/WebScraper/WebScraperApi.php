<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

/**
 * @property string|null $engine
 * @property array|null $apiConfig
 */
abstract class WebScraperApi
{
    /**
     * Configure ZenRows API Driver.
     * Free Tier: ~1,000 requests/month
     *
     * @param string $apiKey
     * @param bool $jsRender
     * @param bool $premiumProxy
     * @return $this
     */
    public function zenrowsApi(string $apiKey, bool $jsRender = true, bool $premiumProxy = true)
    {
        $this->engine    = 'zenrows';
        $this->apiConfig = [
            'provider' => 'zenrows',
            'endpoint' => 'https://api.zenrows.com/v1/',
            'key'      => $apiKey,
            'params'   => [
                'apikey'        => $apiKey,
                'js_render'     => $jsRender ? 'true' : 'false',
                'premium_proxy' => $premiumProxy ? 'true' : 'false',
            ],
        ];

        return $this;
    }

    /**
     * Configure ScraperAPI Driver.
     * Free Tier: ~5,000 requests/month
     *
     * @param string $apiKey
     * @param bool $renderJs
     * @param bool $premium
     * @param string|null $countryCode
     * @return $this
     */
    public function scraperApi(string $apiKey, bool $renderJs = true, bool $premium = false, ?string $countryCode = 'us')
    {
        $this->engine    = 'scraperapi';
        $this->apiConfig = [
            'provider' => 'scraperapi',
            'endpoint' => 'http://api.scraperapi.com',
            'key'      => $apiKey,
            'params'   => array_filter([
                'api_key'      => $apiKey,
                'render'       => $renderJs ? 'true' : 'false',
                'premium'      => $premium ? 'true' : 'false',
                'country_code' => $countryCode,
            ]),
        ];

        return $this;
    }

    /**
     * Configure ScrapingBee API Driver.
     * Free Tier: ~1,000 requests/month
     *
     * @param string $apiKey
     * @param bool $renderJs
     * @param bool $premiumProxy
     * @return $this
     */
    public function scrapingBeeApi(string $apiKey, bool $renderJs = true, bool $premiumProxy = false)
    {
        $this->engine    = 'scrapingbee';
        $this->apiConfig = [
            'provider' => 'scrapingbee',
            'endpoint' => 'https://app.scrapingbee.com/api/v1/',
            'key'      => $apiKey,
            'params'   => [
                'api_key'       => $apiKey,
                'render_js'     => $renderJs ? 'true' : 'false',
                'premium_proxy' => $premiumProxy ? 'true' : 'false',
            ],
        ];

        return $this;
    }

    /**
     * Configure ScraperAnt API Driver.
     * Free Tier: ~10,000 credits/month
     *
     * @param string $apiKey
     * @param bool $browser
     * @param string $proxyType 'residential'|'datacenter'
     * @return $this
     */
    public function scraperAntApi(string $apiKey, bool $browser = true, string $proxyType = 'datacenter')
    {
        $this->engine    = 'scraperant';
        $this->apiConfig = [
            'provider' => 'scraperant',
            'endpoint' => 'https://api.scraperant.com/v2/general',
            'key'      => $apiKey,
            'params'   => [
                'x-api-key'  => $apiKey,
                'browser'    => $browser ? 'true' : 'false',
                'proxy_type' => $proxyType,
            ],
        ];

        return $this;
    }
}
