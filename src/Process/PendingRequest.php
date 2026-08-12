<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use Illuminate\Http\Client\PendingRequest as LaravelPendingRequest;

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

        // Apply baseline configuration and non-blocking headers
        $this->withHeaders([
            'User-Agent'      => 'TamedevelopersHttp/1.0 (https://tamedevelopers.com; contact@tamedevelopers.com)',
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
}