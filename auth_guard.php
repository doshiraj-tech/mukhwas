<?php
/**
 * Admin Authentication Guard
 * -------------------------------------------------
 * Include this file at the TOP of every admin page.
 * It handles:
 *  1. Session auth check (redirect to login if not logged in)
 *  2. Session hijack prevention (user-agent binding)
 *  3. Auto session timeout after ADMIN_SESSION_TIMEOUT minutes of inactivity
 *  4. No-cache headers so browser back-button can't show protected pages
 * -------------------------------------------------
 */

define('ADMIN_SESSION_TIMEOUT', 1800); // seconds of inactivity before auto-logout (30 minutes)

// Prevent browser from caching admin pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

// 1. Check admin session exists
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// 2. Session hijack prevention — user-agent binding
if (!isset($_SESSION['admin_agent']) || $_SESSION['admin_agent'] !== md5($_SERVER['HTTP_USER_AGENT'])) {
    session_unset();
    session_destroy();
    header("Location: login.php?reason=security");
    exit();
}

// 3. Auto-timeout: check last activity timestamp
if (isset($_SESSION['admin_last_activity'])) {
    $idle_seconds = time() - $_SESSION['admin_last_activity'];
    if ($idle_seconds > ADMIN_SESSION_TIMEOUT) {
        // Session expired — destroy everything and redirect
        session_unset();
        session_destroy();
        header("Location: login.php?reason=timeout");
        exit();
    }
}

// Reset the activity timer on every page load
$_SESSION['admin_last_activity'] = time();

// 4. Role-based access control helpers
$admin_role = $_SESSION['admin_role'] ?? 'subadmin';

/**
 * Check if the current admin is the main admin
 */
function is_main_admin() {
    return ($_SESSION['admin_role'] ?? 'subadmin') === 'admin';
}

/**
 * Block subadmin access — redirect to dashboard with a message
 */
function require_main_admin() {
    if (!is_main_admin()) {
        header("Location: dashboard.php?denied=1");
        exit();
    }
}

// Pages that subadmins are allowed to access
$subadmin_allowed_pages = [
    'dashboard.php',
    'products.php',
    'add_product.php',
    'edit_product.php',
    'delete_product.php',
    'categories.php',
    'orders.php',
    'order_details.php',
    'reviews.php',
    'posts.php',
    'post_add.php',
    'post_edit.php',
    'post_categories.php',
    'coupons.php',
    'customers.php',
    'admin_profile.php',
];

// Auto-block subadmins from restricted pages
if ($admin_role === 'subadmin') {
    $current_file = basename($_SERVER['PHP_SELF']);
    if (!in_array($current_file, $subadmin_allowed_pages)) {
        header("Location: dashboard.php?denied=1");
        exit();
    }
}
?>
