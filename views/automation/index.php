<?php $pageTitle = 'Automation'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Automation & Rules</h4>
        <p class="text-muted small mb-0">IF–THEN rule engine, vacation mode, and eco challenges.</p>
    </div>
</div>

<div class="row g-4">

    <!-- ── Create Rule ───────────────────────────────── -->
    <?php if ($currentUser['role'] === 'admin'): ?>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="bi bi-plus-circle-fill me-2" style="color:#00c896"></i>New Rule</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= BASE_URL ?>/automation/rule">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Rule Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Overheat Shutdown">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label class="form-label small fw-semibold">IF Field</label>
                            <select name="condition_field" class="form-select">
                                <option value="temperature">Temperature (°C)</option>
                                <option value="amperage">Amperage (A)</option>
                                <option value="value">Wattage (W)</option>
                                <option value="water_flow">Water Flow</option>
                                <option value="budget_pct">Budget %</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-semibold">Operator</label>
                            <select name="condition_operator" class="form-select">
                                <option value=">">></option>
                                <option value="<"><</option>
                                <option value=">=">&ge;</option>
                                <option value="<=">&le;</option>
                                <option value="==">=</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Value</label>
                            <input type="number" name="condition_value" class="form-control" step="0.01" placeholder="90">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">THEN Action</label>
                            <select name="action_type" class="form-select">
                                <option value="SHUTDOWN">SHUTDOWN</option>
                                <option value="ALERT">ALERT</option>
                                <option value="LOG">LOG</option>
                                <option value="SCHEDULE">SCHEDULE</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-semibold">Priority</label>
                            <input type="number" name="priority" class="form-control" min="1" max="10" value="5">
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-semibold">Value</label>
                            <input type="text" name="action_value" class="form-control" placeholder="all">
                        </div>
                    </div>
                    <button type="submit" class="btn w-100" style="background:#00c896;color:#0f1117;font-weight:600">
                        <i class="bi bi-plus-lg me-1"></i> Add Rule
                    </button>
                </form>
            </div>
        </div>

        <!-- Vacation Mode -->
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="bi bi-airplane-fill me-2" style="color:#0dcaf0"></i>Vacation Mode</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small">Activates ultra-low anomaly thresholds while the home is empty. Any resource usage above ε triggers an immediate alert.</p>
                <form method="POST" action="<?= BASE_URL ?>/automation/vacation" class="d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <button name="vacation" value="1" class="btn flex-grow-1" style="background:#0dcaf022;color:#0dcaf0;border:1px solid #0dcaf044">
                        <i class="bi bi-airplane-fill me-1"></i> Activate
                    </button>
                    <button name="vacation" value="0" class="btn flex-grow-1 btn-outline-secondary">
                        <i class="bi bi-house-fill me-1"></i> Deactivate
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Rules Table ───────────────────────────────── -->
    <div class="col-lg-<?= $currentUser['role'] === 'admin' ? '7' : '12' ?>">
        <div class="card mb-3">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="bi bi-robot me-2" style="color:#00c896"></i>Active Rules</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead style="font-size:.75rem;color:#64748b;border-bottom:1px solid #1e2a3a">
                            <tr>
                                <th class="px-4 py-3">Rule</th>
                                <th>Condition</th>
                                <th>Action</th>
                                <th>Priority</th>
                                <th>Active</th>
                                <?php if ($currentUser['role'] === 'admin'): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rules as $r):
                            $priCol = $r['priority'] <= 2 ? '#ef4444' : ($r['priority'] <= 4 ? '#f59e0b' : '#64748b');
                        ?>
                        <tr>
                            <td class="px-4"><span class="fw-semibold small"><?= htmlspecialchars($r['name']) ?></span></td>
                            <td><code style="font-size:.75rem;color:#94a3b8"><?= $r['condition_field'] ?> <?= $r['condition_operator'] ?> <?= $r['condition_value'] ?></code></td>
                            <td>
                                <?php $actCol = ['SHUTDOWN'=>'danger','ALERT'=>'warning','LOG'=>'secondary','SCHEDULE'=>'info'][$r['action_type']] ?? 'secondary'; ?>
                                <span class="badge bg-<?= $actCol ?> rounded-pill"><?= $r['action_type'] ?></span>
                            </td>
                            <td><span style="color:<?= $priCol ?>;font-weight:700"><?= $r['priority'] ?></span></td>
                            <td>
                                <?php if ($currentUser['role'] === 'admin'): ?>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input toggle-rule" type="checkbox" data-id="<?= $r['id'] ?>"
                                           <?= $r['is_active'] ? 'checked':'' ?>>
                                </div>
                                <?php else: ?>
                                <span class="badge <?= $r['is_active'] ? 'bg-success':'bg-secondary' ?>"><?= $r['is_active'] ? 'ON':'OFF' ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                            <td>
                                <form method="POST" action="<?= BASE_URL ?>/automation/delete-rule/<?= $r['id'] ?>" class="d-inline"
                                      onsubmit="return confirm('Delete this rule?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Eco Challenges -->
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="bi bi-trophy-fill me-2" style="color:#ffc107"></i>Eco Challenges</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                <?php foreach ($challenges as $ch):
                    $joined = false;
                    foreach ($myChallenges as $mc) { if ($mc['challenge_id'] == $ch['id']) { $joined = true; break; } }
                ?>
                <div class="col-md-6">
                    <div class="p-3 rounded" style="background:#0f1117;border:1px solid #1e2a3a">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="fw-semibold small"><?= htmlspecialchars($ch['name']) ?></span>
                            <span class="badge" style="background:#ffc10722;color:#ffc107">
                                <i class="bi bi-stars"></i> <?= number_format($ch['points_reward']) ?> pts
                            </span>
                        </div>
                        <p class="text-muted" style="font-size:.75rem;margin-bottom:.5rem"><?= htmlspecialchars($ch['description']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Ends <?= $ch['end_date'] ?></small>
                            <?php if ($joined): ?>
                            <span class="badge bg-success"><i class="bi bi-check me-1"></i>Joined</span>
                            <?php else: ?>
                            <form method="POST" action="<?= BASE_URL ?>/automation/challenge-join/<?= $ch['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                <button type="submit" class="btn btn-sm" style="background:#00c89622;color:#00c896;border:1px solid #00c89644;font-size:.75rem">
                                    Join Challenge
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-rule').forEach(sw => {
    sw.addEventListener('change', async function () {
        const res  = await fetch(`<?= BASE_URL ?>/automation/toggle-rule/${this.dataset.id}`, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`csrf_token=<?= htmlspecialchars($csrf) ?>`
        });
        const data = await res.json();
        showToast(data.is_active ? 'Rule activated' : 'Rule deactivated', data.is_active ? 'success':'secondary');
    });
});
</script>
