<?php $pageTitle = 'Appliances'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Appliances</h1>
        <div class="page-sub">Manage devices, monitor health, and control power states.</div>
    </div>
    <div class="page-actions">
        <?php if($currentUser['role']==='admin'): ?>
        <a href="<?= BASE_URL ?>/appliances/create" class="btn-accent"><i class="bi bi-plus-lg"></i> Add Device</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($maintenance)): ?>
<div class="flash-bar flash-warning" style="margin-bottom:20px">
    <i class="bi bi-tools"></i>
    <span><strong><?= count($maintenance) ?> device(s)</strong> need maintenance —
        <?php foreach($maintenance as $m): ?>
        <span style="opacity:.8"><?= htmlspecialchars($m['name']) ?> (<?= $m['health_score'] ?>%)</span><?= !($m==end($maintenance))?', ':'' ?>
        <?php endforeach; ?>
    </span>
</div>
<?php endif; ?>

<!-- Filter bar -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
    <select id="fStatus" class="form-select form-select-sm" style="width:auto">
        <option value="">All Status</option>
        <option value="ON">ON</option><option value="OFF">OFF</option>
        <option value="FAULT">FAULT</option><option value="SHUTDOWN">SHUTDOWN</option>
    </select>
    <select id="fResource" class="form-select form-select-sm" style="width:auto">
        <option value="">All Resources</option>
        <option value="electricity">Electricity</option>
        <option value="water">Water</option><option value="gas">Gas</option>
    </select>
    <input type="text" id="fSearch" class="form-control form-control-sm" placeholder="Search devices…" style="width:200px">
    <span id="fCount" style="font-size:.75rem;color:var(--text-3);margin-left:4px"></span>
</div>

<div class="sh-card">
    <div style="overflow-x:auto">
        <table class="tbl" id="appTable">
            <thead>
                <tr>
                    <th style="padding-left:20px">Device</th>
                    <th>Room</th>
                    <th>Wattage</th>
                    <th>Resource</th>
                    <th>Health</th>
                    <th>Status</th>
                    <th style="text-align:center">Power</th>
                    <?php if($currentUser['role']==='admin'): ?><th style="text-align:center">Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody id="appBody">
            <?php foreach ($appliances as $a):
                $pillMap = ['ON'=>'pill-on','OFF'=>'pill-off','FAULT'=>'pill-fault','SHUTDOWN'=>'pill-shutdown'];
                $pill    = $pillMap[$a['status']] ?? 'pill-off';
                $health  = (int)$a['health_score'];
                $hc      = $health>=80?'var(--accent)':($health>=60?'var(--yellow)':'var(--red)');
                $rIco    = ['electricity'=>'lightning-charge-fill','water'=>'droplet-fill','gas'=>'fire'][$a['resource_type']]??'dash';
                $rCol    = ['electricity'=>'var(--yellow)','water'=>'var(--cyan)','gas'=>'var(--orange)'][$a['resource_type']]??'#fff';
                $csrf    = htmlspecialchars($_SESSION['csrf_token']??'');
            ?>
            <tr data-status="<?= $a['status'] ?>" data-resource="<?= $a['resource_type'] ?>" data-name="<?= strtolower(htmlspecialchars($a['name'])) ?>">
                <td style="padding-left:20px">
                    <div style="font-weight:600;font-size:.83rem"><?= htmlspecialchars($a['name']) ?></div>
                    <div style="font-size:.7rem;color:var(--text-3)"><?= htmlspecialchars($a['type']) ?></div>
                </td>
                <td style="font-size:.78rem;color:var(--text-2)"><?= htmlspecialchars($a['room_name']) ?></td>
                <td style="font-size:.78rem;color:var(--text-2)"><?= number_format($a['base_wattage'],0) ?> W</td>
                <td>
                    <i class="bi bi-<?= $rIco ?>" style="color:<?= $rCol ?>;font-size:.82rem"></i>
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
                            data-id="<?= $a['id'] ?>" data-csrf="<?= $csrf ?>"
                            title="Toggle power"
                            <?= $currentUser['role']==='guest'?'disabled style="opacity:.35"':'' ?>>
                        <i class="bi bi-power"></i>
                    </button>
                </td>
                <?php if($currentUser['role']==='admin'): ?>
                <td style="text-align:center">
                    <div style="display:flex;gap:5px;justify-content:center">
                        <a href="<?= BASE_URL ?>/appliances/edit/<?= $a['id'] ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= BASE_URL ?>/appliances/delete/<?= $a['id'] ?>" style="display:inline"
                              onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($a['name'])) ?>?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="btn-icon danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.btn-icon.power-on{border-color:var(--accent-b);color:var(--accent);background:var(--accent-d);}
.btn-icon.power-on:hover{background:var(--accent);color:#0b0f18;border-color:var(--accent);}
</style>
<script>
// Table filter
function applyFilter(){
    const st=document.getElementById('fStatus').value.toUpperCase();
    const rs=document.getElementById('fResource').value.toLowerCase();
    const q=document.getElementById('fSearch').value.toLowerCase();
    let vis=0;
    document.querySelectorAll('#appBody tr').forEach(tr=>{
        const show=(!st||tr.dataset.status===st)&&(!rs||tr.dataset.resource===rs)&&(!q||tr.dataset.name.includes(q));
        tr.style.display=show?'':'none';
        if(show)vis++;
    });
    document.getElementById('fCount').textContent=`${vis} device${vis!==1?'s':''} shown`;
}
['fStatus','fResource','fSearch'].forEach(id=>document.getElementById(id)?.addEventListener('input',applyFilter));
applyFilter();

// Toggle
document.querySelectorAll('.toggle-btn').forEach(btn=>{
    btn.addEventListener('click', async function(){
        if(this.disabled)return;
        this.disabled=true;
        try{
            const res=await fetch(`${BASE_URL}/appliances/toggle/${this.dataset.id}`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`csrf_token=${this.dataset.csrf}`});
            const d=await res.json();
            if(d.success){showToast(d.status==='ON'?'⚡ Device turned ON':'🔴 Device turned OFF',d.status==='ON'?'success':'info');setTimeout(()=>location.reload(),800);}
        }catch(_){showToast('Toggle failed','error');}
        this.disabled=false;
    });
});
</script>
