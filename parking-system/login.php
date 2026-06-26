<?php
session_start();
require_once "includes/db_connect.php";

if (isset($_SESSION['admin_id'])) { header("Location: admin/admin_dashboard.php"); exit; }
if (isset($_SESSION['user_id']))  { header("Location: user/user_dashboard.php");  exit; }

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];
    $role  = $_POST['role'];

    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['admin_id']            = $admin['id'];
            $_SESSION['admin_name']          = $admin['name'];
            $_SESSION['admin_role']          = $admin['role'] ?? 'super';
            $_SESSION['admin_location_id']   = $admin['location_id'] ?? null;
            // Store location name for sub-admin badge
            if (!empty($admin['location_id'])) {
                $loc_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT location_name FROM locations WHERE id=" . (int)$admin['location_id']));
                $_SESSION['admin_location_name'] = $loc_row['location_name'] ?? '';
            }
            header("Location: admin/admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid admin credentials.";
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: user/user_dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Smart Parking</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<script>(function(){if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark');})();</script>

<nav class="pub-nav">
    <a href="index.php" class="brand">&#128663; Smart<span>Parking</span></a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="pages/about.php">About</a>
        <a href="pages/contact.php">Contact</a>
        <a href="login.php" class="nav-active">Login</a>
        <a href="register.php" class="btn-nav">Get Started</a>
        <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">&#127769; Dark</button>
    </div>
    <div class="nav-right-mobile">
        <button class="dark-toggle" onclick="toggleDark()" id="darkBtnMobile">&#127769;</button>
        <button class="nav-hamburger" onclick="toggleMenu()" id="hamburger">&#9776;</button>
    </div>
</nav>
<div class="nav-mobile" id="navMobile">
    <a href="index.php">Home</a>
    <a href="pages/about.php">About</a>
    <a href="pages/contact.php">Contact</a>
    <a href="login.php">Login</a>
    <a href="register.php" class="nav-mobile-cta">Get Started</a>
</div>

<div class="auth-page-wrap">
    <div class="auth-split-card animate-in">

        <!-- LEFT PANEL -->
        <div class="auth-panel-left">
            <div class="auth-panel-brand">
                <div class="auth-panel-icon">&#128663;</div>
                <h2>Smart<span>Parking</span></h2>
            </div>
            <h3>Park Smarter,<br>Save Time</h3>
            <p>Book parking slots instantly, manage your vehicles, and pay securely — all in one place.</p>
            <ul class="auth-panel-perks">
                <li>&#10003; Real-time slot availability</li>
                <li>&#10003; Instant booking confirmation</li>
                <li>&#10003; Secure UPI payments</li>
                <li>&#10003; Full booking history</li>
            </ul>
            <div class="auth-panel-badge">&#127942; Trusted by 5,000+ users</div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="auth-panel-right">
            <div class="auth-logo">
                <div class="auth-logo-icon">&#128274;</div>
                <h2>Welcome Back</h2>
                <p>Sign in to your account to continue</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">&#10003; <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">&#9888; <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="form-group">
                    <label>Login As</label>
                    <div class="role-selector">
                        <label class="role-option" id="role-user">
                            <input type="radio" name="role" value="user" checked onchange="setRole('user')">
                            <span class="role-icon">&#128100;</span> User
                        </label>
                        <label class="role-option" id="role-admin">
                            <input type="radio" name="role" value="admin" onchange="setRole('admin')">
                            <span class="role-icon">&#128737;</span> Admin
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-icon-wrap">
                        <span class="input-icon">&#9993;</span>
                        <input type="email" name="email" class="form-control has-icon"
                               placeholder="Enter your email"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="pass-wrap input-icon-wrap">
                        <span class="input-icon">&#128274;</span>
                        <input type="password" id="password" name="password"
                               class="form-control has-icon" placeholder="Enter your password" required>
                        <button type="button" class="pass-toggle" onclick="togglePass()">&#128065;</button>
                    </div>
                </div>

                <button type="submit" name="login" class="btn btn-primary btn-block auth-submit-btn">
                    &#128274; Sign In
                </button>
            </form>

            <div class="auth-divider"><span>New here?</span></div>
            <a href="register.php" class="btn btn-outline btn-block">&#128640; Create Free Account</a>

            <div class="auth-footer">
                <a href="pages/contact.php">Need help? Contact support</a>
            </div>
        </div>

    </div>
</div>

<?php require_once 'includes/public_footer.php'; ?>

<script>
function toggleDark() {
    const isDark = document.body.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    const label = isDark ? '\u2600\ufe0f Light' : '\ud83c\udf19 Dark';
    const btn = document.getElementById('darkBtn');
    const btnM = document.getElementById('darkBtnMobile');
    if (btn) btn.textContent = label;
    if (btnM) btnM.textContent = isDark ? '\u2600\ufe0f' : '\ud83c\udf19';
}
(function(){
    if (localStorage.getItem('theme') === 'dark') {
        const btn = document.getElementById('darkBtn');
        const btnM = document.getElementById('darkBtnMobile');
        if (btn) btn.textContent = '\u2600\ufe0f Light';
        if (btnM) btnM.textContent = '\u2600\ufe0f';
    }
})();
function toggleMenu() {
    document.getElementById('navMobile').classList.toggle('open');
}
function togglePass() {
    const p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
}
function setRole(r) {
    document.getElementById('role-user').classList.toggle('active', r === 'user');
    document.getElementById('role-admin').classList.toggle('active', r === 'admin');
}
document.addEventListener('DOMContentLoaded', () => {
    const checked = document.querySelector('input[name="role"]:checked');
    if (checked) setRole(checked.value);
});
</script>
</body>
</html>
