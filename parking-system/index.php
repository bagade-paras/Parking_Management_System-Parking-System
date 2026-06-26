<?php
session_start();
if (isset($_SESSION['admin_id'])) { header("Location: admin/admin_dashboard.php"); exit; }
if (isset($_SESSION['user_id']))  { header("Location: user/user_dashboard.php");  exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Smart Parking - Park Smarter, Save Time</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script>(function(){if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark');})();</script>
</head>
<body>

<!-- NAVBAR -->
<nav class="pub-nav">
    <a href="index.php" class="brand">&#128663; Smart<span>Parking</span></a>
    <div class="nav-links">
        <a href="index.php" class="nav-active">Home</a>
        <a href="pages/about.php">About</a>
        <a href="pages/contact.php">Contact</a>
        <a href="login.php">Login</a>
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

<!-- HERO -->
<section class="hero">
    <div class="hero-bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="hero-content">
        <div class="hero-badge">&#127942;&nbsp; #1 Smart Parking Management System</div>
        <h1>Park Smarter,<br><span>Save Time & Money</span></h1>
        <p>Book parking slots instantly, manage your vehicles, track spending, and pay securely online. No more circling the block.</p>
        <div class="hero-btns">
            <a href="register.php" class="btn btn-accent hero-cta">&#128640; Get Started Free</a>
            <a href="login.php" class="btn hero-outline-btn">&#128274; Sign In</a>
        </div>
        <div class="hero-trust">
            <span>&#10003;&nbsp; No credit card required</span>
            <span>&#10003;&nbsp; Free to use</span>
            <span>&#10003;&nbsp; Instant booking</span>
        </div>
    </div>
    <div class="hero-visual">
        <div class="parking-card-demo">
            <div class="pcd-header">&#127359; Live Parking Status</div>
            <div class="pcd-body">
                <div class="pcd-slot available">A1</div>
                <div class="pcd-slot available">A2</div>
                <div class="pcd-slot booked">A3</div>
                <div class="pcd-slot available">A4</div>
                <div class="pcd-slot booked">B1</div>
                <div class="pcd-slot available">B2</div>
                <div class="pcd-slot available">B3</div>
                <div class="pcd-slot booked">B4</div>
                <div class="pcd-slot available">C1</div>
                <div class="pcd-slot selected">C2</div>
                <div class="pcd-slot available">C3</div>
                <div class="pcd-slot booked">C4</div>
            </div>
            <div class="pcd-footer">
                <span class="pcd-dot available"></span> Available &nbsp;
                <span class="pcd-dot booked"></span> Booked &nbsp;
                <span class="pcd-dot selected"></span> Selected
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats-section">
    <div class="stats-inner">
        <div class="stat-item">
            <div class="stat-num" data-target="500">0</div>
            <div class="stat-label">Happy Users</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-num" data-target="1200">0</div>
            <div class="stat-label">Bookings Made</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-num" data-target="4">0</div>
            <div class="stat-label">Parking Locations</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-num" data-target="99">0</div>
            <div class="stat-label">% Uptime</div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section">
    <div class="section-label">Simple Process</div>
    <h2 class="section-title">How It Works</h2>
    <p class="section-sub">Book your parking slot in just 4 easy steps</p>
    <div class="steps-grid">
        <div class="step-card">
            <div class="step-num-badge">1</div>
            <div class="step-icon">&#128100;</div>
            <h3>Create Account</h3>
            <p>Sign up for free and register your vehicles in seconds.</p>
        </div>
        <div class="step-arrow">&#8594;</div>
        <div class="step-card">
            <div class="step-num-badge">2</div>
            <div class="step-icon">&#128205;</div>
            <h3>Choose Location</h3>
            <p>Browse available parking locations and check real-time slot availability.</p>
        </div>
        <div class="step-arrow">&#8594;</div>
        <div class="step-card">
            <div class="step-num-badge">3</div>
            <div class="step-icon">&#127359;</div>
            <h3>Pick Your Slot</h3>
            <p>Select your preferred slot from the interactive visual grid.</p>
        </div>
        <div class="step-arrow">&#8594;</div>
        <div class="step-card">
            <div class="step-num-badge">4</div>
            <div class="step-icon">&#128179;</div>
            <h3>Pay & Park</h3>
            <p>Complete payment via UPI and your slot is instantly reserved.</p>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="features">
    <div class="section-label">What We Offer</div>
    <h2 class="section-title">Everything You Need</h2>
    <p class="section-sub">A complete parking solution built for users and administrators</p>
    <div class="features-grid">
        <div class="feature-card">
            <div class="fc-icon-wrap" style="background:linear-gradient(135deg,#e8eaf6,#c5cae9);">
                <div class="fc-icon">&#128205;</div>
            </div>
            <h3>Multiple Locations</h3>
            <p>Choose from multiple parking locations with real-time slot availability and map preview.</p>
        </div>
        <div class="feature-card">
            <div class="fc-icon-wrap" style="background:linear-gradient(135deg,#e8f5e9,#c8e6c9);">
                <div class="fc-icon">&#127359;</div>
            </div>
            <h3>Visual Slot Selection</h3>
            <p>See available and booked slots on an interactive grid and pick your preferred spot.</p>
        </div>
        <div class="feature-card">
            <div class="fc-icon-wrap" style="background:linear-gradient(135deg,#fff8e1,#ffecb3);">
                <div class="fc-icon">&#128179;</div>
            </div>
            <h3>Secure UPI Payment</h3>
            <p>Pay via UPI QR code simulation. Fast, secure, and completely hassle-free.</p>
        </div>
        <div class="feature-card">
            <div class="fc-icon-wrap" style="background:linear-gradient(135deg,#fce4ec,#f8bbd0);">
                <div class="fc-icon">&#128663;</div>
            </div>
            <h3>Vehicle Management</h3>
            <p>Register multiple vehicles and manage all your bookings from one clean dashboard.</p>
        </div>
        <div class="feature-card">
            <div class="fc-icon-wrap" style="background:linear-gradient(135deg,#e3f2fd,#bbdefb);">
                <div class="fc-icon">&#9203;</div>
            </div>
            <h3>Extend Booking</h3>
            <p>Running late? Extend your active parking session with just one click.</p>
        </div>
        <div class="feature-card">
            <div class="fc-icon-wrap" style="background:linear-gradient(135deg,#f3e5f5,#e1bee7);">
                <div class="fc-icon">&#128203;</div>
            </div>
            <h3>Booking History</h3>
            <p>View complete history, download receipts, and track your parking spending.</p>
        </div>
        <div class="feature-card">
            <div class="fc-icon-wrap" style="background:linear-gradient(135deg,#e0f7fa,#b2ebf2);">
                <div class="fc-icon">&#128276;</div>
            </div>
            <h3>Email Reminders</h3>
            <p>Get email reminders before your booking starts so you never miss your slot.</p>
        </div>
        <div class="feature-card">
            <div class="fc-icon-wrap" style="background:linear-gradient(135deg,#fff3e0,#ffe0b2);">
                <div class="fc-icon">&#128202;</div>
            </div>
            <h3>Admin Dashboard</h3>
            <p>Full admin control over locations, slots, users, bookings, and revenue analytics.</p>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials-section">
    <div class="section-label">User Reviews</div>
    <h2 class="section-title">What Users Say</h2>
    <p class="section-sub">Trusted by hundreds of daily parkers</p>
    <div class="testimonials-grid">
        <div class="testimonial-card">
            <div class="t-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p>"Smart Parking saved me so much time. I book my slot before leaving home and just drive straight in!"</p>
            <div class="t-author">
                <div class="t-avatar">R</div>
                <div><strong>Rahul Sharma</strong><br><span>Daily Commuter</span></div>
            </div>
        </div>
        <div class="testimonial-card">
            <div class="t-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p>"The visual slot selection is brilliant. I can see exactly which spots are free and pick the best one."</p>
            <div class="t-author">
                <div class="t-avatar">P</div>
                <div><strong>Priya Mehta</strong><br><span>Office Worker</span></div>
            </div>
        </div>
        <div class="testimonial-card">
            <div class="t-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p>"The extend booking feature is a lifesaver. Meetings run late and I can just add more hours instantly."</p>
            <div class="t-author">
                <div class="t-avatar">A</div>
                <div><strong>Amit Patel</strong><br><span>Business Owner</span></div>
            </div>
        </div>
    </div>
</section>

<!-- CTA BANNER -->
<section class="cta-section">
    <div class="cta-inner">
        <div class="cta-text">
            <h2>Ready to Park Smarter?</h2>
            <p>Join hundreds of users who save time every day with Smart Parking.</p>
        </div>
        <div class="cta-btns">
            <a href="register.php" class="btn btn-accent" style="font-size:16px;padding:14px 36px;">&#128640; Get Started Free</a>
            <a href="pages/about.php" class="btn cta-outline-btn">Learn More</a>
        </div>
    </div>
</section>


<?php require_once 'includes/public_footer.php'; ?>

<script>
// Dark mode
function toggleDark() {
    const isDark = document.body.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    const label = isDark ? '\u2600\ufe0f Light' : '\ud83c\udf19 Dark';
    const labelMobile = isDark ? '\u2600\ufe0f' : '\ud83c\udf19';
    const btn = document.getElementById('darkBtn');
    const btnM = document.getElementById('darkBtnMobile');
    if (btn) btn.textContent = label;
    if (btnM) btnM.textContent = labelMobile;
}
(function(){
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        const btn = document.getElementById('darkBtn');
        const btnM = document.getElementById('darkBtnMobile');
        if (btn) btn.textContent = '\u2600\ufe0f Light';
        if (btnM) btnM.textContent = '\u2600\ufe0f';
    }
})();

// Mobile menu
function toggleMenu() {
    document.getElementById('navMobile').classList.toggle('open');
}

// Stats counter animation
function animateCounters() {
    document.querySelectorAll('.stat-num[data-target]').forEach(el => {
        const target = parseInt(el.dataset.target);
        let current = 0;
        const step = Math.ceil(target / 60);
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = current + (el.dataset.target == '99' ? '%' : '+');
            if (current >= target) clearInterval(timer);
        }, 25);
    });
}

// Trigger counter when stats section is visible
const statsObs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { animateCounters(); statsObs.disconnect(); } });
}, { threshold: 0.3 });
const statsEl = document.querySelector('.stats-section');
if (statsEl) statsObs.observe(statsEl);

// Scroll reveal
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('revealed'); });
}, { threshold: 0.1 });
document.querySelectorAll('.feature-card, .step-card, .testimonial-card, .stat-item').forEach(el => {
    el.classList.add('reveal');
    revealObs.observe(el);
});
</script>
</body>
</html>
