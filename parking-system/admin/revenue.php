<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

// Build location filter for sub-admin
$my_loc  = my_location();
$is_sub  = is_sub();

// Payment filter: sub-admin only sees payments linked to their location's bookings
$pf = ($is_sub && $my_loc)
    ? " AND id IN (SELECT payment_id FROM bookings WHERE location_id=$my_loc AND payment_id IS NOT NULL)"
    : "";

// KPI queries
$total      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE 1$pf"))['t'];
$today      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE DATE(created_at)=CURDATE()$pf"))['t'];
$week       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)$pf"))['t'];
$month      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())$pf"))['t'];
$last_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE MONTH(created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))$pf"))['t'];
$count      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM payments WHERE 1$pf"))['t'];
$avg_txn    = $count > 0 ? round($total / $count) : 0;
$growth     = $last_month > 0 ? round((($month - $last_month) / $last_month) * 100, 1) : ($month > 0 ? 100 : 0);

// Payment methods
$methods = [];
$res = mysqli_query($conn, "SELECT method, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS rev FROM payments WHERE 1$pf GROUP BY method ORDER BY rev DESC");
if ($res) while ($r = mysqli_fetch_assoc($res)) $methods[] = $r;

// Daily — last 14 days
$day_map = [];
for ($i = 13; $i >= 0; $i--) $day_map[date('Y-m-d', strtotime("-$i days"))] = ['rev'=>0,'txns'=>0];
$res = mysqli_query($conn, "SELECT DATE(created_at) AS d, COALESCE(SUM(amount),0) AS rev, COUNT(*) AS txns FROM payments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)$pf GROUP BY d");
if ($res) while ($r = mysqli_fetch_assoc($res)) $day_map[$r['d']] = ['rev'=>(float)$r['rev'],'txns'=>(int)$r['txns']];
$daily_labels = $daily_data = $daily_txns = [];
foreach ($day_map as $k=>$v) { $daily_labels[]=date('D d',strtotime($k)); $daily_data[]=$v['rev']; $daily_txns[]=$v['txns']; }

// Weekly — last 10 weeks
$weekly_labels = $weekly_data = $weekly_txns = [];
$res = mysqli_query($conn, "SELECT YEARWEEK(created_at,1) AS yw, COALESCE(SUM(amount),0) AS rev, COUNT(*) AS txns FROM payments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 9 WEEK)$pf GROUP BY yw ORDER BY yw ASC");
if ($res) while ($r = mysqli_fetch_assoc($res)) {
    $yr = intdiv((int)$r['yw'],100); $wk = (int)$r['yw']%100;
    $ts = strtotime("$yr-01-01 +".($wk-1)." weeks");
    $weekly_labels[] = 'W'.$wk.' '.date('M',$ts);
    $weekly_data[]   = (float)$r['rev'];
    $weekly_txns[]   = (int)$r['txns'];
}

// Monthly — last 12 months
$monthly_labels = $monthly_data = $monthly_txns = $monthly_rows = [];
$res = mysqli_query($conn, "SELECT DATE_FORMAT(created_at,'%Y-%m') AS m, DATE_FORMAT(created_at,'%b %Y') AS label, COALESCE(SUM(amount),0) AS rev, COUNT(*) AS txns FROM payments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)$pf GROUP BY m ORDER BY m ASC");
if ($res) while ($r = mysqli_fetch_assoc($res)) {
    $monthly_labels[] = $r['label'];
    $monthly_data[]   = (float)$r['rev'];
    $monthly_txns[]   = (int)$r['txns'];
    $monthly_rows[]   = $r;
}

// Recent payments
$recent = mysqli_query($conn, "SELECT p.*, u.name AS user_name FROM payments p JOIN users u ON p.user_id=u.id WHERE 1$pf ORDER BY p.id DESC LIMIT 8");

