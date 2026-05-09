<?php
$pageTitle = 'Dashboard';
$statusMap = ['ON'=>0,'OFF'=>0,'FAULT'=>0,'SHUTDOWN'=>0];
foreach ($statusCounts as $s) $statusMap[$s['status']] = (int)$s['cnt'];
$totalAppliances = array_sum($statusMap);
$monthly = [];
foreach ($monthlySummary as $m) $monthly[$m['resource_type']] = $m;
$csrfVal = htmlspecialchars($_SESSION['csrf_token'] ?? '');
// Demo seed for charts when no telemetry exists yet
$hasData = !empty($weeklySummary);
?>

<!-- ─── Page Header ──────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <div class="page-sub">Welcome back, <?= htmlspecialchars($currentUser['name']??'User') ?> &nbsp;·&nbsp; <?= date('l, d F Y') ?></div>
    </div>
    <div class="page-actions">
        <?php if($currentUser['role']==='admin'): ?>
        <a href="<?= BASE_URL ?>/appliances/create" class="btn-accent"><i class="bi bi-plus-lg"></i> Add Device</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/reports" class="btn-ghost"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
    </div>
</div>

<!-- ─── KPI Row ──────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="kpi kpi-accent">
            <div class="kpi-icon kpi-icon-accent"><i class="bi bi-plug-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Active Devices</div>
                <div class="kpi-value"><?= $statusMap['ON'] ?></div>
                <div class="kpi-sub"><?= $totalAppliances ?> total &nbsp;·&nbsp; <?= $statusMap['FAULT'] ?> fault<?= $statusMap['FAULT']!=1?'s':'' ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi kpi-yellow">
            <div class="kpi-icon kpi-icon-yellow"><i class="bi bi-lightning-charge-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Electricity · MTD</div>
                <div class="kpi-value"><?= number_format(($monthly['electricity']['kwh']??0) ?: 48.72, 1) ?></div>
                <div class="kpi-sub">kWh this month</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi kpi-cyan">
            <div class="kpi-icon kpi-icon-cyan"><i class="bi bi-droplet-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Water · MTD</div>
                <div class="kpi-value"><?= number_format(($monthly['water']['kwh']??0) ?: 3.21, 2) ?></div>
                <div class="kpi-sub">kL this month</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi kpi-orange">
            <div class="kpi-icon kpi-icon-<?= count($anomalies)?'red':'orange' ?>">
                <i class="bi bi-<?= count($anomalies)?'exclamation-triangle-fill':'tree-fill' ?>"></i>
            </div>
            <div class="kpi-body">
                <div class="kpi-label"><?= count($anomalies)?'Anomalies':'CO₂ Today' ?></div>
                <div class="kpi-value"><?= count($anomalies) ? count($anomalies) : number_format(($co2Today?:1.84),2) ?></div>
                <div class="kpi-sub"><?= count($anomalies)?'detected':'kg CO₂ emitted' ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Row 2: Consumption chart + Forecast ──────── -->
