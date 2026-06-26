<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

// Location filter
$my_loc = my_location();
$is_sub = is_sub();

// Stat cards
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM users"))['t'];
$total_vehicles = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM vehicles"))['t'];
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM bookings" . ($is_sub && $my_loc ? " WHERE location_id=$my_loc" : "")))['t'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS t FROM payments" . ($is_sub && $my_loc ? " WHERE id IN (SELECT payment_id FROM bookings WHERE location_id=$my_loc AND payment_id IS NOT NULL)" : "")))['t'];

// Unread contact messages (super admin only)
$unread_messages = !$is_sub ? (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM contact_messages WHERE status='unread'"))['t'] : 0;
$recent_messages = !$is_sub ? mysqli_query($conn, "SELECT * FROM contact_messages WHERE status='unread' ORDER BY created_at DESC LIMIT 3") : null;

// Top Insights — filtered by location for sub-admin
$top_location = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT l.location_name, COUNT(b.id) AS cnt FROM bookings b
 JOIN locations l ON b.location_id=l.id
 WHERE 1" . ($is_sub && $my_loc ? " AND b.location_id=$my_loc" : "") .
" GROUP BY b.location_id ORDER BY cnt DESC LIMIT 1"));

$top_user = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT u.name, COALESCE(SUM(p.amount),0) AS total FROM payments p
 JOIN users u ON p.user_id=u.id
 JOIN bookings b ON b.payment_id=p.id
 WHERE 1" . ($is_sub && $my_loc ? " AND b.location_id=$my_loc" : "") .
" GROUP BY p.user_id ORDER BY total DESC LIMIT 1"));

$busy_day = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT DAYNAME(created_at) AS day, COUNT(*) AS cnt FROM bookings
 WHERE 1" . ($is_sub && $my_loc ? " AND location_id=$my_loc" : "") .
" GROUP BY day ORDER BY cnt DESC LIMIT 1"));

$top_slot = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT s.slot_number, l.location_name, COUNT(b.id) AS cnt FROM bookings b
 JOIN slots s ON b.slot_id=s.id JOIN locations l ON b.location_id=l.id
 WHERE 1" . ($is_sub && $my_loc ? " AND b.location_id=$my_loc" : "") .
" GROUP BY b.slot_id ORDER BY cnt DESC LIMIT 1"));

// Slot Occupancy — sub-admin sees only their location
$occupancy = mysqli_query($conn,
"SELECT l.id, l.location_name,
        COUNT(s.id) AS total,
        COALESCE(SUM(s.status='booked'),0) AS booked,
        COALESCE(SUM(s.status='available'),0) AS available,
        COALESCE(SUM(s.status='maintenance'),0) AS maintenance
 FROM locations l
 LEFT JOIN slots s ON s.location_id = l.id
 WHERE 1" . ($is_sub && $my_loc ? " AND l.id=$my_loc" : "") .
" GROUP BY l.id, l.location_name ORDER BY l.location_name");

// Recent Bookings — filtered
$recent_bookings = mysqli_query($conn,
"SELECT b.*, u.name AS user_name, l.location_name, s.slot_number
 FROM bookings b
 JOIN users u ON b.user_id = u.id
 JOIN locations l ON b.location_id = l.id
 JOIN slots s ON b.slot_id = s.id
 WHERE 1" . ($is_sub && $my_loc ? " AND b.location_id=$my_loc" : "") .
" ORDER BY b.id DESC LIMIT 5");

// Recent Payments — filtered
$recent_payments = mysqli_query($conn,
"SELECT p.*, u.name AS user_name FROM payments p
 JOIN users u ON p.user_id = u.id
 WHERE 1" . ($is_sub && $my_loc ? " AND p.id IN (SELECT payment_id FROM bookings WHERE location_id=$my_loc AND payment_id IS NOT NULL)" : "") .
" ORDER BY p.id DESC LIMIT 5");

