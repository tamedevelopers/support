<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process\Session\Handlers;

use PDO;
use SessionHandlerInterface;

/**
 * PDO-backed session handler.
 * Creates table structure if missing (simple schema).
 */
final class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private string $table;
    private int $ttl;

    public function __construct(PDO $pdo, string $table = 'sessions', int $ttl = 1440)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->ttl = $ttl;
        $this->createTableIfMissing();
    }

    private function createTableIfMissing(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id VARCHAR(128) PRIMARY KEY,
            payload BLOB NOT NULL,
            last_activity INT NOT NULL
        )";
        $this->pdo->exec($sql);
    }

    public function open($savePath, $sessionName): bool { return true; }
    public function close(): bool { return true; }

    public function read($id): string|false
    {
        $stmt = $this->pdo->prepare("SELECT payload FROM {$this->table} WHERE id = :id AND last_activity > :exp");
        $stmt->execute([
            ':id' => $id,
            ':exp' => time() - $this->ttl,
        ]);
        $row = $stmt->fetchColumn();
        return $row !== false ? (string) $row : '';
    }

    public function write($id, $data): bool
    {
        $stmt = $this->pdo->prepare("REPLACE INTO {$this->table} (id, payload, last_activity) VALUES (:id, :payload, :time)");
        return $stmt->execute([
            ':id' => $id,
            ':payload' => $data,
            ':time' => time(),
        ]);
    }

    public function destroy($id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function gc($max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE last_activity < :exp");
        $stmt->execute([':exp' => time() - $this->ttl]);
        return $stmt->rowCount();
    }
}