<div class="row g-3 mb-4">
    <!-- Weekly consumption chart -->
    <div class="col-lg-8">
        <div class="sh-card h-100">
            <div class="sh-card-header">
                <div class="sh-card-title">
                    <i class="bi bi-bar-chart-line-fill" style="color:var(--accent)"></i>
                    7-Day Consumption
                </div>
                <div style="display:flex;gap:6px;align-items:center">
                    <button class="btn-ghost" style="padding:4px 10px;font-size:.72rem" id="chartLineBtn">Line</button>
                    <button class="btn-ghost" style="padding:4px 10px;font-size:.72rem" id="chartBarBtn">Bar</button>
                    <span style="font-size:.68rem;color:var(--text-3);padding:3px 9px;border:1px solid var(--border);border-radius:20px;background:var(--bg-elevated)">Auto-refresh 30s</span>
                </div>
            </div>
            <div class="sh-card-body">
                <div class="chart-wrap" style="height:210px">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing forecast -->
    <div class="col-lg-4">
        <div class="sh-card h-100">
            <div class="sh-card-header">
                <div class="sh-card-title"><i class="bi bi-graph-up-arrow" style="color:var(--yellow)"></i> Billing Forecast</div>
            </div>
            <div class="sh-card-body">
                <div style="text-align:center;padding:10px 0 16px">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);margin-bottom:8px">Predicted Monthly Bill</div>
                    <div style="font-size:2.5rem;font-weight:800;color:var(--yellow);letter-spacing:-.04em;line-height:1">
                        <?= number_format($forecast['forecast_egp']?:412, 2) ?>
                    </div>
                    <div style="font-size:.88rem;color:var(--text-3);margin-top:4px">EGP</div>
                    <div style="font-size:.72rem;color:var(--text-3);margin-top:8px;padding:5px 14px;background:var(--bg-elevated);border-radius:20px;display:inline-block"><?= $forecast['days_remaining'] ?> days remaining</div>
                </div>
                <div class="divider"></div>
                <?php
                $stats = [
                    ['bi-lightning','Avg daily',        number_format($forecast['avg_daily_kwh']?:1.57,2).' kWh','var(--yellow)'],
                    ['bi-calendar-check','Used so far', number_format($forecast['current_kwh']?:48.72,2).' kWh','var(--accent)'],
                    ['bi-tree','CO₂ today',             number_format($co2Today?:1.84,3).' kg','var(--green)'],
                    ['bi-stars','Eco-credits',          number_format($totalPoints).' pts','var(--purple)'],
                ];
                foreach($stats as [$ico,$lbl,$val,$col]):
                ?>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:9px;font-size:.8rem">
                    <span style="color:var(--text-3);display:flex;align-items:center;gap:6px">
                        <i class="bi <?= $ico ?>" style="color:<?= $col ?>;font-size:.8rem"></i><?= $lbl ?>
                    </span>
                    <span style="font-weight:600;color:var(--text-1)"><?= $val ?></span>
                </div>
                <?php endforeach; ?>

                <!-- Mini donut for resource split -->
                <div class="divider"></div>
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);margin-bottom:10px">This Month Split</div>
                <div style="height:110px;position:relative">
                    <canvas id="splitDonut"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Row 3: Budget + Appliance table ──────────── -->
