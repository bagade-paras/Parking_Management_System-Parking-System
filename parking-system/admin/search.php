<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

$q       = trim($_GET['q'] ?? '');
$results = [];

if ($q !== '') {
    $safe = mysqli_real_escape_string($conn, $q);

    $results['users'] = mysqli_query($conn,
    "SELECT id, name, email, phone FROM users
     WHERE name LIKE '%$safe%' OR email LIKE '%$safe%' LIMIT 10");

    $results['bookings'] = mysqli_query($conn,
    "SELECT b.id, u.name AS user_name, l.location_name, s.slot_number, b.amount, b.status
     FROM bookings b
     JOIN users u ON b.user_id=u.id
     JOIN locations l ON b.location_id=l.id
     JOIN slots s ON b.slot_id=s.id
     WHERE u.name LIKE '%$safe%' OR l.location_name LIKE '%$safe%' OR s.slot_number LIKE '%$safe%'
     LIMIT 10");

    $results['slots'] = mysqli_query($conn,
    "SELECT s.id, s.slot_number, s.status, l.location_name FROM slots s
     JOIN locations l ON s.location_id=l.id
     WHERE s.slot_number LIKE '%$safe%' OR l.location_name LIKE '%$safe%' LIMIT 10");

    $results['locations'] = mysqli_query($conn,
    "SELECT id, location_name FROM locations WHERE location_name LIKE '%$safe%' LIMIT 10");

    $results['payments'] = mysqli_query($conn,
    "SELECT p.id, u.name AS user_name, p.amount, p.method, p.reference_id
     FROM payments p JOIN users u ON p.user_id=u.id
     WHERE u.name LIKE '%$safe%' OR p.reference_id LIKE '%$safe%' LIMIT 10");
}

function highlight($text, $q) {
    if (!$q) return htmlspecialchars($text);
    return preg_replace('/('.preg_quote(htmlspecialchars($q),'/').')/i', '<mark>$1</mark>', htmlspecialchars($text));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Global Search - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'search'; include "../includes/admin_sidebar.php"; ?>
<div class="main-content">
    <div class="topbar"><h1>🔍 Global Search</h1></div>

    <!-- Search Box -->
    <div class="card" style="margin-bottom:24px;">
        <form method="GET" class="filter-bar">
            <input type="text" name="q" class="form-control" placeholder="Search users, bookings, slots, locations, payments…" value="<?= htmlspecialchars($q) ?>" autofocus style="font-size:16px;">
            <button type="submit" class="btn btn-primary" style="padding:11px 28px;">🔍 Search</button>
            <?php if ($q): ?><a href="search.php" class="btn btn-outline">Reset</a><?php endif; ?>
        </form>
    </div>

    <?php if ($q === ''): ?>
    <div style="text-align:center;padding:60px 0;color:#999;">
        <div style="font-size:56px;margin-bottom:16px;">🔍</div>
        <p style="font-size:16px;">Type something above to search across all records.</p>
    </div>
    <?php else: ?>

    <!-- Users -->
    <?php $urows = mysqli_num_rows($results['users']); ?>
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h2>👥 Users <span class="badge badge-info"><?= $urows ?></span></h2>
            <?php if ($urows): ?><a href="view_users.php?search=<?= urlencode($q) ?>" class="btn btn-outline btn-sm">View All</a><?php endif; ?>
        </div>
        <?php if (!$urows): ?>
            <p style="color:#999;padding:12px 0;">No users found.</p>
        <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th></tr></thead>
            <tbody>
            <?php while ($r = mysqli_fetch_assoc($results['users'])): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td>👤 <?= highlight($r['name'], $q) ?></td>
                <td><?= highlight($r['email'], $q) ?></td>
                <td><?= htmlspecialchars($r['phone'] ?? '—') ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>

    <!-- Bookings -->
    <?php $brows = mysqli_num_rows($results['bookings']); ?>
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h2>📋 Bookings <span class="badge badge-info"><?= $brows ?></span></h2>
            <?php if ($brows): ?><a href="view_bookings.php?search=<?= urlencode($q) ?>" class="btn btn-outline btn-sm">View All</a><?php endif; ?>
        </div>
        <?php if (!$brows): ?>
            <p style="color:#999;padding:12px 0;">No bookings found.</p>
        <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>#</th><th>User</th><th>Location</th><th>Slot</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            <?php while ($r = mysqli_fetch_assoc($results['bookings'])): ?>
            <?php $st = strtolower($r['status']??'booked'); ?>
            <tr>
                <td>#<?= $r['id'] ?></td>
                <td><?= highlight($r['user_name'], $q) ?></td>
                <td><?= highlight($r['location_name'], $q) ?></td>
                <td><?= highlight($r['slot_number'], $q) ?></td>
                <td><strong>₹<?= $r['amount'] ?></strong></td>
                <td><span class="badge badge-<?= $st==='booked'?'info':($st==='completed'?'success':'danger') ?>"><?= ucfirst($st) ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>

    <!-- Slots -->
    <?php $srows = mysqli_num_rows($results['slots']); ?>
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h2>🅿️ Slots <span class="badge badge-info"><?= $srows ?></span></h2>
            <?php if ($srows): ?><a href="manage_slots.php" class="btn btn-outline btn-sm">Manage Slots</a><?php endif; ?>
        </div>
        <?php if (!$srows): ?>
            <p style="color:#999;padding:12px 0;">No slots found.</p>
        <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>#</th><th>Slot</th><th>Location</th><th>Status</th></tr></thead>
            <tbody>
            <?php while ($r = mysqli_fetch_assoc($results['slots'])): ?>
            <?php $st = strtolower($r['status']); ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><strong><?= highlight($r['slot_number'], $q) ?></strong></td>
                <td><?= highlight($r['location_name'], $q) ?></td>
                <td><span class="badge badge-<?= $st==='available'?'success':($st==='maintenance'?'warning':'danger') ?>"><?= ucfirst($st) ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>

    <!-- Locations -->
    <?php $lrows = mysqli_num_rows($results['locations']); ?>
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h2>📍 Locations <span class="badge badge-info"><?= $lrows ?></span></h2>
            <?php if ($lrows): ?><a href="manage_locations.php" class="btn btn-outline btn-sm">Manage</a><?php endif; ?>
        </div>
        <?php if (!$lrows): ?>
            <p style="color:#999;padding:12px 0;">No locations found.</p>
        <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:10px;padding:4px 0;">
            <?php while ($r = mysqli_fetch_assoc($results['locations'])): ?>
            <span class="preview-chip" style="font-size:14px;padding:8px 16px;">📍 <?= highlight($r['location_name'], $q) ?></span>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Payments -->
    <?php $prows = mysqli_num_rows($results['payments']); ?>
    <div class="card">
        <div class="card-header">
            <h2>💳 Payments <span class="badge badge-info"><?= $prows ?></span></h2>
            <?php if ($prows): ?><a href="view_payments.php?search=<?= urlencode($q) ?>" class="btn btn-outline btn-sm">View All</a><?php endif; ?>
        </div>
        <?php if (!$prows): ?>
            <p style="color:#999;padding:12px 0;">No payments found.</p>
        <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>#</th><th>User</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
            <tbody>
            <?php while ($r = mysqli_fetch_assoc($results['payments'])): ?>
            <tr>
                <td>#<?= $r['id'] ?></td>
                <td><?= highlight($r['user_name'], $q) ?></td>
                <td><strong style="color:#2e7d32;">₹<?= $r['amount'] ?></strong></td>
                <td><span class="badge badge-info"><?= htmlspecialchars($r['method']??'UPI') ?></span></td>
                <td><code style="font-size:12px;background:#f5f5f5;padding:2px 6px;border-radius:4px;"><?= highlight($r['reference_id']??'—', $q) ?></code></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>
</div>
</body>
</html>
