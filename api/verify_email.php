<?php
/**
 * AJAX Email Verification Endpoint
 * Returns JSON object containing Abstract API validation results.
 */

header('Content-Type: application/json; charset=utf-8');

$base_path = "../";
include_once($base_path . "config/db.php");
include_once($base_path . "includes/abstract_api.php");

// Get email from request (POST or GET)
$email = $_POST['email'] ?? $_GET['email'] ?? '';

if (empty($email)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Email parameter is required.',
        'result'  => null
    ]);
    exit();
}

// Execute Abstract API verification
$verification = verify_email_abstract($email);

echo json_encode([
    'status'     => $verification['is_valid'] ? 'success' : 'warning',
    'message'    => $verification['reason'],
    'is_valid'   => $verification['is_valid'],
    'is_fraud'   => $verification['is_fraud'],
    'details'    => $verification
]);
exit();
