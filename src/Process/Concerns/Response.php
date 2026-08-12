<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process\Concerns;

use ArrayAccess;
use JsonSerializable;
use Stringable;

class Response implements ArrayAccess, JsonSerializable, Stringable
{
    /**
     * @param int $status HTTP status code.
     * @param array<string, array<string>>|array<string, string> $headers Response headers.
     * @param string $body Raw response body.
     * @param array $info Additional metadata (e.g., cURL info).
     */
    public function __construct(
        protected int $status,
        protected array $headers,
        protected string $body,
        protected array $info = []
    ) {}

    /**
     * Get the HTTP status code.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Get the raw response body.
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Decode JSON body into an array or object.
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $decoded = json_decode($this->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }

        if ($key === null) {
            return $decoded;
        }

        return $this->arrayGet($decoded, $key, $default);
    }

    /**
     * Get response headers.
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header by name.
     */
    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return $default;
    }

    /**
     * Determine if status is 2xx OK.
     */
    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Determine if status is 200 OK.
     */
    public function ok(): bool
    {
        return $this->status === 200;
    }

    /**
     * Determine if status is a redirect (3xx).
     */
    public function redirect(): bool
    {
        return $this->status >= 300 && $this->status < 400;
    }

    /**
     * Determine if status is a client error (4xx).
     */
    public function clientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Determine if status is a server error (5xx).
     */
    public function serverError(): bool
    {
        return $this->status >= 500 && $this->status < 600;
    }

    /**
     * Determine if status is 401 Unauthorized.
     */
    public function unauthorized(): bool
    {
        return $this->status === 401;
    }

    /**
     * Determine if status is 403 Forbidden.
     */
    public function forbidden(): bool
    {
        return $this->status === 403;
    }

    /**
     * Determine if status is 404 Not Found.
     */
    public function notFound(): bool
    {
        return $this->status === 404;
    }

    /**
     * Determine if request failed (4xx or 5xx).
     */
    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * Get request transfer info (cURL statistics, execution time, etc.).
     */
    public function transferInfo(?string $key = null): mixed
    {
        if ($key !== null) {
            return $this->info[$key] ?? null;
        }

        return $this->info;
    }

    /**
     * Convert response to Symfony HttpFoundation Response if needed.
     */
    public function toSymfonyResponse(): \Symfony\Component\HttpFoundation\Response
    {
        return new \Symfony\Component\HttpFoundation\Response(
            $this->body(),
            $this->status(),
            $this->headers()
        );
    }

    // --- Interface Methods ---

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->json()[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->json()[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Responses are immutable
    }

    public function offsetUnset(mixed $offset): void
    {
        // Responses are immutable
    }

    public function jsonSerialize(): mixed
    {
        return $this->json();
    }

    public function __toString(): string
    {
        return $this->body();
    }

    /**
     * Dot notation array fetch helper.
     */
    private function arrayGet(mixed $array, string $key, mixed $default = null): mixed
    {
        if (!is_array($array)) {
            return $default;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }
}