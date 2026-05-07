<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?><?= isset($pageTitle) ? ' — '.$pageTitle : '' ?></title>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <link href="<?= BASE_URL ?>/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="sb-overlay" id="sbOverlay"></div>

<!-- SIDEBAR -->
<nav id="sidebar">
    <div class="sb-brand">
        <div class="sb-brand-icon"><i class="bi bi-house-gear-fill"></i></div>
        <div>
            <div class="sb-brand-text">SmartHome</div>
            <div class="sb-brand-sub">Resource Management</div>
        </div>
    </div>
    <div class="sb-nav">
        <div class="sb-section">Overview</div>
        <a href="<?= BASE_URL ?>/dashboard" class="sb-link <?= str_contains($_SERVER['REQUEST_URI'],'/dashboard')?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <div class="sb-section">Management</div>
        <a href="<?= BASE_URL ?>/appliances" class="sb-link <?= str_contains($_SERVER['REQUEST_URI'],'/appliances')?'active':'' ?>"><i class="bi bi-plug-fill"></i> Appliances</a>
        <a href="<?= BASE_URL ?>/budget" class="sb-link <?= str_contains($_SERVER['REQUEST_URI'],'/budget')?'active':'' ?>"><i class="bi bi-wallet2"></i> Budget & Alerts</a>
        <a href="<?= BASE_URL ?>/automation" class="sb-link <?= str_contains($_SERVER['REQUEST_URI'],'/automation')?'active':'' ?>"><i class="bi bi-cpu-fill"></i> Automation</a>
        <a href="<?= BASE_URL ?>/reports" class="sb-link <?= str_contains($_SERVER['REQUEST_URI'],'/reports')?'active':'' ?>"><i class="bi bi-bar-chart-fill"></i> Reports</a>
        <?php if (isset($currentUser) && $currentUser['role']==='admin'): ?>
        <div class="sb-section">Admin</div>
        <a href="<?= BASE_URL ?>/automation/audit" class="sb-link <?= str_contains($_SERVER['REQUEST_URI'],'/audit')?'active':'' ?>"><i class="bi bi-shield-check"></i> Audit Trail</a>
        <?php endif; ?>
    </div>
    <div class="sb-footer">
        <div class="sb-user">
            <div class="sb-avatar"><?= strtoupper(substr($currentUser['name']??'U',0,1)) ?></div>
            <div style="min-width:0;flex:1">
                <div class="sb-user-name text-truncate"><?= htmlspecialchars($currentUser['name']??'') ?></div>
                <div class="sb-user-role"><?= ucfirst($currentUser['role']??'') ?></div>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout" class="sb-logout" title="Sign out"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>

<!-- TOPBAR -->
<header id="topbar">
    <div class="topbar-left">
        <button id="menuToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
        <nav class="page-breadcrumb">
            <a href="<?= BASE_URL ?>/dashboard">Home</a>
            <?php if(isset($pageTitle)): ?><span class="sep">/</span><span><?= htmlspecialchars($pageTitle) ?></span><?php endif; ?>
        </nav>
    </div>
    <div class="topbar-right">

        <!-- Sensor Simulation -->
        <label class="sim-wrap" for="simToggle" title="Simulate sensor data feed">
            <span class="sim-indicator" id="simDot"></span>
            <input type="checkbox" id="simToggle" role="switch">
            <span class="sim-label">Sensor Sim</span>
        </label>

        <!-- Notification Bell -->
        <div class="notif-wrap">
            <button class="notif-bell" id="notifBell" aria-label="Notifications" aria-expanded="false">
                <i class="bi bi-bell"></i>
                <span class="notif-badge" id="notifBadge">0</span>
            </button>
            <!-- Notification Dropdown Panel -->
            <div class="notif-panel" id="notifPanel" role="dialog" aria-label="Notifications">
                <div class="notif-header">
                    <span class="notif-title">Notifications</span>
                    <button class="notif-mark-all" id="notifMarkAll">Mark all read</button>
                </div>
                <div class="notif-body" id="notifBody">
                    <div class="notif-empty"><i class="bi bi-bell-slash"></i> No notifications yet</div>
                </div>
                <a href="<?= BASE_URL ?>/budget" class="notif-footer-link">View all alerts →</a>
            </div>
        </div>

        <!-- Clock -->
        <div class="topbar-clock">
            <i class="bi bi-clock"></i>
            <span id="liveClock">--:--:--</span>
        </div>
    </div>
</header>

<!-- MAIN -->
<main id="main">
    <div class="page-body">
        <?php
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        if ($flash):
            $ficon = ['success'=>'check-circle-fill','danger'=>'x-circle-fill','warning'=>'exclamation-triangle-fill','info'=>'info-circle-fill'][$flash['type']] ?? 'info-circle-fill';
        ?>
        <div class="flash-bar flash-<?= htmlspecialchars($flash['type']) ?>" role="alert">
            <i class="bi bi-<?= $ficon ?>"></i>
            <span><?= htmlspecialchars($flash['message']) ?></span>
            <button onclick="this.parentElement.remove()" class="flash-close"><i class="bi bi-x-lg"></i></button>
        </div>
        <?php endif; ?>
        <?= $content ?>
    </div>