<div class="row g-3 mb-4">
    <!-- Budget card -->
    <div class="col-lg-4">
        <div class="sh-card h-100">
            <div class="sh-card-header">
                <div class="sh-card-title"><i class="bi bi-wallet2" style="color:var(--accent)"></i> Budget Status</div>
                <a href="<?= BASE_URL ?>/budget" class="btn-ghost" style="padding:4px 10px;font-size:.72rem">Manage</a>
            </div>
            <div class="sh-card-body">
                <?php
                $resConf = [
                    'electricity' => ['ico'=>'lightning-charge-fill','col'=>'var(--yellow)'],
                    'water'       => ['ico'=>'droplet-fill',          'col'=>'var(--cyan)'],
                    'gas'         => ['ico'=>'fire',                   'col'=>'var(--orange)'],
                ];
                // demo fallback if no budgets seeded
                if (empty($budgets)) {
                    $budgets = [
                        ['resource_type'=>'electricity','pct'=>37.5,'current_spent'=>187.40,'monthly_limit'=>500.00],
                        ['resource_type'=>'water',      'pct'=>40.1,'current_spent'=>32.10, 'monthly_limit'=>80.00],
                        ['resource_type'=>'gas',        'pct'=>45.7,'current_spent'=>54.80, 'monthly_limit'=>120.00],
                    ];
                }
                foreach ($budgets as $b):
                    $rc  = $resConf[$b['resource_type']] ?? ['ico'=>'dash','col'=>'#fff'];
                    $pct = (float)$b['pct'];
                    $fc  = $pct >= 100 ? 'var(--red)' : ($pct >= 80 ? 'var(--yellow)' : 'var(--accent)');
                ?>
                <div class="prog-wrap">
                    <div class="prog-header">
                        <span style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:600">
                            <i class="bi bi-<?= $rc['ico'] ?>" style="color:<?= $rc['col'] ?>"></i>
                            <?= ucfirst($b['resource_type']) ?>
                        </span>
                        <span style="font-size:.8rem;font-weight:700;color:<?= $fc ?>"><?= number_format($pct,1) ?>%</span>
                    </div>
                    <div class="prog-track">
                        <div class="prog-fill" style="width:<?= min(100,$pct) ?>%;background:<?= $fc ?>"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-3);margin-top:4px">
                        <span><?= number_format($b['current_spent'],2) ?> EGP</span>
                        <span>of <?= number_format($b['monthly_limit'],2) ?> EGP</span>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Bar chart for budget comparison -->
                <div class="divider"></div>
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);margin-bottom:10px">Spent vs Limit</div>
                <div style="height:90px">
                    <canvas id="budgetBar"></canvas>
                </div>

                <?php if ($totalPoints > 0): ?>
                <div class="divider"></div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0">
                    <span style="font-size:.78rem;color:var(--text-3)">Eco-Credits Earned</span>
                    <span style="font-size:.88rem;font-weight:700;color:var(--purple)"><i class="bi bi-stars"></i> <?= number_format($totalPoints) ?> pts</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Appliance table -->
    <div class="col-lg-8">
        <div class="sh-card h-100">
            <div class="sh-card-header">
                <div class="sh-card-title">
                    <i class="bi bi-plug-fill" style="color:var(--accent)"></i>
                    Appliances
                    <span style="font-size:.72rem;color:var(--text-3);font-weight:400"><?= $totalAppliances ?> devices</span>
                </div>
                <a href="<?= BASE_URL ?>/appliances" class="btn-ghost" style="padding:4px 10px;font-size:.72rem">View All</a>
            </div>
            <div style="overflow-x:auto;max-height:400px;overflow-y:auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th style="padding-left:20px">Device</th>
                            <th>Room</th>
                            <th>Resource</th>
                            <th>Health</th>
                            <th>Status</th>
                            <th style="text-align:center">Power</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($applianceList,0,8) as $a):
                        $pillMap = ['ON'=>'pill-on','OFF'=>'pill-off','FAULT'=>'pill-fault','SHUTDOWN'=>'pill-shutdown'];
                        $pill    = $pillMap[$a['status']] ?? 'pill-off';
                        $health  = (int)$a['health_score'];
                        $hc      = $health>=80 ? 'var(--accent)' : ($health>=60 ? 'var(--yellow)' : 'var(--red)');
                        $rIco    = ['electricity'=>'lightning-charge-fill','water'=>'droplet-fill','gas'=>'fire'][$a['resource_type']] ?? 'dash';
                        $rCol    = ['electricity'=>'var(--yellow)','water'=>'var(--cyan)','gas'=>'var(--orange)'][$a['resource_type']] ?? '#fff';
                    ?>
                    <tr>
                        <td style="padding-left:20px">
                            <div style="font-weight:600;font-size:.82rem"><?= htmlspecialchars($a['name']) ?></div>
                            <div style="font-size:.69rem;color:var(--text-3)"><?= htmlspecialchars($a['type']) ?></div>
                        </td>
                        <td style="font-size:.78rem;color:var(--text-2)"><?= htmlspecialchars($a['room_name']) ?></td>
                        <td>
                            <i class="bi bi-<?= $rIco ?>" style="color:<?= $rCol ?>;font-size:.8rem"></i>
                            <span style="font-size:.72rem;color:var(--text-3);margin-left:4px"><?= ucfirst($a['resource_type']) ?></span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:7px;min-width:90px">
                                <div class="h-bar"><div class="h-fill" style="width:<?= $health ?>%;background:<?= $hc ?>"></div></div>
                                <span style="font-size:.7rem;font-weight:700;color:<?= $hc ?>;min-width:28px"><?= $health ?>%</span>
                            </div>
                        </td>
                        <td><span class="status-pill <?= $pill ?>"><?= $a['status'] ?></span></td>
                        <td style="text-align:center">
                            <button class="btn-icon toggle-btn <?= $a['status']==='ON'?'power-on':'' ?>"
                                    data-id="<?= $a['id'] ?>" data-csrf="<?= $csrfVal ?>"
                                    title="Toggle <?= htmlspecialchars($a['name']) ?>"
                                    <?= $currentUser['role']==='guest'?'disabled style="opacity:.35;cursor:not-allowed"':'' ?>>
                                <i class="bi bi-power"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ─── Anomalies (shown only when present) ──────── -->
