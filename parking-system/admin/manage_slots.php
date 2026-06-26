<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

$message = $error = '';

if (isset($_POST['add_slot'])) {
    $loc_id      = (int)$_POST['location_id'];
    $slot_number = trim($_POST['slot_number']);
    $slot_type   = in_array($_POST['slot_type'], ['car','bike','heavy','any']) ? $_POST['slot_type'] : 'any';
    if (empty($slot_number)) {
        $error = "Slot number is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO slots (location_id, slot_number, slot_type, status) VALUES (?,?,?,'available')");
        $stmt->bind_param("iss", $loc_id, $slot_number, $slot_type);
        $stmt->execute() ? $message = "Slot added successfully!" : $error = "Error: " . $conn->error;
    }
}

if (isset($_POST['bulk_add'])) {
    $loc_id    = (int)$_POST['bulk_location_id'];
    $prefix    = trim($_POST['bulk_prefix']);
    $start     = (int)$_POST['bulk_start'];
    $count     = min((int)$_POST['bulk_count'], 100);
    $slot_type = in_array($_POST['bulk_slot_type'], ['car','bike','heavy','any']) ? $_POST['bulk_slot_type'] : 'any';
    if ($loc_id < 1 || $count < 1) {
        $error = "Please fill all bulk fields correctly.";
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO slots (location_id, slot_number, slot_type, status) VALUES (?,?,?,'available')");
        $added = 0;
        for ($i = $start; $i < $start + $count; $i++) {
            $slot_num = $prefix . $i;
            $stmt->bind_param("iss", $loc_id, $slot_num, $slot_type);
            if ($stmt->execute()) $added++;
        }
        $message = "$added slot(s) added successfully!";
    }
}

if (isset($_POST['bulk_remove'])) {
    $loc_id      = (int)$_POST['remove_location_id'];
    $remove_ids  = array_map('intval', $_POST['remove_ids'] ?? []);
    if ($loc_id < 1 || empty($remove_ids)) {
        $error = "Please select a location and at least one slot to remove.";
    } else {
        $placeholders = implode(',', $remove_ids);
        $res     = $conn->query("DELETE FROM slots WHERE location_id=$loc_id AND id IN ($placeholders)");
        $removed = $conn->affected_rows;
        $removed > 0 ? $message = "$removed slot(s) removed successfully!" : $error = "No matching slots found.";
    }
}

if (isset($_GET['toggle_status'])) {
    $tid = (int)$_GET['toggle_status'];
    $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM slots WHERE id=$tid"))['status'] ?? '';
    if ($cur === 'booked') {
        header("Location: manage_slots.php?msg=Cannot+change+status+of+a+booked+slot."); exit;
    }
    $new = ($cur === 'available') ? 'maintenance' : 'available';
    mysqli_query($conn, "UPDATE slots SET status='$new' WHERE id=$tid");
    header("Location: manage_slots.php?msg=Slot+status+updated."); exit;
}
if (isset($_GET['msg'])) $message = $_GET['msg'];

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM slots WHERE id=$id");
    header("Location: manage_slots.php?deleted=1"); exit;
}
if (isset($_GET['deleted'])) $message = "Slot deleted.";

$locations = mysqli_query($conn, (is_sub() && my_location())
    ? "SELECT * FROM locations WHERE id=" . my_location()
    : "SELECT * FROM locations ORDER BY location_name");
$locs_arr = [];
while ($l = mysqli_fetch_assoc($locations)) $locs_arr[] = $l;

