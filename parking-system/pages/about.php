<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>About - Smart Parking</title>
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
        <a href="about.php" class="nav-active">About</a>
        <a href="contact.php">Contact</a>
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

<!-- ABOUT HERO -->
<section class="about-hero">
    <div class="about-hero-bg"></div>
    <div class="about-hero-content">
        <span class="hero-badge">🏢 Our Story</span>
        <h1>Smarter Parking for <span>Everyone</span></h1>
        <p>We built Smart Parking to eliminate the frustration of finding a spot. From real-time availability to instant booking — we make parking effortless.</p>
        <div class="about-hero-btns">
            <a href="../register.php" class="btn btn-accent hero-cta">🚀 Get Started Free</a>
            <a href="contact.php" class="btn hero-outline-btn">📬 Contact Us</a>
        </div>
    </div>
</section>

<!-- STATS COUNTER -->
<section class="about-stats-section">
    <div class="about-stats-inner">
        <div class="about-stat-item reveal">
            <div class="about-stat-num" data-target="5000">0</div>
            <div class="about-stat-label">Happy Users</div>
        </div>
        <div class="about-stat-divider"></div>
        <div class="about-stat-item reveal">
            <div class="about-stat-num" data-target="120">0</div>
            <div class="about-stat-label">Parking Locations</div>
        </div>
        <div class="about-stat-divider"></div>
        <div class="about-stat-item reveal">
            <div class="about-stat-num" data-target="18000">0</div>
            <div class="about-stat-label">Bookings Completed</div>
        </div>
        <div class="about-stat-divider"></div>
        <div class="about-stat-item reveal">
            <div class="about-stat-num" data-target="99">0</div>
            <div class="about-stat-label">% Uptime</div>
        </div>
    </div>
</section>

<!-- MISSION & VISION -->
<section class="about-mv-section">
    <div class="about-mv-inner">
        <div class="about-mv-card reveal">
            <div class="about-mv-icon">🎯</div>
            <h3>Our Mission</h3>
            <p>To reduce parking congestion, save time, and provide a smart digital parking experience for everyone — from daily commuters to weekend shoppers.</p>
        </div>
        <div class="about-mv-card reveal">
            <div class="about-mv-icon">🏆</div>
            <h3>Our Vision</h3>
            <p>A future where finding and booking parking is as easy as a few taps — fully automated, real-time, and accessible to all.</p>
        </div>
        <div class="about-mv-card reveal">
            <div class="about-mv-icon">💡</div>
            <h3>Our Values</h3>
            <p>Transparency, reliability, and user-first design. We believe technology should simplify life, not complicate it.</p>
        </div>
    </div>
</section>

<!-- OUR STORY TIMELINE -->
<section class="about-timeline-section">
    <div class="about-section-header reveal">
        <span class="section-label">Our Journey</span>
        <h2 class="section-title">How We Got Here</h2>
    </div>
    <div class="timeline">
        <div class="timeline-item reveal">
            <div class="timeline-dot">2022</div>
            <div class="timeline-card">
                <h4>The Idea</h4>
                <p>Frustrated by endless parking searches, our founders sketched the first wireframes of Smart Parking on a napkin.</p>
            </div>
        </div>
        <div class="timeline-item right reveal">
            <div class="timeline-dot">2023</div>
            <div class="timeline-card">
                <h4>Beta Launch</h4>
                <p>Launched beta with 10 locations and 500 users. Received overwhelming positive feedback and iterated rapidly.</p>
            </div>
        </div>
        <div class="timeline-item reveal">
            <div class="timeline-dot">2024</div>
            <div class="timeline-card">
                <h4>Full Launch</h4>
                <p>Expanded to 120+ locations, introduced QR payments, admin dashboards, and real-time slot tracking.</p>
            </div>
        </div>
        <div class="timeline-item right reveal">
            <div class="timeline-dot">2025</div>
            <div class="timeline-card">
                <h4>Growing Strong</h4>
                <p>5,000+ active users, 18,000+ bookings, and expanding to new cities with AI-powered slot prediction.</p>
            </div>
        </div>
    </div>
</section>

<!-- KEY FEATURES -->
<section class="about-features-section">
    <div class="about-section-header reveal">
        <span class="section-label">What We Offer</span>
        <h2 class="section-title">✨ Key Features</h2>
        <p class="section-sub">Everything you need for a seamless parking experience.</p>
    </div>
    <div class="about-features-grid">
        <?php
        $features = [
            ['🅿️','#e8eaf6','Online Slot Booking','Reserve parking slots instantly from anywhere, anytime — no queues, no hassle.'],
            ['🚗','#e8f5e9','Vehicle Management','Register and manage multiple vehicles under one account with ease.'],
            ['📊','#e3f2fd','Real-time Availability','See live slot availability before you even leave home.'],
            ['💳','#fff8e1','QR Payment Simulation','Secure UPI-based QR payment simulation with instant confirmation.'],
            ['🛡️','#fce4ec','Admin Dashboard','Full control over locations, slots, users, and revenue analytics.'],
            ['📋','#f3e5f5','Booking History','Complete history of all bookings with receipts and status tracking.'],
            ['⏰','#e0f7fa','Extend Bookings','Need more time? Extend your active booking without losing your spot.'],
            ['🔔','#fff3e0','Smart Reminders','Get notified before your booking expires so you\'re never caught off guard.'],
        ];
        foreach ($features as $f): ?>
        <div class="about-feature-card reveal" style="--fc-bg:<?= $f[1] ?>;">
            <div class="about-fc-icon-wrap" style="background:<?= $f[1] ?>;"><?= $f[0] ?></div>
            <h4><?= $f[2] ?></h4>
            <p><?= $f[3] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- TEAM SECTION -->
