<?php
/**
 * api/telemetry.php — Sensor & Analytics AJAX endpoint
 * All sensor data is simulated (prototype/demo).
 * No external APIs used — all data is generated locally.
 */

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/Database.php';
require_once ROOT_PATH . '/models/Appliance.php';
require_once ROOT_PATH . '/models/Telemetry.php';
require_once ROOT_PATH . '/models/Budget.php';
require_once ROOT_PATH . '/models/Automation.php';

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store');

// Auth guard
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$uid    = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'live';
$tel    = new Telemetry();
$appMdl = new Appliance();

try {
    switch ($action) {

        // ── Live readings (latest per device) ────────────
        case 'live':
            echo json_encode(['data' => $tel->latestAll(), 'ts' => time()]);
            break;

        // ── Weekly chart data shaped for Chart.js ────────
        case 'weekly':
            $rows   = $tel->weeklySummary();
            $labels = [];
            $elec = $water = $gas = [];

            foreach ($rows as $r) {
                if (!in_array($r['day'], $labels, true)) $labels[] = $r['day'];
                match($r['resource_type']) {
                    'electricity' => $elec[$r['day']]  = (float)$r['kwh'],
                    'water'       => $water[$r['day']] = (float)$r['kwh'],
                    'gas'         => $gas[$r['day']]   = (float)$r['kwh'],
                    default       => null,
                };
            }
            sort($labels);

            echo json_encode([
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label'           => 'Electricity (kWh)',
                        'data'            => array_values(array_map(fn($d) => $elec[$d]  ?? 0, $labels)),
                        'borderColor'     => '#fbbf24',
                        'backgroundColor' => 'rgba(251,191,36,.12)',
                        'fill'            => true,
                    ],
                    [
                        'label'           => 'Water (kL)',
                        'data'            => array_values(array_map(fn($d) => $water[$d] ?? 0, $labels)),
                        'borderColor'     => '#22d3ee',
                        'backgroundColor' => 'rgba(34,211,238,.12)',
                        'fill'            => true,
                    ],
                    [
                        'label'           => 'Gas (m³)',
                        'data'            => array_values(array_map(fn($d) => $gas[$d]   ?? 0, $labels)),
                        'borderColor'     => '#fb923c',
                        'backgroundColor' => 'rgba(251,146,60,.12)',
                        'fill'            => true,
                    ],
                ],
            ]);
            break;

        // ── Billing forecast ─────────────────────────────
        case 'forecast':
            echo json_encode($tel->predictMonthlyBill($uid));
            break;

        // ── Anomaly list ──────────────────────────────────
        case 'anomalies':
            echo json_encode(['anomalies' => $tel->detectAnomalies()]);
            break;

        // ── Device history (last N readings) ─────────────
        case 'device':
            $deviceId = (int)($_GET['device_id'] ?? 0);
            if (!$deviceId) { http_response_code(400); echo json_encode(['error' => 'device_id required']); exit; }
            $rows   = $tel->recentByDevice($deviceId, 30);
            $labels = array_reverse(array_column($rows, 'recorded_at'));
            $values = array_reverse(array_column($rows, 'value'));
            echo json_encode(['labels' => $labels, 'values' => $values]);
            break;

        // ── Mock sensor tick (simulation engine) ─────────
        case 'mock_tick':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST required']);
                exit;
            }

            $appliances = $appMdl->byStatus('ON');
            $auto       = new Automation();
            $inserted   = 0;
            $triggered  = [];

            foreach ($appliances as $a) {
                $reading = $tel->generateReading($a);
                if ($tel->insertReading($reading)) {
                    $inserted++;

                    // Evaluate automation rules
                    $payload = [
                        'temperature' => $reading['temperature'],
                        'amperage'    => $reading['amperage'],
                        'value'       => $reading['value'],
                        'water_flow'  => $a['resource_type'] === 'water' ? $reading['value'] : 0,
                        'budget_pct'  => 0, // resolved below
                    ];
                    $actions = $auto->evaluate($payload);

                    foreach ($actions as $act) {
                        $triggered[] = $act;
                        if ($act['action_type'] === 'SHUTDOWN') {
                            $appMdl->setStatus($a['id'], 'SHUTDOWN');
                            Database::getInstance()->insert(
                                'INSERT INTO alerts (user_id, device_id, type, message, priority) VALUES (?,?,\'SAFETY\',?,\'CRITICAL\')',
                                [$uid, $a['id'], "Emergency shutdown: {$a['name']} — rule: {$act['rule_name']}"]
                            );
                        }
                    }
                }
            }

            // Budget escalation check
            $bud = new Budget();
            foreach ($tel->monthlySummaryByResource() as $m) {
                $bud->checkEscalation($uid, $m['resource_type']);
            }

            echo json_encode([
                'inserted'  => $inserted,
                'triggered' => $triggered,
                'ts'        => date('Y-m-d H:i:s'),
            ]);
            break;

        // ── Unread alert count (for badge) ───────────────
        case 'alert_count':
            $count = Database::getInstance()->fetchOne(
                'SELECT COUNT(*) AS c FROM alerts WHERE user_id=? AND is_read=0',
                [$uid]
            );
            echo json_encode(['count' => (int)($count['c'] ?? 0)]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