</main>

<div id="toast-zone" aria-live="polite"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Constants ─────────────────────────────────── */
const BASE_URL = '<?= BASE_URL ?>';
const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* ── Clock ─────────────────────────────────────── */
(function tick() {
    const el = document.getElementById('liveClock');
    if (el) el.textContent = new Date().toLocaleTimeString('en-EG',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
    setTimeout(tick, 1000);
})();

/* ── Sidebar (mobile) ──────────────────────────── */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sbOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    overlay.classList.toggle('show', open);
});
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('show'); });

/* ── Toast ─────────────────────────────────────── */
function showToast(msg, type = 'info') {
    const icons = {success:'check-circle-fill',error:'x-circle-fill',warning:'exclamation-triangle-fill',info:'info-circle-fill'};
    const zone  = document.getElementById('toast-zone');
    const el    = document.createElement('div');
    el.className = `sh-toast ${type}`;
    el.innerHTML = `
        <div class="toast-icon-wrap"><i class="bi bi-${icons[type]||'info-circle-fill'}"></i></div>
        <div class="toast-content">
            <div class="toast-msg">${msg}</div>
            <div class="toast-ts">${new Date().toLocaleTimeString('en-EG',{hour:'2-digit',minute:'2-digit'})}</div>
        </div>
        <button onclick="this.parentElement.classList.add('out');setTimeout(()=>this.parentElement.remove(),250)" class="toast-x"><i class="bi bi-x"></i></button>`;
    zone.appendChild(el);
    setTimeout(() => { el.classList.add('out'); setTimeout(() => el.remove(), 260); }, 5000);
}

/* ── Notification Panel ────────────────────────── */
let _notifs  = [];
let _unread  = 0;

function pushNotif(msg, type='info', priority='MEDIUM') {
    _notifs.unshift({ id: Date.now()+Math.random(), msg, type, priority, time: new Date(), read: false });
    if (_notifs.length > 25) _notifs.pop();
    _unread++;
    _renderNotifs();
}

function _renderNotifs() {
    const badge = document.getElementById('notifBadge');
    const body  = document.getElementById('notifBody');
    const bell  = document.getElementById('notifBell');

    badge.textContent   = _unread > 9 ? '9+' : _unread;
    badge.style.display = _unread > 0 ? 'flex' : 'none';
    bell.classList.toggle('has-unread', _unread > 0);

    if (_notifs.length === 0) {
        body.innerHTML = '<div class="notif-empty"><i class="bi bi-bell-slash"></i> No notifications yet</div>';
        return;
    }
    const typeIcon  = {success:'check-circle-fill',error:'x-circle-fill',warning:'exclamation-triangle-fill',info:'info-circle-fill',safety:'fire',budget:'wallet2',anomaly:'activity'};
    const typeColor = {success:'var(--accent)',error:'var(--red)',warning:'var(--yellow)',info:'var(--cyan)',safety:'var(--red)',budget:'var(--orange)',anomaly:'var(--yellow)'};
    const priClass  = {LOW:'np-low',MEDIUM:'np-med',HIGH:'np-high',CRITICAL:'np-critical'};
    const priLabel  = {LOW:'Low',MEDIUM:'Med',HIGH:'High',CRITICAL:'Critical'};

    body.innerHTML = _notifs.slice(0,12).map(n => `
    <div class="notif-item${n.read?' n-read':''}" onclick="_readNotif(${n.id},this)">
        <div class="ni-icon" style="color:${typeColor[n.type]||typeColor.info}">
            <i class="bi bi-${typeIcon[n.type]||'info-circle-fill'}"></i>
        </div>
        <div class="ni-content">
            <div class="ni-msg">${n.msg}</div>
            <div class="ni-meta">
                <span class="ni-pri ${priClass[n.priority]||'np-low'}">${priLabel[n.priority]||''}</span>
                <span class="ni-time">${n.time.toLocaleTimeString('en-EG',{hour:'2-digit',minute:'2-digit'})}</span>
            </div>
        </div>
        ${!n.read ? '<div class="ni-dot"></div>' : ''}
    </div>`).join('');
}

function _readNotif(id, el) {
    const n = _notifs.find(x=>x.id===id);
    if (n && !n.read) { n.read=true; _unread=Math.max(0,_unread-1); el.classList.add('n-read'); el.querySelector('.ni-dot')?.remove(); _renderNotifs(); }
}

document.getElementById('notifMarkAll')?.addEventListener('click', () => {
    _notifs.forEach(n=>n.read=true); _unread=0; _renderNotifs();
});

