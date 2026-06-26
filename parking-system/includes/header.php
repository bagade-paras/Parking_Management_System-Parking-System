<?php if (!isset($session_started)) { session_start(); $session_started = true; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Smart Parking</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<script>(function(){if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark');})();</script>
</head>
<body>
<nav class="pub-nav">
    <a href="/index.php" class="brand">🚗 Smart<span>Parking</span></a>
    <div class="nav-links">
        <a href="/index.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/user/user_dashboard.php">Dashboard</a>
            <a href="/logout.php" class="btn-nav">Logout</a>
        <?php elseif (isset($_SESSION['admin_id'])): ?>
            <a href="/admin/admin_dashboard.php">Dashboard</a>
            <a href="/logout.php" class="btn-nav">Logout</a>
        <?php else: ?>
            <a href="/login.php">Login</a>
            <a href="/register.php" class="btn-nav">Get Started</a>
        <?php endif; ?>
        <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark</button>
    </div>
    <div class="nav-right-mobile">
        <button class="dark-toggle" onclick="toggleDark()" id="darkBtnMobile">🌙</button>
        <button class="nav-hamburger" onclick="toggleMenu()" id="hamburger">&#9776;</button>
    </div>
</nav>
<div class="nav-mobile" id="navMobile">
    <a href="/index.php">Home</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/user/user_dashboard.php">Dashboard</a>
        <a href="/logout.php" class="nav-mobile-cta">Logout</a>
    <?php elseif (isset($_SESSION['admin_id'])): ?>
        <a href="/admin/admin_dashboard.php">Dashboard</a>
        <a href="/logout.php" class="nav-mobile-cta">Logout</a>
    <?php else: ?>
        <a href="/login.php">Login</a>
        <a href="/register.php" class="nav-mobile-cta">Get Started</a>
    <?php endif; ?>
</div>
<script>
function toggleDark() {
    const isDark = document.body.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    const label = isDark ? '☀️ Light' : '🌙 Dark';
    const btn = document.getElementById('darkBtn');
    const btnM = document.getElementById('darkBtnMobile');
    if (btn) btn.textContent = label;
    if (btnM) btnM.textContent = isDark ? '☀️' : '🌙';
}
function toggleMenu() {
    document.getElementById('navMobile').classList.toggle('open');
}
(function(){
    if (localStorage.getItem('theme') === 'dark') {
        const btn = document.getElementById('darkBtn');
        const btnM = document.getElementById('darkBtnMobile');
        if (btn) btn.textContent = '☀️ Light';
        if (btnM) btnM.textContent = '☀️';
    }
})();
</script>
<div style="max-width:1100px;margin:32px auto;padding:0 24px;">
