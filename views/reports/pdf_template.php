<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SmartHome Report — <?= date('F Y') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1a202c; background: #fff; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 3px solid #00c896; margin-bottom: 30px; }
        .logo { font-size: 1.6rem; font-weight: 800; color: #00c896; }
        .report-meta { text-align: right; font-size: .85rem; color: #718096; }
        h2 { font-size: 1.1rem; font-weight: 700; color: #2d3748; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; }
        .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .kpi { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; }
        .kpi .label { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: #718096; }
        .kpi .value { font-size: 1.8rem; font-weight: 800; color: #2d3748; }
        table { width: 100%; border-collapse: collapse; font-size: .83rem; }
        thead th { background: #f7fafc; padding: 8px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; color: #718096; border-bottom: 1px solid #e2e8f0; }
        tbody td { padding: 8px 12px; border-bottom: 1px solid #f0f4f8; }
        tbody tr:nth-child(even) { background: #f7fafc; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: .7rem; font-weight: 600; }
        .badge-elec { background: #fefcbf; color: #92400e; }
        .badge-water { background: #e0f2fe; color: #075985; }
        .badge-gas  { background: #fff7ed; color: #9a3412; }
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: .72rem; color: #a0aec0; text-align: center; }
        @media print {
            body { padding: 20px; }
            button { display: none; }
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <div class="logo">⚡ SmartHome</div>
        <div style="font-size:.82rem;color:#718096">Resource Management System</div>
    </div>
    <div class="report-meta">
        <strong>Monthly Report — <?= date('F Y') ?></strong><br>
        Generated: <?= date('d M Y, H:i') ?><br>
        Resident: <?= htmlspecialchars($user['name']) ?>
    </div>
</div>

<!-- KPIs -->
<div class="kpi-row">
    <div class="kpi">
        <div class="label">Forecast Bill</div>
        <div class="value" style="color:#d97706"><?= number_format($forecast['forecast_egp'], 2) ?> <span style="font-size:1rem">EGP</span></div>
    </div>
    <div class="kpi">
        <div class="label">Avg Daily Usage</div>
        <div class="value" style="color:#059669"><?= number_format($forecast['avg_daily_kwh'], 3) ?> <span style="font-size:1rem">kWh</span></div>
    </div>
    <div class="kpi">
        <div class="label">CO₂ Today</div>
        <div class="value" style="color:#7c3aed"><?= number_format($co2Today, 3) ?> <span style="font-size:1rem">kg</span></div>
    </div>
</div>

<!-- Budget Summary -->
<h2>Budget Overview — <?= date('F Y') ?></h2>
<table>
    <thead>
        <tr>
            <th>Resource</th>
            <th>Monthly Limit (EGP)</th>
            <th>Current Spent (EGP)</th>
            <th>Usage %</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($budgets as $b):
        $pct    = $b['pct'];
        $status = $pct >= 100 ? '🔴 Over Budget' : ($pct >= 80 ? '🟡 Warning' : '🟢 On Track');
    ?>
    <tr>
        <td><?= ucfirst($b['resource_type']) ?></td>
        <td><?= number_format($b['monthly_limit'], 2) ?></td>
        <td><?= number_format($b['current_spent'], 2) ?></td>
        <td><strong><?= $pct ?>%</strong></td>
        <td><?= $status ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Weekly Consumption -->
<h2>7-Day Consumption Trend</h2>
<table>
    <thead>
        <tr><th>Date</th><th>Resource</th><th>Consumption (kWh)</th></tr>
    </thead>
    <tbody>
    <?php foreach ($weekly as $w): ?>
    <tr>
        <td><?= htmlspecialchars($w['day']) ?></td>
        <td>
            <span class="badge badge-<?= $w['resource_type'] === 'electricity' ? 'elec' : ($w['resource_type'] === 'water' ? 'water' : 'gas') ?>">
                <?= ucfirst($w['resource_type']) ?>
            </span>
        </td>
        <td><?= number_format($w['kwh'], 4) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="footer">
    SmartHome Resource Management System &nbsp;|&nbsp; Report generated automatically &nbsp;|&nbsp; <?= date('Y') ?>
</div>

<br>
<div style="text-align:center">
    <button onclick="window.print()" style="background:#00c896;color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:.9rem;cursor:pointer;font-weight:600">
        🖨️ Print / Save as PDF
    </button>
</div>
</body>
</html>
