<?php
require_once __DIR__ . '/config.php';

/**
 * Database — Singleton Pattern
 * Provides a single shared PDO connection throughout the request lifecycle.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /** Private constructor prevents direct instantiation */
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose credentials — log and show a generic message
            error_log('[DB] Connection failed: ' . $e->getMessage());
            http_response_code(503);
            die(json_encode(['error' => 'Database unavailable. Please try later.']));
        }
    }

    /** Prevent cloning */
    private function __clone() {}

    /** Prevent unserialization */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize a singleton.');
    }

    /**
     * Returns the single Database instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Expose the raw PDO object for query execution.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // ── Convenience helpers ──────────────────────────────────

    /**
     * Execute a prepared statement and return the PDOStatement.
     *
     * @param string $sql
     * @param array  $params
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row.
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    /**
     * Fetch all rows.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Insert a row and return the last insert ID.
     */
    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Execute an UPDATE/DELETE and return affected rows.
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): void   { $this->pdo->beginTransaction(); }
    public function commit(): void             { $this->pdo->commit(); }
    public function rollback(): void           { $this->pdo->rollBack(); }
}
