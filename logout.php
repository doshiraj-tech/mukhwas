<?php
include("../config/db.php");

// Delete "Remember Me" token from DB & clear cookie
if (!empty($_COOKIE['admin_remember'])) {
    $cookie_data = explode(':', $_COOKIE['admin_remember']);
    if (!empty($cookie_data[0])) {
        $selector = mysqli_real_escape_string($conn, $cookie_data[0]);
        mysqli_query($conn, "DELETE FROM admin_remember_tokens WHERE selector='$selector'");
    }
    setcookie('admin_remember', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Fully destroy the admin session
session_unset();
session_destroy();

// Redirect to login
header("Location: login.php");
exit();
?>

