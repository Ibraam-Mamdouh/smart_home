<?php
require_once ROOT_PATH . '/config/Database.php';

/**
 * Automation Model
 * - Rule-Based Logic Engine  (FR-24)
 * - System Audit Trail       (FR-29)
 * - Eco-Challenge Engine     (FR-21)
 * - Vacation Mode            (FR-22)
 */
class Automation
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Rule Engine (FR-24) ──────────────────────────────────

    public function allRules(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM automation_rules WHERE user_id=? ORDER BY priority ASC',
            [$userId]
        );
    }

    public function activeRules(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM automation_rules WHERE is_active=1 ORDER BY priority ASC'
        );
    }

    public function createRule(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO automation_rules
             (user_id, name, condition_field, condition_operator, condition_value,
              action_type, action_value, priority)
             VALUES (?,?,?,?,?,?,?,?)',
            [
                (int)$data['user_id'],
                $data['name'],
                $data['condition_field'],
                $data['condition_operator'],
                (float)$data['condition_value'],
                $data['action_type'],
                $data['action_value'] ?? null,
                (int)($data['priority'] ?? 5),
            ]
        );
    }

    public function updateRule(int $id, array $data): void
    {
        $this->db->execute(
            'UPDATE automation_rules
             SET name=?, condition_field=?, condition_operator=?, condition_value=?,
                 action_type=?, action_value=?, priority=?, is_active=?
             WHERE id=?',
            [
                $data['name'], $data['condition_field'], $data['condition_operator'],
                $data['condition_value'], $data['action_type'], $data['action_value'],
                $data['priority'], (int)($data['is_active'] ?? 1), $id,
            ]
        );
    }

    public function deleteRule(int $id): void
    {
        $this->db->execute('DELETE FROM automation_rules WHERE id=?', [$id]);
    }

    /**
     * Evaluate a single sensor reading against all active rules.
     * Returns list of triggered actions.
     *
     * Condition => Action  (FR-24)
     */
    public function evaluate(array $telemetry): array
    {
        $triggered = [];
        $rules     = $this->activeRules();

        foreach ($rules as $rule) {
            $field = $rule['condition_field'];
            $val   = $telemetry[$field] ?? null;
            if ($val === null) continue;

            $match = match($rule['condition_operator']) {
                '>'  => $val >  $rule['condition_value'],
                '<'  => $val <  $rule['condition_value'],
                '>=' => $val >= $rule['condition_value'],
                '<=' => $val <= $rule['condition_value'],
                '==' => $val == $rule['condition_value'],
                '!=' => $val != $rule['condition_value'],
                default => false,
            };

            if ($match) {
                $triggered[] = [
                    'rule_id'     => $rule['id'],
                    'rule_name'   => $rule['name'],
                    'action_type' => $rule['action_type'],
                    'action_value'=> $rule['action_value'],
                    'priority'    => $rule['priority'],
                ];
            }
        }

        // Sort highest-priority (lowest number) first
        usort($triggered, fn($a, $b) => $a['priority'] <=> $b['priority']);
        return $triggered;
    }

    // ── Audit Trail (FR-29) ──────────────────────────────────

    /**
     * Append an immutable audit record.
     * Log = (u, r, v, t)
     */
    public function audit(
        ?int   $userId,
        string $resource,
        string $action,
        mixed  $oldVal = null,
        mixed  $newVal = null,
        string $ip     = ''
    ): void {
        $this->db->insert(
            'INSERT INTO system_audit_trail
             (user_id, resource, action, old_value, new_value, ip_address)
             VALUES (?,?,?,?,?,?)',
            [
                $userId,
                $resource,
                $action,
                $oldVal !== null ? json_encode($oldVal) : null,
                $newVal !== null ? json_encode($newVal) : null,
                $ip ?: ($_SERVER['REMOTE_ADDR'] ?? ''),
            ]
        );
    }

    public function auditLog(int $limit = 100): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, u.name AS user_name
             FROM system_audit_trail a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC LIMIT ?',
            [$limit]
        );
    }

    // ── Eco Challenges (FR-21) ───────────────────────────────

    public function activeChallenges(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM eco_challenges
             WHERE start_date <= CURDATE() AND end_date >= CURDATE()
             ORDER BY end_date ASC'
        );
    }

    public function allChallenges(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM eco_challenges ORDER BY start_date DESC'
        );
    }

    public function joinChallenge(int $userId, int $challengeId): void
    {
        try {
            $this->db->insert(
                'INSERT INTO user_challenges (user_id, challenge_id) VALUES (?,?)',
                [$userId, $challengeId]
            );
        } catch (\PDOException $e) {
            // Already joined — ignore duplicate
        }
    }

    public function userChallenges(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT uc.*, ec.name, ec.description, ec.target_reduction,
                    ec.resource_type, ec.points_reward, ec.end_date, uc.status
             FROM user_challenges uc
             JOIN eco_challenges ec ON ec.id = uc.challenge_id
             WHERE uc.user_id = ?
             ORDER BY uc.joined_at DESC',
            [$userId]
        );
    }

    // ── Vacation Mode (FR-22) ────────────────────────────────

    public function setVacationMode(int $userId, bool $on): void
    {
        $action = $on ? 'VACATION_ON' : 'VACATION_OFF';
        $this->audit($userId, 'system', $action, null, null);

        if ($on) {
            // Inject a high-sensitivity anomaly rule
            $this->db->execute(
                'UPDATE automation_rules
                 SET is_active=1
                 WHERE condition_field=\'water_flow\' AND user_id=?',
                [$userId]
            );
        }
    }
}
