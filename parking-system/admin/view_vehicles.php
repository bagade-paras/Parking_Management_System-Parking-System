<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

$result = mysqli_query($conn,
"SELECT v.*, u.name AS owner_name
 FROM vehicles v
 JOIN users u ON v.user_id = u.id
 ORDER BY v.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vehicles - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'vehicles'; include "../includes/admin_sidebar.php"; ?>

<div class="main-content">
    <div class="topbar">
        <h1>🚗 All Vehicles</h1>
        <div class="topbar-right"><?= mysqli_num_rows($result) ?> registered vehicles</div>
    </div>

    <div class="card">
        <div class="card-header"><h2>Vehicle Registry</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Owner</th><th>Vehicle Number</th><th>Type</th></tr></thead>
                <tbody>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;padding:24px;">No vehicles registered.</td></tr>
                <?php else: ?>
                    <?php $i=1; while ($v = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>👤 <?= htmlspecialchars($v['owner_name']) ?></td>
                        <td><strong><?= htmlspecialchars($v['vehicle_number']) ?></strong></td>
                        <td>
                            <?php
                            $t = $v['type'] ?? '';
                            $icons = [
                                'Car'          => '🚗',
                                'Bike'         => '🏍️',
                                'SUV'          => '🚙',
                                'Sedan'        => '🚗',
                                'Hatchback'    => '🚗',
                                'Electric Car' => '⚡',
                                'Electric Bike'=> '⚡',
                                'Scooter'      => '🛵',
                                'Truck'        => '🚛',
                                'Bus'          => '🚌',
                                'Van'          => '🚐',
                                'Auto Rickshaw'=> '🛺',
                                'Bicycle'      => '🚲',
                                'Other'        => '🚗',
                            ];
                            echo ($icons[$t] ?? '🚗') . ' ' . htmlspecialchars($t);
                            ?>
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
</body>
</html>
