<?php
require_once ROOT_PATH . '/config/Database.php';

/**
 * Telemetry Model
 * - Sensor Mock-Generator   (FR-25)
 * - Tiered Tariff Engine     (FR-01)
 * - Predictive Billing       (FR-02)
 * - Anomaly Detection        (FR-03)
 * - Historical Aggregator    (FR-08)
 * - Carbon Footprint         (FR-05)
 */
class Telemetry
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Mock Generator (FR-25) ───────────────────────────────

    /**
     * Generate a Gaussian-distributed mock telemetry reading.
     *   v = B + N(0, σ²)
     */
    public function generateReading(array $appliance): array
    {
        $base  = (float)$appliance['base_wattage'];
        $sigma = $base * 0.05;  // 5% standard deviation

        // Box-Muller transform for normal distribution
        $u1 = (mt_rand(1, PHP_INT_MAX - 1)) / PHP_INT_MAX;
        $u2 = (mt_rand(1, PHP_INT_MAX - 1)) / PHP_INT_MAX;
        $z  = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);

        $value    = round(max(0, $base + $z * $sigma), 4);
        $temp     = round(20 + mt_rand(0, 500) / 100, 2); // 20–25 °C base
        $amperage = $appliance['status'] === 'ON'
                    ? round($value / 220, 4) : 0;

        return [
            'device_id'     => $appliance['id'],
            'resource_type' => $appliance['resource_type'],
            'value'         => $appliance['status'] === 'ON' ? $value : 0,
            'unit'          => 'W',
            'temperature'   => $temp,
            'amperage'      => $amperage,
            'packet_id'     => bin2hex(random_bytes(16)),
            'recorded_at'   => date('Y-m-d H:i:s.') . str_pad(rand(0,999),3,'0',STR_PAD_LEFT),
        ];
    }

    public function insertReading(array $r): bool
    {
        try {
            $this->db->insert(
                'INSERT INTO telemetry_logs
                 (device_id, resource_type, value, unit, temperature, amperage, packet_id, recorded_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $r['device_id'], $r['resource_type'], $r['value'],
                    $r['unit'], $r['temperature'], $r['amperage'],
                    $r['packet_id'], $r['recorded_at'],
                ]
            );
            return true;
        } catch (\PDOException $e) {
            // duplicate packet_id → idempotent ignore
            return false;
        }
    }

    // ── Recent readings for AJAX dashboard ─────────────────

    public function recentByDevice(int $deviceId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT value, temperature, amperage, recorded_at
             FROM telemetry_logs
             WHERE device_id = ?
             ORDER BY recorded_at DESC
             LIMIT ?',
            [$deviceId, $limit]
        );
    }

    public function latestAll(): array
    {
        return $this->db->fetchAll(
            'SELECT t.*, a.name AS device_name, a.resource_type
             FROM telemetry_logs t
             JOIN appliances a ON a.id = t.device_id
             WHERE t.recorded_at = (
                 SELECT MAX(t2.recorded_at) FROM telemetry_logs t2 WHERE t2.device_id = t.device_id
             )
             ORDER BY a.name'
        );
    }

    // ── Tiered Tariff Engine (FR-01) ─────────────────────────

    /**
     * TotalCost = Σ(Usage_t × R(t))
     * R(t) = PeakRate if t ∈ [T_peak] else OffPeakRate
     */
    public function tariffRate(string $datetime): float
    {
        $hour = (int)date('H', strtotime($datetime));
        return ($hour >= PEAK_START && $hour < PEAK_END)
            ? TARIFF_PEAK
            : TARIFF_OFFPEAK;
    }

    public function computeDailyCost(int $deviceId, string $date): float
    {
        $rows = $this->db->fetchAll(
            'SELECT value, recorded_at FROM telemetry_logs
             WHERE device_id = ? AND DATE(recorded_at) = ?
             ORDER BY recorded_at',
            [$deviceId, $date]
        );

        $total = 0.0;
        foreach ($rows as $r) {
            // Convert W to kWh for the interval (assuming ~5s interval)
            $kwh    = ($r['value'] / 1000) * (5 / 3600);
            $total += $kwh * $this->tariffRate($r['recorded_at']);
        }
        return round($total, 4);
    }

    // ── Predictive Billing (FR-02) ───────────────────────────

    /**
     * P = ((Σ U_i / 7) × D_remaining) + U_current
     */
    public function predictMonthlyBill(int $userId): array
    {
        $dailyAvg = $this->db->fetchOne(
            'SELECT AVG(daily_total) AS avg_day
             FROM (
                 SELECT DATE(t.recorded_at) AS d,
                        SUM((t.value/1000)*(5/3600)) AS daily_total
                 FROM telemetry_logs t
                 JOIN appliances a ON a.id = t.device_id
                 WHERE t.recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY DATE(t.recorded_at)
             ) AS sub',
            []
        );

        $currentKwh = $this->db->fetchOne(
            'SELECT COALESCE(SUM((t.value/1000)*(5/3600)), 0) AS kwh
             FROM telemetry_logs t
             WHERE t.recorded_at >= DATE_FORMAT(NOW(), \'%Y-%m-01\')',
            []
        );

        $dayOfMonth    = (int)date('j');
        $daysInMonth   = (int)date('t');
        $dRemaining    = $daysInMonth - $dayOfMonth;
        $avgDay        = (float)($dailyAvg['avg_day'] ?? 0);
        $current       = (float)($currentKwh['kwh']   ?? 0);
        $forecast_kwh  = ($avgDay * $dRemaining) + $current;
        $forecast_egp  = round($forecast_kwh * TARIFF_PEAK, 2);

        return [
            'avg_daily_kwh'  => round($avgDay, 4),
            'current_kwh'    => round($current, 4),
            'forecast_kwh'   => round($forecast_kwh, 4),
            'forecast_egp'   => $forecast_egp,
            'days_remaining' => $dRemaining,
        ];
    }

    // ── Anomaly Detection (FR-03) ────────────────────────────

    /**
     * Alert = true if |U_current − Mean| > k × σ
     * k = 2.5 (standard "outlier" factor)
     */
    public function detectAnomalies(float $k = 2.5): array
    {
        $rows = $this->db->fetchAll(
            'SELECT t.device_id, a.name AS device_name,
                    AVG(t.value) AS mean_val,
                    STD(t.value) AS std_val,
                    (SELECT value FROM telemetry_logs tl
                     WHERE tl.device_id = t.device_id
                     ORDER BY recorded_at DESC LIMIT 1) AS latest_val
             FROM telemetry_logs t
             JOIN appliances a ON a.id = t.device_id
             WHERE t.recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY t.device_id, a.name
             HAVING ABS(latest_val - mean_val) > ? * std_val
               AND std_val > 0',
            [$k]
        );
        return $rows;
    }

    // ── Historical Aggregator (FR-08) ────────────────────────

    public function dailySummary(string $date): array
    {
        return $this->db->fetchAll(
            'SELECT a.name AS device_name,
                    t.resource_type,
                    SUM((t.value/1000)*(5/3600)) AS kwh,
                    COUNT(*)                      AS readings
             FROM telemetry_logs t
             JOIN appliances a ON a.id = t.device_id
             WHERE DATE(t.recorded_at) = ?
             GROUP BY t.device_id, a.name, t.resource_type
             ORDER BY kwh DESC',
            [$date]
        );
    }

    public function weeklySummary(): array
    {
        return $this->db->fetchAll(
            'SELECT DATE(recorded_at) AS day,
                    resource_type,
                    ROUND(SUM((value/1000)*(5/3600)), 4) AS kwh
             FROM telemetry_logs
             WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(recorded_at), resource_type
             ORDER BY day ASC'
        );
    }

    public function monthlySummaryByResource(): array
    {
        return $this->db->fetchAll(
            'SELECT resource_type,
                    ROUND(SUM((value/1000)*(5/3600)), 4) AS kwh,
                    COUNT(DISTINCT device_id)            AS devices
             FROM telemetry_logs
             WHERE recorded_at >= DATE_FORMAT(NOW(), \'%Y-%m-01\')
             GROUP BY resource_type'
        );
    }

    // ── Carbon Footprint (FR-05) ─────────────────────────────

    /**
     * CO2_Total = Σ(Usage_resource × EmissionFactor_resource)
     */
    public function carbonFootprintToday(): float
    {
        $rows = $this->db->fetchAll(
            'SELECT resource_type,
                    SUM((value/1000)*(5/3600)) AS kwh
             FROM telemetry_logs
             WHERE DATE(recorded_at) = CURDATE()
             GROUP BY resource_type'
        );

        $factors = [
            'electricity' => EMISSION_ELECTRICITY,
            'gas'         => EMISSION_GAS,
            'water'       => EMISSION_WATER,
        ];

        $co2 = 0.0;
        foreach ($rows as $r) {
            $co2 += $r['kwh'] * ($factors[$r['resource_type']] ?? 0);
        }
        return round($co2, 4);
    }

    // ── Resource Baseline (FR-06) ────────────────────────────

    /**
     * Baseline(h) = (1/N) × Σ Usage_{h,day}
     */
    public function hourlyBaseline(int $deviceId): array
    {
        return $this->db->fetchAll(
            'SELECT HOUR(recorded_at) AS hour_slot,
                    AVG(value) AS baseline_w
             FROM telemetry_logs
             WHERE device_id = ?
               AND recorded_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
             GROUP BY HOUR(recorded_at)
             ORDER BY hour_slot',
            [$deviceId]
        );
    }
}
