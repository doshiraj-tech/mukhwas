<?php
include("../config/db.php");
require_once('auth_guard.php');


if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    // Retrieve the product image filename to delete it from disk
    $img_query = mysqli_query($conn, "SELECT image FROM products WHERE id='$product_id'");
    if (mysqli_num_rows($img_query) > 0) {
        $row = mysqli_fetch_assoc($img_query);
        $image_name = $row['image'];
        
        // Delete the image file if it's not a default image
        $default_images = ['roasted-saunf.jpg', 'ayurvedic-mukhwas.jpg', 'sweet-mukhwas.jpg'];
        if (!empty($image_name) && !in_array($image_name, $default_images)) {
            $image_path = "../assets/uploads/" . $image_name;
            if (file_exists($image_path)) {
                @unlink($image_path);
            }
        }
    }

    // Delete the product from database
    $delete = mysqli_query($conn, "DELETE FROM products WHERE id='$product_id'");
}

header("Location: products.php");
exit();
?>
