<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

$search = trim($_GET['search'] ?? '');
$where  = $search ? "WHERE u.name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR u.email LIKE '%".mysqli_real_escape_string($conn,$search)."%'" : "";

$users = mysqli_query($conn,
"SELECT u.*, (SELECT COUNT(*) FROM bookings WHERE user_id=u.id) AS bcount,
        (SELECT COALESCE(SUM(amount),0) FROM payments WHERE user_id=u.id) AS total_spent
 FROM users u $where ORDER BY u.id DESC");
$total = mysqli_num_rows($users);

// CSV Export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['#','Name','Email','Phone','Bookings','Total Spent','Joined']);
    $i = 1;
    while ($u = mysqli_fetch_assoc($users))
        fputcsv($out, [$i++, $u['name'], $u['email'], $u['phone']??'', $u['bcount'], 'Rs.'.$u['total_spent'], isset($u['created_at'])?date('d M Y',strtotime($u['created_at'])):'']);
    fclose($out); exit;
}

// Fetch all users data for modal (re-query since export may have consumed result)
$users_data = [];
while ($u = mysqli_fetch_assoc($users)) $users_data[] = $u;

// Build booking history per user for modal
$user_ids = array_column($users_data, 'id');
$bookings_map = [];
if ($user_ids) {
    $ids_str = implode(',', $user_ids);
    $bres = mysqli_query($conn,
    "SELECT b.user_id, b.id, l.location_name, s.slot_number, b.hours, b.amount, b.status, b.created_at
     FROM bookings b
     JOIN locations l ON b.location_id=l.id
     JOIN slots s ON b.slot_id=s.id
     WHERE b.user_id IN ($ids_str) ORDER BY b.id DESC");
    while ($brow = mysqli_fetch_assoc($bres)) $bookings_map[$brow['user_id']][] = $brow;

    $vres = mysqli_query($conn, "SELECT * FROM vehicles WHERE user_id IN ($ids_str)");
    $vehicles_map = [];
    while ($vrow = mysqli_fetch_assoc($vres)) $vehicles_map[$vrow['user_id']][] = $vrow;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Users - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'users'; include "../includes/admin_sidebar.php"; ?>
<div class="main-content">
    <div class="topbar">
        <h1>👥 All Users</h1>
        <div class="topbar-right print-hide" style="display:flex;gap:10px;align-items:center;">
            <span style="color:#666;"><?= $total ?> user(s)</span>
            <a href="?search=<?= urlencode($search) ?>&export=1" class="btn btn-success btn-sm">📤 Export CSV</a>
            <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print</button>
        </div>
    </div>

    <div class="card print-hide" style="margin-bottom:20px;">
        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="form-control" placeholder="🔍 Search by name or email…" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="view_users.php" class="btn btn-outline">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h2>Registered Users</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Total Spent</th><th>Joined</th><th class="print-hide">Detail</th></tr></thead>
                <tbody>
                <?php if ($total === 0): ?>
                    <tr><td colspan="8" style="text-align:center;color:#999;padding:24px;">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users_data as $i => $u): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td>👤 <strong><?= htmlspecialchars($u['name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                        <td><span class="badge badge-info"><?= $u['bcount'] ?> bookings</span></td>
                        <td><strong style="color:#2e7d32;">₹<?= number_format($u['total_spent']) ?></strong></td>
                        <td style="font-size:13px;color:#666;"><?= isset($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '—' ?></td>
                        <td class="print-hide">
                            <button class="btn btn-outline btn-sm" onclick="openModal(<?= $u['id'] ?>)">👁️ View</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<!-- User Detail Modal -->
<div id="user-modal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="modal-title">User Details</h2>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body" id="modal-body"></div>
    </div>
</div>

<script>
const usersData = <?php echo json_encode(array_combine(array_column($users_data,'id'), $users_data)); ?>;
const bookingsMap = <?php echo json_encode($bookings_map ?? []); ?>;
const vehiclesMap = <?php echo json_encode($vehicles_map ?? []); ?>;

function openModal(uid) {
    const u = usersData[uid];
    const bookings = bookingsMap[uid] || [];
    const vehicles = vehiclesMap[uid] || [];

    let vhtml = vehicles.length
        ? vehicles.map(v => `<span class="preview-chip">${v.vehicle_number} (${v.type||'Car'})</span>`).join('')
        : '<span style="color:#999;">No vehicles registered.</span>';

    let bhtml = bookings.length ? `
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead><tr style="background:#1a237e;color:#fff;">
                <th style="padding:8px;">#</th><th style="padding:8px;">Location</th>
                <th style="padding:8px;">Slot</th><th style="padding:8px;">Hrs</th>
                <th style="padding:8px;">Amount</th><th style="padding:8px;">Status</th><th style="padding:8px;">Date</th>
            </tr></thead><tbody>` +
        bookings.map((b,i) => {
            const st = b.status||'booked';
            const badge = st==='completed'?'badge-success':st==='cancelled'?'badge-danger':'badge-info';
            return `<tr style="border-bottom:1px solid #eee;">
                <td style="padding:8px;">#${b.id}</td>
                <td style="padding:8px;">📍 ${b.location_name}</td>
                <td style="padding:8px;">${b.slot_number}</td>
                <td style="padding:8px;">${b.hours}h</td>
                <td style="padding:8px;color:#2e7d32;font-weight:700;">₹${b.amount}</td>
                <td style="padding:8px;"><span class="badge ${badge}">${st.charAt(0).toUpperCase()+st.slice(1)}</span></td>
                <td style="padding:8px;color:#666;">${b.created_at ? b.created_at.substring(0,10) : '—'}</td>
            </tr>`;
        }).join('') + '</tbody></table>'
        : '<p style="color:#999;text-align:center;padding:16px;">No bookings yet.</p>';

    document.getElementById('modal-title').textContent = '👤 ' + u.name;
    document.getElementById('modal-body').innerHTML = `
        <div class="modal-user-info">
            <div class="mui-row"><span class="mui-label">Email</span><span>${u.email}</span></div>
            <div class="mui-row"><span class="mui-label">Phone</span><span>${u.phone||'—'}</span></div>
            <div class="mui-row"><span class="mui-label">Joined</span><span>${u.created_at ? u.created_at.substring(0,10) : '—'}</span></div>
            <div class="mui-row"><span class="mui-label">Total Spent</span><span style="color:#2e7d32;font-weight:700;">₹${Number(u.total_spent).toLocaleString()}</span></div>
        </div>
        <div style="margin:16px 0 8px;font-weight:700;color:#1a237e;">🚗 Vehicles</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">${vhtml}</div>
        <div style="margin:0 0 8px;font-weight:700;color:#1a237e;">📋 Booking History (${bookings.length})</div>
        <div style="max-height:260px;overflow-y:auto;">${bhtml}</div>`;
    document.getElementById('user-modal').style.display = 'flex';
}
function closeModal() { document.getElementById('user-modal').style.display = 'none'; }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });
</script>
</body>
</html>
