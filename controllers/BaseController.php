<?php
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/Database.php';
require_once ROOT_PATH . '/models/User.php';
require_once ROOT_PATH . '/models/Automation.php';

/**
 * BaseController — shared helpers for all controllers.
 *
 * Provides:
 *  - Session management
 *  - RBAC enforcement (FR-26)
 *  - View rendering
 *  - JSON response helper (for AJAX)
 *  - Flash messages
 *  - Input sanitisation
 *  - CSRF protection
 */
abstract class BaseController
{
    protected User       $userModel;
    protected Automation $audit;
    protected ?array     $currentUser = null;

    public function __construct()
    {
        // Start secure session
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false,   // set true in HTTPS production
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }

        $this->userModel = new User();
        $this->audit     = new Automation();

        // Hydrate current user from session
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = $this->userModel->findById((int)$_SESSION['user_id']);
        }
    }

    // ── RBAC (FR-26) ────────────────────────────────────────

    protected function requireAuth(): void
    {
        if (!$this->currentUser) {
            $this->redirect('/auth/login');
            exit;
        }
    }

    protected function requireRole(string ...$roles): void
    {
        $this->requireAuth();
        if (!in_array($this->currentUser['role'], $roles, true)) {
            http_response_code(403);
            $this->render('errors/403');
            exit;
        }
    }

    protected function isAdmin(): bool
    {
        return $this->currentUser && $this->currentUser['role'] === 'admin';
    }

    // ── View Rendering ───────────────────────────────────────

    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $currentUser = $this->currentUser;

        // Load shared layout
        ob_start();
        $viewFile = ROOT_PATH . "/views/{$view}.php";
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "<h1>View not found: {$view}</h1>";
            return;
        }
        include $viewFile;
        $content = ob_get_clean();

        include ROOT_PATH . '/views/layout/main.php';
    }

    protected function renderPartial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $currentUser = $this->currentUser;
        $viewFile    = ROOT_PATH . "/views/{$view}.php";
        if (file_exists($viewFile)) include $viewFile;
    }

    // ── JSON Helper (AJAX) ───────────────────────────────────

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Redirect ─────────────────────────────────────────────

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    // ── Flash Messages ───────────────────────────────────────

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    // ── CSRF ─────────────────────────────────────────────────

    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            $this->json(['error' => 'CSRF token mismatch'], 419);
        }
    }

    // ── Input Sanitisation ───────────────────────────────────

    protected function sanitize(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    protected function post(string $key, mixed $default = ''): mixed
    {
        return isset($_POST[$key]) ? $this->sanitize((string)$_POST[$key]) : $default;
    }

    protected function get(string $key, mixed $default = ''): mixed
    {
        return isset($_GET[$key]) ? $this->sanitize((string)$_GET[$key]) : $default;
    }
}