// Toggle panel open/close
const notifBell  = document.getElementById('notifBell');
const notifPanel = document.getElementById('notifPanel');
notifBell?.addEventListener('click', e => {
    e.stopPropagation();
    const open = notifPanel.classList.toggle('open');
    notifBell.setAttribute('aria-expanded', open);
});
document.addEventListener('click', e => {
    if (!notifPanel?.contains(e.target) && e.target !== notifBell && !notifBell?.contains(e.target)) {
        notifPanel?.classList.remove('open');
    }
});

/* ── Sensor Simulation Engine ──────────────────── */
let _simTimer = null;
let _evtIdx   = 0;

// Realistic sensor event feed — simulates an external sensor gateway
const SENSOR_EVENTS = [
    {msg:'Living Room AC — power reading nominal (1,782 W)',          type:'info',    priority:'LOW'},
    {msg:'Refrigerator — temperature stable at 4°C',                  type:'success', priority:'LOW'},
    {msg:'Water Heater — duty cycle 68%, within range',               type:'info',    priority:'LOW'},
    {msg:'Smart TV — low standby draw detected (8 W)',                 type:'info',    priority:'LOW'},
    {msg:'EV Charger — charging session in progress (7.1 kW)',        type:'success', priority:'MEDIUM'},
    {msg:'Sensor gateway — all 8 devices reporting OK',               type:'success', priority:'LOW'},
    {msg:'Peak tariff active (17:00–23:00) — rate: 1.85 EGP/kWh',   type:'warning', priority:'MEDIUM'},
    {msg:'Electricity budget now at 65% of monthly limit',            type:'info',    priority:'MEDIUM'},
    {msg:'CO₂ footprint today: 1.84 kg — within green threshold',    type:'success', priority:'LOW'},
    {msg:'Master Bedroom AC — wattage spike detected (+12%)',         type:'warning', priority:'HIGH'},
    {msg:'Water Heater — temperature warning: 58°C',                  type:'warning', priority:'HIGH'},
    {msg:'Gas budget alert — 80% of monthly cap reached',             type:'error',   priority:'HIGH'},
    {msg:'Kids Room Fan — health score dropped to 72%',               type:'warning', priority:'MEDIUM'},
    {msg:'Anomaly detected: Dishwasher water flow irregular',         type:'warning', priority:'HIGH'},
    {msg:'Automation rule fired: off-peak schedule applied',          type:'success', priority:'LOW'},
    {msg:'Refrigerator — compressor cycle frequency normal',          type:'info',    priority:'LOW'},
    {msg:'Monthly forecast updated — predicted bill: 412 EGP',       type:'info',    priority:'MEDIUM'},
    {msg:'Eco-challenge "May Energy Saver" — you are on track!',     type:'success', priority:'LOW'},
];

document.getElementById('simToggle')?.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('simDot').classList.add('active');
        showToast('Sensor simulation started — live data feed active', 'success');
        pushNotif('Sensor gateway connected — data feed started', 'success', 'LOW');
        _tickSim();
        _simTimer = setInterval(_tickSim, 6000);
    } else {
        document.getElementById('simDot').classList.remove('active');
        clearInterval(_simTimer);
        showToast('Sensor simulation paused', 'info');
    }
});

async function _tickSim() {
    // Push a realistic sensor event to notification panel
    const evt = SENSOR_EVENTS[_evtIdx++ % SENSOR_EVENTS.length];
    pushNotif(evt.msg, evt.type, evt.priority);

    // Only toast HIGH/CRITICAL to avoid noise
    if (evt.priority === 'HIGH' || evt.priority === 'CRITICAL') {
        showToast(evt.msg, evt.type === 'error' ? 'error' : 'warning');
    }

    // Backend tick — store telemetry, evaluate rules
    try {
        const r = await fetch(`${BASE_URL}/api/telemetry.php?action=mock_tick`, {
            method :'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body   :`csrf_token=${CSRF}`
        });
        const d = await r.json();
        d.triggered?.forEach(t => {
            if (t.action_type==='SHUTDOWN') {
                pushNotif(`Emergency shutdown triggered — ${t.rule_name}`, 'error', 'CRITICAL');
                showToast(`🚨 Emergency shutdown: ${t.rule_name}`, 'error');
            }
        });
        if (typeof refreshDashboard === 'function') refreshDashboard();
    } catch(_) { /* silent when offline */ }
}

/* ── Flash auto-dismiss ────────────────────────── */
setTimeout(() => {
    document.querySelectorAll('.flash-bar').forEach(el => {
        el.style.transition = 'opacity .4s, transform .4s';
        el.style.opacity    = '0';
        el.style.transform  = 'translateY(-6px)';
        setTimeout(() => el.remove(), 420);
    });
}, 5500);
</script>
</body>
</html>
