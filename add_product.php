<?php
include("../config/db.php");
require_once('auth_guard.php');


// Add Product
if(isset($_POST['add_product']))
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

            // Secure image upload
            $upload_error = '';
            $new_filename = secure_upload_image(
                $_FILES['image'],
                realpath('../assets/uploads'),
                $upload_error,
                'product'
            );

            if ($new_filename === false) {
                echo "<script>alert('" . addslashes($upload_error) . "');</script>";
            } else {
                $image  = mysqli_real_escape_string($conn, $new_filename);
                $insert = mysqli_query($conn, "
                    INSERT INTO products
                        (category_id, name, price, selling_price, description, image, stock, star_rating)
                    VALUES
                        ('$category_id', '$name', '$price', '$selling_price', '$description', '$image', '$stock', '$star_rating')
                ");

                if ($insert) {
                    $product_id = mysqli_insert_id($conn);
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
<title>Add Product - Raj Kathiyawadi Mukhwash</title>
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
            <h4>Add New Product</h4>
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
                        ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="category_ids[]" value="<?php echo $cat['id']; ?>" id="cat_<?php echo $cat['id']; ?>">
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
                    <label class="form-label fw-bold">Product Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Royal Shahi Mukhwas" required>
                </div>

                <!-- Price -->
                <div class="mb-3">
                    <label class="form-label fw-bold">MRP (₹)</label>
                    <input type="number" step="0.01" name="price" id="price" class="form-control" placeholder="100.00" required>
                    <small class="text-muted">Enter the Maximum Retail Price.</small>
                </div>

                <!-- Selling Price -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Selling Price (₹)</label>
                    <input type="number" step="0.01" name="selling_price" id="selling_price" class="form-control" placeholder="70.00" required>
                    <small class="text-muted">Enter the Selling Price (autofilled to 30% off MRP by default).</small>
                </div>

                <!-- Stock -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Stock</label>
                    <input type="number" name="stock" class="form-control" placeholder="100" required>
                </div>

                <!-- Star Rating -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Initial Star Rating</label>
                    <input type="number" name="star_rating" step="0.1" min="0" max="5" value="5.0" class="form-control">
                    <small class="text-muted">Enter a default star rating (0.0 to 5.0) to show when there are no user reviews.</small>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" rows="4" class="form-control" placeholder="Product details, ingredients, taste profile..." required></textarea>
                </div>

                <!-- Image -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Product Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*" required>
                </div>

                <button type="submit" name="add_product" class="btn btn-success px-4 me-2">
                    <i class="bi bi-plus-circle me-1"></i>Add Product
                </button>
                <a href="products.php" class="btn btn-secondary px-4">
                    Back
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
