<?php
// ─── App Configuration ────────────────────────────────────────
define('APP_NAME',    'SmartHome Dashboard');
define('APP_VERSION', '1.0.0');
// Auto-detect: PHP built-in server (port 3000) vs Apache (port 80)
$_detected_port = $_SERVER['SERVER_PORT'] ?? 80;
$_detected_base = ($_detected_port == 80 || $_detected_port == 443)
    ? 'http://localhost/smart_home'
    : "http://localhost:{$_detected_port}";
define('BASE_URL', $_detected_base);
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));

// ─── Database ─────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_home_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── Session ──────────────────────────────────────────────────
define('SESSION_NAME',    'smart_home_sess');
define('SESSION_LIFETIME', 3600 * 8);   // 8 hours

// ─── Tariff (EGP / kWh — fallback if DB unavailable) ──────────
define('TARIFF_PEAK',     1.85);
define('TARIFF_OFFPEAK',  0.95);
define('PEAK_START',      17);   // 17:00
define('PEAK_END',        23);   // 23:00

// ─── Emission factors (kg CO2 per kWh) ────────────────────────
define('EMISSION_ELECTRICITY', 0.520);
define('EMISSION_GAS',         0.202);
define('EMISSION_WATER',       0.001);

// ─── File Upload ──────────────────────────────────────────────
define('UPLOAD_DIR',      ROOT_PATH . '/uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);   // 5 MB
define('ALLOWED_TYPES',   ['image/jpeg','image/png','application/pdf']);

// ─── Pagination ───────────────────────────────────────────────
define('ROWS_PER_PAGE', 15);

// ─── Environment ──────────────────────────────────────────────
define('APP_DEBUG', false);
if (!APP_DEBUG) {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}