$loc_where = (is_sub() && my_location()) ? " AND s.location_id=" . my_location() : "";
$slots = mysqli_query($conn,
"SELECT s.*, l.location_name FROM slots s
 JOIN locations l ON s.location_id = l.id
 WHERE 1 $loc_where
 ORDER BY l.location_name, s.slot_number");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Slots - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'slots'; include "../includes/admin_sidebar.php"; ?>

<div class="main-content">
    <div class="topbar"><h1>🅿️ Manage Slots</h1></div>

    <!-- Bulk Action Buttons -->
    <div style="display:flex;gap:12px;margin-bottom:20px;">
        <button class="btn btn-accent" onclick="toggleBulk('add')" id="bulk-add-btn">⚡ Bulk Add Slots</button>
        <button class="btn btn-danger" onclick="toggleBulk('remove')" id="bulk-remove-btn">🗑️ Bulk Remove Slots</button>
    </div>

    <!-- Bulk Add Card -->
    <div id="bulk-add-card" class="card bulk-card" style="display:none;margin-bottom:24px;">
        <div class="card-header">
            <h2>⚡ Bulk Add Slots</h2>
            <button class="btn btn-sm btn-outline" onclick="toggleBulk('add')">✕ Close</button>
        </div>
        <?php if (isset($_POST['bulk_add'])): ?>
            <?php if ($message): ?><div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php endif; ?>
        <form method="POST">
            <div class="bulk-grid">
                <div class="form-group">
                    <label>Location</label>
                    <select name="bulk_location_id" class="form-control" required>
                        <option value="">— Select Location —</option>
                        <?php foreach ($locs_arr as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['location_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Slot Prefix <span class="hint">(e.g. A, B, P-)</span></label>
                    <input type="text" name="bulk_prefix" class="form-control" placeholder="e.g. A" maxlength="10">
                </div>
                <div class="form-group">
                    <label>Start Number</label>
                    <input type="number" name="bulk_start" class="form-control" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label>Number of Slots <span class="hint">(max 100)</span></label>
                    <input type="number" name="bulk_count" class="form-control" value="10" min="1" max="100" required>
                </div>
                <div class="form-group">
                    <label>Slot Type</label>
                    <select name="bulk_slot_type" class="form-control" required>
                        <option value="any">&#9899; Any Vehicle</option>
                        <option value="car">&#128663; Car / SUV / Sedan</option>
                        <option value="bike">&#127949; Bike / Scooter</option>
                        <option value="heavy">&#128667; Heavy (Truck / Bus / Van)</option>
                    </select>
                </div>
            </div>
            <div class="bulk-preview" id="bulk-preview"></div>
            <button type="submit" name="bulk_add" class="btn btn-primary">➕ Add Bulk Slots</button>
        </form>
    </div>

    <!-- Bulk Remove Card -->
    <div id="bulk-remove-card" class="card bulk-remove-card" style="display:none;margin-bottom:24px;">
        <div class="card-header">
            <h2>🗑️ Bulk Remove Slots</h2>
            <button class="btn btn-sm btn-outline" onclick="toggleBulk('remove')">✕ Close</button>
        </div>
        <?php if (isset($_POST['bulk_remove'])): ?>
            <?php if ($message): ?><div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php endif; ?>
        <form method="POST" onsubmit="return confirmBulkRemove(this)">
            <div class="bulk-grid" style="grid-template-columns:2fr 1fr 1fr;">
                <div class="form-group">
                    <label>Location</label>
                    <select name="remove_location_id" id="remove_loc" class="form-control" required onchange="loadRemoveSlots()">
                        <option value="">— Select Location —</option>
                        <?php foreach ($locs_arr as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['location_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Remove Type</label>
                    <select name="remove_type" id="remove_type" class="form-control" onchange="togglePrefixField()">
                        <option value="all">All Slots</option>
                        <option value="available">Available Only</option>
                        <option value="prefix">By Prefix</option>
                    </select>
                </div>
                <div class="form-group" id="prefix-field" style="display:none;">
                    <label>Prefix to Remove</label>
                    <input type="text" name="remove_prefix" id="remove_prefix" class="form-control" placeholder="e.g. A" maxlength="10" oninput="loadRemoveSlots()">
                </div>
            </div>
            <!-- Slot checkboxes -->
            <div id="remove-slots-wrap" class="remove-slots-wrap" style="display:none;">
                <div class="remove-slots-header">
                    <span class="preview-label" style="margin:0;">Slots to be removed:</span>
                    <label class="select-all-label"><input type="checkbox" id="select-all" onchange="toggleAll(this)"> Select All</label>
                </div>
                <div id="remove-slots-list" class="remove-slots-list"></div>
            </div>
            <button type="submit" name="bulk_remove" class="btn btn-danger" style="margin-top:8px;">🗑️ Remove Selected Slots</button>
        </form>
    </div>

    <div class="manage-slots-layout">

        <div class="card">
            <div class="card-header"><h2>Add Slot</h2></div>
            <?php if (!isset($_POST['bulk_add']) && !isset($_POST['bulk_remove']) && $message): ?><div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if (!isset($_POST['bulk_add']) && !isset($_POST['bulk_remove']) && $error):   ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Location</label>
                    <select name="location_id" class="form-control" required>
                        <option value="">— Select Location —</option>
                        <?php foreach ($locs_arr as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['location_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Slot Number</label>
                    <input type="text" name="slot_number" class="form-control" placeholder="e.g. A1, B2, P-01" required>
                </div>
                <div class="form-group">
                    <label>Slot Type</label>
                    <select name="slot_type" class="form-control" required>
                        <option value="any">&#9899; Any Vehicle</option>
                        <option value="car">&#128663; Car / SUV / Sedan</option>
                        <option value="bike">&#127949; Bike / Scooter</option>
                        <option value="heavy">&#128667; Heavy (Truck / Bus / Van)</option>
                    </select>
                </div>
                <button type="submit" name="add_slot" class="btn btn-primary btn-block">Add Slot</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>All Slots</h2>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <span class="admin-slot-legend"><span class="asl-dot asl-available"></span>Available</span>
                    <span class="admin-slot-legend"><span class="asl-dot asl-booked"></span>Booked</span>
                    <span class="admin-slot-legend"><span class="asl-dot asl-maintenance"></span>Maintenance</span>
                </div>
            </div>
            <?php
            // Group slots by location
            $by_loc = [];
            mysqli_data_seek($slots, 0);
            while ($s = mysqli_fetch_assoc($slots)) {
                $lid = $s['location_id'];
                $by_loc[$lid]['name'] = $s['location_name'];
                $by_loc[$lid]['slots'][] = $s;
            }
            $type_icons  = ['car'=>'🚗','bike'=>'🏍️','heavy'=>'🚛','any'=>'🅿️'];
            $type_labels = ['car'=>'Car','bike'=>'Bike','heavy'=>'Heavy','any'=>'Any'];
            if (empty($by_loc)): ?>
                <div class="alert alert-info">No slots added yet.</div>
            <?php else: foreach ($by_loc as $lid => $loc_data): ?>
            <div class="admin-loc-block">
                <div class="admin-loc-header">
                    <span>📍 <?= htmlspecialchars($loc_data['name']) ?></span>
                    <span class="admin-loc-count"><?= count($loc_data['slots']) ?> slot<?= count($loc_data['slots'])!==1?'s':'' ?></span>
                </div>
                <?php
                // Group by type within location
                $by_type = [];
                foreach ($loc_data['slots'] as $s) {
                    $by_type[$s['slot_type'] ?? 'any'][] = $s;
                }
                $type_order = ['car','bike','heavy','any'];
                foreach ($type_order as $stype):
                    if (empty($by_type[$stype])) continue;
                ?>
                <div class="admin-type-row">
                    <span class="admin-type-label"><?= $type_icons[$stype] ?? '🅿️' ?> <?= $type_labels[$stype] ?? $stype ?></span>
                    <div class="admin-slot-grid">
                    <?php foreach ($by_type[$stype] as $s):
                        $st = strtolower($s['status']);
                    ?>
                        <div class="admin-slot-box admin-slot-<?= $st ?>" title="<?= htmlspecialchars($s['slot_number']) ?> — <?= ucfirst($st) ?>">
                            <span class="asb-num"><?= htmlspecialchars($s['slot_number']) ?></span>
                            <div class="asb-actions">
                                <?php if ($st !== 'booked'): ?>
                                <a href="?toggle_status=<?= $s['id'] ?>" class="asb-btn asb-toggle"
                                   title="<?= $st==='available'?'Set Maintenance':'Set Available' ?>">
                                   <?= $st==='available'?'🔧':'✅' ?>
                                </a>
                                <?php endif; ?>
                                <a href="?delete=<?= $s['id'] ?>" class="asb-btn asb-del"
                                   onclick="return confirm('Delete slot <?= htmlspecialchars($s['slot_number']) ?>?')" title="Delete">🗑️</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
</div>
<script>
// Slot data from PHP
const allSlots = <?php
    $slot_map = [];
    $res = mysqli_query($conn, "SELECT id, location_id, slot_number, slot_type, status FROM slots ORDER BY slot_number");
    while ($r = mysqli_fetch_assoc($res)) $slot_map[] = $r;
    echo json_encode($slot_map);
?>;

function toggleBulk(type) {
    const addCard = document.getElementById('bulk-add-card');
    const remCard = document.getElementById('bulk-remove-card');
    const addBtn  = document.getElementById('bulk-add-btn');
    const remBtn  = document.getElementById('bulk-remove-btn');

    if (type === 'add') {
        const show = addCard.style.display === 'none';
        addCard.style.display = show ? 'block' : 'none';
        remCard.style.display = 'none';
        addBtn.textContent = show ? '✕ Close Bulk Add' : '⚡ Bulk Add Slots';
        remBtn.textContent = '🗑️ Bulk Remove Slots';
        if (show) addCard.scrollIntoView({behavior:'smooth', block:'start'});
    } else {
        const show = remCard.style.display === 'none';
        remCard.style.display = show ? 'block' : 'none';
        addCard.style.display = 'none';
        remBtn.textContent = show ? '✕ Close Bulk Remove' : '🗑️ Bulk Remove Slots';
        addBtn.textContent = '⚡ Bulk Add Slots';
        if (show) remCard.scrollIntoView({behavior:'smooth', block:'start'});
    }
}

// Bulk Add preview
(function(){
    const prefix = document.querySelector('[name=bulk_prefix]');
    const start  = document.querySelector('[name=bulk_start]');
    const count  = document.querySelector('[name=bulk_count]');
    const prev   = document.getElementById('bulk-preview');
    function update() {
        const p = prefix.value, s = parseInt(start.value)||1, c = Math.min(parseInt(count.value)||0,100);
        if (!c) { prev.innerHTML=''; return; }
        let html = '<div class="preview-label">Preview:</div><div class="preview-slots">';
        const show = Math.min(c, 12);
        for (let i=s; i<s+show; i++) html += `<span class="preview-chip">${p}${i}</span>`;
        if (c > show) html += `<span class="preview-chip preview-more">+${c-show} more</span>`;
        html += '</div>';
        prev.innerHTML = html;
    }
    [prefix, start, count].forEach(el => el && el.addEventListener('input', update));
    update();
})();

// Bulk Remove: show/hide prefix field
function togglePrefixField() {
    const type = document.getElementById('remove_type').value;
    document.getElementById('prefix-field').style.display = type === 'prefix' ? 'block' : 'none';
    loadRemoveSlots();
}

// Load slot checkboxes based on location + type
function loadRemoveSlots() {
    const locId  = parseInt(document.getElementById('remove_loc').value);
    const type   = document.getElementById('remove_type').value;
    const pfx    = (document.getElementById('remove_prefix')?.value || '').toLowerCase();
    const wrap   = document.getElementById('remove-slots-wrap');
    const list   = document.getElementById('remove-slots-list');

    if (!locId) { wrap.style.display='none'; list.innerHTML=''; return; }

    let filtered = allSlots.filter(s => s.location_id == locId);
    if (type === 'available') filtered = filtered.filter(s => s.status === 'available');
    if (type === 'prefix' && pfx) filtered = filtered.filter(s => s.slot_number.toLowerCase().startsWith(pfx));

    if (!filtered.length) {
        wrap.style.display = 'block';
        list.innerHTML = '<span style="color:#999;font-size:13px;">No matching slots found.</span>';
        return;
    }

    list.innerHTML = filtered.map(s =>
        `<label class="remove-chip remove-chip-${s.status}">
            <input type="checkbox" name="remove_ids[]" value="${s.id}" checked>
            ${s.slot_number}
            <span class="chip-status">${s.status}</span>
        </label>`
    ).join('');
    wrap.style.display = 'block';
    document.getElementById('select-all').checked = true;
}

function toggleAll(cb) {
    document.querySelectorAll('[name="remove_ids[]"]').forEach(c => c.checked = cb.checked);
}

function confirmBulkRemove(form) {
    const checked = form.querySelectorAll('[name="remove_ids[]"]:checked').length;
    if (!checked) { alert('No slots selected.'); return false; }
    return confirm(`Are you sure you want to permanently delete ${checked} slot(s)? This cannot be undone.`);
}

// Re-open cards after POST
<?php if (isset($_POST['bulk_add'])): ?>
document.getElementById('bulk-add-card').style.display = 'block';
document.getElementById('bulk-add-btn').textContent = '✕ Close Bulk Add';
<?php endif; ?>
<?php if (isset($_POST['bulk_remove'])): ?>
document.getElementById('bulk-remove-card').style.display = 'block';
document.getElementById('bulk-remove-btn').textContent = '✕ Close Bulk Remove';
<?php endif; ?>
</script>
</body>
</html>