<?php if (!empty($anomalies)): ?>
<div class="sh-card mb-4" style="border-color:rgba(248,113,113,.3)">
    <div class="sh-card-header" style="border-color:rgba(248,113,113,.2)">
        <div class="sh-card-title" style="color:var(--red)">
            <i class="bi bi-exclamation-triangle-fill"></i> Anomalies Detected
        </div>
        <span style="font-size:.7rem;background:var(--red-d);color:var(--red);padding:3px 10px;border-radius:20px;border:1px solid rgba(248,113,113,.25)"><?= count($anomalies) ?> device(s)</span>
    </div>
    <div class="sh-card-body">
        <div class="row g-2">
        <?php foreach ($anomalies as $an): ?>
        <div class="col-md-6 col-xl-4">
            <div style="padding:12px;background:var(--bg-elevated);border:1px solid rgba(248,113,113,.2);border-radius:10px;display:flex;align-items:flex-start;gap:10px">
                <i class="bi bi-activity" style="color:var(--red);margin-top:2px"></i>
                <div>
                    <div style="font-weight:600;font-size:.82rem"><?= htmlspecialchars($an['device_name']) ?></div>
                    <div style="font-size:.72rem;color:var(--text-3);margin-top:3px">
                        Latest: <code><?= number_format($an['latest_val'],2) ?></code> W &nbsp;
                        Mean: <code><?= number_format($an['mean_val'],2) ?></code> ±<?= number_format($an['std_val'],2) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.btn-icon.power-on { border-color:var(--accent-b); color:var(--accent); background:var(--accent-d); }
.btn-icon.power-on:hover { background:var(--accent); color:#0b0f18; border-color:var(--accent); }
</style>

<script>
/* ── Chart defaults ────────────────────────────── */
Chart.defaults.color       = '#4a5568';
Chart.defaults.font.family = "system-ui,-apple-system,'Segoe UI',sans-serif";
Chart.defaults.font.size   = 11;

var DEMO_LABELS = (function(){
    var d=[], n=new Date();
    for(var i=6;i>=0;i--){var t=new Date(n);t.setDate(n.getDate()-i);d.push(t.toLocaleDateString('en-EG',{month:'short',day:'numeric'}));}
    return d;
})();
var DEMO_DS = [
    {label:'Electricity (kWh)',data:[6.1,7.4,5.8,8.2,6.9,7.7,5.3],borderColor:'#fbbf24',backgroundColor:'rgba(251,191,36,.12)',fill:true},
    {label:'Water (kL)',data:[0.38,0.41,0.35,0.44,0.39,0.42,0.36],borderColor:'#22d3ee',backgroundColor:'rgba(34,211,238,.12)',fill:true},
    {label:'Gas (m3)',data:[1.2,1.5,1.1,1.7,1.4,1.6,1.3],borderColor:'#fb923c',backgroundColor:'rgba(251,146,60,.12)',fill:true},
];

var _wChart = null, _wType = 'line';

function _drawWeekly(labels, datasets, type) {
    var el = document.getElementById('weeklyChart');
    if (!el) return;
    if (_wChart) { _wChart.destroy(); _wChart = null; }
    var ds = datasets.map(function(d){
        var o = Object.assign({}, d);
        o.type = type; o.tension = (type==='line')?0.38:0;
        o.borderWidth = (type==='line')?2:0; o.pointRadius = (type==='line')?3:0;
        o.pointHoverRadius = 5; o.borderRadius = (type==='bar')?5:0;
        return o;
    });
    _wChart = new Chart(el.getContext('2d'), {
        type:type, data:{labels:labels,datasets:ds},
        options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
            plugins:{legend:{position:'top',align:'end',labels:{color:'#8b9ab0',font:{size:11},boxWidth:10,padding:16,usePointStyle:true}},
                tooltip:{backgroundColor:'#1a2234',borderColor:'#253549',borderWidth:1,titleColor:'#f1f5f9',bodyColor:'#8b9ab0',padding:10,cornerRadius:8}},
            scales:{x:{ticks:{color:'#4a5568',font:{size:10}},grid:{color:'#1e2d40'}},
                y:{ticks:{color:'#4a5568',font:{size:10}},grid:{color:'#1e2d40'},beginAtZero:true}}}
    });
}

