<?php
$active_page = $active_page ?? '';
?>
<script>(function(){if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark');})();</script>

<!-- MOBILE TOP BAR (dashboard pages only, visible on small screens) -->
<div class="dash-topnav">
    <button class="dash-hamburger" onclick="toggleSidebar()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <div class="dash-topnav-brand">&#128663; Smart<span>Parking</span></div>
    <button class="dark-toggle dash-dark-btn" onclick="toggleDark()" id="darkBtnMobile">&#127769;</button>
</div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sb-brand">
        <span class="sb-brand-icon">&#128663;</span>
        Smart<span>Parking</span>
    </div>

    <!-- User info -->
    <div class="sb-user">
        <div class="sb-user-avatar">&#128100;</div>
        <div class="sb-user-info">
            <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
            <span>User Account</span>
        </div>
    </div>

    <!-- Nav links -->
    <nav>
        <a href="user_dashboard.php" class="<?= $active_page==='dashboard'?'active':'' ?>">
            <span class="icon">&#127968;</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="book_slot.php" class="<?= $active_page==='book'?'active':'' ?>">
            <span class="icon">&#127359;</span>
            <span class="nav-label">Book Slot</span>
        </a>
        <a href="booking_history.php" class="<?= $active_page==='history'?'active':'' ?>">
            <span class="icon">&#128203;</span>
            <span class="nav-label">Booking History</span>
        </a>
        <a href="add_vehicle.php" class="<?= $active_page==='vehicles'?'active':'' ?>">
            <span class="icon">&#128663;</span>
            <span class="nav-label">My Vehicles</span>
        </a>
        <a href="profile.php" class="<?= $active_page==='profile'?'active':'' ?>">
            <span class="icon">&#128100;</span>
            <span class="nav-label">Profile</span>
        </a>
        <a href="support.php" class="<?= $active_page==='support'?'active':'' ?>">
            <span class="icon">&#127911;</span>
            <span class="nav-label">Support</span>
        </a>
    </nav>

    <!-- Dark mode toggle -->
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
    const label = isDark ? '&#9728;&#65039; Light Mode' : '&#127769; Dark Mode';
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
