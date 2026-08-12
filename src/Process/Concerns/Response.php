<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process\Concerns;

use ArrayAccess;
use JsonSerializable;
use Stringable;
use Exception;

class Response implements ArrayAccess, JsonSerializable, Stringable
{
    protected mixed $decodedJson = null;
    protected bool $isJsonDecoded = false;

    /**
     * @param int $status HTTP status code.
     * @param array<string, array<string>>|array<string, string> $headers Response headers.
     * @param string $body Raw response body.
     * @param array $info Additional metadata (e.g., execution time).
     */
    public function __construct(
        protected int $status,
        protected array $headers,
        protected string $body,
        protected array $info = []
    ) {}

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * Memoized JSON Decoder (Decodes once per response lifecycle).
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if (!$this->isJsonDecoded) {
            $this->decodedJson = json_decode($this->body, true);
            $this->isJsonDecoded = true;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }

        if ($key === null) {
            return $this->decodedJson;
        }

        return $this->arrayGet($this->decodedJson, $key, $default);
    }

    /**
     * Decode body into an object.
     */
    public function object(): ?object
    {
        return json_decode($this->body, false) ?: null;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower((string)$key) === $normalized) {
                return is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return $default;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function ok(): bool
    {
        return $this->status === 200;
    }

    public function redirect(): bool
    {
        return $this->status >= 300 && $this->status < 400;
    }

    public function clientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    public function serverError(): bool
    {
        return $this->status >= 500 && $this->status < 600;
    }

    public function unauthorized(): bool
    {
        return $this->status === 401;
    }

    public function forbidden(): bool
    {
        return $this->status === 403;
    }

    public function notFound(): bool
    {
        return $this->status === 404;
    }

    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * Throw an exception if request failed.
     */
    public function throw(): static
    {
        if ($this->failed()) {
            throw new Exception("HTTP request failed with status code {$this->status}: {$this->body}");
        }

        return $this;
    }

    public function transferInfo(?string $key = null): mixed
    {
        if ($key !== null) {
            return $this->info[$key] ?? null;
        }

        return $this->info;
    }

    // --- Interface Implementations ---

    public function offsetExists(mixed $offset): bool
    {
        $data = $this->json();
        return is_array($data) && isset($data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $data = $this->json();
        return is_array($data) ? ($data[$offset] ?? null) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void {}

    public function offsetUnset(mixed $offset): void {}

    public function jsonSerialize(): mixed
    {
        return $this->json();
    }

    public function __toString(): string
    {
        return $this->body();
    }

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