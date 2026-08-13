<?php
include("../config/db.php");
require_once('auth_guard.php');

// Only main admin can access this page
require_main_admin();

$success = '';
$error = '';

// ─── Delete Admin ───
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    // Prevent deleting yourself
    $check = mysqli_query($conn, "SELECT username FROM admin WHERE id=$del_id");
    if ($check && mysqli_num_rows($check) > 0) {
        $del_admin = mysqli_fetch_assoc($check);
        if ($del_admin['username'] === $_SESSION['admin']) {
            $error = "You cannot delete your own account!";
        } else {
            // Ensure at least 1 admin remains
            $count_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admin");
            $total = mysqli_fetch_assoc($count_q)['cnt'];
            if ($total <= 1) {
                $error = "Cannot delete the last admin account!";
            } else {
                mysqli_query($conn, "DELETE FROM admin WHERE id=$del_id");
                $success = "Admin account deleted successfully.";
            }
        }
    }
}

// ─── Create Admin ───
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_admin') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed.";
    } else {
        $new_username = mysqli_real_escape_string($conn, trim($_POST['new_username']));
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        if (empty($new_username) || empty($new_password)) {
            $error = "Please fill in all fields.";
        } elseif (strlen($new_username) < 3) {
            $error = "Username must be at least 3 characters.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Check if username already exists
            $exists = mysqli_query($conn, "SELECT id FROM admin WHERE username='$new_username'");
            if (mysqli_num_rows($exists) > 0) {
                $error = "Username '$new_username' already exists.";
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $plain_escaped = mysqli_real_escape_string($conn, $new_password);
                $insert = mysqli_query($conn, "INSERT INTO admin (username, password, plain_password) VALUES ('$new_username', '$hashed', '$plain_escaped')");
                if ($insert) {
                    $success = "Admin '$new_username' created successfully!";
                } else {
                    $error = "Error creating admin account.";
                }
            }
        }
    }
}

// ─── Reset Password ───
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed.";
    } else {
        $reset_id = (int)$_POST['admin_id'];
        $new_pw = trim($_POST['reset_password']);

        if (empty($new_pw) || strlen($new_pw) < 6) {
            $error = "New password must be at least 6 characters.";
        } else {
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $plain_escaped = mysqli_real_escape_string($conn, $new_pw);
            $update = mysqli_query($conn, "UPDATE admin SET password='$hashed', plain_password='$plain_escaped' WHERE id=$reset_id");
            if ($update) {
                $success = "Password reset successfully!";
            } else {
                $error = "Error resetting password.";
            }
        }
    }
}

// ─── Revoke Active Remember-Me Session ───
if (isset($_GET['revoke_token']) && is_numeric($_GET['revoke_token'])) {
    $tok_id = (int)$_GET['revoke_token'];
    mysqli_query($conn, "DELETE FROM admin_remember_tokens WHERE id=$tok_id");
    $success = "Active login session revoked successfully.";
}

// Fetch all admins
$admins = mysqli_query($conn, "SELECT * FROM admin ORDER BY id ASC");
$admin_count = mysqli_num_rows($admins);

// Fetch Active Remember-Me Sessions
$active_tokens = mysqli_query($conn, "SELECT * FROM admin_remember_tokens WHERE expires_at > NOW() ORDER BY id DESC");
$token_count = $active_tokens ? mysqli_num_rows($active_tokens) : 0;

