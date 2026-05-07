<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SmartHome Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#0f1117; color:#e2e8f0; padding:30px; font-family:monospace; }
        .box { background:#161b27; border:1px solid #1e2a3a; border-radius:10px; padding:20px; margin-bottom:16px; }
        .ok  { color:#00c896; } .err { color:#ef4444; } .warn { color:#f59e0b; }
        code { background:#0f1117; padding:2px 6px; border-radius:4px; color:#00c896; }
    </style>
</head>
<body>
<h4 class="mb-4">🔍 SmartHome — Login Diagnostics</h4>

<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'smart_home_db';

// ── Test 1: DB Connection ─────────────────────────
echo '<div class="box"><strong>1. Database Connection</strong><br>';
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    echo '<span class="ok">✅ Connected to MySQL — database exists</span>';
} catch (PDOException $e) {
    echo '<span class="err">❌ '.$e->getMessage().'</span><br>';
    echo '<span class="warn">👉 Run setup.php first OR check if MySQL is running in XAMPP</span>';
    echo '</div></body></html>'; exit;
}
echo '</div>';

// ── Test 2: Users table ───────────────────────────
echo '<div class="box"><strong>2. Users Table</strong><br>';
try {
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<span class='ok'>✅ users table exists — $count user(s) found</span><br>";
    $users = $pdo->query("SELECT id, name, email, role, LEFT(password,30) AS hash_preview FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "<br>👤 ID:{$u['id']} | <code>{$u['email']}</code> | Role:{$u['role']} | Hash starts: <code>{$u['hash_preview']}…</code>";
    }
} catch (PDOException $e) {
    echo '<span class="err">❌ users table missing — run setup.php</span>';
    echo '</div></body></html>'; exit;
}
echo '</div>';

// ── Test 3: Password verification ─────────────────
echo '<div class="box"><strong>3. Password Verification Test</strong><br>';
$testEmail = 'admin@smarthome.local';
$testPass  = 'Admin@1234';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$testEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<span class='err'>❌ No user found with email <code>$testEmail</code></span><br>";
    echo "<span class='warn'>👉 Run setup.php to create the admin user</span>";
} else {
    echo "<span class='ok'>✅ User found: {$user['name']}</span><br>";
    $verify = password_verify($testPass, $user['password']);
    if ($verify) {
        echo "<span class='ok'>✅ Password <code>$testPass</code> matches the stored hash — login SHOULD work</span><br>";
        echo "<span class='warn'>⚠️ If login still fails, clear your browser cookies/session and try again</span>";
    } else {
        echo "<span class='err'>❌ Password does NOT match stored hash</span><br>";
        echo "Stored hash: <code>{$user['password']}</code><br><br>";
        // Fix it right now
        $newHash = password_hash($testPass, PASSWORD_BCRYPT, ['cost'=>12]);
        $pdo->prepare("UPDATE users SET password=? WHERE email=?")->execute([$newHash, $testEmail]);
        echo "<span class='ok'>✅ AUTO-FIXED: Password hash has been updated. Try logging in now.</span>";
    }
}
echo '</div>';

// ── Test 4: Session ───────────────────────────────
echo '<div class="box"><strong>4. PHP Session Test</strong><br>';
session_start();
$_SESSION['test'] = 'ok';
echo (isset($_SESSION['test']) && $_SESSION['test'] === 'ok')
    ? "<span class='ok'>✅ Sessions working</span>"
    : "<span class='err'>❌ Sessions not working</span>";
echo '<br>Session save path: <code>'.session_save_path().'</code>';
echo '</div>';

// ── Test 5: PHP version ───────────────────────────
echo '<div class="box"><strong>5. PHP Version</strong><br>';
$ver = PHP_VERSION;
$ok  = version_compare($ver, '8.0', '>=');
echo $ok
    ? "<span class='ok'>✅ PHP $ver</span>"
    : "<span class='err'>❌ PHP $ver — need 8.0+</span>";
echo '</div>';
?>

<div class="box">
    <strong>Next Step</strong><br>
    <?php if (isset($verify) && $verify): ?>
    <span class="ok">✅ Everything looks correct!</span><br>
    1. <a href="auth/login" style="color:#00c896">Go to login page</a><br>
    2. Clear cookies: press <kbd>Ctrl+Shift+Delete</kbd> → clear cookies for localhost<br>
    3. Try logging in again
    <?php else: ?>
    <span class="warn">👉 Refresh this page after any fixes, then <a href="auth/login" style="color:#00c896">go to login</a></span>
    <?php endif; ?>
</div>

<p style="color:#4a5568;font-size:.75rem">⚠️ Delete debug.php after troubleshooting</p>
</body>
</html>
