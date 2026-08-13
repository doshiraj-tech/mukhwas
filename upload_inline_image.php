<?php
require_once('auth_guard.php');

header('Content-Type: application/json');

if (isset($_FILES['inline_image'])) {
    $upload_error = '';
    $new_filename = secure_upload_image(
        $_FILES['inline_image'],
        realpath('../assets/uploads'),
        $upload_error,
        'inline'
    );

    if ($new_filename !== false) {
        echo json_encode([
            'success' => true,
            'url'     => '../assets/uploads/' . $new_filename,
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $upload_error]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'No file received.']);
