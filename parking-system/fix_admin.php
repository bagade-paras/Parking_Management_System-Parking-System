<?php
require_once "includes/db_connect.php";

// Block if admin table exists and has a valid account
$admin_check = mysqli_query($conn, "SELECT id FROM admin LIMIT 1");
if ($admin_check && mysqli_num_rows($admin_check) > 0) {
?>
<!DOCTYPE html>
<html>
<head>
<title>Fix Admin - Blocked</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',Arial,sans-serif; background:#f0f2f8; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
    .box { background:#fff; border-radius:14px; box-shadow:0 8px 32px rgba(26,35,126,.12); width:100%; max-width:480px; overflow:hidden; }
    .box-header { padding:26px 30px; background:linear-gradient(135deg,#e65100,#f9a825); color:#fff; }
    .box-header h2 { font-size:20px; margin-bottom:4px; }
    .box-header p  { opacity:.85; font-size:13px; }
    .box-body { padding:26px 30px; }
    .info { background:#fff8e1; border-left:4px solid #f9a825; border-radius:8px; padding:14px 16px; font-size:14px; color:#e65100; margin-bottom:20px; line-height:1.65; }
    .actions { display:flex; gap:10px; }
    .btn { display:inline-flex; align-items:center; justify-content:center; padding:10px 20px; border-radius:8px; font-size:14px; font-weight:600; text-decoration:none; flex:1; }
    .btn-primary { background:#1a237e; color:#fff; }
    .btn-green   { background:#2e7d32; color:#fff; }
</style>
</head>
<body>
<div class="box">
    <div class="box-header">
        <h2>&#128683; fix_admin.php Blocked</h2>
        <p>Admin account already exists &mdash; no action needed.</p>
    </div>
    <div class="box-body">
        <div class="info">
            &#9989; An admin account already exists in the database.<br><br>
            Running <strong>fix_admin.php</strong> is blocked to prevent unauthorized password resets. If you genuinely forgot your password, reset it directly from <strong>phpMyAdmin</strong>.
        </div>
        <div class="actions">
            <a href="login.php" class="btn btn-primary">&#8594; Go to Login</a>
            <a href="index.php" class="btn btn-green">&#8594; Go to Home</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

$msgs = [];

// Step 1: Ensure 'name' column exists in admin table
$col_name = mysqli_query($conn, "SHOW COLUMNS FROM admin LIKE 'name'");
if (mysqli_num_rows($col_name) == 0) {
    mysqli_query($conn, "ALTER TABLE admin ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT 'Administrator'");
    $msgs[] = "Added 'name' column to admin table.";
}

// Step 2: Ensure 'email' column exists in admin table
$col_email = mysqli_query($conn, "SHOW COLUMNS FROM admin LIKE 'email'");
if (mysqli_num_rows($col_email) == 0) {
    mysqli_query($conn, "ALTER TABLE admin ADD COLUMN email VARCHAR(100) NOT NULL DEFAULT ''");
    $msgs[] = "Added 'email' column to admin table.";
}

// Step 3: Hash the password correctly
$hashed = password_hash("admin123", PASSWORD_DEFAULT);

// Step 4: Check if any admin exists
$check = mysqli_query($conn, "SELECT COUNT(*) AS c FROM admin");
$count = mysqli_fetch_assoc($check)['c'];

if ($count > 0) {
    // Update first admin record
    $r = mysqli_query($conn, "UPDATE admin SET name='Administrator', email='admin@parking.com', password='$hashed' LIMIT 1");
    $msgs[] = $r ? "✅ Admin password reset to <strong>admin123</strong>" : "❌ Update failed: " . mysqli_error($conn);
} else {
    // Insert new admin
    $r = mysqli_query($conn, "INSERT INTO admin (name, email, password) VALUES ('Administrator','admin@parking.com','$hashed')");
    $msgs[] = $r ? "✅ Admin account created." : "❌ Insert failed: " . mysqli_error($conn);
}

// Show all admin rows
$admins = mysqli_query($conn, "SELECT * FROM admin");
$cols   = mysqli_fetch_fields($admins);
$admins = mysqli_query($conn, "SELECT * FROM admin"); // re-run after fetch_fields
?>
<!DOCTYPE html>
<html>
<head>
<title>Fix Admin</title>
<style>
body{font-family:Arial,sans-serif;background:#f0f2f8;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;}
.box{background:#fff;padding:32px 36px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.12);max-width:520px;width:100%;}
h2{color:#1a237e;margin-bottom:16px;}
.msg{padding:10px 14px;border-radius:8px;margin-bottom:8px;font-size:14px;}
.ok{background:#e8f5e9;color:#2e7d32;}
.info{background:#e3f2fd;color:#1565c0;}
.cred{background:#fff8e1;border:2px solid #ffd600;border-radius:8px;padding:16px;margin:20px 0;}
.cred p{margin:5px 0;font-size:15px;}
table{width:100%;border-collapse:collapse;margin-bottom:20px;font-size:13px;}
th{background:#1a237e;color:#fff;padding:8px 10px;text-align:left;}
td{padding:8px 10px;border-bottom:1px solid #eee;word-break:break-all;}
.btn{display:inline-block;background:#1a237e;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;}
.warn{background:#fff3e0;color:#e65100;padding:10px 14px;border-radius:8px;font-size:13px;margin-top:16px;}
</style>
</head>
<body>
<div class="box">
    <h2>🔧 Admin Account Fix</h2>

    <?php foreach ($msgs as $m): ?>
        <div class="msg ok"><?= $m ?></div>
    <?php endforeach; ?>

    <div class="cred">
        <p><strong>Login with these credentials:</strong></p>
        <p>📧 <strong>Email:</strong> admin@parking.com</p>
        <p>🔑 <strong>Password:</strong> admin123</p>
        <p>🛡️ <strong>Role:</strong> Select "Admin" on login page</p>
    </div>

    <p><strong>Admin table rows:</strong></p>
    <table>
        <tr>
            <?php foreach ($cols as $c): ?><th><?= $c->name ?></th><?php endforeach; ?>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($admins)): ?>
        <tr>
            <?php foreach ($row as $k => $v): ?>
                <td><?= $k === 'password' ? substr($v,0,20).'...' : htmlspecialchars($v ?? '') ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endwhile; ?>
    </table>

    <a href="login.php" class="btn">→ Go to Login</a>

    <div class="warn">⚠️ Delete <code>fix_admin.php</code> after logging in successfully.</div>
</div>
</body>
</html>