<section class="about-team-section">
    <div class="about-section-header reveal">
        <span class="section-label">The People</span>
        <h2 class="section-title">Meet Our Team</h2>
        <p class="section-sub">Passionate developers and designers building the future of parking.</p>
    </div>
    <div class="team-grid">
        <?php
        $team = [
            ['👨‍⚖️','Manav Chhodiwala','Project Leader','End-to-end product development and leadership.','#1a237e'],
            ['🤵','Fenil Katkatiya','Frontend Developer','Crafts intuitive interfaces that users love to interact with.','#880e4f'],
            ['👨‍💻','Yug Kayasth','Backend Developer','Strengthening project foundations through clean code and teamwork.','#1b5e20'],
            ['👨‍💼','Paras Bagade','Tech Enthusiast','Curious about the latest technologies and their applications.','#e65100'],
        ];
        foreach ($team as $t): ?>
        <div class="team-card reveal">
            <div class="team-avatar" style="background:<?= $t[4] ?>20;color:<?= $t[4] ?>;"><?= $t[0] ?></div>
            <h4><?= $t[1] ?></h4>
            <span class="team-role"><?= $t[2] ?></span>
            <p><?= $t[3] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- TECH STACK -->
<section class="about-tech-section">
    <div class="about-section-header reveal">
        <span class="section-label">Built With</span>
        <h2 class="section-title">Our Tech Stack</h2>
    </div>
    <div class="tech-grid">
        <?php
        $tech = [
            ['🐘','PHP 8','Backend'],['🗄️','MySQL','Database'],['🎨','CSS3','Styling'],
            ['⚡','JavaScript','Interactivity'],['🔒','Sessions','Auth'],['📱','Responsive','Mobile-first'],
        ];
        foreach ($tech as $t): ?>
        <div class="tech-chip reveal">
            <span class="tech-icon"><?= $t[0] ?></span>
            <div><strong><?= $t[1] ?></strong><small><?= $t[2] ?></small></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- FAQ -->
<section class="about-faq-section">
    <div class="about-section-header reveal">
        <span class="section-label">Got Questions?</span>
        <h2 class="section-title">Frequently Asked Questions</h2>
    </div>
    <div class="faq-list">
        <?php
        $faqs = [
            ['Is Smart Parking free to use?','Yes! Creating an account and browsing available slots is completely free. You only pay when you book a slot.'],
            ['How do I cancel a booking?','You can cancel an active booking from your dashboard under "Booking History" before the booking start time.'],
            ['What payment methods are supported?','We currently support UPI-based QR payment simulation. More payment options are coming soon.'],
            ['Can I manage multiple vehicles?','Absolutely. You can register and manage multiple vehicles from your profile page.'],
            ['Is my data secure?','Yes. We use session-based authentication and never store sensitive payment data on our servers.'],
            ['How do I report an issue?','Use our Contact page to send us a message. We typically respond within 24 hours.'],
        ];
        foreach ($faqs as $i => $faq): ?>
        <div class="faq-item reveal" onclick="toggleFaq(this)">
            <div class="faq-question">
                <span><?= $faq[0] ?></span>
                <span class="faq-icon">+</span>
            </div>
            <div class="faq-answer"><?= $faq[1] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA -->
<section class="about-cta-section">
    <div class="about-cta-inner reveal">
        <div>
            <h2>Ready to park smarter?</h2>
            <p>Join 5,000+ users who save time every day with Smart Parking.</p>
        </div>
        <div class="about-cta-btns">
            <a href="../register.php" class="btn btn-accent hero-cta">🚀 Create Free Account</a>
            <a href="contact.php" class="btn cta-outline-btn">📬 Get in Touch</a>
        </div>
    </div>
</section>

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

// FAQ accordion
function toggleFaq(el) {
    const isOpen = el.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(f => f.classList.remove('open'));
    if (!isOpen) el.classList.add('open');
}

// Scroll reveal
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('revealed'); observer.unobserve(e.target); } });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Counter animation
function animateCounter(el) {
    const target = +el.dataset.target;
    const duration = 1800;
    const step = target / (duration / 16);
    let current = 0;
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = Math.floor(current).toLocaleString();
        if (current >= target) clearInterval(timer);
    }, 16);
}
const counterObserver = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) { animateCounter(e.target); counterObserver.unobserve(e.target); }
    });
}, { threshold: 0.5 });
document.querySelectorAll('.about-stat-num').forEach(el => counterObserver.observe(el));
</script>
</body>
</html>