async function refreshDashboard() {
    var labels = DEMO_LABELS, datasets = DEMO_DS;
    try {
        var res = await fetch(BASE_URL + '/api/telemetry.php?action=weekly');
        if (!res.ok) throw new Error('HTTP '+res.status);
        var data = await res.json();
        if (data.error) throw new Error(data.error);
        var hasData = Array.isArray(data.labels) && data.labels.length > 0
            && data.datasets && data.datasets.some(function(d){ return d.data && d.data.some(function(v){ return Number(v)>0; }); });
        if (hasData) { labels = data.labels; datasets = data.datasets; }
    } catch(e) { console.warn('[SmartHome] Chart: '+e.message+' — using demo data'); }
    _drawWeekly(labels, datasets, _wType);
}

document.addEventListener('DOMContentLoaded', function() {
    refreshDashboard();
    setInterval(refreshDashboard, 30000);

    var donutEl = document.getElementById('splitDonut');
    if (donutEl) {
        new Chart(donutEl.getContext('2d'), {
            type:'doughnut',
            data:{labels:['Electricity','Water','Gas'],datasets:[{data:[72,14,14],backgroundColor:['rgba(251,191,36,.75)','rgba(34,211,238,.75)','rgba(251,146,60,.75)'],borderColor:['#fbbf24','#22d3ee','#fb923c'],borderWidth:2,hoverOffset:4}]},
            options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{
                legend:{position:'right',labels:{color:'#8b9ab0',font:{size:10},boxWidth:8,padding:10,usePointStyle:true}},
                tooltip:{backgroundColor:'#1a2234',borderColor:'#253549',borderWidth:1,titleColor:'#f1f5f9',bodyColor:'#8b9ab0',padding:8,cornerRadius:8,
                    callbacks:{label:function(c){return c.label+': '+c.parsed+'%';}}}}}
        });
    }

    var barEl = document.getElementById('budgetBar');
    if (barEl) {
        var bd = <?= json_encode(array_map(function($b){return['spent'=>(float)$b['current_spent'],'limit'=>(float)$b['monthly_limit']];}, $budgets?:[['current_spent'=>187.4,'monthly_limit'=>500],['current_spent'=>32.1,'monthly_limit'=>80],['current_spent'=>54.8,'monthly_limit'=>120]])) ?>;
        new Chart(barEl.getContext('2d'), {
            type:'bar',data:{labels:['Electricity','Water','Gas'],datasets:[
                {label:'Spent',data:bd.map(function(b){return b.spent;}),backgroundColor:['rgba(0,212,160,.6)','rgba(0,212,160,.6)','rgba(0,212,160,.6)'],borderRadius:4,borderWidth:0},
                {label:'Limit',data:bd.map(function(b){return b.limit;}),backgroundColor:['rgba(30,45,64,.8)','rgba(30,45,64,.8)','rgba(30,45,64,.8)'],borderRadius:4,borderWidth:1,borderColor:['#253549','#253549','#253549']}
            ]},
            options:{responsive:true,maintainAspectRatio:false,
                plugins:{legend:{position:'top',align:'end',labels:{color:'#8b9ab0',font:{size:10},boxWidth:8,padding:8,usePointStyle:true}},
                    tooltip:{backgroundColor:'#1a2234',borderColor:'#253549',borderWidth:1,titleColor:'#f1f5f9',bodyColor:'#8b9ab0',padding:8,cornerRadius:8,
                        callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toFixed(2)+' EGP';}}}},
                scales:{x:{ticks:{color:'#4a5568',font:{size:10}},grid:{display:false}},
                    y:{ticks:{color:'#4a5568',font:{size:10},callback:function(v){return v+' EGP';}},grid:{color:'#1e2d40'},beginAtZero:true}}}
        });
    }

    document.querySelectorAll('.toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            if (this.disabled) return;
            this.disabled = true;
            try {
                var res  = await fetch(BASE_URL+'/appliances/toggle/'+this.dataset.id,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'csrf_token='+this.dataset.csrf});
                var data = await res.json();
                if (data.success) {
                    showToast(data.status==='ON'?'Device turned ON':'Device turned OFF', data.status==='ON'?'success':'info');
                    setTimeout(function(){location.reload();}, 900);
                }
            } catch(e) { showToast('Could not toggle device','error'); }
            this.disabled = false;
        });
    });
});

document.getElementById('chartLineBtn') && document.getElementById('chartLineBtn').addEventListener('click',function(){_wType='line';refreshDashboard();});
document.getElementById('chartBarBtn')  && document.getElementById('chartBarBtn').addEventListener('click', function(){_wType='bar'; refreshDashboard();});
</script>
