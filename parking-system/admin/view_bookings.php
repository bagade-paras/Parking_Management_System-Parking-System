<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

// Must match timezone used when saving start_time
date_default_timezone_set('Asia/Kolkata');

$message = $error = '';

if (isset($_GET['set_status'])) {
    $bid    = (int)$_GET['set_status'];
    $status = in_array($_GET['status'], ['cancelled','completed']) ? $_GET['status'] : '';
    if ($status) {
        $booking = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT slot_id, status, start_time, created_at, hours FROM bookings WHERE id=$bid"));

        if ($booking && $booking['status'] === 'booked') {
            $raw      = !empty($booking['start_time']) ? $booking['start_time'] : $booking['created_at'];
            $start_ts = strtotime($raw);
            $end_ts   = $start_ts + ((int)$booking['hours'] * 3600);
            $now_ts   = time();
            $is_active = $now_ts >= $start_ts && $now_ts < $end_ts;
            $force     = isset($_GET['force']) && $_GET['force'] === '1';

            if ($status === 'cancelled' && $is_active && !$force) {
                $remaining = '';
                $diff = $end_ts - $now_ts;
                $h = floor($diff/3600); $m = floor(($diff%3600)/60);
                $remaining = ($h > 0 ? $h.'h ' : '') . str_pad($m,2,'0',STR_PAD_LEFT).'m';
                $error = "CONFIRM_ACTIVE:{$bid}:{$remaining}";
            } else {
                mysqli_query($conn, "UPDATE bookings SET status='$status' WHERE id=$bid");
                mysqli_query($conn, "UPDATE slots SET status='available' WHERE id={$booking['slot_id']} AND status='booked'");
                $message = "Booking #$bid marked as " . ucfirst($status) . ". Slot freed.";
            }
        } else {
            $message = "Booking #$bid is already {$booking['status']} — no changes made.";
        }
    }
}
if (isset($_GET['msg'])) $message = urldecode($_GET['msg']);

$where = "1";
if (is_sub() && my_location()) $where .= " AND b.location_id=" . my_location();
$search        = trim($_GET['search'] ?? '');
$filter_status = $_GET['filter_status'] ?? '';
$filter_date   = $_GET['filter_date'] ?? '';

if ($search)        $where .= " AND (u.name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR s.slot_number LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR l.location_name LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($filter_status) $where .= " AND b.status='".mysqli_real_escape_string($conn,$filter_status)."'";
if ($filter_date)   $where .= " AND DATE(COALESCE(b.start_time,b.created_at))='".mysqli_real_escape_string($conn,$filter_date)."'";

$result = mysqli_query($conn,
"SELECT b.*, u.name AS user_name, l.location_name, s.slot_number
 FROM bookings b
 JOIN users u ON b.user_id = u.id
 JOIN locations l ON b.location_id = l.id
 JOIN slots s ON b.slot_id = s.id
 WHERE $where ORDER BY b.id DESC");

// CSV Export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bookings_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['#','User','Location','Slot','Hours','Amount','Status','Entry Time','Exit Time']);
    while ($b = mysqli_fetch_assoc($result)) {
        $raw   = !empty($b['start_time']) ? $b['start_time'] : $b['created_at'];
        $s_ts  = strtotime($raw);
        $e_ts  = $s_ts + ((int)$b['hours'] * 3600);
        fputcsv($out, [
            $b['id'], $b['user_name'], $b['location_name'], $b['slot_number'],
            $b['hours'].'hrs', 'Rs.'.$b['amount'], ucfirst($b['status']),
            date('d M Y h:i A', $s_ts), date('d M Y h:i A', $e_ts)
        ]);
    }
    fclose($out); exit;
}

$total  = mysqli_num_rows($result);
$now_ts = time();

function formatCountdown($sec) {
    if ($sec <= 0) return '0s';
    $h = floor($sec / 3600);
    $m = floor(($sec % 3600) / 60);
    $s = $sec % 60;
    if ($h > 0) return $h.'h '.str_pad($m,2,'0',STR_PAD_LEFT).'m '.str_pad($s,2,'0',STR_PAD_LEFT).'s';
    if ($m > 0) return $m.'m '.str_pad($s,2,'0',STR_PAD_LEFT).'s';
    return $s.'s';
}

