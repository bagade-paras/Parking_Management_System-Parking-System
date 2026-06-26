<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "parking_system";

$conn = mysqli_connect($host, $user, $pass);

if (!$conn) {
    die("
    <!DOCTYPE html><html><head><title>Database Error</title>
    <style>
        body{font-family:'Segoe UI',Arial,sans-serif;background:#f0f2f8;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;}
        .box{background:#fff;padding:36px 40px;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.12);max-width:500px;width:100%;text-align:center;}
        h2{color:#c62828;margin-bottom:12px;font-size:22px;}
        p{color:#555;line-height:1.7;margin-bottom:10px;font-size:14px;}
        code{background:#f5f5f5;padding:2px 8px;border-radius:4px;font-size:13px;}
        .btn{display:inline-block;margin-top:16px;background:#1a237e;color:#fff;padding:11px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;}
    </style>
    </head><body><div class='box'>
        <h2>&#9888;&#65039; Cannot Connect to MySQL</h2>
        <p>Make sure <strong>XAMPP MySQL</strong> is running in the XAMPP Control Panel.</p>
        <p style='color:#888;font-size:13px;'>Error: " . mysqli_connect_error() . "</p>
        <a class='btn' href='http://localhost/phpmyadmin' target='_blank'>Open phpMyAdmin</a>
    </div></body></html>
    ");
}

// Select or auto-create the database
if (!mysqli_select_db($conn, $db)) {
    // Database doesn't exist — create it automatically
    if (mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        mysqli_select_db($conn, $db);
    } else {
        die("
        <!DOCTYPE html><html><head><title>Database Error</title>
        <style>
            body{font-family:'Segoe UI',Arial,sans-serif;background:#f0f2f8;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;}
            .box{background:#fff;padding:36px 40px;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.12);max-width:500px;width:100%;text-align:center;}
            h2{color:#c62828;margin-bottom:12px;}
            p{color:#555;line-height:1.7;font-size:14px;}
            code{background:#f5f5f5;padding:2px 8px;border-radius:4px;}
            .btn{display:inline-block;margin-top:16px;background:#1a237e;color:#fff;padding:11px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;}
        </style>
        </head><body><div class='box'>
            <h2>&#9888;&#65039; Could Not Create Database</h2>
            <p>Please create a database named <code>parking_system</code> manually in phpMyAdmin, then refresh.</p>
            <a class='btn' href='http://localhost/phpmyadmin' target='_blank'>Open phpMyAdmin</a>
        </div></body></html>
        ");
    }
}

// Check if tables exist — if not, redirect to migrate.php to set them up
$tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if ($tables_check && mysqli_num_rows($tables_check) === 0) {
    $current = basename($_SERVER['PHP_SELF']);
    if ($current !== 'migrate.php' && $current !== 'db_connect.php') {
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '') . 'migrate.php');
        exit;
    }
}


// Auto-migrate: add slot_type to slots table
$_slot_type_col = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='slots' AND COLUMN_NAME='slot_type'"));
if (!$_slot_type_col) {
    mysqli_query($conn, "ALTER TABLE slots ADD COLUMN slot_type ENUM('car','bike','heavy','any') NOT NULL DEFAULT 'any' AFTER slot_number");
}
unset($_slot_type_col);

// Auto-migrate: add role + location_id to admin table for sub-admin support
$_adm_col = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='admin' AND COLUMN_NAME='role'"));
if (!$_adm_col) {
    mysqli_query($conn, "ALTER TABLE admin ADD COLUMN role ENUM('super','sub') NOT NULL DEFAULT 'super' AFTER name");
    mysqli_query($conn, "ALTER TABLE admin ADD COLUMN location_id INT NULL DEFAULT NULL AFTER role");
}
unset($_adm_col);

// Auto-migrate: safely add 'maintenance' to slots.status ENUM if not already present.
// Runs on every device on first load — harmless if already up to date.
$col = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='slots' AND COLUMN_NAME='status'"));
if ($col && strpos($col['COLUMN_TYPE'], 'maintenance') === false) {
    mysqli_query($conn, "ALTER TABLE slots MODIFY status ENUM('available','booked','maintenance') DEFAULT 'available'");
}

// Auto-migrate: add start_time to bookings if not present
$st_col = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='bookings' AND COLUMN_NAME='start_time'"));
if (!$st_col) {
    mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN start_time DATETIME NULL DEFAULT NULL AFTER hours");
    // Backfill existing rows: start_time = created_at
    mysqli_query($conn, "UPDATE bookings SET start_time = created_at WHERE start_time IS NULL");
}

// Auto-migrate: add booking_id and payment_type to payments table
$_pay_col = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='payments' AND COLUMN_NAME='booking_id'"));
if (!$_pay_col) {
    mysqli_query($conn, "ALTER TABLE payments ADD COLUMN booking_id INT NULL DEFAULT NULL AFTER user_id");
    mysqli_query($conn, "ALTER TABLE payments ADD COLUMN payment_type ENUM('booking','extension') NOT NULL DEFAULT 'booking' AFTER booking_id");
    // Link existing booking payments
    mysqli_query($conn, "UPDATE payments p JOIN bookings b ON b.payment_id = p.id SET p.booking_id = b.id, p.payment_type = 'booking'");
}
unset($_pay_col);

// Auto-complete expired bookings and free their slots on every page load.
mysqli_query($conn,
    "UPDATE bookings SET status='completed'
     WHERE status='booked'
     AND DATE_ADD(COALESCE(start_time, created_at), INTERVAL hours HOUR) < NOW()");

// Mark slots as booked if they have any active OR upcoming booking
mysqli_query($conn,
    "UPDATE slots SET status='booked'
     WHERE status='available'
     AND id IN (
         SELECT slot_id FROM bookings
         WHERE status='booked'
     )");

// Free slots that have NO active or upcoming booking at all
mysqli_query($conn,
    "UPDATE slots SET status='available'
     WHERE status='booked'
     AND id NOT IN (
         SELECT slot_id FROM bookings
         WHERE status='booked'
     )");
?>
