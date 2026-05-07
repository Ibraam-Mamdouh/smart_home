<?php
require_once ROOT_PATH . '/config/Database.php';

/**
 * Budget Model
 * - Budget CRUD               (FR-17)
 * - Overrun Escalation        (FR-18)
 * - Dynamic Budget Adjuster   (FR-17)
 * - Reward Points Logic       (FR-20)
 */
class Budget
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Queries ─────────────────────────────────────────────

    public function forUser(int $userId, string $cycle = ''): array
    {
        $cycle = $cycle ?: date('Y-m');
        return $this->db->fetchAll(
            'SELECT * FROM budgets WHERE user_id = ? AND billing_cycle = ?',
            [$userId, $cycle]
        );
    }

    public function findOne(int $userId, string $resource, string $cycle = ''): ?array
    {
        $cycle = $cycle ?: date('Y-m');
        return $this->db->fetchOne(
            'SELECT * FROM budgets WHERE user_id=? AND resource_type=? AND billing_cycle=?',
            [$userId, $resource, $cycle]
        );
    }

    // ── CRUD ────────────────────────────────────────────────

    public function upsert(int $userId, string $resource, float $limit, string $cycle = ''): void
    {
        $cycle = $cycle ?: date('Y-m');
        $this->db->execute(
            'INSERT INTO budgets (user_id, resource_type, billing_cycle, monthly_limit)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit)',
            [$userId, $resource, $cycle, $limit]
        );
    }

    public function addSpending(int $userId, string $resource, float $amount): void
    {
        $cycle = date('Y-m');
        $this->db->execute(
            'UPDATE budgets SET current_spent = current_spent + ?
             WHERE user_id=? AND resource_type=? AND billing_cycle=?',
            [$amount, $userId, $resource, $cycle]
        );
        $this->checkEscalation($userId, $resource, $cycle);
    }

    // ── Overrun Escalation (FR-18) ───────────────────────────

    /**
     * AlertLevel = floor((Cost / Budget) × 10)
     * Triggers at 80%, 90%, 100%
     */
    public function checkEscalation(int $userId, string $resource, string $cycle = ''): ?string
    {
        $cycle = $cycle ?: date('Y-m');
        $b = $this->findOne($userId, $resource, $cycle);
        if (!$b || $b['monthly_limit'] <= 0) return null;

        $pct = ($b['current_spent'] / $b['monthly_limit']) * 100;

        if ($pct >= 100) return $this->escalate($userId, $resource, 100, 'CRITICAL');
        if ($pct >= 90)  return $this->escalate($userId, $resource, 90,  'HIGH');
        if ($pct >= 80)  return $this->escalate($userId, $resource, 80,  'MEDIUM');

        return null;
    }

    private function escalate(int $userId, string $resource, int $pct, string $priority): string
    {
        $msg = "Budget alert: {$resource} usage has reached {$pct}% of your monthly limit.";
        // Prevent duplicate alerts for same threshold
        $exists = $this->db->fetchOne(
            'SELECT id FROM alerts
             WHERE user_id=? AND type=\'BUDGET\' AND message LIKE ?
               AND created_at >= DATE_FORMAT(NOW(), \'%Y-%m-01\')',
            [$userId, "%{$resource}%{$pct}%"]
        );
        if (!$exists) {
            $this->db->insert(
                'INSERT INTO alerts (user_id, type, message, priority) VALUES (?,?,?,?)',
                [$userId, 'BUDGET', $msg, $priority]
            );
        }
        return $msg;
    }

    // ── Dynamic Budget Adjuster (FR-17) ─────────────────────

    /**
     * B_new = Actual_prev × 0.95
     */
    public function suggestNext(int $userId, string $resource): ?float
    {
        $prevCycle = date('Y-m', strtotime('first day of last month'));
        $prev = $this->db->fetchOne(
            'SELECT current_spent FROM budgets
             WHERE user_id=? AND resource_type=? AND billing_cycle=?',
            [$userId, $resource, $prevCycle]
        );
        if (!$prev) return null;
        return round((float)$prev['current_spent'] * 0.95, 2);
    }

    // ── Summary for dashboard ────────────────────────────────

    public function dashboardSummary(int $userId): array
    {
        $rows = $this->forUser($userId);
        $summary = [];
        foreach ($rows as $r) {
            $pct = $r['monthly_limit'] > 0
                ? round(($r['current_spent'] / $r['monthly_limit']) * 100, 1)
                : 0;
            $summary[] = array_merge($r, ['pct' => $pct]);
        }
        return $summary;
    }

    // ── Alerts ──────────────────────────────────────────────

    public function unreadAlerts(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM alerts WHERE user_id=? AND is_read=0
             ORDER BY created_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    public function allAlerts(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM alerts WHERE user_id=? ORDER BY created_at DESC LIMIT 50',
            [$userId]
        );
    }

    public function markRead(int $alertId): void
    {
        $this->db->execute('UPDATE alerts SET is_read=1 WHERE id=?', [$alertId]);
    }

    public function markAllRead(int $userId): void
    {
        $this->db->execute('UPDATE alerts SET is_read=1 WHERE user_id=?', [$userId]);
    }

    // ── Reward Points (FR-20) ────────────────────────────────

    /**
     * Points = max(0, (B - A) × k)  where k=10
     */
    public function awardPoints(int $userId, float $budget, float $actual): int
    {
        $k      = 10;
        $points = (int)max(0, ($budget - $actual) * $k);
        if ($points > 0) {
            $this->db->insert(
                'INSERT INTO reward_points (user_id, points, action) VALUES (?,?,?)',
                [$userId, $points, "Monthly savings reward ({$points} pts)"]
            );
        }
        return $points;
    }
}
