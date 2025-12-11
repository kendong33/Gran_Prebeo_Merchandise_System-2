<?php
if (session_status() === PHP_SESSION_ACTIVE) {
    return;
}

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.use_cookies', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $secure ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
session_name('GPSESSID');

require_once __DIR__ . '/db.php';

final class DbSessionHandler implements SessionHandlerInterface
{
    private mysqli $connection;
    private int $ttl;

    public function __construct(mysqli $connection, int $ttl = 86400)
    {
        $this->connection = $connection;
        $this->ttl = $ttl;
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->connection->query(
            'CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(128) PRIMARY KEY,
                data LONGBLOB NOT NULL,
                expires_at INT UNSIGNED NOT NULL,
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function open($savePath, $sessionName): bool { return true; }
    public function close(): bool { return true; }

    public function read($id): string|false
    {
        $now = time();
        $stmt = $this->connection->prepare('SELECT data FROM sessions WHERE id = ? AND expires_at > ? LIMIT 1');
        $stmt->bind_param('si', $id, $now);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            return '';
        }
        $row = $res->fetch_assoc();
        return (string)$row['data'];
    }

    public function write($id, $data): bool
    {
        $exp = time() + $this->ttl;
        $stmt = $this->connection->prepare('INSERT INTO sessions (id, data, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)');
        $stmt->bind_param('ssi', $id, $data, $exp);
        return $stmt->execute();
    }

    public function destroy($id): bool
    {
        $stmt = $this->connection->prepare('DELETE FROM sessions WHERE id = ?');
        $stmt->bind_param('s', $id);
        return $stmt->execute();
    }

    public function gc($max_lifetime): int|false
    {
        $now = time();
        $this->connection->query('DELETE FROM sessions WHERE expires_at <= ' . (int)$now);
        return $this->connection->affected_rows;
    }
}

try {
    $conn = get_db_connection();
    $ttlEnv = getenv('SESSION_TTL');
    $ttl = ($ttlEnv !== false && ctype_digit((string)$ttlEnv)) ? (int)$ttlEnv : 86400;
    $handler = new DbSessionHandler($conn, $ttl);
    session_set_save_handler($handler, true);
} catch (Throwable $e) {
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