$confirm_active = null;
if (str_starts_with($error, 'CONFIRM_ACTIVE:')) {
    $parts = explode(':', $error);
    $confirm_active = ['bid' => $parts[1], 'remaining' => $parts[2]];
    $error = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bookings - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'bookings'; include "../includes/admin_sidebar.php"; ?>
<div class="main-content">
    <div class="topbar">
        <h1>&#128203; All Bookings</h1>
        <div class="topbar-right print-hide" style="display:flex;gap:10px;align-items:center;">
            <span style="color:#666;"><?= $total ?> result(s)</span>
            <a href="?search=<?= urlencode($search) ?>&filter_status=<?= urlencode($filter_status) ?>&filter_date=<?= urlencode($filter_date) ?>&export=1" class="btn btn-success btn-sm">&#128228; Export CSV</a>
            <button onclick="window.print()" class="btn btn-outline btn-sm">&#128424; Print</button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success print-hide">&#9989; <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger print-hide">&#9888; <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($confirm_active): ?>
    <div class="alert alert-warning print-hide" style="border-left-color:#e65100;background:#fff8e1;color:#e65100;">
        <div style="font-size:15px;font-weight:700;margin-bottom:8px;">
            &#9888; This booking is currently ACTIVE — <?= htmlspecialchars($confirm_active['remaining']) ?> remaining
        </div>
        <p style="margin-bottom:12px;font-size:14px;">The user's parking session is still in progress. Cancel early and free the slot?</p>
        <div style="display:flex;gap:10px;">
            <a href="?set_status=<?= $confirm_active['bid'] ?>&status=cancelled&force=1" class="btn btn-danger btn-sm">&#128465; Yes, Cancel Early</a>
            <a href="view_bookings.php" class="btn btn-outline btn-sm">&#10005; No, Keep Booking</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card print-hide" style="margin-bottom:20px;">
        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="form-control" placeholder="Search user, slot, location…" value="<?= htmlspecialchars($search) ?>">
            <select name="filter_status" class="form-control">
                <option value="">All Statuses</option>
                <option value="booked"    <?= $filter_status==='booked'?'selected':'' ?>>Booked</option>
                <option value="completed" <?= $filter_status==='completed'?'selected':'' ?>>Completed</option>
                <option value="cancelled" <?= $filter_status==='cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
            <input type="date" name="filter_date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="view_bookings.php" class="btn btn-outline">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Booking Records</h2>
            <span class="print-only" style="font-size:13px;color:#666;">Printed: <?= date('d M Y, h:i A') ?></span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>User</th><th>Location</th><th>Slot</th>
                        <th>Hrs</th><th>Amount</th><th>Status</th>
                        <th>Time Remaining</th><th>Entry / Exit</th>
                        <th class="print-hide">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($total === 0): ?>
                    <tr><td colspan="10" style="text-align:center;color:#999;padding:24px;">No bookings found.</td></tr>
                <?php else: ?>
                    <?php while ($b = mysqli_fetch_assoc($result)):
                        $st       = strtolower($b['status'] ?? 'booked');
                        $raw      = !empty($b['start_time']) ? $b['start_time'] : $b['created_at'];
                        $start_ts = strtotime($raw);
                        $end_ts   = $start_ts + ((int)$b['hours'] * 3600);
                        $diff     = $end_ts - $now_ts;
                        $is_active   = $st === 'booked' && $now_ts >= $start_ts && $diff > 0;
                        $is_upcoming = $st === 'booked' && $now_ts < $start_ts;
                    ?>
                    <tr class="<?= $is_active ? 'row-active-booking' : '' ?>">
                        <td>#<?= $b['id'] ?></td>
                        <td>&#128100; <?= htmlspecialchars($b['user_name']) ?></td>
                        <td>&#128205; <?= htmlspecialchars($b['location_name']) ?></td>
                        <td><strong><?= htmlspecialchars($b['slot_number']) ?></strong></td>
                        <td><?= $b['hours'] ?>h</td>
                        <td><strong>&#8377;<?= $b['amount'] ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $st==='booked'?'info':($st==='completed'?'success':'danger') ?>">
                                <?= ucfirst($st) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($st === 'booked'): ?>
                                <?php if ($is_upcoming): ?>
                                    <?php
                                        $d = $start_ts - $now_ts;
                                        $h = floor($d/3600); $m = floor(($d%3600)/60); $s = $d%60;
                                        if ($h > 0) $cd = $h.'h '.str_pad($m,2,'0',STR_PAD_LEFT).'m '.str_pad($s,2,'0',STR_PAD_LEFT).'s';
                                        elseif ($m > 0) $cd = $m.'m '.str_pad($s,2,'0',STR_PAD_LEFT).'s';
                                        else $cd = $s.'s';
                                    ?>
                                    <span class="time-upcoming"
                                          data-start="<?= $start_ts ?>"
                                          data-end="<?= $end_ts ?>"
                                          style="display:inline-block;font-size:12px;font-weight:700;color:#2e7d32;background:#e8f5e9;padding:3px 10px;border-radius:20px;border:1px solid #a5d6a7;">
                                        Starts in <?= $cd ?>
                                    </span>
                                <?php elseif ($is_active): ?>
                                    <?php
                                        $d = $end_ts - $now_ts;
                                        $h = floor($d/3600); $m = floor(($d%3600)/60); $s = $d%60;
                                        if ($h > 0) $cd = $h.'h '.str_pad($m,2,'0',STR_PAD_LEFT).'m '.str_pad($s,2,'0',STR_PAD_LEFT).'s';
                                        elseif ($m > 0) $cd = $m.'m '.str_pad($s,2,'0',STR_PAD_LEFT).'s';
                                        else $cd = $s.'s';
                                    ?>
                                    <span class="time-remaining active" data-end="<?= $end_ts ?>">
                                        <?= $cd ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Expired</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#bbb;font-size:12px;">&#8212;</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:#666;">
                            <?= date('d M Y', $start_ts) ?><br>
                            <span style="color:#1a237e;font-weight:600;">&#128994; <?= date('h:i A', $start_ts) ?></span><br>
                            <span style="color:#c62828;font-weight:600;">&#128308; <?= date('h:i A', $end_ts) ?></span>
                        </td>
                        <td class="print-hide">
                            <?php if ($st === 'booked'): ?>
                            <div class="status-actions">
                                <a href="?set_status=<?= $b['id'] ?>&status=completed&search=<?= urlencode($search) ?>&filter_status=<?= urlencode($filter_status) ?>&filter_date=<?= urlencode($filter_date) ?>"
                                   class="btn btn-success btn-sm"
                                   onclick="return confirm('Mark booking #<?= $b['id'] ?> as Completed?')">&#9989;</a>
                                <a href="?set_status=<?= $b['id'] ?>&status=cancelled&search=<?= urlencode($search) ?>&filter_status=<?= urlencode($filter_status) ?>&filter_date=<?= urlencode($filter_date) ?>"
                                   class="btn btn-danger btn-sm <?= $is_active ? 'btn-active-cancel' : '' ?>"
                                   onclick="return confirmCancel(<?= $b['id'] ?>, <?= $is_active ? 'true' : 'false' ?>)">&#10005;</a>
                            </div>
                            <?php else: ?>
                                <span style="font-size:12px;color:#bbb;">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<script>
function pad(n) { return String(n).padStart(2,'0'); }

function formatCountdown(sec) {
    if (sec <= 0) return '0s';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    if (h > 0) return h + 'h ' + pad(m) + 'm ' + pad(s) + 's';
    if (m > 0) return m + 'm ' + pad(s) + 's';
    return s + 's';
}

function updateTimers() {
    const nowSec = Math.floor(Date.now() / 1000);

    document.querySelectorAll('.time-remaining.active[data-end]').forEach(el => {
        const diff = parseInt(el.dataset.end) - nowSec;
        if (diff <= 0) {
            el.outerHTML = '<span class="badge badge-warning">&#9200; Time Up</span>';
            return;
        }
        el.textContent = formatCountdown(diff);
        diff < 600 ? el.classList.add('expiring') : el.classList.remove('expiring');
    });

    document.querySelectorAll('.time-upcoming[data-start]').forEach(el => {
        const startSec = parseInt(el.dataset.start);
        const endSec   = parseInt(el.dataset.end);
        const diffStart = startSec - nowSec;

        if (diffStart <= 0) {
            const diffEnd = endSec - nowSec;
            if (diffEnd > 0) {
                el.className   = 'time-remaining active';
                el.dataset.end = endSec;
                el.removeAttribute('data-start');
                el.style       = '';
                el.textContent = formatCountdown(diffEnd);
            } else {
                el.outerHTML = '<span class="badge badge-warning">&#9200; Time Up</span>';
            }
            return;
        }
        el.textContent = 'Starts in ' + formatCountdown(diffStart);
    });
}

updateTimers();
setInterval(updateTimers, 1000);

function confirmCancel(bid, isActive) {
    if (isActive) {
        return confirm('WARNING: This booking is currently ACTIVE!\n\nThe user\'s parking session is still in progress.\n\nCancel early and free the slot?');
    }
    return confirm('Cancel booking #' + bid + '? The slot will be freed.');
}
</script>
</body>
</html>
