<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use Tamedevelopers\Support\Process\Concerns\SessionInterface;

/**
 * Native PHP session implementation for SessionInterface.
 */
final class Session implements SessionInterface
{
    /** @inheritDoc */
    public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    /** @inheritDoc */
    public function id(): ?string
    {
        return session_id() ?: null;
    }

    /** @inheritDoc */
    public function regenerate(bool $deleteOldSession = false): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->start();
        }
        return @session_regenerate_id($deleteOldSession);
    }

    /** @inheritDoc */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /** @inheritDoc */
    public function put(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /** @inheritDoc */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION ?? []);
    }

    /** @inheritDoc */
    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** @inheritDoc */
    public function all(): array
    {
        return (array) ($_SESSION ?? []);
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_unset();
            @session_destroy();
        }
    }
}