<?php
/**
 * index.php — Front Controller & Router
 *
 * All requests are routed through this file via Apache mod_rewrite (.htaccess).
 * URL pattern: /controller/action/optional_id
 */

if (!defined('ROOT_PATH')) define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/config.php';

// ── Simple Router ────────────────────────────────────────────

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base   = parse_url(BASE_URL, PHP_URL_PATH);
$uri    = '/' . ltrim(substr($uri, strlen($base)), '/');
$parts  = array_values(array_filter(explode('/', trim($uri, '/'))));

$segment0 = $parts[0] ?? 'dashboard';
$segment1 = $parts[1] ?? 'index';
$segment2 = isset($parts[2]) ? (int)$parts[2] : null;

// ── Route Table ──────────────────────────────────────────────
$routes = [

    // AUTH
    ['GET',  'auth',       'login',          'AuthController',       'loginForm'],
    ['POST', 'auth',       'login',          'AuthController',       'login'],
    ['GET',  'auth',       'register',       'AuthController',       'registerForm'],
    ['POST', 'auth',       'register',       'AuthController',       'register'],
    ['GET',  'auth',       'logout',         'AuthController',       'logout'],

    // DASHBOARD
    ['GET',  'dashboard',  'index',          'DashboardController',  'index'],
    ['GET',  '',           '',               'DashboardController',  'index'],

    // APPLIANCES
    ['GET',  'appliances', 'index',          'ApplianceController',  'index'],
    ['GET',  'appliances', 'create',         'ApplianceController',  'create'],
    ['POST', 'appliances', 'store',          'ApplianceController',  'store'],
    ['GET',  'appliances', 'edit',           'ApplianceController',  'edit'],
    ['POST', 'appliances', 'update',         'ApplianceController',  'update'],
    ['POST', 'appliances', 'delete',         'ApplianceController',  'delete'],
    ['POST', 'appliances', 'toggle',         'ApplianceController',  'toggle'],

    // BUDGET
    ['GET',  'budget',     'index',          'BudgetController',     'index'],
    ['POST', 'budget',     'save',           'BudgetController',     'save'],
    ['POST', 'budget',     'mark-read',      'BudgetController',     'markRead'],
    ['POST', 'budget',     'mark-all-read',  'BudgetController',     'markAllRead'],

    // AUTOMATION
    ['GET',  'automation', 'index',          'AutomationController', 'index'],
    ['POST', 'automation', 'rule',           'AutomationController', 'storeRule'],
    ['POST', 'automation', 'delete-rule',    'AutomationController', 'deleteRule'],
    ['POST', 'automation', 'toggle-rule',    'AutomationController', 'toggleRule'],
    ['GET',  'automation', 'audit',          'AutomationController', 'auditLog'],
    ['POST', 'automation', 'challenge-join', 'AutomationController', 'joinChallenge'],
    ['POST', 'automation', 'vacation',       'AutomationController', 'vacationMode'],

    // REPORTS
    ['GET',  'reports',    'index',          'ReportsController',    'index'],
    ['POST', 'reports',    'upload',         'ReportsController',    'upload'],
    ['GET',  'reports',    'pdf',            'ReportsController',    'generatePdf'],
];

$method   = $_SERVER['REQUEST_METHOD'];
$matched  = false;

foreach ($routes as [$rm, $rc, $ra, $controller, $action]) {
    $seg1Match = ($segment1 === $ra) || ($ra === '' && $segment1 === 'index') || ($ra === '' && $segment0 === '');
    $seg0Match = $segment0 === $rc || ($rc === '' && $segment0 === '');

    if ($method === $rm && $seg0Match && ($segment1 === $ra || ($ra === 'index' && $segment1 === 'index'))) {
        $file = ROOT_PATH . "/controllers/{$controller}.php";
        if (!file_exists($file)) break;
        require_once $file;
        $ctrl = new $controller();
        if ($segment2 !== null && method_exists($ctrl, $action)) {
            $ctrl->$action($segment2);
        } else {
            $ctrl->$action();
        }
        $matched = true;
        break;
    }
}

if (!$matched) {
    // Default: dashboard if logged in, else login
    session_name(SESSION_NAME);
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (isset($_SESSION['user_id'])) {
        require_once ROOT_PATH . '/controllers/DashboardController.php';
        (new DashboardController())->index();
    } else {
        require_once ROOT_PATH . '/controllers/AuthController.php';
        (new AuthController())->loginForm();
    }
}
