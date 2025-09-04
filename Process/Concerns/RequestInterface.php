<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process\Concerns;

/**
 * Defines a lightweight, framework-agnostic HTTP request contract.
 */
interface RequestInterface
{
    /**
     * Get the HTTP method (e.g., GET, POST).
     * @return string
     */
    public static function method(): string;

    /**
     * Get the request URI
     * 
     * @return string
     */
    public static function url(): string;

    /**
     * Get the raw request URI (path + query string).
     * @return string
     */
    public static function uri(): string;

    /**
     * Get the request path without the query string.
     * @param string|null $path
     * @return string
     */
    public static function path($path = null): string;

    /**
     * Get Server Path
     * @return string
     */
    public static function server(): string;

    /**
     * Get Request Url
     * @return string
     */
    public static function request(): string;

    /**
     * Get Referral Url
     * @return string|null
     */
    public static function referral(): ?string;

    /**
     * Get URL HTTP
     * @return string
     */
    public static function http(): string;

    /**
     * Get URL Host
     * @return string
     */
    public static function host(): string;

    /**
     * Get URL Fullpath
     * @return string
     */
    public static function full(): string;

    /**
     * Retrieve a value from the query string or all query params.
     * @param string|int|null $key Key to retrieve, or null to get all.
     * @param mixed $default Default value if key is missing.
     * @return mixed
     */
    public static function query($key = null, $default = null);

    /**
     * Retrieve a value from POST body or all POST params.
     * @param string|int|null $key Key to retrieve, or null to get all.
     * @param mixed $default Default value if key is missing.
     * @return mixed
     */
    public static function post($key = null, $default = null);

    /**
     * Retrieve from merged input (POST first, then GET) or all inputs.
     * @param string|int|null $key Key to retrieve, or null to get all.
     * @param mixed $default Default value if key is missing.
     * @return mixed
     */
    public static function input($key = null, $default = null);

    /**
     * Get a specific HTTP header value (case-insensitive).
     * @param string $key Header name.
     * @param mixed $default Default value if missing.
     * @return mixed
     */
    public static function header(string $key, $default = null);

    /**
     * Get all HTTP headers as an associative array.
     * @return array<string, string>
     */
    public static function headers(): array;

    /**
     * Get a specific cookie value.
     * @param string $key Cookie name.
     * @param mixed $default Default value if missing.
     * @return mixed
     */
    public static function cookie(string $key, $default = null);

    /**
     * Get all cookies.
     * @return array<string, string>
     */
    public static function cookies(): array;

    /**
     * Attempt to determine the client IP address.
     * @return string|null
     */
    public static function ip(): ?string;

    /**
     * Determine if the request was made via AJAX.
     * @return bool
     */
    public static function isAjax(): bool;
}