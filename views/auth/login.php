<?php if (isset($flash)): ?>
<div class="flash-bar flash-<?= $flash['type'] ?>" style="margin-bottom:16px">
    <i class="bi bi-<?= $flash['type']==='success'?'check-circle-fill':'exclamation-triangle-fill' ?>"></i>
    <span><?= htmlspecialchars($flash['message']) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/auth/login" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

    <div class="auth-field">
        <label class="form-label">Email Address</label>
        <div class="input-group">
            <span class="input-group-text" style="border-right:none"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control"
                   style="border-left:none"
                   placeholder="admin@smarthome.local"
                   value="admin@smarthome.local" required autofocus>
        </div>
    </div>

    <div class="auth-field">
        <label class="form-label">Password</label>
        <div class="input-group">
            <span class="input-group-text" style="border-right:none"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="pwdInput"
                   class="form-control" style="border-left:none;border-right:none"
                   placeholder="••••••••" value="Admin@1234" required>
            <button type="button" class="input-group-text" id="togglePwd"
                    style="cursor:pointer" title="Show/hide password">
                <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn-accent" style="width:100%;justify-content:center;padding:10px;margin-top:6px;font-size:.86rem">
        <i class="bi bi-box-arrow-in-right"></i> Sign In
    </button>
</form>

<div class="auth-foot">
    No account? <a href="<?= BASE_URL ?>/auth/register">Create one</a>
</div>
<div class="auth-demo">
    <i class="bi bi-info-circle" style="margin-right:4px"></i>
    Demo &nbsp;·&nbsp; <kbd>admin@smarthome.local</kbd> / <kbd>Admin@1234</kbd>
</div>

<script>
document.getElementById('togglePwd')?.addEventListener('click', function() {
    const inp  = document.getElementById('pwdInput');
    const icon = document.getElementById('eyeIcon');
    const show = inp.type === 'password';
    inp.type       = show ? 'text'     : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    this.setAttribute('title', show ? 'Hide password' : 'Show password');
});
</script>
