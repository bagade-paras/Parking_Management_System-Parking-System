<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!function_exists('is_super')) {
    function is_super()    { return ($_SESSION['admin_role'] ?? 'super') === 'super'; }
    function is_sub()      { return ($_SESSION['admin_role'] ?? 'super') === 'sub'; }
    function my_location() { return (int)($_SESSION['admin_location_id'] ?? 0); }
    function require_super() {
        if (is_sub()) {
            header("Location: admin_dashboard.php");
            exit;
        }
    }
}
