<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use Illuminate\Http\Client\PendingRequest as LaravelPendingRequest;
use Tamedevelopers\Support\Process\HttpRequest;

/**
 * @see \Tamedevelopers\Support\Process\HttpRequest
 */
class PendingRequest extends LaravelPendingRequest
{
    /**
     * Create a new PendingRequest instance with default custom headers.
     *
     * @param  \Illuminate\Http\Client\Factory|null  $factory
     * @param  array  $middleware
     */
    public function __construct($factory = null, $middleware = [])
    {
        parent::__construct($factory, $middleware);
        
        $this->applyBaselineWithHeaders();
    }

    /**
     * Specify the User-Agent header for the request.
     *
     * @param  string|bool  $userAgent
     * @return $this
     */
    public function withUserAgent($userAgent)
    {
        if (is_string($userAgent)) {
            return $this->withHeader('User-Agent', $userAgent);
        }

        // Remove User-Agent header if explicitly set to false
        if ($userAgent === false) {
            return $this->withHeader('User-Agent', '');
        }

        return $this;
    }

    /**
     * Apply baseline configuration and non-blocking headers
     */
    private function applyBaselineWithHeaders(): void
    {
        $this->withHeaders([
            'User-Agent'      => 'TamedevelopersHttp/1.0 (https://example.com; contact@example.com)',
            'Accept'          => 'application/json, text/plain, */*',
            'Accept-Encoding' => 'gzip, deflate',
        ])->withOptions([
            'connect_timeout' => 10,
            'curl' => [
                CURLOPT_FORBID_REUSE  => false,
                CURLOPT_FRESH_CONNECT => false,
                CURLOPT_TCP_KEEPALIVE => 1,
            ],
        ]);
    }

    /**
     * Dynamically proxy non-static calls instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters = [])
    {
        if (self::isHttpRequestMethod($method)) {
            return self::initHttpRequest($method, $parameters);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Dynamically proxy static calls instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters = [])
    {
        if (self::isHttpRequestMethod($method)) {
            return self::initHttpRequest($method, $parameters);
        }

        return parent::__callStatic($method, $parameters);
    }

    /**
     * Get instance of custom HttpRequest service.
     *
     * @return \Tamedevelopers\Support\Process\HttpRequest
     */
    private static function request()
    {
        return new HttpRequest();
    }

    /**
     * Check if Request Method Exists
     */
    private static function isHttpRequestMethod(?string $method = null): bool
    {
        return method_exists(static::request(), $method);
    }

    /**
     * init Http Request
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    private static function initHttpRequest($method, $parameters)
    {
        return static::request()->$method(...$parameters);
    }

}