<?php $pageTitle = 'Audit Trail'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">System Audit Trail</h4>
        <p class="text-muted small mb-0">Immutable log of all user actions and system events.</p>
    </div>
    <span class="badge bg-secondary"><?= count($logs) ?> records</span>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" id="auditTable">
                <thead style="font-size:.75rem;color:#64748b;border-bottom:1px solid #1e2a3a">
                    <tr>
                        <th class="px-4 py-3">Timestamp</th>
                        <th>User</th>
                        <th>Resource</th>
                        <th>Action</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $l):
                    $actionColor = match(true) {
                        str_contains($l['action'], 'DELETE')   => '#ef4444',
                        str_contains($l['action'], 'CREATE')   => '#00c896',
                        str_contains($l['action'], 'SHUTDOWN') => '#f59e0b',
                        str_contains($l['action'], 'LOGIN')    => '#0dcaf0',
                        str_contains($l['action'], 'LOGOUT')   => '#64748b',
                        default => '#94a3b8',
                    };
                ?>
                <tr>
                    <td class="px-4">
                        <span class="text-muted" style="font-size:.75rem;font-family:monospace">
                            <?= htmlspecialchars($l['created_at']) ?>
                        </span>
                    </td>
                    <td><span class="small"><?= htmlspecialchars($l['user_name'] ?? 'System') ?></span></td>
                    <td><code style="font-size:.75rem;color:#94a3b8"><?= htmlspecialchars($l['resource']) ?></code></td>
                    <td>
                        <span class="badge rounded-pill" style="background:<?= $actionColor ?>22;color:<?= $actionColor ?>">
                            <?= htmlspecialchars($l['action']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($l['old_value']): ?>
                        <span class="text-muted" style="font-size:.7rem;font-family:monospace">
                            <?= htmlspecialchars(mb_strimwidth($l['old_value'], 0, 40, '…')) ?>
                        </span>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($l['new_value']): ?>
                        <span style="font-size:.7rem;font-family:monospace;color:#00c896">
                            <?= htmlspecialchars(mb_strimwidth($l['new_value'], 0, 40, '…')) ?>
                        </span>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td><span class="text-muted" style="font-size:.7rem"><?= htmlspecialchars($l['ip_address'] ?? '') ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
