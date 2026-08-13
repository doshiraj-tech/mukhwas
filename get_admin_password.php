<?php
/**
 * AJAX endpoint: Fetch admin plain password
 * Only accessible by the main admin.
 * Returns JSON: { "password": "..." } or { "error": "..." }
 */
include("../config/db.php");
require_once('auth_guard.php');

header('Content-Type: application/json');

// Only main admin can access
if (!is_main_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit();
}

// Validate request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid admin ID.']);
    exit();
}

$admin_id = (int)$_GET['id'];

$query = mysqli_query($conn, "SELECT plain_password FROM admin WHERE id=$admin_id");
if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    $pw = $row['plain_password'] ?? '';
    if (!empty($pw)) {
        echo json_encode(['password' => $pw]);
    } else {
        echo json_encode(['error' => 'Password not stored for this admin.']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Admin not found.']);
}