// Fetch Recent Security Login Logs
$security_logs = mysqli_query($conn, "SELECT * FROM admin_login_logs ORDER BY id DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Management - Raj Kathiyawadi Mukhwash</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Outfit', 'Segoe UI', sans-serif;
        background: #0f1118;
        color: #e2e8f0;
        min-height: 100vh;
        transition: background 0.4s ease, color 0.4s ease;
    }
    [data-theme="light"] body,
    body[data-theme="light"] {
        background: #f1f5f9;
        color: #1e293b;
    }
    html[data-theme="light"] body {
        background: #f1f5f9;
        color: #1e293b;
    }

    .main-content {
        margin-left: 255px;
        padding: 32px;
        min-height: 100vh;
    }

    /* Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }
    .page-header h2 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    [data-theme="light"] .page-header h2 { color: #0f172a; }
    .page-header h2 i {
        color: #0066ff;
        font-size: 1.4rem;
    }
    .page-header .subtitle {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 400;
        margin-top: 4px;
    }

    /* ── Theme Toggle Button ── */
    .theme-toggle-btn {
        position: relative;
        width: 64px;
        height: 34px;
        border-radius: 50px;
        border: 2px solid rgba(255, 255, 255, 0.12);
        background: linear-gradient(135deg, #1a1d2e, #0f1118);
        cursor: pointer;
        padding: 0;
        transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        flex-shrink: 0;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3), inset 0 1px 2px rgba(255, 255, 255, 0.05);
    }
    .theme-toggle-btn:hover {
        border-color: rgba(0, 102, 255, 0.4);
        box-shadow: 0 4px 20px rgba(0, 102, 255, 0.2), inset 0 1px 2px rgba(255, 255, 255, 0.05);
        transform: scale(1.05);
    }
    .theme-toggle-btn:active {
        transform: scale(0.97);
    }
    .theme-toggle-knob {
        position: absolute;
        left: 3px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #334155, #475569);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        z-index: 2;
    }
    .theme-toggle-knob i {
        font-size: 14px;
        color: #fbbf24;
        transition: all 0.35s ease;
    }
    /* Icons inside the track */
    .theme-toggle-track {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 8px;
        pointer-events: none;
    }
    .theme-toggle-track .track-icon {
        font-size: 12px;
        transition: opacity 0.35s ease;
    }
    .theme-toggle-track .track-sun {
        color: #fbbf24;
        opacity: 0.3;
    }
    .theme-toggle-track .track-moon {
        color: #60a5fa;
        opacity: 1;
    }

    /* Light mode toggle styles */
    [data-theme="light"] .theme-toggle-btn {
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        border-color: #93c5fd;
        box-shadow: 0 2px 12px rgba(59, 130, 246, 0.15), inset 0 1px 2px rgba(255, 255, 255, 0.6);
    }
    [data-theme="light"] .theme-toggle-btn:hover {
        border-color: #60a5fa;
        box-shadow: 0 4px 20px rgba(59, 130, 246, 0.25), inset 0 1px 2px rgba(255, 255, 255, 0.6);
    }
    [data-theme="light"] .theme-toggle-knob {
        left: 33px;
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
    }
    [data-theme="light"] .theme-toggle-knob i {
        color: #ffffff;
    }
    [data-theme="light"] .theme-toggle-track .track-sun { opacity: 1; }
    [data-theme="light"] .theme-toggle-track .track-moon { opacity: 0.3; }

    .theme-toggle-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .theme-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    /* Cards */
    .glass-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }
    .glass-card:hover {
        border-color: rgba(0, 102, 255, 0.2);
    }
    [data-theme="light"] .glass-card {
        background: #ffffff;
        border-color: #e2e8f0;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    }
    [data-theme="light"] .glass-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 8px 30px rgba(59, 130, 246, 0.1);
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .card-title i {
        color: #0066ff;
    }
    [data-theme="light"] .card-title { color: #0f172a; }

    /* Form Styles */
    .form-label {
        font-size: 0.82rem;
        font-weight: 600;
        color: #94a3b8;
        letter-spacing: 0.3px;
        margin-bottom: 6px;
    }
    [data-theme="light"] .form-label { color: #475569; }
    .form-control {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border-radius: 10px;
        padding: 11px 16px;
        font-size: 0.9rem;
        font-family: 'Outfit', sans-serif;
        transition: all 0.3s ease;
    }
    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.25);
    }
    .form-control:focus {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(0, 102, 255, 0.5);
        box-shadow: 0 0 0 4px rgba(0, 102, 255, 0.12);
        color: #ffffff;
    }
    [data-theme="light"] .form-control {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }
    [data-theme="light"] .form-control::placeholder { color: #94a3b8; }
    [data-theme="light"] .form-control:focus {
        background: #ffffff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        color: #0f172a;
    }

    /* Create Button */
    .btn-create {
        background: linear-gradient(135deg, #0066ff, #4a6cf7);
        border: none;
        color: #fff;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 12px 28px;
        border-radius: 12px;
        letter-spacing: 0.3px;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 102, 255, 0.35);
        color: #fff;
    }
    .btn-create:active {
        transform: translateY(0);
    }

    /* Admin Table */
    .admin-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .admin-table thead th {
        background: rgba(255, 255, 255, 0.04);
        color: #94a3b8;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 14px 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .admin-table thead th:first-child { border-radius: 10px 0 0 0; }
    .admin-table thead th:last-child { border-radius: 0 10px 0 0; }
    [data-theme="light"] .admin-table thead th {
        background: #f1f5f9;
        color: #475569;
        border-bottom-color: #e2e8f0;
    }

    .admin-table tbody tr {
        transition: all 0.25s ease;
    }
    .admin-table tbody tr:hover {
        background: rgba(0, 102, 255, 0.05);
    }
    [data-theme="light"] .admin-table tbody tr:hover {
        background: #eff6ff;
    }
    .admin-table tbody td {
        padding: 16px 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 0.9rem;
        vertical-align: middle;
    }
    [data-theme="light"] .admin-table tbody td {
        border-bottom-color: #f1f5f9;
        color: #334155;
    }

    /* Admin Avatar */
    .admin-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0066ff, #7c3aed);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .admin-name-cell {
        display: flex;
        align-items: center;
    }
    .admin-name-text {
        font-weight: 600;
        color: #e2e8f0;
    }
    [data-theme="light"] .admin-name-text { color: #1e293b; }

    /* Badge */
    .badge-you {
        background: rgba(0, 102, 255, 0.15);
        color: #60a5fa;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        margin-left: 10px;
        letter-spacing: 0.5px;
    }
    [data-theme="light"] .badge-you {
        background: #dbeafe;
        color: #2563eb;
    }
    .badge-role {
        background: rgba(124, 58, 237, 0.15);
        color: #a78bfa;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
    }
    .badge-role.main {
        background: rgba(0, 102, 255, 0.15);
        color: #60a5fa;
    }
    [data-theme="light"] .badge-role {
        background: #ede9fe;
        color: #7c3aed;
    }
    [data-theme="light"] .badge-role.main {
        background: #dbeafe;
        color: #2563eb;
    }

    /* Delete Button */
    .btn-delete {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #f87171;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 7px 16px;
        border-radius: 8px;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    .btn-delete:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        transform: translateY(-1px);
    }
    .btn-delete.disabled {
        opacity: 0.3;
        pointer-events: none;
    }
    [data-theme="light"] .btn-delete {
        background: #fef2f2;
        border-color: #fecaca;
        color: #dc2626;
    }
    [data-theme="light"] .btn-delete:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    /* Reset Password Button */
    .btn-reset-pw {
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.2);
        color: #fbbf24;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 7px 16px;
        border-radius: 8px;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        cursor: pointer;
    }
    .btn-reset-pw:hover {
        background: rgba(245, 158, 11, 0.2);
        color: #fde68a;
        transform: translateY(-1px);
    }
    [data-theme="light"] .btn-reset-pw {
        background: #fffbeb;
        border-color: #fde68a;
        color: #d97706;
    }
    [data-theme="light"] .btn-reset-pw:hover {
        background: #fef3c7;
        color: #b45309;
    }

    /* Alerts */
    .alert-glass-success {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.25);
        color: #4ade80;
        border-radius: 12px;
        padding: 14px 20px;
        font-size: 0.88rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        animation: slideIn 0.3s ease-out;
    }
    [data-theme="light"] .alert-glass-success {
        background: #f0fdf4;
        border-color: #86efac;
        color: #16a34a;
    }
    .alert-glass-danger {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.25);
        color: #f87171;
        border-radius: 12px;
        padding: 14px 20px;
        font-size: 0.88rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        animation: slideIn 0.3s ease-out;
    }
    [data-theme="light"] .alert-glass-danger {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #dc2626;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Stats Row */
    .stats-row {
        display: flex;
        gap: 16px;
        margin-bottom: 28px;
    }
    .stat-pill {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 16px 24px;
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 1;
        transition: all 0.3s ease;
    }
    .stat-pill:hover {
        border-color: rgba(0, 102, 255, 0.2);
        transform: translateY(-2px);
    }
    [data-theme="light"] .stat-pill {
        background: #ffffff;
        border-color: #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    [data-theme="light"] .stat-pill:hover {
        border-color: #93c5fd;
        box-shadow: 0 4px 16px rgba(59, 130, 246, 0.1);
    }
    .stat-pill-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .stat-pill-icon.blue {
        background: rgba(0, 102, 255, 0.12);
        color: #60a5fa;
    }
    .stat-pill-icon.purple {
        background: rgba(124, 58, 237, 0.12);
        color: #a78bfa;
    }
    [data-theme="light"] .stat-pill-icon.blue {
        background: #dbeafe;
        color: #2563eb;
    }
    [data-theme="light"] .stat-pill-icon.purple {
        background: #ede9fe;
        color: #7c3aed;
    }
    .stat-pill-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: #ffffff;
    }
    [data-theme="light"] .stat-pill-value { color: #0f172a; }
    .stat-pill-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
    }

    /* Password Toggle */
    .input-group .btn-toggle-pw {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-left: none;
        color: rgba(255, 255, 255, 0.5);
        border-radius: 0 10px 10px 0;
        padding: 0 14px;
        transition: all 0.25s ease;
    }
    .input-group .btn-toggle-pw:hover {
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.8);
    }
    .input-group .form-control {
        border-radius: 10px 0 0 10px;
    }
    [data-theme="light"] .input-group .btn-toggle-pw {
        background: #f1f5f9;
        border-color: #cbd5e1;
        border-left: none;
        color: #64748b;
    }
    [data-theme="light"] .input-group .btn-toggle-pw:hover {
        background: #e2e8f0;
        color: #334155;
    }

    /* Password Cell */
    .pw-cell {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .pw-value {
        font-family: 'Courier New', monospace;
        font-size: 0.88rem;
        color: #94a3b8;
        letter-spacing: 1px;
        min-width: 80px;
    }
    .pw-value.revealed {
        color: #fbbf24;
        letter-spacing: 0;
        background: rgba(245, 158, 11, 0.08);
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid rgba(245, 158, 11, 0.15);
    }
    [data-theme="light"] .pw-value { color: #64748b; }
    [data-theme="light"] .pw-value.revealed {
        color: #b45309;
        background: #fffbeb;
        border-color: #fde68a;
    }
    .btn-show-pw {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }
    .btn-show-pw:hover {
        background: rgba(0, 102, 255, 0.12);
        border-color: rgba(0, 102, 255, 0.25);
        color: #60a5fa;
    }
    .btn-show-pw.active {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.25);
        color: #fbbf24;
    }
    [data-theme="light"] .btn-show-pw {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #475569;
    }
    [data-theme="light"] .btn-show-pw:hover {
        background: #dbeafe;
        border-color: #93c5fd;
        color: #2563eb;
    }
    [data-theme="light"] .btn-show-pw.active {
        background: #fef3c7;
        border-color: #fde68a;
        color: #b45309;
    }

    /* No password badge */
    .pw-na {
        color: #475569;
        font-size: 0.78rem;
        font-style: italic;
    }

    /* Action buttons group */
    .action-btns {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* Responsive */
    @media (max-width: 900px) {
        .main-content {
            margin-left: 0;
            padding: 20px 16px;
        }
        .stats-row {
            flex-direction: column;
        }
        .admin-table {
            display: block;
            overflow-x: auto;
        }
    }

    /* Delete Confirm Modal */
    .modal-content {
        background: #1a1d2e;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        color: #e2e8f0;
    }
    .modal-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .modal-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .btn-close {
        filter: invert(1);
    }
    [data-theme="light"] .modal-content {
        background: #ffffff;
        border-color: #e2e8f0;
        color: #1e293b;
    }
    [data-theme="light"] .modal-header { border-bottom-color: #e2e8f0; }
    [data-theme="light"] .modal-footer { border-top-color: #e2e8f0; }
    [data-theme="light"] .btn-close { filter: none; }

    /* Reset Password Modal */
    .modal-content .form-control {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.12);
    }
    .modal-content .form-control:focus {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(0, 102, 255, 0.5);
    }
    .modal-content .form-label {
        color: #94a3b8;
    }
    [data-theme="light"] .modal-content .form-control {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }
    [data-theme="light"] .modal-content .form-control:focus {
        background: #ffffff;
        border-color: #3b82f6;
    }
    [data-theme="light"] .modal-content .form-label { color: #475569; }

    /* Warning banner */
    .security-banner {
        background: rgba(245, 158, 11, 0.08);
        border: 1px solid rgba(245, 158, 11, 0.2);
        border-radius: 12px;
        padding: 12px 18px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.82rem;
        color: #fbbf24;
    }
    .security-banner i {
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    [data-theme="light"] .security-banner {
        background: #fffbeb;
        border-color: #fde68a;
        color: #b45309;
    }

    /* ID column light */
    [data-theme="light"] .admin-table tbody td[style] {
        color: #94a3b8 !important;
    }

    /* Protected badge light */
    [data-theme="light"] .btn-delete.disabled {
        color: #94a3b8;
        background: #f1f5f9;
        border-color: #e2e8f0;
    }
</style>

<!-- Apply saved theme before paint to avoid flash -->
<script>
(function() {
    var t = localStorage.getItem('admin_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
})();
</script>

</head>
<body>

<?php include('includes/sidebar.php'); ?>

<div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2><i class="bi bi-shield-lock"></i> Admin Management</h2>
            <div class="subtitle">Create, manage and view administrator accounts & credentials</div>
        </div>
        <div class="theme-toggle-wrapper">
            <span class="theme-label" id="themeLabel">Dark</span>
            <button class="theme-toggle-btn" id="themeToggleBtn" title="Toggle Light / Dark Theme" aria-label="Toggle theme">
                <div class="theme-toggle-track">
                    <span class="track-icon track-sun"><i class="bi bi-sun-fill"></i></span>
                    <span class="track-icon track-moon"><i class="bi bi-moon-stars-fill"></i></span>
                </div>
                <div class="theme-toggle-knob">
                    <i class="bi bi-moon-stars-fill"></i>
                </div>
            </button>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($success)) { ?>
        <div class="alert-glass-success">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php } ?>
    <?php if (!empty($error)) { ?>
        <div class="alert-glass-danger">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php } ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-pill">
            <div class="stat-pill-icon blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="stat-pill-value"><?php echo $admin_count; ?></div>
                <div class="stat-pill-label">Total Admins</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="stat-pill-icon purple">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div>
                <div class="stat-pill-value"><?php echo htmlspecialchars($_SESSION['admin']); ?></div>
                <div class="stat-pill-label">Logged In As</div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Create New Admin Form -->
        <div class="col-lg-5">
            <div class="glass-card">
                <div class="card-title">
                    <i class="bi bi-person-plus-fill"></i> Create New Admin
                </div>

                <form method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="create_admin">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="new_username" class="form-control" placeholder="Enter admin username" required minlength="3" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="newPassword" class="form-control" placeholder="Minimum 6 characters" required minlength="6" autocomplete="new-password">
                            <button class="btn btn-toggle-pw" type="button" onclick="togglePw('newPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Re-enter password" required minlength="6" autocomplete="new-password">
                            <button class="btn btn-toggle-pw" type="button" onclick="togglePw('confirmPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-create w-100">
                        <i class="bi bi-plus-circle-fill"></i> Create Admin Account
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Admins List -->
        <div class="col-lg-7">
            <div class="glass-card">
                <div class="card-title">
                    <i class="bi bi-people-fill"></i> All Admin Accounts
                </div>

                <!-- Security Notice -->
                <div class="security-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Passwords are visible only to the main administrator. Click <strong>"Show"</strong> to reveal credentials.</span>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Admin</th>
                            <th>Password</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($admin_count > 0) {
                            // Reset pointer since we already counted
                            mysqli_data_seek($admins, 0);
                            while ($row = mysqli_fetch_assoc($admins)) {
                                $is_self = ($row['username'] === $_SESSION['admin']);
                                $initial = strtoupper(substr($row['username'], 0, 1));
                                $has_pw = !empty($row['plain_password'] ?? '');
                                $role = $row['role'] ?? 'subadmin';
                        ?>
                        <tr>
                            <td style="color: #64748b; font-weight: 600;">#<?php echo $row['id']; ?></td>
                            <td>
                                <div class="admin-name-cell">
                                    <div class="admin-avatar"><?php echo $initial; ?></div>
                                    <span class="admin-name-text"><?php echo htmlspecialchars($row['username']); ?></span>
                                    <?php if ($is_self) { ?>
                                        <span class="badge-you">YOU</span>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <div class="pw-cell">
                                    <?php if ($has_pw) { ?>
                                        <span class="pw-value" id="pw-<?php echo $row['id']; ?>">••••••••</span>
                                        <button class="btn-show-pw" onclick="togglePassword(<?php echo $row['id']; ?>, this)">
                                            <i class="bi bi-eye"></i> Show
                                        </button>
                                    <?php } else { ?>
                                        <span class="pw-na"><i class="bi bi-dash-circle"></i> Not stored</span>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-role <?php echo $role === 'admin' ? 'main' : ''; ?>">
                                    <?php echo $role === 'admin' ? 'Main Admin' : 'Sub Admin'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($is_self) { ?>
                                        <span class="btn-delete disabled"><i class="bi bi-lock-fill"></i> Protected</span>
                                    <?php } else { ?>
                                        <button class="btn-reset-pw" onclick="openResetModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username'], ENT_QUOTES); ?>')">
                                            <i class="bi bi-key-fill"></i> Reset
                                        </button>
                                        <a href="#" class="btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>')">
                                            <i class="bi bi-trash3-fill"></i> Delete
                                        </a>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; color:#64748b; padding:30px;'>No admin accounts found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Security Audit Logs & Active Sessions Section -->
    <div class="row g-4 mt-2">
        <!-- Recent Login Audit Log -->
        <div class="col-lg-7">
            <div class="glass-card">
                <div class="card-title">
                    <i class="bi bi-shield-check text-primary"></i> Recent Login Security Audit Log
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Admin User</th>
                                <th>IP Address</th>
                                <th>Status</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($security_logs && mysqli_num_rows($security_logs) > 0) {
                                while ($slog = mysqli_fetch_assoc($security_logs)) {
                                    $st = $slog['status'];
                                    $st_class = ($st === 'SUCCESS') ? 'bg-success text-white' : (($st === 'BLOCKED') ? 'bg-danger text-white' : 'bg-warning text-dark');
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($slog['username']); ?></strong></td>
                                <td style="font-family: monospace; font-size: 0.85rem; color: #94a3b8;"><?php echo htmlspecialchars($slog['ip_address']); ?></td>
                                <td>
                                    <span class="badge <?php echo $st_class; ?>" style="font-size: 0.72rem; padding: 4px 8px; border-radius: 12px;">
                                        <?php echo $st; ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.8rem; color: #94a3b8;"><?php echo date('d M Y, h:i A', strtotime($slog['created_at'])); ?></td>
                            </tr>
                            <?php } } else { ?>
                            <tr><td colspan="4" style="text-align:center; color:#64748b; padding:20px;">No login security logs recorded yet.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Active Remember-Me Sessions -->
        <div class="col-lg-5">
            <div class="glass-card">
                <div class="card-title">
                    <i class="bi bi-key-fill text-warning"></i> Active Remember-Me Sessions
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Admin User</th>
                                <th>Expires At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($active_tokens && mysqli_num_rows($active_tokens) > 0) {
                                while ($tok = mysqli_fetch_assoc($active_tokens)) {
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tok['username']); ?></strong></td>
                                <td style="font-size: 0.8rem; color: #94a3b8;"><?php echo date('d M Y, h:i A', strtotime($tok['expires_at'])); ?></td>
                                <td>
                                    <a href="manage_admins.php?revoke_token=<?php echo $tok['id']; ?>" class="btn-delete" style="font-size: 0.75rem; padding: 4px 10px;" onclick="return confirm('Revoke this session token?')">
                                        <i class="bi bi-x-circle-fill"></i> Revoke
                                    </a>
                                </td>
                            </tr>
                            <?php } } else { ?>
                            <tr><td colspan="3" style="text-align:center; color:#64748b; padding:20px;">No active persistent sessions.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete admin <strong id="deleteAdminName"></strong>? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-sm btn-danger" style="border-radius:8px;">
                    <i class="bi bi-trash3-fill me-1"></i> Yes, Delete
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-key-fill me-2" style="color: #fbbf24;"></i> Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="admin_id" id="resetAdminId">
                    
                    <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 16px;">
                        Set a new password for <strong id="resetAdminName" style="color: #60a5fa;"></strong>
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" name="reset_password" id="resetPasswordInput" class="form-control" placeholder="Minimum 6 characters" required minlength="6" autocomplete="new-password">
                            <button class="btn btn-toggle-pw" type="button" onclick="togglePw('resetPasswordInput', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
                    <button type="submit" class="btn btn-sm" style="border-radius:8px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; font-weight: 600;">
                        <i class="bi bi-check-circle-fill me-1"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password toggle for form inputs
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// Show/Hide password in table (fetched via AJAX — never in HTML source)
function togglePassword(id, btn) {
    const pwEl = document.getElementById('pw-' + id);
    
    if (btn.classList.contains('active')) {
        // Hide
        pwEl.textContent = '••••••••';
        pwEl.classList.remove('revealed');
        btn.innerHTML = '<i class="bi bi-eye"></i> Show';
        btn.classList.remove('active');
    } else {
        // Fetch password from server
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Loading...';
        
        fetch('get_admin_password.php?id=' + id)
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                if (data.password) {
                    pwEl.textContent = data.password;
                    pwEl.classList.add('revealed');
                    btn.innerHTML = '<i class="bi bi-eye-slash"></i> Hide';
                    btn.classList.add('active');
                    
                    // Auto-hide after 10 seconds
                    setTimeout(() => {
                        if (btn.classList.contains('active')) {
                            pwEl.textContent = '••••••••';
                            pwEl.classList.remove('revealed');
                            btn.innerHTML = '<i class="bi bi-eye"></i> Show';
                            btn.classList.remove('active');
                        }
                    }, 10000);
                } else {
                    btn.innerHTML = '<i class="bi bi-eye"></i> Show';
                    alert(data.error || 'Could not fetch password.');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-eye"></i> Show';
                alert('Network error. Please try again.');
            });
    }
}

// Delete confirmation
function confirmDelete(id, name) {
    document.getElementById('deleteAdminName').textContent = name;
    document.getElementById('deleteConfirmBtn').href = 'manage_admins.php?delete=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Reset password modal
function openResetModal(id, name) {
    document.getElementById('resetAdminId').value = id;
    document.getElementById('resetAdminName').textContent = name;
    document.getElementById('resetPasswordInput').value = '';
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}

// ── Theme Toggle ──
(function() {
    const btn = document.getElementById('themeToggleBtn');
    const label = document.getElementById('themeLabel');
    const knobIcon = btn.querySelector('.theme-toggle-knob i');

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('admin_theme', theme);
        if (theme === 'light') {
            label.textContent = 'Light';
            knobIcon.className = 'bi bi-sun-fill';
        } else {
            label.textContent = 'Dark';
            knobIcon.className = 'bi bi-moon-stars-fill';
        }
    }

    // Set initial label from saved theme
    var saved = localStorage.getItem('admin_theme') || 'dark';
    applyTheme(saved);

    btn.addEventListener('click', function() {
        var current = document.documentElement.getAttribute('data-theme') || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    });
})();
</script>

</body>
</html>
