<?php
require_once ROOT_PATH . '/controllers/BaseController.php';

class AuthController extends BaseController
{
    // GET /auth/login
    public function loginForm(): void
    {
        if ($this->currentUser) $this->redirect('/dashboard');
        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();

        ob_start();
        include ROOT_PATH . '/views/auth/login.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/views/layout/auth.php';
    }

    // POST /auth/login
    public function login(): void
    {
        $this->verifyCsrf();

        $email    = strtolower(trim($this->post('email')));
        $password = $_POST['password'] ?? '';   // raw – not sanitized (it's hashed)

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('danger', 'Invalid email format.');
            $this->redirect('/auth/login');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password'])) {
            $this->audit->audit(null, 'auth', 'LOGIN_FAILED', $email);
            $this->flash('danger', 'Invalid credentials. Please try again.');
            $this->redirect('/auth/login');
        }

        // Regenerate session on login to prevent session fixation
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];

        $this->audit->audit($user['id'], 'auth', 'LOGIN_SUCCESS');
        $this->flash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
        $this->redirect('/dashboard');
    }

    // GET /auth/register
    public function registerForm(): void
    {
        if ($this->currentUser) $this->redirect('/dashboard');
        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();

        ob_start();
        include ROOT_PATH . '/views/auth/register.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/views/layout/auth.php';
    }

    // POST /auth/register
    public function register(): void
    {
        $this->verifyCsrf();

        $name     = $this->post('name');
        $email    = strtolower(trim($this->post('email')));
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $role     = $this->post('role', 'resident');

        // Validation
        $errors = [];
        if (strlen($name) < 2)                       $errors[] = 'Name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if (strlen($password) < 8)                   $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)                  $errors[] = 'Passwords do not match.';
        if ($this->userModel->findByEmail($email))   $errors[] = 'Email already registered.';
        if (!in_array($role, ['admin','resident','guest'])) $role = 'resident';

        if ($errors) {
            $this->flash('danger', implode(' ', $errors));
            $this->redirect('/auth/register');
        }

        $id = $this->userModel->create($name, $email, $password, $role);
        $this->audit->audit($id, 'auth', 'REGISTER');
        $this->flash('success', 'Account created! Please log in.');
        $this->redirect('/auth/login');
    }

    // GET /auth/logout
    public function logout(): void
    {
        $uid = $_SESSION['user_id'] ?? null;
        $this->audit->audit($uid, 'auth', 'LOGOUT');

        session_unset();
        session_destroy();
        $this->redirect('/auth/login');
    }
}
