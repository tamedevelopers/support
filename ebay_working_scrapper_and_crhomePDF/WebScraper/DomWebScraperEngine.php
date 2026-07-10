<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

use RuntimeException;

/**
 * Classic fetch: cURL downloads the first response; no JavaScript execution.
 */
final class DomWebScraperEngine implements WebScraperEngineInterface
{

    /**
     * Get the name of the engine.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'dom';
    }

    /**
     * Fetch the HTML from the URL.
     *
     * @param string $url
     * @param array $options
     * @return WebScraperFetchResult
     */
    public function fetch(string $url, array $options = []): WebScraperFetchResult
    {
        $ch = curl_init();
        $userAgent = is_string($options['user_agent'] ?? null) && ($options['user_agent'] ?? '') !== ''
            ? (string) $options['user_agent']
            : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_TIMEOUT => (int) ($options['timeout'] ?? 30),
            CURLOPT_CONNECTTIMEOUT => (int) ($options['connect_timeout'] ?? 15),
            CURLOPT_SSL_VERIFYPEER => (bool) ($options['verify_ssl'] ?? false),
            CURLOPT_SSL_VERIFYHOST => ($options['verify_ssl'] ?? false) ? 2 : 0,
            CURLOPT_ENCODING => '',
        ];

        // Production Fix: Automatically utilize proxy strings if passed to engine config
        if (!empty($options['proxy'])) {
            $curlOptions[CURLOPT_PROXY] = $options['proxy'];
        }

        curl_setopt_array($ch, $curlOptions);

        $extraHeaders = $options['http_headers'] ?? null;
        if (is_array($extraHeaders) && $extraHeaders !== []) {
            /** @var list<string> $extraHeaders */
            curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHeaders);
        }

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effective = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlError = curl_error($ch);
        unset($ch);

        if ($raw === false) {
            throw new RuntimeException('cURL Error: ' . ($curlError !== '' ? $curlError : 'Request failed'));
        }

        $html = (string) $raw;
        if ($curlError !== '' && $html === '' && $httpCode === 0) {
            throw new RuntimeException('cURL Error: ' . $curlError);
        }

        // Return the payload on Cloudflare 403 blocks instead of throwing 
        // to let the built-in block detector process it safely
        if ($httpCode !== 200 && !str_contains(strtolower($html), 'cloudflare')) {
            throw new RuntimeException("HTTP Error: {$httpCode} - Failed to fetch {$url}");
        }

        if ($html === '') {
            throw new RuntimeException("No content received from {$url}");
        }

        if ($effective === '') {
            $effective = $url;
        }

        return new WebScraperFetchResult($html, $effective, $httpCode, $this->getName());
    }
}
