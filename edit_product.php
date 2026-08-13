<?php
include("../config/db.php");
require_once('auth_guard.php');


// Check Product ID
if(!isset($_GET['id']))
{
    header("Location: products.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Fetch Product Details
$product_query = mysqli_query($conn, "SELECT * FROM products WHERE id='$product_id'");
if(mysqli_num_rows($product_query) == 0)
{
    header("Location: products.php");
    exit();
}

$product = mysqli_fetch_assoc($product_query);

// Fetch Categories selected for this product
$sel_cats = [];
$sel_query = mysqli_query($conn, "SELECT category_id FROM product_categories WHERE product_id='$product_id'");
while($r = mysqli_fetch_assoc($sel_query)) {
    $sel_cats[] = $r['category_id'];
}

// Update Product
if(isset($_POST['edit_product']))
{
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('Security token validation failed. Please try again.');</script>";
    } else {
        $category_ids  = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
        $name          = mysqli_real_escape_string($conn, trim($_POST['name']));
        $price         = (float)$_POST['price'];
        $selling_price = (float)$_POST['selling_price'];
        $description   = mysqli_real_escape_string($conn, trim($_POST['description']));
        $stock         = (int)$_POST['stock'];
        $star_rating   = isset($_POST['star_rating']) ? min(5.0, max(0.0, (float)$_POST['star_rating'])) : 0.0;

        if (empty($category_ids)) {
            echo "<script>alert('Please select at least one category');</script>";
        } else {
            $category_id = (int)$category_ids[0];

            // Handle image upload if a new file was selected
            if (isset($_FILES['image']['name']) && $_FILES['image']['name'] !== '') {
                $upload_error = '';
                $new_filename = secure_upload_image(
                    $_FILES['image'],
                    realpath('../assets/uploads'),
                    $upload_error,
                    'product'
                );

                if ($new_filename === false) {
                    echo "<script>alert('" . addslashes($upload_error) . "');</script>";
                    $update_query = '';
                } else {
                    $default_images = ['roasted-saunf.jpg', 'ayurvedic-mukhwas.jpg', 'sweet-mukhwas.jpg'];
                    if (!empty($product['image']) && !in_array($product['image'], $default_images) && $product['image'] !== $new_filename) {
                        $old_path = "../assets/uploads/" . $product['image'];
                        if (file_exists($old_path)) {
                            @unlink($old_path);
                        }
                    }
                    $image_esc    = mysqli_real_escape_string($conn, $new_filename);
                    $update_query = "
                        UPDATE products
                        SET category_id='$category_id', name='$name', price='$price',
                            selling_price='$selling_price', description='$description',
                            image='$image_esc', stock='$stock', star_rating='$star_rating'
                        WHERE id='$product_id'
                    ";
                }
            } else {
                $update_query = "
                    UPDATE products
                    SET category_id='$category_id', name='$name', price='$price',
                        selling_price='$selling_price', description='$description',
                        stock='$stock', star_rating='$star_rating'
                    WHERE id='$product_id'
                ";
            }

            if (!empty($update_query)) {
                $update = mysqli_query($conn, $update_query);
                if ($update) {
                    // Refresh category mappings
                    mysqli_query($conn, "DELETE FROM product_categories WHERE product_id='$product_id'");
                    foreach ($category_ids as $cat_id) {
                        $cat_id = (int)$cat_id;
                        mysqli_query($conn, "INSERT INTO product_categories (product_id, category_id) VALUES ('$product_id', '$cat_id')");
                    }
                    echo "<script>window.location.href='products.php';</script>";
                } else {
                    echo "<script>alert('Database Error');</script>";
                }
            }
        }
    }
}

// Fetch Categories
$categories = mysqli_query($conn,"SELECT * FROM categories");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Product - Raj Kathiyawadi Mukhwash</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6f9;
}

.main-content{
    margin-left:255px;
    padding:30px;
}

.card{
    border:none;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
}
</style>

