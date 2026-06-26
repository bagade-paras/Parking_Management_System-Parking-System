<?php
// Detect path depth so links work from any folder
$root = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
?>
<footer class="pub-footer-new">
    <div class="footer-inner">
        <div class="footer-brand">
            <div style="font-size:28px;margin-bottom:8px;">🚗</div>
            <strong>Smart Parking</strong>
            <p>The smartest way to find, book, and manage your parking — anytime, anywhere.</p>
            <div class="footer-social">
                <a href="#" title="Facebook">📘</a>
                <a href="#" title="Twitter">🐦</a>
                <a href="#" title="LinkedIn">💼</a>
                <a href="#" title="Instagram">📸</a>
            </div>
        </div>
        <div class="footer-links">
            <strong>Quick Links</strong>
            <a href="<?= $root ?>index.php">Home</a>
            <a href="<?= $root ?>pages/about.php">About</a>
            <a href="<?= $root ?>pages/contact.php">Contact</a>
            <a href="<?= $root ?>login.php">Login</a>
            <a href="<?= $root ?>register.php">Register</a>
        </div>
        <div class="footer-links">
            <strong>Features</strong>
            <a href="<?= $root ?>register.php">Book a Slot</a>
            <a href="<?= $root ?>register.php">Manage Vehicles</a>
            <a href="<?= $root ?>register.php">View History</a>
            <a href="<?= $root ?>register.php">Download Receipt</a>
            <a href="<?= $root ?>register.php">Extend Booking</a>
        </div>
        <div class="footer-contact">
            <strong>Contact Us</strong>
            <p>✉️ support@smartparking.com</p>
            <p>📞+91 99789 25962</p>
            <p>📍 Surat City, Gujarat, India</p>
            <p style="margin-top:10px;font-size:12px;opacity:1;">🕐 Mon–Fri : 9AM – 8PM</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date("Y") ?> <span>Smart Parking</span> — All rights reserved.</p>
    </div>
</footer>
