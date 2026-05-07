<?php $pageTitle = 'Edit Appliance'; ?>
<div class="page-header">
    <div><h1 class="page-title">Edit: <?= htmlspecialchars($appliance['name']) ?></h1></div>
    <div class="page-actions"><a href="<?= BASE_URL ?>/appliances" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a></div>
</div>
<div class="row justify-content-center">
<div class="col-lg-7 col-xl-6">
<div class="sh-card">
    <div class="sh-card-header"><div class="sh-card-title"><i class="bi bi-pencil" style="color:var(--accent)"></i> Device Details</div></div>
    <div class="sh-card-body">
        <form method="POST" action="<?= BASE_URL ?>/appliances/update/<?= $appliance['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Device Name</label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($appliance['name']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Type</label>
                    <select name="type" class="form-select"><?php foreach(['Air Conditioner','Refrigerator','Water Heater','Dishwasher','Television','Fan','EV Charger','Washing Machine','Microwave','Other'] as $t): ?><option value="<?= $t ?>" <?= $appliance['type']===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Room</label>
                    <select name="room_id" class="form-select"><?php foreach($rooms as $r): ?><option value="<?= $r['id'] ?>" <?= $appliance['room_id']==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Resource Type</label>
                    <select name="resource_type" class="form-select"><?php foreach(['electricity','water','gas'] as $rt): ?><option value="<?= $rt ?>" <?= $appliance['resource_type']===$rt?'selected':'' ?>><?= ucfirst($rt) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Base Wattage (W)</label><input type="number" name="base_wattage" class="form-control" step="0.01" value="<?= $appliance['base_wattage'] ?>"></div>
                <div class="col-md-6"><label class="form-label">Status</label>
                    <select name="status" class="form-select"><?php foreach(['ON','OFF','FAULT','SHUTDOWN'] as $st): ?><option value="<?= $st ?>" <?= $appliance['status']===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></div>
            </div>
            <div style="display:flex;gap:8px;margin-top:20px">
                <button type="submit" class="btn-accent"><i class="bi bi-check-lg"></i> Save Changes</button>
                <a href="<?= BASE_URL ?>/appliances" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
