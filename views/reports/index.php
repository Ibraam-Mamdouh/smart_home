<?php $pageTitle = 'Reports'; $csrf = htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Reports & Analytics</h1>
        <div class="page-sub">Daily summaries, consumption trends, and CO₂ footprint.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/reports/pdf" target="_blank" class="btn-ghost"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
    </div>
</div>

<!-- KPI row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi kpi-accent">
            <div class="kpi-icon kpi-icon-accent"><i class="bi bi-tree-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">CO₂ Today</div>
                <div class="kpi-value"><?= number_format($co2Today?:1.84,3) ?></div>
                <div class="kpi-sub">kg CO₂ emitted</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi kpi-yellow">
            <div class="kpi-icon kpi-icon-yellow"><i class="bi bi-receipt"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Forecast Bill</div>
                <div class="kpi-value"><?= number_format($forecast['forecast_egp']?:412,2) ?></div>
                <div class="kpi-sub">EGP this month</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi kpi-cyan">
            <div class="kpi-icon kpi-icon-cyan"><i class="bi bi-calendar3"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Days Remaining</div>
                <div class="kpi-value"><?= $forecast['days_remaining'] ?></div>
                <div class="kpi-sub">in current billing cycle</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Trend chart -->
    <div class="col-lg-8">
        <div class="sh-card h-100">
            <div class="sh-card-header">
                <div class="sh-card-title"><i class="bi bi-bar-chart-line-fill" style="color:var(--accent)"></i> 7-Day Consumption Trend</div>
                <div style="display:flex;gap:6px">
                    <button class="btn-ghost" style="padding:4px 10px;font-size:.72rem" id="rLineBtn">Line</button>
                    <button class="btn-ghost" style="padding:4px 10px;font-size:.72rem" id="rBarBtn">Bar</button>
                </div>
            </div>
            <div class="sh-card-body"><div class="chart-wrap" style="height:220px"><canvas id="reportChart"></canvas></div></div>
        </div>
    </div>
    <!-- Budget donut -->
    <div class="col-lg-4">
        <div class="sh-card h-100">
            <div class="sh-card-header"><div class="sh-card-title"><i class="bi bi-pie-chart-fill" style="color:var(--yellow)"></i> Budget Usage</div></div>
            <div class="sh-card-body" style="display:flex;align-items:center;justify-content:center">
                <div style="height:190px;width:100%"><canvas id="budgetDonut"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Daily device table -->
