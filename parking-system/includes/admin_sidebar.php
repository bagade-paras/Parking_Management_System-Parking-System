<?php
$active_page = $active_page ?? '';
?>
<script>(function(){if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark');})();</script>

<!-- MOBILE TOP BAR -->
<div class="dash-topnav">
    <button class="dash-hamburger" onclick="toggleSidebar()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <div class="dash-topnav-brand">&#128663; Smart<span>Parking</span></div>
    <button class="dark-toggle dash-dark-btn" onclick="toggleDark()" id="darkBtnMobile">&#127769;</button>
</div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<style>
.admin-sidebar { overflow: hidden; }
.admin-sidebar .sb-brand { padding: 10px 18px; }
.admin-sidebar .sb-user { padding: 8px 18px; }
.admin-sidebar nav { overflow: visible !important; padding: 2px 0; }
.admin-sidebar nav a { padding: 6px 14px; font-size: 13px; margin: 0; }
.admin-sidebar .sb-nav-group-label { padding: 5px 18px 1px; font-size: 9px; }
.admin-sidebar .sb-dark-toggle { padding: 6px 18px; }
.admin-sidebar .sb-logout { padding: 6px 18px; }
.admin-sidebar .sb-logout a { padding: 6px 10px; }
</style>
<div class="sidebar admin-sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sb-brand">
        <span class="sb-brand-icon">&#128663;</span>
        Smart<span>Parking</span>
    </div>

    <!-- Admin user block -->
    <div class="sb-user">
        <div class="sb-user-avatar sb-user-avatar--admin">&#128737;</div>
        <div class="sb-user-info">
            <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong>
            <span><?= is_super() ? 'Super Admin' : 'Sub Admin' ?></span>
        </div>
    </div>

    <!-- Nav -->
    <nav>
        <div class="sb-nav-group-label">Overview</div>
        <a href="admin_dashboard.php" class="<?= $active_page==='dashboard'?'active':'' ?>">
            <span class="icon">&#128202;</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="revenue.php" class="<?= $active_page==='revenue'?'active':'' ?>">
            <span class="icon">&#128176;</span>
            <span class="nav-label">Revenue</span>
        </a>

        <div class="sb-nav-group-label">Management</div>
        <a href="manage_locations.php" class="<?= $active_page==='locations'?'active':'' ?>">
            <span class="icon">&#128205;</span>
            <span class="nav-label">Locations</span>
        </a>
        <a href="manage_slots.php" class="<?= $active_page==='slots'?'active':'' ?>">
            <span class="icon">&#127359;</span>
            <span class="nav-label">Manage Slots</span>
        </a>
        <a href="view_bookings.php" class="<?= $active_page==='bookings'?'active':'' ?>">
            <span class="icon">&#128203;</span>
            <span class="nav-label">Bookings</span>
        </a>
        <a href="view_payments.php" class="<?= $active_page==='payments'?'active':'' ?>">
            <span class="icon">&#128179;</span>
            <span class="nav-label">Payments</span>
        </a>

        <div class="sb-nav-group-label">Users</div>
        <a href="view_users.php" class="<?= $active_page==='users'?'active':'' ?>">
            <span class="icon">&#128101;</span>
            <span class="nav-label">All Users</span>
        </a>
        <a href="view_vehicles.php" class="<?= $active_page==='vehicles'?'active':'' ?>">
            <span class="icon">&#128663;</span>
            <span class="nav-label">Vehicles</span>
        </a>

        <div class="sb-nav-group-label">Tools</div>
        <a href="search.php" class="<?= $active_page==='search'?'active':'' ?>">
            <span class="icon">&#128269;</span>
            <span class="nav-label">Global Search</span>
        </a>
        <a href="manage_subadmins.php" class="<?= $active_page==='subadmins'?'active':'' ?>">
            <span class="icon">&#128737;</span>
            <span class="nav-label">Sub Admins</span>
        </a>
        <a href="view_messages.php" class="<?= $active_page==='messages'?'active':'' ?>" style="position:relative;">
            <span class="icon">&#128140;</span>
            <span class="nav-label">Messages</span>
            <?php
            $unread_msgs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM contact_messages WHERE status='unread'"))['t'] ?? 0;
            if ($unread_msgs > 0): ?>
                <span style="margin-left:auto;background:#c62828;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;"><?= $unread_msgs ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <!-- Dark toggle -->
    <div class="sb-dark-toggle">
        <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">&#127769; Dark Mode</button>
    </div>

    <!-- Logout -->
    <div class="sb-logout">
        <a href="../logout.php" onclick="return confirm('Logout?')">
            <span>&#128682;</span> Logout
        </a>
    </div>
</div>

<script>
function toggleDark() {
    const isDark = document.body.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    const d = document.getElementById('darkBtn');
    const m = document.getElementById('darkBtnMobile');
    if (d) d.innerHTML = isDark ? '&#9728;&#65039; Light Mode' : '&#127769; Dark Mode';
    if (m) m.innerHTML = isDark ? '&#9728;&#65039;' : '&#127769;';
}
(function(){
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        const d = document.getElementById('darkBtn');
        const m = document.getElementById('darkBtnMobile');
        if (d) d.innerHTML = '&#9728;&#65039; Light Mode';
        if (m) m.innerHTML = '&#9728;&#65039;';
    }
})();
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}
</script>
