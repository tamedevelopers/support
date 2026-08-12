<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process\Concerns;

use Tamedevelopers\Support\Process\Concerns\Response;

abstract class RequestConcern
{
    protected string $baseUrl = '';
    protected array $headers = [];
    protected array $options = [];
    protected array $cookies = [];
    protected array $multipart = [];
    protected string $bodyFormat = 'json'; // 'json', 'form', 'multipart'
    protected ?string $rawBody = null;
    protected int $timeout = 30;
    protected int $connectTimeout = 10;
    protected bool $verifySsl = true;
    protected int $retryTimes = 1;
    protected int $retrySleep = 100;

    /**
     * Set base URL for requests.
     */
    public function baseUrl(string $url): static
    {
        $this->baseUrl = rtrim($url, '/');
        return $this;
    }

    /**
     * Add multiple headers.
     */
    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Add a single header.
     */
    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Add an Authorization header token.
     */
    public function withToken(string $token, string $type = 'Bearer'): static
    {
        return $this->withHeader('Authorization', trim("{$type} {$token}"));
    }

    /**
     * Add Basic Authentication credentials.
     */
    public function withBasicAuth(string $username, string $password): static
    {
        return $this->withHeader('Authorization', 'Basic ' . base64_encode("{$username}:{$password}"));
    }

    /**
     * Request payload as JSON (`application/json`).
     */
    public function asJson(): static
    {
        $this->bodyFormat = 'json';
        return $this->withHeader('Content-Type', 'application/json');
    }

    /**
     * Request payload as Form URL Encoded (`application/x-www-form-urlencoded`).
     */
    public function asForm(): static
    {
        $this->bodyFormat = 'form';
        return $this->withHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Request payload as Multipart Form (`multipart/form-data`).
     */
    public function asMultipart(): static
    {
        $this->bodyFormat = 'multipart';
        return $this;
    }

    /**
     * Attach a raw body string directly.
     */
    public function withBody(string $content, string $contentType = 'text/plain'): static
    {
        $this->rawBody = $content;
        return $this->withHeader('Content-Type', $contentType);
    }

    /**
     * Attach a file to the multipart payload.
     */
    public function attach(string $name, string $contents = '', ?string $filename = null, array $headers = []): static
    {
        $this->asMultipart();
        $this->multipart[] = [
            'name'     => $name,
            'contents' => $contents,
            'filename' => $filename,
            'headers'  => $headers,
        ];
        return $this;
    }

    /**
     * Add cookies to the request.
     */
    public function withCookies(array $cookies, string $domain): static
    {
        $this->cookies = array_merge($this->cookies, [
            'domain' => $domain,
            'data'   => $cookies,
        ]);
        return $this;
    }

    /**
     * Set request timeout in seconds.
     */
    public function timeout(int $seconds): static
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set connection timeout in seconds.
     */
    public function connectTimeout(int $seconds): static
    {
        $this->connectTimeout = $seconds;
        return $this;
    }

    /**
     * Disable SSL verification.
     */
    public function withoutVerifying(): static
    {
        $this->verifySsl = false;
        return $this;
    }

    /**
     * Set retry strategy on failure.
     */
    public function retry(int $times, int $sleepMilliseconds = 100): static
    {
        $this->retryTimes = max(1, $times);
        $this->retrySleep = $sleepMilliseconds;
        return $this;
    }

    /**
     * Execute GET request.
     */
    public function get(string $url, array|string $query = []): Response
    {
        return $this->send('GET', $url, ['query' => $query]);
    }

    /**
     * Execute POST request.
     */
    public function post(string $url, array|string $data = []): Response
    {
        return $this->send('POST', $url, ['body' => $data]);
    }

    /**
     * Execute PUT request.
     */
    public function put(string $url, array|string $data = []): Response
    {
        return $this->send('PUT', $url, ['body' => $data]);
    }

    /**
     * Execute PATCH request.
     */
    public function patch(string $url, array|string $data = []): Response
    {
        return $this->send('PATCH', $url, ['body' => $data]);
    }

    /**
     * Execute DELETE request.
     */
    public function delete(string $url, array|string $data = []): Response
    {
        return $this->send('DELETE', $url, ['body' => $data]);
    }

    /**
     * Execute HEAD request.
     */
    public function head(string $url, array|string $query = []): Response
    {
        return $this->send('HEAD', $url, ['query' => $query]);
    }

    /**
     * Execute OPTIONS request.
     */
    public function options(string $url, array|string $query = []): Response
    {
        return $this->send('OPTIONS', $url, ['query' => $query]);
    }

    /**
     * Build full URL with query parameters.
     */
    protected function buildUrl(string $url, array|string $query = []): string
    {
        $fullUrl = $this->baseUrl !== '' ? $this->baseUrl . '/' . ltrim($url, '/') : $url;

        if (!empty($query)) {
            $queryString = is_array($query) ? http_build_query($query) : $query;
            $fullUrl .= (str_contains($fullUrl, '?') ? '&' : '?') . $queryString;
        }

        return $fullUrl;
    }

    /**
     * Send the request (cURL / Native Stream implementation hook).
     */
    abstract public function send(string $method, string $url, array $options = []): Response;
    
}