// Revenue chart data — filtered
$day_map = [];
for ($i = 6; $i >= 0; $i--) $day_map[date('Y-m-d', strtotime("-$i days"))] = ['rev'=>0,'txns'=>0];
$pay_filter = ($is_sub && $my_loc) ? " AND p.id IN (SELECT payment_id FROM bookings WHERE location_id=$my_loc AND payment_id IS NOT NULL)" : "";
$res = mysqli_query($conn, "SELECT DATE(p.created_at) AS d, COALESCE(SUM(p.amount),0) AS rev, COUNT(*) AS txns
    FROM payments p WHERE p.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)$pay_filter GROUP BY d");
if ($res) while ($r = mysqli_fetch_assoc($res)) $day_map[$r['d']] = ['rev'=>(float)$r['rev'],'txns'=>(int)$r['txns']];
$daily_labels = $daily_data = $daily_txns = [];
foreach ($day_map as $k=>$v) { $daily_labels[]=date('D d',strtotime($k)); $daily_data[]=$v['rev']; $daily_txns[]=$v['txns']; }

$weekly_labels=$weekly_data=$weekly_txns=[];
$res = mysqli_query($conn, "SELECT YEARWEEK(p.created_at,1) AS yw, COALESCE(SUM(p.amount),0) AS rev, COUNT(*) AS txns
    FROM payments p WHERE p.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 WEEK)$pay_filter GROUP BY yw ORDER BY yw ASC");
if ($res) while ($r = mysqli_fetch_assoc($res)) {
    $yr=intdiv((int)$r['yw'],100); $wk=(int)$r['yw']%100;
    $ts=strtotime("$yr-01-01 +".($wk-1)." weeks");
    $weekly_labels[]='W'.$wk.' '.date('M',$ts); $weekly_data[]=(float)$r['rev']; $weekly_txns[]=(int)$r['txns'];
}

$monthly_labels=$monthly_data=$monthly_txns=[];
$res = mysqli_query($conn, "SELECT DATE_FORMAT(p.created_at,'%Y-%m') AS m, DATE_FORMAT(p.created_at,'%b %Y') AS label,
    COALESCE(SUM(p.amount),0) AS rev, COUNT(*) AS txns
    FROM payments p WHERE p.created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)$pay_filter GROUP BY m ORDER BY m ASC");
