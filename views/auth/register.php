<?php if (isset($flash)): ?>
<div class="flash-bar flash-<?= $flash['type'] ?>" style="margin-bottom:16px">
    <i class="bi bi-<?= $flash['type']==='success'?'check-circle-fill':'exclamation-triangle-fill' ?>"></i>
    <span><?= htmlspecialchars($flash['message']) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/auth/register" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

    <div class="auth-field">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Ahmed Hassan" required>
    </div>
    <div class="auth-field">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
    </div>
    <div class="auth-field">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
            <option value="resident">Resident</option>
            <option value="guest">Guest (read-only)</option>
        </select>
    </div>
    <div class="auth-field">
        <label class="form-label">Password <span style="color:var(--text-3);font-weight:400">(min 8 chars)</span></label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <div class="auth-field">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
    </div>

    <button type="submit" class="btn-accent" style="width:100%;justify-content:center;padding:10px;margin-top:6px;font-size:.86rem">
        <i class="bi bi-person-plus"></i> Create Account
    </button>
</form>

<div class="auth-foot" style="margin-top:16px">
    Already have an account? <a href="<?= BASE_URL ?>/auth/login">Sign in</a>
</div>
