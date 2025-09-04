<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process\Session\Handlers;

use Redis;
use SessionHandlerInterface;

/**
 * Redis-backed session handler using phpredis.
 */
final class RedisSessionHandler implements SessionHandlerInterface
{
    private Redis $redis;
    private string $prefix;
    private int $ttl;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 1.5,
        $auth = null,
        int $database = 0,
        string $prefix = 'sess:',
        int $ttl = 1440
    ) {
        $this->redis = new Redis();
        $this->redis->connect($host, $port, $timeout);
        if ($auth !== null && $auth !== '') {
            $this->redis->auth($auth);
        }
        if ($database > 0) {
            $this->redis->select($database);
        }
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    private function key(string $id): string
    {
        return $this->prefix . $id;
    }

    public function open($savePath, $sessionName): bool { return true; }
    public function close(): bool { return true; }

    public function read($id): string|false
    {
        $data = $this->redis->get($this->key($id));
        return $data === false ? '' : (string) $data;
    }

    public function write($id, $data): bool
    {
        return (bool) $this->redis->setex($this->key($id), $this->ttl, (string) $data);
    }

    public function destroy($id): bool
    {
        return (bool) $this->redis->del($this->key($id));
    }

    public function gc($max_lifetime): int|false
    {
        // Redis handles expiration automatically via TTL
        return 0;
    }
}