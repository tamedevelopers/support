<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

use RuntimeException;
use Tamedevelopers\Support\ChromePdf\Internal\ChromiumStealthScript;

/**
 * Classic fetch: cURL downloads the first response; no JavaScript execution.
 */
final class DomWebScraperEngine implements WebScraperEngineInterface
{
    public function getName(): string
    {
        return 'dom';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function fetch(string $url, array $options = []): WebScraperFetchResult
    {
        $ch = curl_init();
        $userAgent = is_string($options['user_agent'] ?? null) && ($options['user_agent'] ?? '') !== ''
            ? (string) $options['user_agent']
            : ChromiumStealthScript::chromeUserAgent();

        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache',
            'Upgrade-Insecure-Requests: 1',
        ];
        $extraHeaders = $options['http_headers'] ?? null;
        if (is_array($extraHeaders) && $extraHeaders !== []) {
            /** @var list<string> $extraHeaders */
            $headers = array_merge($headers, $extraHeaders);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_TIMEOUT => (int) ($options['timeout'] ?? 8),
            CURLOPT_CONNECTTIMEOUT => (int) ($options['connect_timeout'] ?? 4),
            CURLOPT_SSL_VERIFYPEER => (bool) ($options['verify_ssl'] ?? false),
            CURLOPT_SSL_VERIFYHOST => ($options['verify_ssl'] ?? false) ? 2 : 0,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effective = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('cURL Error: ' . ($curlError !== '' ? $curlError : 'Request failed'));
        }

        $html = (string) $raw;
        if ($curlError !== '' && $html === '' && $httpCode === 0) {
            throw new RuntimeException('cURL Error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $challengeStatus = in_array($httpCode, [403, 429, 503], true);
            if (!$challengeStatus || $html === '') {
                throw new RuntimeException("HTTP Error: {$httpCode} - Failed to fetch {$url}");
            }
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
