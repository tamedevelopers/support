<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use Tamedevelopers\Support\Process\Concerns\RequestConcern;
use Tamedevelopers\Support\Process\Concerns\Response;

class PendingRequest extends RequestConcern
{
    /**
     * Execute HTTP request via cURL.
     */
    public function send(string $method, string $url, array $options = []): Response
    {
        $fullUrl = $this->buildUrl($url, $options['query'] ?? []);
        $attempts = 0;

        do {
            $attempts++;
            $response = $this->executeCurl($method, $fullUrl, $options['body'] ?? null);

            if ($response->successful() || $attempts >= $this->retryTimes) {
                return $response;
            }

            usleep($this->retrySleep * 1000);
        } while ($attempts < $this->retryTimes);

        return $response;
    }

    /**
     * Low-level cURL execution wrapper.
     */
    private function executeCurl(string $method, string $url, mixed $body): Response
    {
        $ch = curl_init();

        $curlOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
        ];

        // Format Request Headers
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }
        $curlOptions[CURLOPT_HTTPHEADER] = $headers;

        // Payload Handling
        if ($this->rawBody !== null) {
            $curlOptions[CURLOPT_POSTFIELDS] = $this->rawBody;
        } elseif ($body !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            if ($this->bodyFormat === 'json' && is_array($body)) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($body);
            } elseif ($this->bodyFormat === 'form' && is_array($body)) {
                $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($body);
            } else {
                $curlOptions[CURLOPT_POSTFIELDS] = $body;
            }
        }

        curl_setopt_array($ch, $curlOptions);

        $rawResponse = curl_exec($ch);
        $info = curl_getinfo($ch);
        $statusCode = (int) ($info['http_code'] ?? 500);

        if ($rawResponse === false) {
            $error = curl_error($ch);
            unset($ch);
            return new Response(500, [], json_encode(['error' => $error]), $info);
        }

        unset($ch);

        // Separate header and body
        $headerSize = $info['header_size'];
        $rawHeaders = substr((string) $rawResponse, 0, $headerSize);
        $responseBody = substr((string) $rawResponse, $headerSize);

        return new Response($statusCode, $this->parseHeaders($rawHeaders), $responseBody, $info);
    }

    /**
     * Parse raw cURL header string into associative array.
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        return $headers;
    }
}