</head>
<body>

<?php include('includes/sidebar.php'); ?>

<!-- Main Content -->
<div class="main-content">

    <div class="card">
        <div class="card-header bg-success text-white">
            <h4>Edit Product</h4>
        </div>

        <div class="card-body">

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <!-- Category -->
                <div class="mb-3">
                    <label class="form-label d-block fw-bold">Categories</label>
                    <div class="border rounded p-3 bg-white" style="max-height: 200px; overflow-y: auto;">
                        <?php
                        while($cat=mysqli_fetch_assoc($categories))
                        {
                            $checked = in_array($cat['id'], $sel_cats) ? "checked" : "";
                        ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="category_ids[]" value="<?php echo $cat['id']; ?>" id="cat_<?php echo $cat['id']; ?>" <?php echo $checked; ?>>
                            <label class="form-check-label text-dark mb-0" for="cat_<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </label>
                        </div>
                        <?php
                        }
                        ?>
                    </div>
                    <small class="text-muted">Select one or more categories that apply to this product.</small>
                </div>

                <!-- Product Name -->
                <div class="mb-3">
                    <label class="form-label">Product Name</label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($product['name']); ?>"
                        required>
                </div>

                <!-- Price -->
                <div class="mb-3">
                    <label class="form-label">MRP (₹)</label>

                    <input
                        type="number"
                        step="0.01"
                        name="price"
                        id="price"
                        class="form-control"
                        value="<?php echo htmlspecialchars($product['price']); ?>"
                        required>
                    <small class="text-muted">Enter the Maximum Retail Price.</small>
                </div>

                <!-- Selling Price -->
                <div class="mb-3">
                    <label class="form-label">Selling Price (₹)</label>

                    <input
                        type="number"
                        step="0.01"
                        name="selling_price"
                        id="selling_price"
                        class="form-control"
                        value="<?php echo htmlspecialchars($product['selling_price']); ?>"
                        required>
                    <small class="text-muted">Enter the Selling Price (autofilled to 30% off MRP by default).</small>
                </div>

                <!-- Stock -->
                <div class="mb-3">
                    <label class="form-label">Stock</label>

                    <input
                        type="number"
                        name="stock"
                        class="form-control"
                        value="<?php echo htmlspecialchars($product['stock']); ?>"
                        required>
                </div>

                <!-- Star Rating -->
                <div class="mb-3">
                    <label class="form-label">Initial Star Rating</label>

                    <input
                        type="number"
                        name="star_rating"
                        step="0.1"
                        min="0"
                        max="5"
                        value="<?php echo htmlspecialchars($product['star_rating']); ?>"
                        class="form-control">
                    <small class="text-muted">Enter a default star rating (0.0 to 5.0) to show when there are no user reviews.</small>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label">Description</label>

                    <textarea
                        name="description"
                        rows="5"
                        class="form-control"
                        required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <!-- Current Image -->
                <div class="mb-3">
                    <label class="form-label d-block">Current Product Image</label>
                    <img src="../assets/uploads/<?php echo $product['image']; ?>" class="img-thumbnail" style="max-height: 150px; max-width: 150px; object-fit: cover;" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>

                <!-- Image -->
                <div class="mb-3">
                    <label class="form-label">Update Product Image (Optional)</label>

                    <input
                        type="file"
                        name="image"
                        class="form-control"
                        accept="image/*">
                </div>

                <button
                    type="submit"
                    name="edit_product"
                    class="btn btn-success">

                    Update Product

                </button>

                <a href="products.php" class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('price');
    const sellingPriceInput = document.getElementById('selling_price');

    if (priceInput && sellingPriceInput) {
        priceInput.addEventListener('input', function() {
            const mrp = parseFloat(priceInput.value);
            if (!isNaN(mrp)) {
                sellingPriceInput.value = (mrp * 0.70).toFixed(2);
            } else {
                sellingPriceInput.value = '';
            }
        });
    }
});
</script>
</body>
</html>