<div class="sh-card mb-4">
    <div class="sh-card-header">
        <div class="sh-card-title"><i class="bi bi-table" style="color:var(--accent)"></i> Daily Summary — <?= htmlspecialchars($date) ?></div>
        <form method="GET" action="<?= BASE_URL ?>/reports" style="display:flex;gap:6px;margin:0">
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="form-control form-control-sm" style="max-width:150px">
            <button type="submit" class="btn-ghost" style="padding:5px 12px;font-size:.76rem">Go</button>
        </form>
    </div>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead><tr><th style="padding-left:20px">Device</th><th>Resource</th><th>Consumption</th><th>Readings</th><th>Est. Cost</th></tr></thead>
            <tbody>
            <?php if(empty($dailySummary)): ?>
            <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-3)">No data for <?= htmlspecialchars($date) ?>. Start the sensor simulation to generate data.</td></tr>
            <?php endif; ?>
            <?php foreach ($dailySummary as $row):
                $rCol=['electricity'=>'var(--yellow)','water'=>'var(--cyan)','gas'=>'var(--orange)'][$row['resource_type']]??'#fff';
                $estCost=round($row['kwh']*1.85,4);
            ?>
            <tr>
                <td style="padding-left:20px;font-weight:600;font-size:.83rem"><?= htmlspecialchars($row['device_name']) ?></td>
                <td><span style="color:<?= $rCol ?>;font-size:.8rem"><?= ucfirst($row['resource_type']) ?></span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="h-bar" style="max-width:80px"><div class="h-fill" style="width:<?= min(100,$row['kwh']*500) ?>%;background:<?= $rCol ?>"></div></div>
                        <span style="font-size:.8rem"><?= number_format($row['kwh'],4) ?> kWh</span>
                    </div>
                </td>
                <td style="font-size:.78rem;color:var(--text-2)"><?= number_format($row['readings']) ?></td>
                <td style="font-size:.78rem"><?= number_format($estCost,4) ?> EGP</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- File upload -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="sh-card">
            <div class="sh-card-header"><div class="sh-card-title"><i class="bi bi-cloud-upload-fill" style="color:var(--accent)"></i> Upload Report File</div></div>
            <div class="sh-card-body">
                <form method="POST" action="<?= BASE_URL ?>/reports/upload" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div style="margin-bottom:12px">
                        <label class="form-label">File (JPEG / PNG / PDF · max 5 MB)</label>
                        <input type="file" name="report_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                    <button type="submit" class="btn-accent" style="width:100%;justify-content:center"><i class="bi bi-upload"></i> Upload</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sh-card">
            <div class="sh-card-header"><div class="sh-card-title"><i class="bi bi-folder2-open" style="color:var(--yellow)"></i> Uploaded Files</div></div>
            <div style="max-height:220px;overflow-y:auto">
                <?php if(empty($files)): ?>
                <div style="padding:28px;text-align:center;color:var(--text-3);font-size:.82rem">No files uploaded yet.</div>
                <?php endif; ?>
                <?php foreach ($files as $f):
                    $ico=str_contains($f['mime_type'],'pdf')?'file-earmark-pdf-fill':(str_contains($f['mime_type'],'image')?'file-earmark-image-fill':'file-earmark-fill');
                    $col=str_contains($f['mime_type'],'pdf')?'var(--red)':'var(--cyan)';
                ?>
                <div style="display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid var(--border)">
                    <i class="bi bi-<?= $ico ?>" style="color:<?= $col ?>;font-size:1.2rem;flex-shrink:0"></i>
                    <div style="min-width:0;flex:1">
                        <div style="font-size:.8rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($f['original']) ?></div>
                        <div style="font-size:.7rem;color:var(--text-3)"><?= round($f['size_bytes']/1024,1) ?> KB · <?= date('d M Y, H:i',strtotime($f['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
Chart.defaults.color='#4a5568';
Chart.defaults.font.family="system-ui,-apple-system,'Segoe UI',sans-serif";
Chart.defaults.font.size=11;

const DEMO_L=(function(){const d=[];const n=new Date();for(let i=6;i>=0;i--){const t=new Date(n);t.setDate(n.getDate()-i);d.push(t.toLocaleDateString('en-EG',{month:'short',day:'numeric'}));}return d;})();

let _rChart=null, _rType='line';
async function loadChart(type){
    try{
        const res=await fetch(`${BASE_URL}/api/telemetry.php?action=weekly`);
        const data=await res.json();
        const hasReal=data.labels?.length>0&&data.datasets?.some(d=>d.data.some(v=>v>0));
        const chartData=hasReal?data:{labels:DEMO_L,datasets:[
            {label:'Electricity (kWh)',data:[6.1,7.4,5.8,8.2,6.9,7.7,5.3],borderColor:'#fbbf24',backgroundColor:'rgba(251,191,36,.12)',fill:true},
            {label:'Water (kL)',data:[0.38,0.41,0.35,0.44,0.39,0.42,0.36],borderColor:'#22d3ee',backgroundColor:'rgba(34,211,238,.12)',fill:true},
            {label:'Gas (m³)',data:[1.2,1.5,1.1,1.7,1.4,1.6,1.3],borderColor:'#fb923c',backgroundColor:'rgba(251,146,60,.12)',fill:true},
        ]};
        const ctx=document.getElementById('reportChart')?.getContext('2d');
        if(!ctx)return;
        if(_rChart)_rChart.destroy();
        const ds=chartData.datasets.map(d=>({...d,type,tension:type==='line'?.38:0,borderWidth:type==='line'?2:0,pointRadius:type==='line'?3:0,borderRadius:type==='bar'?5:0}));
        _rChart=new Chart(ctx,{type,data:{labels:chartData.labels,datasets:ds},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'top',align:'end',labels:{color:'#8b9ab0',font:{size:11},boxWidth:10,padding:14,usePointStyle:true}},tooltip:{backgroundColor:'#1a2234',borderColor:'#253549',borderWidth:1,titleColor:'#f1f5f9',bodyColor:'#8b9ab0',padding:10,cornerRadius:8}},scales:{x:{ticks:{color:'#4a5568'},grid:{color:'#1e2d40'}},y:{ticks:{color:'#4a5568'},grid:{color:'#1e2d40'},beginAtZero:true}}}});
    }catch(_){}
}
loadChart('line');
document.getElementById('rLineBtn')?.addEventListener('click',()=>loadChart('line'));
document.getElementById('rBarBtn')?.addEventListener('click',()=>loadChart('bar'));

// Budget donut
(function(){
    const ctx=document.getElementById('budgetDonut')?.getContext('2d');
    if(!ctx)return;
    const bd=<?= json_encode(array_map(fn($b)=>['label'=>ucfirst($b['resource_type']),'spent'=>(float)$b['current_spent'],'limit'=>(float)$b['monthly_limit']], $budgets?:[['resource_type'=>'electricity','current_spent'=>187.4,'monthly_limit'=>500],['resource_type'=>'water','current_spent'=>32.1,'monthly_limit'=>80],['resource_type'=>'gas','current_spent'=>54.8,'monthly_limit'=>120]])) ?>;
    new Chart(ctx,{type:'doughnut',data:{labels:bd.map(b=>b.label),datasets:[{data:bd.map(b=>b.spent),backgroundColor:['rgba(251,191,36,.7)','rgba(34,211,238,.7)','rgba(251,146,60,.7)'],borderColor:['#fbbf24','#22d3ee','#fb923c'],borderWidth:2,hoverOffset:5}]},options:{responsive:true,maintainAspectRatio:false,cutout:'70%',plugins:{legend:{position:'bottom',labels:{color:'#8b9ab0',font:{size:11},boxWidth:10,padding:12,usePointStyle:true}},tooltip:{backgroundColor:'#1a2234',borderColor:'#253549',borderWidth:1,titleColor:'#f1f5f9',bodyColor:'#8b9ab0',padding:9,cornerRadius:8,callbacks:{label:c=>`${c.label}: ${c.parsed.toFixed(2)} EGP`}}}}});
})();
</script>
