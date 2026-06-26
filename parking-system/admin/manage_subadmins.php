<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";
require_super();

$message = $error = '';

// Create sub-admin
if (isset($_POST['create'])) {
    $name   = trim($_POST['name']);
    $email  = trim($_POST['email']);
    $pass   = $_POST['password'];
    $loc_id = (int)$_POST['location_id'];

    if (!$name || !$email || !$pass || !$loc_id) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($pass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin (name, role, location_id, email, password) VALUES (?, 'sub', ?, ?, ?)");
        $stmt->bind_param("siss", $name, $loc_id, $email, $hashed);
        if ($stmt->execute()) {
            $message = "Sub-admin '$name' created successfully!";
        } else {
            $error = "Email already exists or a database error occurred.";
        }
    }
}

// Delete sub-admin
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $chk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role FROM admin WHERE id=$did"));
    if ($chk && $chk['role'] === 'sub') {
        mysqli_query($conn, "DELETE FROM admin WHERE id=$did AND role='sub'");
        $message = "Sub-admin deleted successfully.";
    } else {
        $error = "Cannot delete a super admin account.";
    }
}

// Reset password
if (isset($_POST['reset_pass'])) {
    $rid = (int)$_POST['admin_id'];
    $np  = $_POST['new_password'];
    if (strlen($np) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($np, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE admin SET password='$hashed' WHERE id=$rid AND role='sub'");
        $message = "Password reset successfully.";
    }
}

$locations = mysqli_query($conn, "SELECT * FROM locations ORDER BY location_name");
$locs_arr  = [];
while ($l = mysqli_fetch_assoc($locations)) $locs_arr[] = $l;

$subadmins = mysqli_query($conn,
    "SELECT a.*, l.location_name FROM admin a
     LEFT JOIN locations l ON a.location_id = l.id
     WHERE a.role = 'sub'
     ORDER BY a.id DESC");
$sub_count = mysqli_num_rows($subadmins);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sub Admins - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'subadmins'; include "../includes/admin_sidebar.php"; ?>

<div class="main-content">
    <div class="topbar">
        <h1>&#128737; Manage Sub Admins</h1>
        <div class="topbar-right">
            <span class="badge badge-success">&#128737; Super Admin Only</span>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">&#9989; <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">&#9888; <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start;">

        <!-- Create Form -->
        <div class="card" style="margin-bottom:0;">
            <div class="card-header"><h2>&#10133; Create Sub Admin</h2></div>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Ravi Kumar" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="subadmin@email.com" required>
                </div>
                <div class="form-group">
                    <label>Password <span style="font-size:11px;color:#999;">(min 6 chars)</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Set a password" required>
                </div>
                <div class="form-group">
                    <label>Assign Location</label>
                    <?php if (empty($locs_arr)): ?>
                        <div class="alert alert-warning" style="margin:0;">No locations found. <a href="manage_locations.php">Add a location first.</a></div>
                    <?php else: ?>
                    <select name="location_id" class="form-control" required>
                        <option value="">— Select Location —</option>
                        <?php foreach ($locs_arr as $l): ?>
                        <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['location_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <button type="submit" name="create" class="btn btn-primary btn-block">&#128737; Create Sub Admin</button>
            </form>

            <div class="alert alert-info" style="margin-top:16px;margin-bottom:0;font-size:13px;">
                &#8505;&#65039; Sub-admins log in from the same login page using the <strong>Admin</strong> role. They only see their assigned location's data.
            </div>
        </div>

        <!-- Sub Admins List -->
        <div class="card" style="margin-bottom:0;">
            <div class="card-header">
                <h2>&#128101; Sub Admins</h2>
                <span class="badge badge-info"><?= $sub_count ?> total</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Assigned Location</th><th>Created</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($sub_count === 0): ?>
                        <tr><td colspan="5" style="text-align:center;color:#999;padding:32px;">
                            No sub-admins yet. Create one using the form.
                        </td></tr>
                    <?php else: while ($sa = mysqli_fetch_assoc($subadmins)): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($sa['name']) ?></strong></td>
                        <td style="font-size:13px;color:var(--text-muted);"><?= htmlspecialchars($sa['email']) ?></td>
                        <td>
                            <?php if ($sa['location_name']): ?>
                                <span class="badge badge-info">&#128205; <?= htmlspecialchars($sa['location_name']) ?></span>
                            <?php else: ?>
                                <span class="badge badge-warning">No location</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= date('d M Y', strtotime($sa['created_at'])) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button class="btn btn-outline btn-sm"
                                    onclick="openReset(<?= $sa['id'] ?>, '<?= htmlspecialchars($sa['name'], ENT_QUOTES) ?>')">
                                    &#128274; Reset
                                </button>
                                <a href="?delete=<?= $sa['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete sub-admin \'<?= htmlspecialchars($sa['name'], ENT_QUOTES) ?>\'?')">
                                   &#128465;
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:var(--card-bg);border-radius:14px;padding:28px;width:100%;max-width:360px;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <h3 style="margin-bottom:6px;color:var(--primary);">&#128274; Reset Password</h3>
        <p id="resetName" style="font-size:13px;color:var(--text-muted);margin-bottom:18px;"></p>
        <form method="POST">
            <input type="hidden" name="admin_id" id="resetAdminId">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" required>
            </div>
            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="submit" name="reset_pass" class="btn btn-primary" style="flex:1;">Save</button>
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeReset()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReset(id, name) {
    document.getElementById('resetAdminId').value = id;
    document.getElementById('resetName').textContent = 'For: ' + name;
    document.getElementById('resetModal').style.display = 'flex';
}
function closeReset() {
    document.getElementById('resetModal').style.display = 'none';
}
// Responsive grid
(function check() {
    const g = document.querySelector('.main-content > div[style*="grid"]');
    if (g) g.style.gridTemplateColumns = window.innerWidth < 960 ? '1fr' : '340px 1fr';
    window.addEventListener('resize', check);
})();
</script>
</body>
</html>
