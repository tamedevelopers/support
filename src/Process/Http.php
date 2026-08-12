<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use Tamedevelopers\Support\Process\Concerns\Response;
use Tamedevelopers\Support\Process\PendingRequest;

/**
 * Static Proxy for HTTP Requests (Laravel Style).
 *
 * @method static PendingRequest baseUrl(string $url)
 * @method static PendingRequest withHeaders(array $headers)
 * @method static PendingRequest withHeader(string $name, string $value)
 * @method static PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static PendingRequest withBasicAuth(string $username, string $password)
 * @method static PendingRequest asJson()
 * @method static PendingRequest asForm()
 * @method static PendingRequest asMultipart()
 * @method static PendingRequest attach(string $name, string $contents = '', ?string $filename = null, array $headers = [])
 * @method static PendingRequest withCookies(array $cookies, string $domain)
 * @method static PendingRequest timeout(int $seconds)
 * @method static PendingRequest connectTimeout(int $seconds)
 * @method static PendingRequest retry(int $times, int $sleepMilliseconds = 100)
 * @method static PendingRequest withoutVerifying()
 * @method static Response get(string $url, array|string $query = [])
 * @method static Response post(string $url, array|string $data = [])
 * @method static Response put(string $url, array|string $data = [])
 * @method static Response patch(string $url, array|string $data = [])
 * @method static Response delete(string $url, array|string $data = [])
 * @method static Response head(string $url, array|string $query = [])
 * @method static Response options(string $url, array|string $query = [])
 */
class Http
{
    /**
     * Create a new PendingRequest instance.
     */
    public static function new(): PendingRequest
    {
        return new PendingRequest();
    }

    /**
     * Dynamically proxy static calls to a new PendingRequest instance.
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        return self::new()->$method(...$parameters);
    }
}