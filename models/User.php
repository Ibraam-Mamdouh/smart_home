<?php
require_once ROOT_PATH . '/config/Database.php';

/**
 * User Model — handles authentication and user data.
 */
class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Auth ────────────────────────────────────────────────

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE email = ? LIMIT 1',
            [$email]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT id, name, email, role, avatar, created_at FROM users WHERE id = ?',
            [$id]
        );
    }

    public function create(string $name, string $email, string $password, string $role = 'resident'): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->db->insert(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
            [$name, $email, $hash, $role]
        );
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function updateAvatar(int $userId, string $filename): void
    {
        $this->db->execute(
            'UPDATE users SET avatar = ? WHERE id = ?',
            [$filename, $userId]
        );
    }

    public function all(): array
    {
        return $this->db->fetchAll(
            'SELECT id, name, email, role, created_at FROM users ORDER BY id'
        );
    }

    public function totalRewardPoints(int $userId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COALESCE(SUM(points), 0) AS total FROM reward_points WHERE user_id = ?',
            [$userId]
        );
        return (int)($row['total'] ?? 0);
    }
}
