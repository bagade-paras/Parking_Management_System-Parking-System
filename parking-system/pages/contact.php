<?php
session_start();
require_once '../includes/db_connect.php';
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email   = htmlspecialchars(trim($_POST['email'] ?? ''));
    $phone   = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $msg     = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!$name)                        $errors[] = 'Name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (!$subject)                     $errors[] = 'Please select a subject.';
    if (strlen($msg) < 10)             $errors[] = 'Message must be at least 10 characters.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssss', $name, $email, $phone, $subject, $msg);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact - Smart Parking</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<script>(function(){if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark');})();</script>

<nav class="pub-nav">
    <a href="../index.php" class="brand">🚗 Smart<span>Parking</span></a>
    <div class="nav-links">
        <a href="../index.php">Home</a>
        <a href="about.php">About</a>
        <a href="contact.php" class="nav-active">Contact</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="../user/user_dashboard.php">Dashboard</a>
            <a href="../logout.php" class="btn-nav">Logout</a>
        <?php elseif (isset($_SESSION['admin_id'])): ?>
            <a href="../admin/admin_dashboard.php">Dashboard</a>
            <a href="../logout.php" class="btn-nav">Logout</a>
        <?php else: ?>
            <a href="../login.php">Login</a>
            <a href="../register.php" class="btn-nav">Get Started</a>
        <?php endif; ?>
        <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark</button>
    </div>
    <div class="nav-right-mobile">
        <button class="dark-toggle" onclick="toggleDark()" id="darkBtnMobile">🌙</button>
        <button class="nav-hamburger" onclick="toggleMenu()" id="hamburger">&#9776;</button>
    </div>
</nav>
<div class="nav-mobile" id="navMobile">
    <a href="../index.php">Home</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="../user/user_dashboard.php">Dashboard</a>
        <a href="../logout.php" class="nav-mobile-cta">Logout</a>
    <?php elseif (isset($_SESSION['admin_id'])): ?>
        <a href="../admin/admin_dashboard.php">Dashboard</a>
        <a href="../logout.php" class="nav-mobile-cta">Logout</a>
    <?php else: ?>
        <a href="../login.php">Login</a>
        <a href="../register.php" class="nav-mobile-cta">Get Started</a>
    <?php endif; ?>
</div>

<!-- CONTACT HERO -->
<section class="contact-hero">
    <div class="contact-hero-content">
        <span class="hero-badge">📬 Get In Touch</span>
        <h1>We'd Love to <span>Hear From You</span></h1>
        <p>Have a question, feedback, or just want to say hello? Our team is here to help — usually within 24 hours.</p>
    </div>
</section>

<div class="contact-page-wrap">

    <!-- INFO CARDS ROW -->
    <div class="contact-info-row">
        <div class="contact-info-card reveal">
            <div class="cic-icon" style="background:#e8eaf6;">📍</div>
            <div class="cic-body">
                <h4>Our Office</h4>
                <p>Smart Parking HQ,<br>Surat City, Gujarat<br>India — 395003</p>
            </div>
        </div>
        <div class="contact-info-card reveal">
            <div class="cic-icon" style="background:#e8f5e9;">✉️</div>
            <div class="cic-body">
                <h4>Email Us</h4>
                <p><a href="mailto:support@smartparking.com">support@smartparking.com</a><br>
                   <a href="mailto:admin@smartparking.com">admin@smartparking.com</a></p>
            </div>
        </div>
        <div class="contact-info-card reveal">
            <div class="cic-icon" style="background:#e3f2fd;">📞</div>
            <div class="cic-body">
                <h4>Call Us</h4>
                <p><a href="tel:+919876543210">+91 99789 25962</a><br>
                   <a href="tel:+918012345678">+91 94095 15251</a></p>
            </div>
        </div>
        <div class="contact-info-card reveal">
            <div class="cic-icon" style="background:#fff8e1;">🕐</div>
            <div class="cic-body">
                <h4>Working Hours</h4>
                <p>Mon – Fri: 9:00 AM – 8:00 PM<br>Sat: 10:00 AM – 2:00 PM<br>Sun: Closed</p>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT: FORM + SIDEBAR -->
    <div class="contact-main-grid">

        <!-- CONTACT FORM -->
        <div class="contact-form-card reveal">
            <div class="contact-form-header">
                <h2>📝 Send Us a Message</h2>
                <p>Fill in the form and we'll get back to you as soon as possible.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success contact-success-alert">
                    <div class="csa-icon">✅</div>
                    <div>
                        <strong>Message Sent Successfully!</strong><br>
                        Thank you, <strong><?= htmlspecialchars($_POST['name']) ?></strong>! We've received your message and will reply to <strong><?= htmlspecialchars($_POST['email']) ?></strong> within 24 hours.
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div>
                        <?php foreach ($errors as $e): ?>
                            <div>⚠️ <?= $e ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" id="contactForm" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="req-star">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe"
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span class="req-star">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="you@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number <span class="optional-tag">Optional</span></label>
                        <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Subject <span class="req-star">*</span></label>
                        <select name="subject" class="form-control" required>
                            <option value="">— Select a subject —</option>
                            <option value="General Inquiry" <?= (($_POST['subject'] ?? '') === 'General Inquiry') ? 'selected' : '' ?>>General Inquiry</option>
                            <option value="Booking Issue"   <?= (($_POST['subject'] ?? '') === 'Booking Issue')   ? 'selected' : '' ?>>Booking Issue</option>
                            <option value="Payment Problem" <?= (($_POST['subject'] ?? '') === 'Payment Problem') ? 'selected' : '' ?>>Payment Problem</option>
                            <option value="Account Help"    <?= (($_POST['subject'] ?? '') === 'Account Help')    ? 'selected' : '' ?>>Account Help</option>
                            <option value="Feature Request" <?= (($_POST['subject'] ?? '') === 'Feature Request') ? 'selected' : '' ?>>Feature Request</option>
                            <option value="Bug Report"      <?= (($_POST['subject'] ?? '') === 'Bug Report')      ? 'selected' : '' ?>>Bug Report</option>
                            <option value="Other"           <?= (($_POST['subject'] ?? '') === 'Other')           ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Message <span class="req-star">*</span></label>
                    <textarea name="message" class="form-control" rows="6"
                              placeholder="Describe your question or issue in detail..."
                              required style="resize:vertical;"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    <div class="char-counter"><span id="charCount">0</span> / 1000 characters</div>
                </div>
                <button type="submit" class="btn btn-primary btn-block contact-submit-btn">
                    <span>📤 Send Message</span>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- SIDEBAR -->
        <div class="contact-sidebar">

            <!-- QUICK LINKS -->
            <div class="contact-sidebar-card reveal">
                <h3>⚡ Quick Help</h3>
                <div class="quick-links">
                    <a href="../register.php" class="quick-link-item">
                        <span class="qli-icon">🆕</span>
                        <div><strong>Create Account</strong><small>Get started for free</small></div>
                        <span class="qli-arrow">›</span>
                    </a>
                    <a href="../login.php" class="quick-link-item">
                        <span class="qli-icon">🔑</span>
                        <div><strong>Login</strong><small>Access your dashboard</small></div>
                        <span class="qli-arrow">›</span>
                    </a>
                    <a href="about.php" class="quick-link-item">
                        <span class="qli-icon">ℹ️</span>
                        <div><strong>About Us</strong><small>Learn our story</small></div>
                        <span class="qli-arrow">›</span>
                    </a>
                </div>
            </div>

            <!-- SOCIAL LINKS -->
            <div class="contact-sidebar-card reveal">
                <h3>🌐 Follow Us</h3>
                <div class="social-links">
                    <a href="#" class="social-link-item" style="--sc:#1877f2;">
                        <span>📘</span> Facebook
                    </a>
                    <a href="#" class="social-link-item" style="--sc:#1da1f2;">
                        <span>🐦</span> Twitter / X
                    </a>
                    <a href="#" class="social-link-item" style="--sc:#0a66c2;">
                        <span>💼</span> LinkedIn
                    </a>
                    <a href="#" class="social-link-item" style="--sc:#e1306c;">
                        <span>📸</span> Instagram
                    </a>
                </div>
            </div>

            <!-- RESPONSE TIME -->
            <div class="contact-sidebar-card contact-response-card reveal">
                <div class="response-icon">⏱️</div>
                <h4>Average Response Time</h4>
                <div class="response-time">Under 24 hrs</div>
                <p>Our support team is dedicated to resolving your queries quickly and efficiently.</p>
            </div>

        </div>
    </div>

    <!-- MAP PLACEHOLDER -->
    <div class="contact-map-section reveal">
        <div class="contact-section-header">
            <span class="section-label">Find Us</span>
            <h2 class="section-title">Our Location</h2>
        </div>
        <div class="map-embed-wrap">
            <div class="map-placeholder">
                <div class="map-pin-anim">📍</div>
                <h3>Smart Parking HQ</h3>
                <p>Surat City, Gujarat,India — 395003</p>
                <a href="https://www.google.com/maps/place/Surat,+Gujarat/@21.1592002,72.8222859,32825m/data=!3m2!1e3!4b1!4m6!3m5!1s0x3be04e59411d1563:0xfe4558290938b042!8m2!3d21.1702401!4d72.8310607!16zL20vMDFoMWhu?entry=ttu&g_ep=EgoyMDI2MDQxNS4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="btn btn-primary btn-sm" style="margin-top:12px;">
                    🗺️ Open in Google Maps
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once '../includes/public_footer.php'; ?>

<script>
// Dark mode
function toggleDark() {
    const isDark = document.body.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    const btn = document.getElementById('darkBtn');
    const btnM = document.getElementById('darkBtnMobile');
    if (btn) btn.textContent = isDark ? '☀️ Light' : '🌙 Dark';
    if (btnM) btnM.textContent = isDark ? '☀️' : '🌙';
}
(function(){
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        const btn = document.getElementById('darkBtn');
        const btnM = document.getElementById('darkBtnMobile');
        if (btn) btn.textContent = '☀️ Light';
        if (btnM) btnM.textContent = '☀️';
    }
})();
function toggleMenu() {
    document.getElementById('navMobile').classList.toggle('open');
}

// Scroll reveal
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('revealed'); observer.unobserve(e.target); } });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Character counter
const textarea = document.querySelector('textarea[name="message"]');
const counter  = document.getElementById('charCount');
if (textarea && counter) {
    textarea.addEventListener('input', () => {
        const len = textarea.value.length;
        counter.textContent = len;
        counter.style.color = len > 900 ? '#c62828' : len > 700 ? '#e65100' : '';
        if (textarea.value.length > 1000) textarea.value = textarea.value.slice(0, 1000);
    });
}
</script>
</body>
</html>
