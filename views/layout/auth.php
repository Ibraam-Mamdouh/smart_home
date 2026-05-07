<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> — <?= isset($pageTitle) ? $pageTitle : 'Sign In' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/app.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: var(--bg-base);
            background-image:
                radial-gradient(ellipse 70% 50% at 50% -5%,  rgba(0,212,160,.07),  transparent),
                radial-gradient(ellipse 50% 40% at 85% 85%,  rgba(34,211,238,.04), transparent);
        }
        .auth-wrap  { width: 100%; max-width: 390px; }
        .auth-card  {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            padding: 38px 34px 32px;
            box-shadow: var(--sh-lg);
        }
        .auth-logo  {
            width: 54px; height: 54px;
            background: linear-gradient(135deg, var(--accent), #00a87e);
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.45rem; color: #0b0f18;
            margin: 0 auto 18px;
            box-shadow: 0 8px 24px rgba(0,212,160,.28);
        }
        .auth-title { font-size: 1.25rem; font-weight: 800; text-align: center; margin-bottom: 3px; letter-spacing: -.025em; }
        .auth-sub   { font-size: .76rem; color: var(--text-3); text-align: center; margin-bottom: 26px; }
        .auth-foot  { text-align: center; margin-top: 18px; font-size: .76rem; color: var(--text-3); }
        .auth-foot a{ color: var(--accent); }
        .auth-demo  {
            margin-top: 14px;
            padding: 10px 14px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--r-sm);
            font-size: .72rem;
            color: var(--text-3);
            text-align: center;
        }
        /* form field spacing */
        .auth-field { margin-bottom: 14px; }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo"><i class="bi bi-house-gear-fill"></i></div>
        <div class="auth-title">SmartHome</div>
        <div class="auth-sub">Resource Management System</div>
        <?= $content ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
