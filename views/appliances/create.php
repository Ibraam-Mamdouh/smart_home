<?php $pageTitle = 'Add Appliance'; ?>
<div class="page-header">
    <div><h1 class="page-title">Add Appliance</h1><div class="page-sub">Register a new device in the sensor network.</div></div>
    <div class="page-actions"><a href="<?= BASE_URL ?>/appliances" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a></div>
</div>
<div class="row justify-content-center">
<div class="col-lg-7 col-xl-6">
<div class="sh-card">
    <div class="sh-card-header"><div class="sh-card-title"><i class="bi bi-plug-fill" style="color:var(--accent)"></i> Device Details</div></div>
    <div class="sh-card-body">
        <form method="POST" action="<?= BASE_URL ?>/appliances/store">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Device Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. Living Room AC"></div>
                <div class="col-md-6"><label class="form-label">Type *</label>
                    <select name="type" class="form-select" required><?php foreach(['Air Conditioner','Refrigerator','Water Heater','Dishwasher','Television','Fan','EV Charger','Washing Machine','Microwave','Other'] as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Room *</label>
                    <select name="room_id" class="form-select" required><?php foreach($rooms as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Resource Type *</label>
                    <select name="resource_type" class="form-select"><option value="electricity">⚡ Electricity</option><option value="water">💧 Water</option><option value="gas">🔥 Gas</option></select></div>
                <div class="col-md-6"><label class="form-label">Base Wattage (W) *</label><input type="number" name="base_wattage" class="form-control" min="1" step="0.01" required placeholder="1800"></div>
                <div class="col-md-6"><label class="form-label">Age (hours)</label><input type="number" name="age_hours" class="form-control" min="0" value="0"></div>
                <div class="col-md-6"><label class="form-label">Initial Status</label>
                    <select name="status" class="form-select"><option value="OFF">OFF</option><option value="ON">ON</option></select></div>
            </div>
            <div style="display:flex;gap:8px;margin-top:20px">
                <button type="submit" class="btn-accent"><i class="bi bi-check-lg"></i> Add Device</button>
                <a href="<?= BASE_URL ?>/appliances" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
