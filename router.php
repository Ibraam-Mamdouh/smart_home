<?php
/**
 * router.php — PHP built-in server router
 * Usage: php -S localhost:3000 router.php
 *        (run from inside the smart_home/ directory)
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// ── Serve real static files directly (CSS, JS, images) ──────
$staticFile = __DIR__ . $uri;
if ($uri !== '/' && file_exists($staticFile) && !is_dir($staticFile)) {
    return false; // let the built-in server serve it
}

// ── Block access to sensitive directories ────────────────────
$blocked = ['config', 'models', 'controllers', 'database'];
foreach ($blocked as $dir) {
    if (str_starts_with(ltrim($uri, '/'), $dir . '/')) {
        http_response_code(403);
        echo '<h1>403 Forbidden</h1>';
        return true;
    }
}

// ── Route everything through the front controller ────────────
$_SERVER['REQUEST_URI'] = $uri;
require __DIR__ . '/index.php';
