<?php
include("../config/db.php");
require_once('auth_guard.php');


$product_query = mysqli_query($conn,"
SELECT p.*, GROUP_CONCAT(c.category_name SEPARATOR ', ') AS category_name
FROM products p
LEFT JOIN product_categories pc ON p.id = pc.product_id
LEFT JOIN categories c ON pc.category_id = c.id
GROUP BY p.id
ORDER BY p.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Products - Raj Kathiyawadi Mukhwash</title>
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

.product-img{
    width:70px;
    height:70px;
    object-fit:cover;
    border-radius:8px;
}
</style>

</head>
<body>

<?php include('includes/sidebar.php'); ?>

<!-- Main Content -->
<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Products</h2>

        <a href="add_product.php" class="btn btn-success">
            Add New Product
        </a>
    </div>

    <div class="card">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-success">
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Rating</th>
                            <th>MRP</th>
                            <th>Selling Price (30% Off)</th>
                            <th>Stock</th>
                            <th width="180">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php
                    if(mysqli_num_rows($product_query) > 0)
                    {
                        while($row = mysqli_fetch_assoc($product_query))
                        {
                    ?>

                        <tr>
                            <td><?php echo $row['id']; ?></td>

                            <td>
                                <img src="../assets/uploads/<?php echo $row['image']; ?>"
                                     class="product-img">
                            </td>

                            <td><?php echo htmlspecialchars($row['name']); ?></td>

                            <td>
                                <?php echo $row['category_name']; ?>
                            </td>

                            <td class="text-warning fs-6">
                                <?php 
                                $sr = $row['star_rating'];
                                for($s=1;$s<=5;$s++) echo $s<=$sr ? '★' : '☆'; 
                                ?>
                                <br><small class="text-dark">(<?php echo number_format($sr, 1); ?>)</small>
                            </td>

                            <td>
                                ₹<?php echo number_format($row['price'],2); ?>
                            </td>
                            <td class="text-accent fw-bold">
                                ₹<?php echo number_format($row['selling_price'], 2); ?>
                            </td>

                            <td>
                                <?php echo $row['stock']; ?>
                            </td>

                            <td>

                                <a href="edit_product.php?id=<?php echo $row['id']; ?>"
                                   class="btn btn-primary btn-sm">
                                   Edit
                                </a>

                                <a href="delete_product.php?id=<?php echo $row['id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this product?')">
                                   Delete
                                </a>

                            </td>

                        </tr>

                    <?php
                        }
                    }
                    else
                    {
                        echo "<tr>
                                <td colspan='9' class='text-center'>
                                No Products Found
                                </td>
                              </tr>";
                    }
                    ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

</body>
</html>
