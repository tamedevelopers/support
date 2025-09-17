<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use PDO;
use SessionHandlerInterface;
use Tamedevelopers\Support\Process\Concerns\SessionInterface as BaseSessionInterface;
use Tamedevelopers\Support\Process\Session\Handlers\DatabaseSessionHandler;
use Tamedevelopers\Support\Process\Session\Handlers\RedisSessionHandler;
use Redis;

/**
 * Configurable session manager supporting file, database, and redis drivers.
 *
 * Responsibilities:
 * - Configure PHP sessions based on a chosen driver
 * - For file driver, ensure the directory exists (defaults to storage_path('session'))
 * - For database driver, install a PDO-backed handler
 * - For redis driver, install a phpredis-backed handler with TTL
 * - Provide a simple, framework-agnostic SessionInterface implementation
 */
final class SessionManager implements BaseSessionInterface
{
    /** @var array<string,mixed> Resolved session configuration */
    private array $config;

    /**
     * @param array<string,mixed> $config
     *  - driver: file|files|database|redis|native (default: native)
     *  - lifetime: int seconds (optional, default from ini)
     *  - path: string for file driver (optional; defaults to storage_path('session') when available)
     *  - database: [dsn, username, password, options(array), table(string)]
     *  - redis: [host, port, timeout, auth, database, prefix, ttl]
     */
    public function __construct(array $config = [])
    {
        $this->config = $config + [
            'driver' => 'native',
        ];
    }

    /** @inheritDoc */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $driver = (string) ($this->config['driver'] ?? 'native');
        $lifetime = isset($this->config['lifetime']) ? (int) $this->config['lifetime'] : null;
        if ($lifetime) {
            @ini_set('session.gc_maxlifetime', (string) $lifetime);
        }

        // default path to storage_path('sessions') if not provided for file driver
        if (in_array($driver, ['file', 'files'], true)) {
            if (empty($this->config['path']) && function_exists('storage_path')) {
                $this->config['path'] = storage_path('sessions');
            }
        }

        switch ($driver) {
            case 'file':
                $this->configureFileDriver();
                break;
            case 'files': // alias
                $this->configureFileDriver();
                break;
            case 'database':
                $this->configureDatabaseDriver($lifetime);
                break;
            case 'redis':
                $this->configureRedisDriver($lifetime);
                break;
            case 'native':
            default:
                // No special configuration
                break;
        }

        @session_start();
    }

    private function configureFileDriver(): void
    {
        $path = (string) ($this->config['path'] ?? '');
        if ($path !== '') {
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }
            if (!is_writable($path)) {
                throw new \RuntimeException("Session path not writable: {$path}");
            }
            @session_save_path($path);
        }
    }

    private function configureDatabaseDriver(?int $lifetime = null): void
    {
        $db = (array) ($this->config['database'] ?? []);
        $dsn = (string) ($db['dsn'] ?? '');
        if ($dsn === '') {
            throw new \InvalidArgumentException('database.dsn is required for database session driver.');
        }
        $username = $db['username'] ?? null;
        $password = $db['password'] ?? null;
        $options = (array) ($db['options'] ?? []);
        $table = (string) ($db['table'] ?? 'sessions');

        $pdo = new PDO($dsn, (string) $username, (string) $password, $options);
        $handler = new DatabaseSessionHandler($pdo, $table, $lifetime ?? (int) ini_get('session.gc_maxlifetime'));
        $this->registerHandler($handler);
    }

    private function configureRedisDriver(?int $lifetime = null): void
    {
        if (!class_exists('\Redis')) {
            throw new \RuntimeException('Redis extension (phpredis) is required for redis session driver.');
        }
        $cfg = (array) ($this->config['redis'] ?? []);
        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (int) ($cfg['port'] ?? 6379);
        $timeout = isset($cfg['timeout']) ? (float) $cfg['timeout'] : 1.5;
        $auth = $cfg['auth'] ?? null;
        $database = (int) ($cfg['database'] ?? 0);
        $prefix = (string) ($cfg['prefix'] ?? 'sess:');
        $ttl = $lifetime ?? (int) ($cfg['ttl'] ?? (int) ini_get('session.gc_maxlifetime'));

        $handler = new RedisSessionHandler($host, $port, $timeout, $auth, $database, $prefix, $ttl);
        $this->registerHandler($handler);
    }

    private function registerHandler(SessionHandlerInterface $handler): void
    {
        @session_set_save_handler($handler, true);
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
    public function destroy(?string $key = null): void
    {
        if ($key !== null) {
            unset($_SESSION[$key]);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_unset();
            @session_destroy();
        }
    }
}