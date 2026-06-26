<?php
session_start();
require_once "includes/db_connect.php";

if (isset($_POST['register'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $pass  = $_POST['password'];

    if (strlen($pass) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!preg_match('/[A-Z]/', $pass)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $pass)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $pass)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[@#$%^&*!]/', $pass)) {
        $error = "Password must contain at least one special character (@, #, $, %, etc.).";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashed);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Smart Parking</title>
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
        <a href="login.php">Login</a>
        <a href="register.php" class="btn-nav nav-active">Get Started</a>
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
    <div class="auth-split-card auth-split-card--wide animate-in">

        <!-- LEFT PANEL -->
        <div class="auth-panel-left">
            <div class="auth-panel-brand">
                <div class="auth-panel-icon">&#128663;</div>
                <h2>Smart<span>Parking</span></h2>
            </div>
            <h3>Join Thousands of<br>Smart Parkers</h3>
            <p>Create your free account and start booking parking slots in seconds. No credit card required.</p>
            <ul class="auth-panel-perks">
                <li>&#10003; Free account forever</li>
                <li>&#10003; Book slots in seconds</li>
                <li>&#10003; Manage multiple vehicles</li>
                <li>&#10003; Download receipts anytime</li>
                <li>&#10003; Extend bookings on the go</li>
            </ul>
            <div class="auth-panel-badge">&#128640; Get started in under 1 minute</div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="auth-panel-right">
            <div class="auth-logo">
                <div class="auth-logo-icon">&#128640;</div>
                <h2>Create Account</h2>
                <p>Join Smart Parking today — it's free</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">&#9888; <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="req-star">*</span></label>
                        <div class="input-icon-wrap">
                            <span class="input-icon">&#128100;</span>
                            <input type="text" name="name" class="form-control has-icon"
                                   placeholder="Your full name"
                                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <div class="input-icon-wrap">
                            <span class="input-icon">&#128222;</span>
                            <input type="tel" name="phone" class="form-control has-icon"
                                   placeholder="e.g. 9876543210"
                                   value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address <span class="req-star">*</span></label>
                    <div class="input-icon-wrap">
                        <span class="input-icon">&#9993;</span>
                        <input type="email" name="email" class="form-control has-icon"
                               placeholder="you@email.com"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password <span class="req-star">*</span></label>
                    <div class="pass-wrap input-icon-wrap">
                        <span class="input-icon">&#128274;</span>
                        <input type="password" id="password" name="password"
                               class="form-control has-icon" placeholder="Create a strong password"
                               required oninput="checkStrength(this.value)">
                        <button type="button" class="pass-toggle" onclick="togglePass()">&#128065;</button>
                    </div>
                    <!-- Strength bar -->
                    <div class="strength-wrap">
                        <div class="strength-bar-bg">
                            <div id="strengthBar" class="strength-bar"></div>
                        </div>
                        <span id="strengthText" class="strength-text"></span>
                    </div>
                    <!-- Requirements -->
                    <div class="pass-requirements">
                        <div class="pass-req-title">Password must contain:</div>
                        <div class="pass-req-grid">
                            <div id="req-len"   class="req-item">&#10007; 6+ characters</div>
                            <div id="req-upper" class="req-item">&#10007; Uppercase (A-Z)</div>
                            <div id="req-lower" class="req-item">&#10007; Lowercase (a-z)</div>
                            <div id="req-num"   class="req-item">&#10007; Number (0-9)</div>
                            <div id="req-spec"  class="req-item">&#10007; Special (@#$%)</div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="register" class="btn btn-primary btn-block auth-submit-btn">
                    &#128640; Create Free Account
                </button>
            </form>

            <div class="auth-divider"><span>Already have an account?</span></div>
            <a href="login.php" class="btn btn-outline btn-block">&#128274; Sign In</a>

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
    const btn = document.getElementById('darkBtn');
    const btnM = document.getElementById('darkBtnMobile');
    if (btn) btn.textContent = isDark ? '\u2600\ufe0f Light' : '\ud83c\udf19 Dark';
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
function checkStrength(val) {
    const rules = [
        { id: 'req-len',   test: val.length >= 6 },
        { id: 'req-upper', test: /[A-Z]/.test(val) },
        { id: 'req-lower', test: /[a-z]/.test(val) },
        { id: 'req-num',   test: /[0-9]/.test(val) },
        { id: 'req-spec',  test: /[@#$%^&*!]/.test(val) },
    ];
    let passed = 0;
    rules.forEach(r => {
        const el = document.getElementById(r.id);
        if (r.test) {
            el.classList.add('req-ok');
            el.innerHTML = el.innerHTML.replace('&#10007;','&#10003;').replace('✗','✓');
            passed++;
        } else {
            el.classList.remove('req-ok');
            el.innerHTML = el.innerHTML.replace('&#10003;','&#10007;').replace('✓','✗');
        }
    });
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    const pct  = (passed / 5) * 100;
    bar.style.width = pct + '%';
    const levels = [
        { max:1, color:'#c62828', label:'Very Weak' },
        { max:2, color:'#e65100', label:'Weak' },
        { max:3, color:'#f9a825', label:'Fair' },
        { max:4, color:'#43a047', label:'Strong' },
        { max:5, color:'#2e7d32', label:'Very Strong' },
    ];
    const lvl = levels.find(l => passed <= l.max) || levels[4];
    bar.style.background = lvl.color;
    text.textContent = val ? lvl.label : '';
    text.style.color = lvl.color;
    if (!val) bar.style.width = '0';
}
</script>
</body>
</html>
