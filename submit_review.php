<?php
include("config/db.php");

header('Content-Type: application/json');





if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review.']);
    exit;
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Security token validation failed.']);
    exit;
}

if (!isset($_POST['product_id'], $_POST['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];
$rating     = (int)$_POST['rating'];
$review_text = mysqli_real_escape_string($conn, trim($_POST['review_text'] ?? ''));

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit;
}

// Check if user already reviewed this product
$dup = mysqli_query($conn, "SELECT id FROM reviews WHERE product_id='$product_id' AND user_id='$user_id'");
if (mysqli_num_rows($dup) > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this product.']);
    exit;
}



mysqli_query($conn,
    "INSERT INTO reviews (product_id, user_id, rating, review_text)
     VALUES ('$product_id', '$user_id', '$rating', '$review_text')"
);

echo json_encode(['success' => true, 'message' => 'Thank you! Your review has been submitted.']);
exit;
