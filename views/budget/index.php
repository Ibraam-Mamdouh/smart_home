<?php $pageTitle = 'Budget & Alerts'; $csrf = htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Budget & Alerts</h1>
        <div class="page-sub">Set monthly spending limits and manage notifications.</div>
    </div>
    <div class="page-actions">
        <?php if(!empty($alerts)): ?>
        <form method="POST" action="<?= BASE_URL ?>/budget/mark-all-read" style="margin:0">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <button type="submit" class="btn-ghost"><i class="bi bi-check2-all"></i> Mark All Read</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">

    <!-- Budget progress + set form -->
    <div class="col-lg-5">
        <div class="sh-card mb-3">
            <div class="sh-card-header">
                <div class="sh-card-title"><i class="bi bi-wallet2" style="color:var(--accent)"></i> Monthly Budgets</div>
            </div>
            <div class="sh-card-body">
                <?php
                $resConf=['electricity'=>['ico'=>'lightning-charge-fill','col'=>'var(--yellow)'],'water'=>['ico'=>'droplet-fill','col'=>'var(--cyan)'],'gas'=>['ico'=>'fire','col'=>'var(--orange)']];
                foreach ($budgets as $b):
                    $rc=$resConf[$b['resource_type']]??['ico'=>'dash','col'=>'#fff'];
                    $pct=(float)$b['pct'];
                    $fc=$pct>=100?'var(--red)':($pct>=80?'var(--yellow)':'var(--accent)');
                ?>
                <div class="prog-wrap">
                    <div class="prog-header">
                        <span style="display:flex;align-items:center;gap:7px;font-size:.82rem;font-weight:600">
                            <i class="bi bi-<?= $rc['ico'] ?>" style="color:<?= $rc['col'] ?>"></i>
                            <?= ucfirst($b['resource_type']) ?>
                        </span>
                        <span style="font-size:.82rem;font-weight:700;color:<?= $fc ?>"><?= number_format($pct,1) ?>%</span>
                    </div>
                    <div class="prog-track"><div class="prog-fill" style="width:<?= min(100,$pct) ?>%;background:<?= $fc ?>"></div></div>
                    <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-3);margin-top:4px">
                        <span><?= number_format($b['current_spent'],2) ?> EGP spent</span>
                        <span>Limit: <?= number_format($b['monthly_limit'],2) ?> EGP</span>
                    </div>
                    <?php if(!empty($suggestions[$b['resource_type']])): ?>
                    <div style="font-size:.7rem;color:var(--text-3);margin-top:5px">
                        <i class="bi bi-lightbulb" style="color:var(--yellow)"></i>
                        Suggested next month: <strong style="color:var(--text-2)"><?= number_format($suggestions[$b['resource_type']],2) ?> EGP</strong>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Set / update budget -->
        <div class="sh-card mb-3">
            <div class="sh-card-header">
                <div class="sh-card-title"><i class="bi bi-sliders" style="color:var(--accent)"></i> Set Budget</div>
            </div>
            <div class="sh-card-body">
                <form method="POST" action="<?= BASE_URL ?>/budget/save">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-5">
                            <label class="form-label">Resource</label>
                            <select name="resource_type" class="form-select">
                                <option value="electricity">⚡ Electricity</option>
                                <option value="water">💧 Water</option>
                                <option value="gas">🔥 Gas</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Limit (EGP/month)</label>
                            <input type="number" name="monthly_limit" class="form-control" min="1" step="0.01" placeholder="500.00" required>
                        </div>
                        <div class="col-2">
                            <button type="submit" class="btn-accent" style="width:100%;justify-content:center;padding:8px 4px">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Forecast -->
        <div class="sh-card">
            <div class="sh-card-header">
                <div class="sh-card-title"><i class="bi bi-graph-up-arrow" style="color:var(--yellow)"></i> Billing Forecast</div>
            </div>
            <div class="sh-card-body" style="text-align:center">
                <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);margin-bottom:8px">Predicted Bill This Month</div>
                <div style="font-size:2.4rem;font-weight:800;color:var(--yellow);letter-spacing:-.04em"><?= number_format($forecast['forecast_egp']?:412,2) ?></div>
                <div style="font-size:.88rem;color:var(--text-3)">EGP &nbsp;·&nbsp; <?= $forecast['days_remaining'] ?> days left</div>
                <div style="font-size:.72rem;color:var(--text-3);margin-top:10px">
                    Avg <?= number_format($forecast['avg_daily_kwh']?:1.57,3) ?> kWh/day &nbsp;·&nbsp; <?= number_format($forecast['current_kwh']?:48.72,2) ?> kWh used so far
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts panel -->
    <div class="col-lg-7">
        <div class="sh-card h-100">
            <div class="sh-card-header">
                <div class="sh-card-title">
                    <i class="bi bi-bell-fill" style="color:var(--yellow)"></i>
                    Alerts
                    <?php if(!empty($alerts)): ?><span style="font-size:.7rem;background:var(--red-d);color:var(--red);padding:2px 8px;border-radius:10px;border:1px solid rgba(248,113,113,.25);font-weight:600"><?= count($alerts) ?></span><?php endif; ?>
                </div>
            </div>
            <div style="max-height:520px;overflow-y:auto">
                <?php if(empty($alerts)): ?>
                <div style="text-align:center;padding:48px 20px;color:var(--text-3)">
                    <i class="bi bi-check-circle-fill" style="font-size:2rem;color:var(--accent);display:block;margin-bottom:10px"></i>
                    No active alerts — all systems normal.
                </div>
                <?php endif; ?>
                <?php foreach ($alerts as $al):
                    $priCol=['LOW'=>'var(--text-3)','MEDIUM'=>'var(--cyan)','HIGH'=>'var(--yellow)','CRITICAL'=>'var(--red)'][$al['priority']]??'var(--text-3)';
                    $bgOp  =['LOW'=>'','MEDIUM'=>'','HIGH'=>'var(--yellow-d)','CRITICAL'=>'var(--red-d)'][$al['priority']]??'';
                    $priCls=['LOW'=>'np-low','MEDIUM'=>'np-med','HIGH'=>'np-high','CRITICAL'=>'np-critical'][$al['priority']]??'np-low';
                    $typeIco=['SAFETY'=>'fire','BUDGET'=>'wallet2','ANOMALY'=>'activity','MAINTENANCE'=>'tools','SYSTEM'=>'gear'][$al['type']]??'bell';
                ?>
                <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 18px;border-bottom:1px solid var(--border);<?= $bgOp?'background:'.$bgOp.';':'' ?><?= $al['is_read']?'opacity:.5':'' ?>">
                    <i class="bi bi-<?= $typeIco ?>" style="color:<?= $priCol ?>;font-size:.95rem;margin-top:2px;flex-shrink:0"></i>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.8rem;color:var(--text-1);line-height:1.45;margin-bottom:5px"><?= htmlspecialchars($al['message']) ?></div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span class="ni-pri <?= $priCls ?>"><?= $al['priority'] ?></span>
                            <span class="ni-pri np-low"><?= $al['type'] ?></span>
                            <span style="font-size:.68rem;color:var(--text-3)"><?= date('d M, H:i', strtotime($al['created_at'])) ?></span>
                        </div>
                    </div>
                    <?php if(!$al['is_read']): ?>
                    <button class="btn-icon mark-read-btn" data-id="<?= $al['id'] ?>" data-csrf="<?= $csrf ?>" title="Mark read" style="flex-shrink:0">
                        <i class="bi bi-check2"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.mark-read-btn').forEach(btn=>{
    btn.addEventListener('click',async function(){
        await fetch(`${BASE_URL}/budget/mark-read`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${this.dataset.id}&csrf_token=${this.dataset.csrf}`});
        this.closest('[style*="display:flex"]').style.opacity='.35';
        this.remove();
    });
});
</script>
