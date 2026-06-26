<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

$message = $error = '';

if (isset($_POST['add_location'])) {
    $name = trim($_POST['location_name']);
    $rate = max(1, (int)$_POST['rate_per_hour']);
    if (empty($name)) {
        $error = "Location name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO locations (location_name, rate_per_hour) VALUES (?,?)");
        $stmt->bind_param("si", $name, $rate);
        $stmt->execute() ? $message = "Location added successfully!" : $error = "Error: " . $conn->error;
    }
}

if (isset($_POST['edit_location'])) {
    $id   = (int)$_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    $rate = max(1, (int)$_POST['edit_rate']);
    if (empty($name)) {
        $error = "Location name cannot be empty.";
    } else {
        $stmt = $conn->prepare("UPDATE locations SET location_name=?, rate_per_hour=? WHERE id=?");
        $stmt->bind_param("sii", $name, $rate, $id);
        $stmt->execute() ? $message = "Location updated successfully!" : $error = "Error: " . $conn->error;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM locations WHERE id=$id");
    header("Location: manage_locations.php?deleted=1"); exit;
}
if (isset($_GET['deleted'])) $message = "Location deleted.";

$result  = mysqli_query($conn, "SELECT l.*, COUNT(s.id) AS slot_count FROM locations l LEFT JOIN slots s ON s.location_id=l.id GROUP BY l.id ORDER BY l.location_name");
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Locations - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'locations'; include "../includes/admin_sidebar.php"; ?>

<div class="main-content">
    <div class="topbar"><h1>&#128205; Manage Locations</h1></div>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;align-items:start;">

        <!-- Add Location -->
        <div class="card">
            <div class="card-header"><h2>Add Location</h2></div>
            <?php if ($message): ?><div class="alert alert-success">&#9989; <?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger">&#9888; <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Location Name</label>
                    <input type="text" name="location_name" class="form-control" placeholder="e.g. PVR Mall Parking" required>
                </div>
                <div class="form-group">
                    <label>Price / Hour <span style="color:#c62828;font-size:12px;">*</span></label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#666;font-weight:700;">&#8377;</span>
                        <input type="number" name="rate_per_hour" class="form-control"
                               placeholder="e.g. 30" min="1" required
                               style="padding-left:28px;">
                    </div>
                    <small style="color:#888;font-size:12px;margin-top:4px;display:block;">
                        Default rate charged per hour for all slots in this location
                    </small>
                </div>
                <button type="submit" name="add_location" class="btn btn-primary btn-block">Add Location</button>
            </form>
        </div>

        <!-- All Locations -->
        <div class="card">
            <div class="card-header"><h2>All Locations</h2></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Location Name</th>
                            <th>Price/Hr</th>
                            <th>Slots</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($result) === 0): ?>
                        <tr><td colspan="5" style="text-align:center;color:#999;padding:24px;">No locations added yet.</td></tr>
                    <?php else: ?>
                        <?php $i=1; while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <?php if ($edit_id === (int)$row['id']): ?>
                                <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                    <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                    <input type="text" name="edit_name" class="form-control"
                                           value="<?= htmlspecialchars($row['location_name']) ?>"
                                           required style="margin:0;min-width:140px;flex:1;">
                                    <div style="position:relative;width:90px;">
                                        <span style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#666;font-size:12px;font-weight:700;">&#8377;</span>
                                        <input type="number" name="edit_rate" class="form-control"
                                               value="<?= $row['rate_per_hour'] ?>"
                                               min="1" required
                                               style="padding-left:22px;margin:0;">
                                    </div>
                                    <button type="submit" name="edit_location" class="btn btn-success btn-sm" title="Save">&#128190;</button>
                                    <a href="manage_locations.php" class="btn btn-outline btn-sm">&#10005;</a>
                                </form>
                                <?php else: ?>
                                &#128205; <?= htmlspecialchars($row['location_name']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($edit_id !== (int)$row['id']): ?>
                                <span class="price-badge">&#8377;<?= $row['rate_per_hour'] ?>/hr</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-info"><?= $row['slot_count'] ?> slots</span></td>
                            <td style="display:flex;gap:6px;">
                                <a href="?edit=<?= $row['id'] ?>" class="btn btn-accent btn-sm" title="Edit name &amp; price">&#9999;</a>
                                <a href="?delete=<?= $row['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this location? All associated slots will be removed.')">
                                   &#128465;
                                </a>
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
</div>
</body>
</html>