if ($res) while ($r = mysqli_fetch_assoc($res)) {
    $monthly_labels[]=$r['label']; $monthly_data[]=(float)$r['rev']; $monthly_txns[]=(int)$r['txns'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Smart Parking</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'dashboard'; include "../includes/admin_sidebar.php"; ?>

<div class="main-content">
    <div class="topbar">
        <h1><?= $is_sub ? '&#128737; My Dashboard' : '&#128737; Admin Dashboard' ?></h1>
        <div class="topbar-right" style="display:flex;gap:12px;align-items:center;">
            <?php if ($is_sub): ?>
                <span class="badge badge-info">&#128205; <?= htmlspecialchars($_SESSION['admin_location_name'] ?? 'My Location') ?></span>
            <?php endif; ?>
            <span id="live-clock" style="color:#666;font-size:14px;"></span>
            <span style="color:#666;">📅 <?= date('D, d M Y') ?></span>
            <span id="refresh-badge" class="badge badge-success" style="font-size:11px;">🔄 Live</span>
        </div>
    </div>

    <!-- Stat Cards — all 4 always visible, labels change for sub-admin -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info"><h3>Total Users</h3><h2><?= $total_users ?></h2></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚗</div>
            <div class="stat-info"><h3>Total Vehicles</h3><h2><?= $total_vehicles ?></h2></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-info">
                <h3><?= $is_sub ? 'My Bookings' : 'Total Bookings' ?></h3>
                <h2><?= $total_bookings ?></h2>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#f9a825;">
            <div class="stat-icon" style="background:linear-gradient(135deg,#e65100,#f9a825);">&#128176;</div>
            <div class="stat-info">
                <h3><?= $is_sub ? 'My Revenue' : 'Total Revenue' ?></h3>
                <h2>₹<?= number_format($total_revenue) ?></h2>
            </div>
        </div>
    </div>

    <!-- Unread Messages Widget (super admin only) -->
    <?php if (!$is_sub && $unread_messages > 0): ?>
    <div class="card" style="border-left:4px solid #c62828; margin-bottom:24px;">
        <div class="card-header">
            <h2>📬 Unread Messages <span class="badge badge-danger" style="margin-left:8px;"><?= $unread_messages ?> New</span></h2>
            <a href="view_messages.php" class="btn btn-primary btn-sm">View All</a>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px;">
        <?php while ($m = mysqli_fetch_assoc($recent_messages)): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;
                        background:var(--bg); border-radius:10px; padding:12px 16px; border:1px solid var(--border);">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#c62828;flex-shrink:0;"></span>
                    <strong style="font-size:14px;"><?= htmlspecialchars($m['name']) ?></strong>
                    <span style="font-size:13px;color:var(--text-muted);"><?= htmlspecialchars($m['email']) ?></span>
                    <span class="badge badge-info">📌 <?= htmlspecialchars($m['subject']) ?></span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:12px;color:var(--text-muted);"><?= date('d M, h:i A', strtotime($m['created_at'])) ?></span>
                    <a href="view_messages.php?mark_read=<?= $m['id'] ?>" class="btn btn-success btn-sm">✅ Mark Read</a>
                </div>
            </div>
        <?php endwhile; ?>
        <?php if ($unread_messages > 3): ?>
            <div style="text-align:center; padding:4px;">
                <a href="view_messages.php?filter=unread" style="font-size:13px; color:var(--primary-light); font-weight:600;">
                    + <?= $unread_messages - 3 ?> more unread messages →
                </a>
            </div>
        <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Insights -->
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header"><h2>🏆 Top Insights <?= $is_sub ? '<span style="font-size:12px;font-weight:500;color:#999;">— My Location</span>' : '' ?></h2></div>
        <div class="top-stats-grid">
            <div class="top-stat-card">
                <div class="ts-icon">📍</div>
                <div class="ts-info">
                    <div class="ts-label">Most Booked Location</div>
                    <div class="ts-value"><?= $top_location ? htmlspecialchars($top_location['location_name']) : '—' ?></div>
                    <?php if ($top_location): ?><div class="ts-sub"><?= $top_location['cnt'] ?> bookings</div><?php endif; ?>
                </div>
            </div>
            <div class="top-stat-card">
                <div class="ts-icon">👑</div>
                <div class="ts-info">
                    <div class="ts-label">Top Paying User</div>
                    <div class="ts-value"><?= $top_user ? htmlspecialchars($top_user['name']) : '—' ?></div>
                    <?php if ($top_user): ?><div class="ts-sub">₹<?= number_format($top_user['total']) ?> spent</div><?php endif; ?>
                </div>
            </div>
            <div class="top-stat-card">
                <div class="ts-icon">📆</div>
                <div class="ts-info">
                    <div class="ts-label">Busiest Day</div>
                    <div class="ts-value"><?= $busy_day ? htmlspecialchars($busy_day['day']) : '—' ?></div>
                    <?php if ($busy_day): ?><div class="ts-sub"><?= $busy_day['cnt'] ?> bookings</div><?php endif; ?>
                </div>
            </div>
            <div class="top-stat-card">
                <div class="ts-icon">🅿️</div>
                <div class="ts-info">
                    <div class="ts-label">Most Booked Slot</div>
                    <div class="ts-value"><?= $top_slot ? htmlspecialchars($top_slot['slot_number']) : '—' ?></div>
                    <?php if ($top_slot): ?><div class="ts-sub"><?= htmlspecialchars($top_slot['location_name']) ?> &bull; <?= $top_slot['cnt'] ?>x</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h2>&#128200; Revenue Chart <?= $is_sub ? '<span style="font-size:12px;font-weight:500;color:#999;">— My Location</span>' : '' ?></h2>
            <div style="display:flex;gap:4px;background:var(--bg);border-radius:10px;padding:4px;">
                <button class="btn btn-sm btn-primary" id="tabDaily"   onclick="switchChart('daily')">Daily</button>
                <button class="btn btn-sm btn-outline" id="tabWeekly"  onclick="switchChart('weekly')">Weekly</button>
                <button class="btn btn-sm btn-outline" id="tabMonthly" onclick="switchChart('monthly')">Monthly</button>
            </div>
        </div>
        <div style="position:relative;height:280px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header"><h2>⚡ Quick Actions</h2></div>
        <div class="actions-grid">
            <?php if (is_super()): ?>
            <a href="manage_locations.php" class="action-card"><div class="ac-icon">📍</div><div class="ac-label">Locations</div></a>
            <?php endif; ?>
            <a href="manage_slots.php"  class="action-card"><div class="ac-icon">🅿️</div><div class="ac-label">Manage Slots</div></a>
            <?php if (is_super()): ?>
            <a href="view_users.php"    class="action-card"><div class="ac-icon">👥</div><div class="ac-label">Users</div></a>
            <a href="view_vehicles.php" class="action-card"><div class="ac-icon">🚗</div><div class="ac-label">Vehicles</div></a>
            <?php endif; ?>
            <a href="view_bookings.php" class="action-card"><div class="ac-icon">&#128203;</div><div class="ac-label">Bookings</div></a>
            <a href="view_payments.php" class="action-card"><div class="ac-icon">💳</div><div class="ac-label">Payments</div></a>
            <a href="revenue.php"       class="action-card"><div class="ac-icon">&#128176;</div><div class="ac-label">Revenue</div></a>
        </div>
    </div>

    <!-- Slot Occupancy -->
    <div class="card">
        <div class="card-header">
            <h2>🅿️ Slot Occupancy <?= $is_sub ? '<span style="font-size:12px;font-weight:500;color:#999;">— My Location</span>' : '' ?></h2>
            <a href="manage_slots.php" class="btn btn-outline btn-sm">Manage Slots</a>
        </div>
        <?php if (mysqli_num_rows($occupancy) === 0): ?>
            <p style="text-align:center;color:#999;padding:24px;">No locations found.</p>
        <?php else: ?>
        <div class="occupancy-grid">
            <?php while ($oc = mysqli_fetch_assoc($occupancy)):
                $total_s = (int)$oc['total'];
                $booked  = (int)$oc['booked'];
                $maint   = (int)$oc['maintenance'];
                $avail   = (int)$oc['available'];
                $pct     = $total_s > 0 ? round(($booked / $total_s) * 100) : 0;
            ?>
            <div class="occ-card">
                <div class="occ-header">
                    <span class="occ-name">&#128205; <?= htmlspecialchars($oc['location_name']) ?></span>
                    <?php if ($total_s === 0): ?>
                        <span class="occ-pct" style="background:#f5f5f5;color:#999;">No slots</span>
                    <?php else: ?>
                        <span class="occ-pct <?= $pct>=80?'pct-high':($pct>=50?'pct-mid':'pct-low') ?>"><?= $pct ?>% full</span>
                    <?php endif; ?>
                </div>
                <div class="occ-bar-wrap">
                    <div class="occ-bar" style="width:<?= $pct ?>%;background:<?= $pct>=80?'#c62828':($pct>=50?'#e65100':'#2e7d32') ?>;"></div>
                </div>
                <div class="occ-stats">
                    <?php if ($total_s === 0): ?>
                        <span class="occ-stat" style="color:#999;">No slots yet. <a href="manage_slots.php" style="color:#1a237e;">Add slots</a></span>
                    <?php else: ?>
                        <span class="occ-stat occ-avail">&#9989; <?= $avail ?> Available</span>
                        <span class="occ-stat occ-booked">&#128308; <?= $booked ?> Booked</span>
                        <?php if ($maint): ?><span class="occ-stat occ-maint">&#128295; <?= $maint ?> Maintenance</span><?php endif; ?>
                        <span class="occ-stat" style="color:#666;">Total: <?= $total_s ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Bookings -->
    <div class="card">
        <div class="card-header">
            <h2>&#128203; Recent Bookings <?= $is_sub ? '<span style="font-size:12px;font-weight:500;color:#999;">— My Location</span>' : '' ?></h2>
            <a href="view_bookings.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>User</th><th>Location</th><th>Slot</th><th>Hours</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php
                $has = false;
                while ($b = mysqli_fetch_assoc($recent_bookings)):
                    $has = true;
                    $bst  = strtolower($b['status'] ?? 'booked');
                    $bcls = $bst==='booked'?'info':($bst==='completed'?'success':'danger');
                ?>
                <tr>
                    <td>#<?= $b['id'] ?></td>
                    <td><?= htmlspecialchars($b['user_name']) ?></td>
                    <td>&#128205; <?= htmlspecialchars($b['location_name']) ?></td>
                    <td><?= htmlspecialchars($b['slot_number']) ?></td>
                    <td><?= $b['hours'] ?> hrs</td>
                    <td><strong>&#8377;<?= $b['amount'] ?></strong></td>
                    <td><span class="badge badge-<?= $bcls ?>"><?= ucfirst($bst) ?></span></td>
                </tr>
                <?php endwhile;
                if (!$has): ?>
                    <tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">No bookings yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="card">
        <div class="card-header">
            <h2>💳 Recent Payments <?= $is_sub ? '<span style="font-size:12px;font-weight:500;color:#999;">— My Location</span>' : '' ?></h2>
            <a href="view_payments.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>User</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>
                <?php
                $has = false;
                while ($p = mysqli_fetch_assoc($recent_payments)):
                    $has = true;
                ?>
                <tr>
                    <td>#<?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['user_name']) ?></td>
                    <td><strong>₹<?= $p['amount'] ?></strong></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['method']) ?></span></td>
                    <td><?= date('d M Y, h:i A', strtotime($p['created_at'])) ?></td>
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
const chart = new Chart(ctx, {
    data: {
        labels: chartData.daily.labels,
        datasets: [{
            type: 'bar', label: 'Revenue (₹)', data: chartData.daily.rev,
            backgroundColor: 'rgba(26,35,126,0.75)', borderColor: '#1a237e',
            borderWidth: 0, borderRadius: 6, yAxisID: 'y'
        },{
            type: 'line', label: 'Transactions', data: chartData.daily.txns,
            borderColor: '#f9a825', backgroundColor: 'rgba(249,168,37,0.08)',
            borderWidth: 2.5, pointBackgroundColor: '#fff', pointBorderColor: '#f9a825',
            pointBorderWidth: 2, pointRadius: 4, tension: 0.4, yAxisID: 'y2'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 16 } },
            tooltip: { backgroundColor: 'rgba(10,10,31,.92)', titleColor: '#fff', bodyColor: 'rgba(255,255,255,.8)',
                padding: 10, cornerRadius: 8,
                callbacks: { label: c => c.dataset.label === 'Revenue (₹)' ? ' ₹'+c.parsed.y.toLocaleString() : ' '+c.parsed.y+' txns' }
            }
        },
        scales: {
            y:  { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => '₹'+(v>=1000?(v/1000).toFixed(0)+'k':v) } },
            y2: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { stepSize: 1 } },
            x:  { grid: { display: false } }
        }
    }
});
function switchChart(type) {
    chart.data.labels           = chartData[type].labels;
    chart.data.datasets[0].data = chartData[type].rev;
    chart.data.datasets[1].data = chartData[type].txns;
    chart.update();
    ['Daily','Weekly','Monthly'].forEach(t => {
        const btn = document.getElementById('tab'+t);
        btn.className = t.toLowerCase()===type ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline';
    });
}
// Live clock
(function tick(){ const el=document.getElementById('live-clock'); if(el) el.textContent=new Date().toLocaleTimeString(); setTimeout(tick,1000); })();
// Auto-refresh
let cd=30; const badge=document.getElementById('refresh-badge');
setInterval(()=>{ cd--; if(badge) badge.textContent=`↺ Refresh in ${cd}s`; if(cd<=0) location.reload(); },1000);
</script>
</body>
</html>
