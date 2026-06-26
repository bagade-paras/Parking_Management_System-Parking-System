<?php
require_once "includes/db_connect.php";

// Block if all tables already exist
$tables_needed = ['users','admin','locations','slots','vehicles','payments','bookings'];
$missing = [];
foreach ($tables_needed as $tbl) {
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
    if (!$r || mysqli_num_rows($r) === 0) $missing[] = $tbl;
}

if (empty($missing)) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration Blocked</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'Segoe UI',Arial,sans-serif;background:#f0f2f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
    .box{background:#fff;border-radius:14px;box-shadow:0 8px 32px rgba(26,35,126,.12);width:100%;max-width:520px;overflow:hidden;}
    .box-header{padding:28px 32px;background:linear-gradient(135deg,#e65100,#f9a825);color:#fff;}
    .box-header h1{font-size:22px;margin-bottom:4px;}
    .box-header p{opacity:.85;font-size:14px;}
    .box-body{padding:28px 32px;}
    .info{background:#fff8e1;border-left:4px solid #f9a825;border-radius:8px;padding:16px 18px;font-size:14px;color:#e65100;margin-bottom:20px;line-height:1.6;}
    .tables{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 20px;}
    .tbl-chip{background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;border-radius:6px;padding:4px 12px;font-size:13px;font-weight:600;}
    .actions{display:flex;gap:12px;}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 22px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;flex:1;}
    .btn-primary{background:#1a237e;color:#fff;}
    .btn-green{background:#2e7d32;color:#fff;}
</style>
</head>
<body>
<div class="box">
    <div class="box-header">
        <h1>&#128683; Migration Blocked</h1>
        <p>Database is already set up &mdash; no action needed.</p>
    </div>
    <div class="box-body">
        <div class="info">
            &#9989; All required tables already exist in the database.<br><br>
            Your database is already set up. Running this migration is <strong>not needed</strong> and has been blocked to prevent accidental changes.
        </div>
        <strong style="font-size:13px;color:#555;">Tables detected:</strong>
        <div class="tables">
            <?php foreach ($tables_needed as $t): ?>
            <span class="tbl-chip">&#10003; <?= $t ?></span>
            <?php endforeach; ?>
        </div>
        <div class="actions">
            <a href="admin/admin_dashboard.php" class="btn btn-primary">&#8594; Admin Dashboard</a>
            <a href="index.php" class="btn btn-green">&#8594; Go to Home</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

// ── Run migrations ──────────────────────────────────────────
$results = [];

function run($conn, $sql, $label) {
    global $results;
    if (mysqli_query($conn, $sql))
        $results[] = ['ok', $label];
    else
        $results[] = ['err', "$label — " . mysqli_error($conn)];
}

// Create tables if missing
if (in_array('users', $missing)) {
    run($conn, "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(15) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )", "Created table: users");
}

if (in_array('admin', $missing)) {
    run($conn, "CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL DEFAULT 'Administrator',
        role ENUM('super','sub') NOT NULL DEFAULT 'super',
        location_id INT NULL DEFAULT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )", "Created table: admin");

    // Insert default admin
    $hashed = password_hash('admin123', PASSWORD_DEFAULT);
    run($conn, "INSERT IGNORE INTO admin (name, email, password)
        VALUES ('Administrator', 'admin@parking.com', '$hashed')",
        "Inserted default admin (email: admin@parking.com / password: admin123)");
}

if (in_array('locations', $missing)) {
    run($conn, "CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(100) NOT NULL,
        rate_per_hour INT DEFAULT 20,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )", "Created table: locations");
}

if (in_array('slots', $missing)) {
    run($conn, "CREATE TABLE IF NOT EXISTS slots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location_id INT NOT NULL,
        slot_number VARCHAR(20) NOT NULL,
        slot_type ENUM('car','bike','heavy','any') NOT NULL DEFAULT 'any',
        status ENUM('available','booked','maintenance') DEFAULT 'available',
        FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
    )", "Created table: slots");
}

if (in_array('vehicles', $missing)) {
    run($conn, "CREATE TABLE IF NOT EXISTS vehicles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        vehicle_number VARCHAR(20) NOT NULL,
        type VARCHAR(20) DEFAULT 'Car',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )", "Created table: vehicles");
}

if (in_array('payments', $missing)) {
    run($conn, "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        booking_id INT NULL DEFAULT NULL,
        payment_type ENUM('booking','extension') NOT NULL DEFAULT 'booking',
        amount INT NOT NULL,
        method VARCHAR(20) DEFAULT 'UPI',
        reference_id VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )", "Created table: payments");
}

if (in_array('bookings', $missing)) {
    run($conn, "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        vehicle_id INT NOT NULL,
        location_id INT NOT NULL,
        slot_id INT NOT NULL,
        hours INT NOT NULL,
        start_time DATETIME NULL DEFAULT NULL,
        amount INT NOT NULL,
        payment_id INT DEFAULT NULL,
        status ENUM('booked','cancelled','completed') DEFAULT 'booked',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
        FOREIGN KEY (location_id) REFERENCES locations(id),
        FOREIGN KEY (slot_id) REFERENCES slots(id),
        FOREIGN KEY (payment_id) REFERENCES payments(id)
    )", "Created table: bookings");
}

// Alter existing tables if columns are missing
$col = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='slots' AND COLUMN_NAME='status'"));
if ($col && strpos($col['COLUMN_TYPE'], 'maintenance') === false)
    run($conn, "ALTER TABLE slots MODIFY status ENUM('available','booked','maintenance') DEFAULT 'available'",
        "slots.status ENUM updated — added 'maintenance'");
else
    $results[] = ['skip', "slots.status already has 'maintenance' — skipped."];

$st = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME='start_time'"));
if (!$st) {
    run($conn, "ALTER TABLE bookings ADD COLUMN start_time DATETIME NULL DEFAULT NULL AFTER hours",
        "bookings.start_time column added");
    mysqli_query($conn, "UPDATE bookings SET start_time = created_at WHERE start_time IS NULL");
    $results[] = ['ok', "Backfilled start_time for " . mysqli_affected_rows($conn) . " row(s)."];
} else {
    $results[] = ['skip', "bookings.start_time already exists — skipped."];
}

// Sync booking/slot statuses
mysqli_query($conn, "UPDATE bookings SET status='completed'
    WHERE status='booked' AND DATE_ADD(COALESCE(start_time,created_at), INTERVAL hours HOUR) < NOW()");
$results[] = ['ok', "Marked " . mysqli_affected_rows($conn) . " expired booking(s) as completed."];

mysqli_query($conn, "UPDATE slots SET status='booked' WHERE status='available'
    AND id IN (SELECT slot_id FROM bookings WHERE status='booked')");
$results[] = ['ok', "Synced " . mysqli_affected_rows($conn) . " slot(s) to booked."];

mysqli_query($conn, "UPDATE slots SET status='available' WHERE status='booked'
    AND id NOT IN (SELECT slot_id FROM bookings WHERE status='booked')");
$results[] = ['ok', "Freed " . mysqli_affected_rows($conn) . " slot(s) back to available."];

$all_ok = !in_array('err', array_column($results, 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Database Migration</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'Segoe UI',Arial,sans-serif;background:#f0f2f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
    .box{background:#fff;border-radius:14px;box-shadow:0 8px 32px rgba(26,35,126,.12);width:100%;max-width:580px;overflow:hidden;}
    .box-header{padding:28px 32px;background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;}
    .box-header h1{font-size:22px;margin-bottom:4px;}
    .box-header p{opacity:.8;font-size:14px;}
    .box-body{padding:28px 32px;}
    .result-item{display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-radius:8px;margin-bottom:10px;font-size:14px;}
    .result-item.ok  {background:#e8f5e9;color:#2e7d32;border-left:4px solid #2e7d32;}
    .result-item.err {background:#ffebee;color:#c62828;border-left:4px solid #c62828;}
    .result-item.skip{background:#f5f5f5;color:#757575;border-left:4px solid #bdbdbd;}
    .result-icon{font-size:18px;flex-shrink:0;margin-top:1px;}
    .summary{margin-top:20px;padding:16px 20px;border-radius:10px;text-align:center;font-size:15px;font-weight:600;}
    .summary.success{background:#e8f5e9;color:#2e7d32;border:2px solid #a5d6a7;}
    .summary.error  {background:#ffebee;color:#c62828;border:2px solid #ef9a9a;}
    .actions{display:flex;gap:12px;margin-top:20px;}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 22px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;flex:1;}
    .btn-primary{background:#1a237e;color:#fff;}
    .btn-green{background:#2e7d32;color:#fff;}
    .note{margin-top:16px;padding:12px 16px;background:#fff8e1;border-radius:8px;font-size:13px;color:#e65100;border-left:4px solid #e65100;}
</style>
</head>
<body>
<div class="box">
    <div class="box-header">
        <h1>&#128451; Database Migration</h1>
        <p>Smart Parking System &mdash; one-time setup</p>
    </div>
    <div class="box-body">
        <?php foreach ($results as $r): ?>
        <div class="result-item <?= $r[0] ?>">
            <span class="result-icon"><?= $r[0]==='ok' ? '&#9989;' : ($r[0]==='err' ? '&#10060;' : '&#9193;') ?></span>
            <span><?= htmlspecialchars($r[1]) ?></span>
        </div>
        <?php endforeach; ?>

        <div class="summary <?= $all_ok ? 'success' : 'error' ?>">
            <?= $all_ok ? '&#9989; All migrations completed successfully!' : '&#10060; Some migrations failed. Check errors above.' ?>
        </div>

        <div class="actions">
            <a href="admin/admin_dashboard.php" class="btn btn-primary">&#8594; Admin Dashboard</a>
            <a href="index.php" class="btn btn-green">&#8594; Go to Home</a>
        </div>

        <div class="note">
            &#9888; <strong>You can now delete migrate.php</strong> — it is no longer needed.
        </div>
    </div>
</div>
</body>
</html>
