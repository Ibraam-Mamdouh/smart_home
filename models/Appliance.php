<?php
require_once ROOT_PATH . '/config/Database.php';

/**
 * Appliance Model — CRUD + health-degradation logic.
 *
 * Design Patterns used:
 *  - Repository pattern (all DB interactions via this class)
 *  - Factory method (create() validates and inserts)
 */
class Appliance
{
    private Database $db;

    // Degradation coefficient δ per appliance type (FR-10)
    private const DEGRADATION = [
        'Air Conditioner' => 0.0002,
        'Refrigerator'    => 0.0003,
        'Water Heater'    => 0.0004,
        'Dishwasher'      => 0.0001,
        'Television'      => 0.00005,
        'default'         => 0.0001,
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Queries ─────────────────────────────────────────────

    public function all(): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, r.name AS room_name
             FROM appliances a
             JOIN rooms r ON r.id = a.room_id
             ORDER BY r.name, a.name'
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT a.*, r.name AS room_name
             FROM appliances a
             JOIN rooms r ON r.id = a.room_id
             WHERE a.id = ?',
            [$id]
        );
    }

    public function byStatus(string $status): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, r.name AS room_name
             FROM appliances a JOIN rooms r ON r.id = a.room_id
             WHERE a.status = ?',
            [$status]
        );
    }

    public function countByStatus(): array
    {
        return $this->db->fetchAll(
            'SELECT status, COUNT(*) AS cnt FROM appliances GROUP BY status'
        );
    }

    // ── CRUD ────────────────────────────────────────────────

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO appliances
             (room_id, name, type, status, base_wattage, age_hours, resource_type)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                (int)$data['room_id'],
                $data['name'],
                $data['type'],
                $data['status'] ?? 'OFF',
                (float)$data['base_wattage'],
                (int)($data['age_hours'] ?? 0),
                $data['resource_type'] ?? 'electricity',
            ]
        );
    }

    public function update(int $id, array $data): int
    {
        return $this->db->execute(
            'UPDATE appliances
             SET room_id=?, name=?, type=?, status=?, base_wattage=?, resource_type=?
             WHERE id=?',
            [
                (int)$data['room_id'],
                $data['name'],
                $data['type'],
                $data['status'],
                (float)$data['base_wattage'],
                $data['resource_type'],
                $id,
            ]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM appliances WHERE id = ?', [$id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE appliances SET status=? WHERE id=?',
            [$status, $id]
        );
    }

    // ── Health / Degradation (FR-10) ────────────────────────

    /**
     * W_actual = W_nominal * (1 + δ)^age
     */
    public function actualWattage(array $appliance): float
    {
        $delta = self::DEGRADATION[$appliance['type']] ?? self::DEGRADATION['default'];
        return round(
            $appliance['base_wattage'] * pow(1 + $delta, $appliance['age_hours']),
            2
        );
    }

    /**
     * Efficiency = W_nominal / W_actual  → health 0-100
     */
    public function healthScore(array $appliance): int
    {
        $actual = $this->actualWattage($appliance);
        if ($actual <= 0) return 100;
        $score = ($appliance['base_wattage'] / $actual) * 100;
        return max(0, min(100, (int)round($score)));
    }

    public function recalculateHealth(int $id): void
    {
        $a = $this->findById($id);
        if (!$a) return;
        $score = $this->healthScore($a);
        $this->db->execute(
            'UPDATE appliances SET health_score=? WHERE id=?',
            [$score, $id]
        );
    }

    // ── Maintenance Trigger (FR-12) ──────────────────────────

    /** Returns appliances whose efficiency < threshold (default 80%) */
    public function needsMaintenance(float $threshold = 0.80): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, r.name AS room_name
             FROM appliances a JOIN rooms r ON r.id = a.room_id
             WHERE (a.base_wattage / NULLIF(a.base_wattage,0)) * (1/(1+0.0002)) < ?
                OR a.health_score < ?
             ORDER BY a.health_score ASC',
            [$threshold, (int)($threshold * 100)]
        );
    }
}