// Location name for sub-admin heading
$loc_name = '';
if ($is_sub && $my_loc) {
    $ln = mysqli_fetch_assoc(mysqli_query($conn, "SELECT location_name FROM locations WHERE id=$my_loc"));
    $loc_name = $ln['location_name'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Revenue - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.rev-hero{background:linear-gradient(135deg,#0a0a1f 0%,#1a237e 50%,#1565c0 100%);border-radius:16px;padding:28px 32px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;position:relative;overflow:hidden;}
.rev-hero::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.04);top:-100px;right:-60px;pointer-events:none;}
.rev-hero-left h1{font-size:26px;font-weight:900;color:#fff;margin-bottom:6px;}
.rev-hero-left p{font-size:14px;color:rgba(255,255,255,.65);}
.rev-hero-right{text-align:right;}
.rev-hero-total{font-size:42px;font-weight:900;color:#ffd600;letter-spacing:-1px;line-height:1;}
.rev-hero-label{font-size:13px;color:rgba(255,255,255,.6);margin-top:4px;}
.rev-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
.rev-kpi{background:var(--card-bg);border-radius:14px;padding:20px 18px;border:1.5px solid var(--border);display:flex;align-items:center;gap:14px;transition:transform .2s,box-shadow .2s;}
.rev-kpi:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(26,35,126,.12);}
.rev-kpi-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.rev-kpi-label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;}
.rev-kpi-value{font-size:22px;font-weight:800;color:var(--primary);letter-spacing:-.3px;}
.rev-kpi-sub{font-size:12px;color:var(--text-muted);margin-top:2px;}
.growth-up{color:#2e7d32;font-weight:700;}
.growth-down{color:#c62828;font-weight:700;}
.chart-tabs{display:flex;gap:4px;background:var(--bg);border-radius:10px;padding:4px;}
.chart-tab{padding:7px 18px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:transparent;color:var(--text-muted);transition:all .2s;}
.chart-tab.active{background:var(--primary);color:#fff;box-shadow:0 2px 8px rgba(26,35,126,.25);}
.method-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;}
.method-card{background:var(--bg);border:1.5px solid var(--border);border-radius:12px;padding:16px;text-align:center;transition:transform .2s,box-shadow .2s;}
.method-card:hover{transform:translateY(-3px);box-shadow:0 6px 18px rgba(26,35,126,.1);}
.method-icon{font-size:28px;margin-bottom:8px;}
.method-name{font-size:13px;font-weight:700;color:var(--primary);margin-bottom:4px;}
.method-rev{font-size:16px;font-weight:800;color:#2e7d32;}
.method-cnt{font-size:12px;color:var(--text-muted);margin-top:2px;}
body.dark .rev-kpi{background:#1e1e1e;border-color:#2a2a2a;}
body.dark .rev-kpi-value{color:#90caf9;}
body.dark .chart-tabs{background:#252525;}
body.dark .chart-tab.active{background:#1a237e;}
body.dark .method-card{background:#252525;border-color:#333;}
body.dark .method-name{color:#90caf9;}
</style>
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'revenue'; include "../includes/admin_sidebar.php"; ?>

<div class="main-content">

    <!-- Hero -->
    <div class="rev-hero">
        <div class="rev-hero-left">
            <h1>💰 Revenue Report</h1>
            <p>
                📅 <?= date('l, d F Y') ?>
                <?php if ($is_sub && $loc_name): ?>
                    &nbsp;|&nbsp; 📍 <?= htmlspecialchars($loc_name) ?>
                <?php else: ?>
                    &nbsp;|&nbsp; All locations
                <?php endif; ?>
            </p>
        </div>
        <div class="rev-hero-right">
            <div class="rev-hero-total">₹<?= number_format($total) ?></div>
            <div class="rev-hero-label"><?= $is_sub ? 'My Location Revenue' : 'Total Revenue Collected' ?></div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="rev-kpi-grid">
        <div class="rev-kpi">
            <div class="rev-kpi-icon" style="background:linear-gradient(135deg,#e3f2fd,#bbdefb);">📅</div>
            <div>
                <div class="rev-kpi-label">Today</div>
                <div class="rev-kpi-value">₹<?= number_format($today) ?></div>
                <div class="rev-kpi-sub">Revenue today</div>
            </div>
        </div>
        <div class="rev-kpi">
            <div class="rev-kpi-icon" style="background:linear-gradient(135deg,#e8f5e9,#c8e6c9);">📊</div>
            <div>
                <div class="rev-kpi-label">This Week</div>
                <div class="rev-kpi-value">₹<?= number_format($week) ?></div>
                <div class="rev-kpi-sub">Current week</div>
            </div>
        </div>
        <div class="rev-kpi">
            <div class="rev-kpi-icon" style="background:linear-gradient(135deg,#fff8e1,#ffecb3);">📆</div>
            <div>
                <div class="rev-kpi-label">This Month</div>
                <div class="rev-kpi-value">₹<?= number_format($month) ?></div>
                <div class="rev-kpi-sub">
                    <?php if ($growth >= 0): ?>
                        <span class="growth-up">▲ <?= $growth ?>%</span> vs last month
                    <?php else: ?>
                        <span class="growth-down">▼ <?= abs($growth) ?>%</span> vs last month
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="rev-kpi">
            <div class="rev-kpi-icon" style="background:linear-gradient(135deg,#fce4ec,#f8bbd0);">🧾</div>
            <div>
                <div class="rev-kpi-label">Transactions</div>
                <div class="rev-kpi-value"><?= number_format($count) ?></div>
                <div class="rev-kpi-sub">Avg ₹<?= number_format($avg_txn) ?> / txn</div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h2>📈 Revenue Chart <?= $is_sub ? '<span style="font-size:12px;font-weight:500;color:#999;">— My Location</span>' : '' ?></h2>
            <div class="chart-tabs">
                <button class="chart-tab active" id="tabDaily"   onclick="switchChart('daily')">Daily</button>
                <button class="chart-tab"         id="tabWeekly"  onclick="switchChart('weekly')">Weekly</button>
                <button class="chart-tab"         id="tabMonthly" onclick="switchChart('monthly')">Monthly</button>
            </div>
        </div>
        <div style="position:relative;height:320px;padding:8px 0;">
            <canvas id="revenueChart"></canvas>
        </div>
        <div style="display:flex;gap:24px;padding:14px 4px 4px;flex-wrap:wrap;border-top:1px solid var(--border);margin-top:8px;">
            <div style="font-size:13px;color:var(--text-muted);">
                <span style="font-weight:700;color:var(--primary);" id="sumTotal">—</span> total revenue
            </div>
            <div style="font-size:13px;color:var(--text-muted);">
                <span style="font-weight:700;color:#f9a825;" id="sumTxns">—</span> transactions
            </div>
            <div style="font-size:13px;color:var(--text-muted);">
                Peak: <span style="font-weight:700;color:#2e7d32;" id="sumPeak">—</span>
            </div>
        </div>
    </div>

    <!-- Payment Methods + Monthly Breakdown -->
    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:20px;margin-bottom:24px;" class="rev-two-col">

        <div class="card" style="margin-bottom:0;">
            <div class="card-header"><h2>💳 Payment Methods</h2></div>
            <?php if (empty($methods)): ?>
                <p style="text-align:center;color:#999;padding:24px;">No data yet.</p>
            <?php else: ?>
            <div class="method-grid">
                <?php
                $icons = ['cash'=>'💵','card'=>'💳','upi'=>'📱','online'=>'🌐','wallet'=>'👛'];
                foreach ($methods as $m):
                    $icon = $icons[strtolower($m['method'])] ?? '💰';
                ?>
                <div class="method-card">
                    <div class="method-icon"><?= $icon ?></div>
                    <div class="method-name"><?= htmlspecialchars(ucfirst($m['method'])) ?></div>
                    <div class="method-rev">₹<?= number_format($m['rev']) ?></div>
                    <div class="method-cnt"><?= $m['cnt'] ?> transactions</div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-bottom:0;">
            <div class="card-header"><h2>📋 Monthly Breakdown <?= $is_sub ? '<span style="font-size:12px;font-weight:500;color:#999;">— My Location</span>' : '' ?></h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Month</th><th>Txns</th><th>Revenue</th><th>Share</th></tr></thead>
                    <tbody>
                    <?php if (empty($monthly_rows)): ?>
                        <tr><td colspan="4" style="text-align:center;color:#999;padding:24px;">No data yet.</td></tr>
                    <?php else:
                        $max_rev   = max(array_column($monthly_rows,'rev')) ?: 1;
                        $total_rev = array_sum(array_column($monthly_rows,'rev')) ?: 1;
                        foreach (array_reverse($monthly_rows) as $r):
                            $pct   = round(($r['rev']/$max_rev)*100);
                            $share = round(($r['rev']/$total_rev)*100,1);
                    ?>
                    <tr>
                        <td style="font-weight:600;">📆 <?= $r['label'] ?></td>
                        <td><span class="badge badge-info"><?= $r['txns'] ?></span></td>
                        <td><strong style="color:#2e7d32;">₹<?= number_format($r['rev']) ?></strong></td>
                        <td style="min-width:120px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="rev-bar-wrap" style="flex:1;">
                                    <div class="rev-bar" style="width:<?= $pct ?>%"></div>
                                </div>
                                <span style="font-size:11px;font-weight:700;color:var(--text-muted);white-space:nowrap;"><?= $share ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="card">
        <div class="card-header">
            <h2>🕐 Recent Payments <?= $is_sub ? '<span style="font-size:12px;font-weight:500;color:#999;">— My Location</span>' : '' ?></h2>
            <a href="view_payments.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>User</th><th>Amount</th><th>Method</th><th>Date & Time</th></tr></thead>
                <tbody>
                <?php
                $has = false;
                if ($recent) while ($p = mysqli_fetch_assoc($recent)):
                    $has = true;
                ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:12px;">#<?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['user_name']) ?></td>
                    <td><strong style="color:#2e7d32;">₹<?= number_format($p['amount']) ?></strong></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['method']) ?></span></td>
                    <td style="color:var(--text-muted);font-size:13px;"><?= date('d M Y, h:i A', strtotime($p['created_at'])) ?></td>
                </tr>
                <?php endwhile;
                if (!$has): ?>
                    <tr><td colspan="5" style="text-align:center;color:#999;padding:20px;">No payments yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>

<script>
const chartData = {
    daily:   { labels: <?= json_encode($daily_labels) ?>,   rev: <?= json_encode($daily_data) ?>,   txns: <?= json_encode($daily_txns) ?> },
    weekly:  { labels: <?= json_encode($weekly_labels) ?>,  rev: <?= json_encode($weekly_data) ?>,  txns: <?= json_encode($weekly_txns) ?> },
    monthly: { labels: <?= json_encode($monthly_labels) ?>, rev: <?= json_encode($monthly_data) ?>, txns: <?= json_encode($monthly_txns) ?> }
};
const ctx = document.getElementById('revenueChart').getContext('2d');
const gradient = ctx.createLinearGradient(0,0,0,320);
gradient.addColorStop(0,'rgba(26,35,126,0.85)');
gradient.addColorStop(1,'rgba(26,35,126,0.35)');
const chart = new Chart(ctx, {
    data: {
        labels: chartData.daily.labels,
        datasets: [{
            type:'bar', label:'Revenue (₹)', data: chartData.daily.rev,
            backgroundColor: gradient, borderWidth:0, borderRadius:8, borderSkipped:false, yAxisID:'y'
        },{
            type:'line', label:'Transactions', data: chartData.daily.txns,
            borderColor:'#f9a825', backgroundColor:'rgba(249,168,37,0.08)',
            borderWidth:2.5, pointBackgroundColor:'#fff', pointBorderColor:'#f9a825',
            pointBorderWidth:2, pointRadius:5, tension:0.45, fill:true, yAxisID:'y2'
        }]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        interaction:{ mode:'index', intersect:false },
        plugins:{
            legend:{ position:'top', labels:{ usePointStyle:true, padding:20, font:{size:13} } },
            tooltip:{ backgroundColor:'rgba(10,10,31,.92)', titleColor:'#fff', bodyColor:'rgba(255,255,255,.8)',
                padding:12, cornerRadius:10,
                callbacks:{ label: c => c.dataset.label==='Revenue (₹)' ? ' ₹'+c.parsed.y.toLocaleString() : ' '+c.parsed.y+' txns' }
            }
        },
        scales:{
            y:{ beginAtZero:true, grid:{color:'rgba(0,0,0,0.04)'}, ticks:{callback:v=>'₹'+(v>=1000?(v/1000).toFixed(0)+'k':v),font:{size:12}} },
            y2:{ beginAtZero:true, position:'right', grid:{drawOnChartArea:false}, ticks:{stepSize:1,font:{size:12}} },
            x:{ grid:{display:false}, ticks:{font:{size:12}} }
        }
    }
});
function updateSummary(type) {
    const rev=chartData[type].rev, txns=chartData[type].txns, labels=chartData[type].labels;
    const totalRev=rev.reduce((a,b)=>a+b,0), totalTxn=txns.reduce((a,b)=>a+b,0);
    const peakIdx=rev.indexOf(Math.max(...rev.length?rev:[0]));
    document.getElementById('sumTotal').textContent='₹'+totalRev.toLocaleString();
    document.getElementById('sumTxns').textContent=totalTxn;
    document.getElementById('sumPeak').textContent=labels[peakIdx]?labels[peakIdx]+' (₹'+rev[peakIdx].toLocaleString()+')':'—';
}
function switchChart(type) {
    chart.data.labels=chartData[type].labels;
    chart.data.datasets[0].data=chartData[type].rev;
    chart.data.datasets[1].data=chartData[type].txns;
    chart.update(); updateSummary(type);
    document.querySelectorAll('.chart-tab').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab'+type.charAt(0).toUpperCase()+type.slice(1)).classList.add('active');
}
updateSummary('daily');
(function check(){
    const col=document.querySelector('.rev-two-col');
    if(col) col.style.gridTemplateColumns=window.innerWidth<900?'1fr':'1fr 1.6fr';
    window.addEventListener('resize',check);
})();
</script>
</body>
</html>
