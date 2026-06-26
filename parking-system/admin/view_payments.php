<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

$search        = trim($_GET['search'] ?? '');
$filter_date   = $_GET['filter_date'] ?? '';
$filter_method = $_GET['filter_method'] ?? '';

$where = "1";
if (is_sub() && my_location()) $where .= " AND p.id IN (SELECT payment_id FROM bookings WHERE location_id=" . my_location() . " AND payment_id IS NOT NULL)";
if ($search)        $where .= " AND (u.name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR p.reference_id LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($filter_date)   $where .= " AND DATE(p.created_at)='".mysqli_real_escape_string($conn,$filter_date)."'";
if ($filter_method) $where .= " AND p.method='".mysqli_real_escape_string($conn,$filter_method)."'";

$result = mysqli_query($conn,
"SELECT p.*, u.name AS user_name FROM payments p
 JOIN users u ON p.user_id = u.id
 WHERE $where ORDER BY p.id DESC");

// CSV Export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payments_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['#','User','Amount','Method','Reference','Date']);
    while ($p = mysqli_fetch_assoc($result))
        fputcsv($out, [$p['id'],$p['user_name'],'Rs.'.$p['amount'],$p['method']??'UPI',$p['reference_id']??'',isset($p['created_at'])?date('d M Y, h:i A',strtotime($p['created_at'])):'']);
    fclose($out); exit;
}

$total_amount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS t FROM payments p JOIN users u ON p.user_id=u.id WHERE $where"))['t'];
$total_rows   = mysqli_num_rows($result);
$methods      = mysqli_query($conn, "SELECT DISTINCT method FROM payments ORDER BY method");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payments - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'payments'; include "../includes/admin_sidebar.php"; ?>
<div class="main-content">
    <div class="topbar">
        <h1>💳 Payments</h1>
        <div class="topbar-right print-hide" style="display:flex;gap:10px;align-items:center;">
            <span>Filtered Total: <strong style="color:#1a237e;">₹<?= number_format($total_amount) ?></strong></span>
            <a href="?search=<?= urlencode($search) ?>&filter_method=<?= urlencode($filter_method) ?>&filter_date=<?= urlencode($filter_date) ?>&export=1" class="btn btn-success btn-sm">📤 Export CSV</a>
            <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print</button>
        </div>
    </div>

    <div class="card print-hide" style="margin-bottom:20px;">
        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="form-control" placeholder="🔍 Search user or reference…" value="<?= htmlspecialchars($search) ?>">
            <select name="filter_method" class="form-control">
                <option value="">All Methods</option>
                <?php while ($m = mysqli_fetch_assoc($methods)): ?>
                <option value="<?= htmlspecialchars($m['method']) ?>" <?= $filter_method===$m['method']?'selected':'' ?>><?= htmlspecialchars($m['method']) ?></option>
                <?php endwhile; ?>
            </select>
            <input type="date" name="filter_date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="view_payments.php" class="btn btn-outline">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Payment Records</h2>
            <span style="font-size:13px;color:#666;"><?= $total_rows ?> record(s)</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>User</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th></tr></thead>
                <tbody>
                <?php if ($total_rows === 0): ?>
                    <tr><td colspan="6" style="text-align:center;color:#999;padding:24px;">No payments found.</td></tr>
                <?php else: ?>
                    <?php while ($p = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td>👤 <?= htmlspecialchars($p['user_name']) ?></td>
                        <td><strong style="color:#2e7d32;">₹<?= $p['amount'] ?></strong></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($p['method'] ?? 'UPI') ?></span></td>
                        <td><code style="font-size:12px;background:#f5f5f5;padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($p['reference_id'] ?? '—') ?></code></td>
                        <td style="font-size:13px;color:#666;"><?= isset($p['created_at']) ? date('d M Y, h:i A', strtotime($p['created_at'])) : '—' ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</body>
</html>
