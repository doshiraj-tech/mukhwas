<?php
include("config/db.php");

$base_path = "";

// Get active category filter
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if ($category_id > 0) {
    $products_query = mysqli_query($conn, "SELECT p.* FROM products p JOIN product_categories pc ON p.id = pc.product_id WHERE pc.category_id = '$category_id' ORDER BY p.id DESC");
    $current_category_query = mysqli_query($conn, "SELECT category_name FROM categories WHERE id = '$category_id'");
    $current_category = mysqli_fetch_assoc($current_category_query);
    $page_title = $current_category ? $current_category['category_name'] : "Shop All";
} else {
    $products_query = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
    $page_title = "Shop All Products";
}

$categories_query = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore our wide range of premium quality mouth fresheners, roasted saunf, sweet mukhwas, and digestive blends.">
    <title><?php echo $page_title; ?> - Raj Kathiyawadi Mukhwash</title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="fade-in">

<?php include("includes/navbar.php"); ?>

<!-- Shop Header Banner -->
<section class="subpage-banner text-center py-5 mb-5">
    <div class="container py-3">
        <h1 class="display-4 fw-bold text-white mb-2"><?php echo $page_title; ?></h1>
        <p class="text-white-50 fs-5 mb-0">Indulge in the finest selection of authentic Indian mouth fresheners</p>
    </div>
</section>

<!-- Shop Layout -->
<div class="container pb-5">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card filter-card border-0 shadow-sm p-4">
                <h4 class="fw-bold mb-4 text-primary">Categories</h4>
                <div class="list-group list-group-flush">
                    <a href="shop.php" class="list-group-item list-group-item-action border-0 px-0 py-2 d-flex justify-content-between align-items-center <?php echo $category_id == 0 ? 'active fw-bold' : ''; ?>" id="cat-all">
                        <span>All Products</span>
                        <span class="badge bg-light text-dark rounded-pill">
                            <?php 
                            $count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
                            $count_row = mysqli_fetch_assoc($count_query);
                            echo $count_row['total'];
                            ?>
                        </span>
                    </a>
                    <?php while($cat = mysqli_fetch_assoc($categories_query)) { ?>
                        <a href="shop.php?category=<?php echo $cat['id']; ?>" class="list-group-item list-group-item-action border-0 px-0 py-2 d-flex justify-content-between align-items-center <?php echo $category_id == $cat['id'] ? 'active fw-bold' : ''; ?>" id="cat-<?php echo $cat['id']; ?>">
                            <span><?php echo $cat['category_name']; ?></span>
                            <span class="badge bg-light text-dark rounded-pill">
                                <?php 
                                $cat_id = $cat['id'];
                                $count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM product_categories WHERE category_id = '$cat_id'");
                                $count_row = mysqli_fetch_assoc($count_query);
                                echo $count_row['total'];
                                ?>
                            </span>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="text-muted mb-0">Showing <span class="fw-bold text-dark"><?php echo mysqli_num_rows($products_query); ?></span> products</p>
            </div>

            <div class="row">
                <?php
                if (mysqli_num_rows($products_query) > 0) {
                    while($row = mysqli_fetch_assoc($products_query)) {
                ?>
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="card product-card h-100 border-0 shadow-sm">
                            <div class="position-relative overflow-hidden">
                                <img id="shopImg-<?php echo $row['id']; ?>" src="assets/uploads/<?php echo $row['image']; ?>" class="card-img-top" alt="<?php echo $row['name']; ?>">
                                <span class="badge bg-primary position-absolute top-3 start-3">Fresh</span>
                            </div>
                            <div class="card-body d-flex flex-column p-4">
                                <h5 class="card-title fw-bold text-dark mb-2"><?php echo $row['name']; ?></h5>
                                
                                <?php 
                                $pid = $row['id'];
                                $rating_q = mysqli_query($conn, "SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(id) as review_count FROM reviews WHERE product_id = '$pid' AND status = 'approved'");
                                if($rating_q) {
                                    $rating_data = mysqli_fetch_assoc($rating_q);
                                    $count = $rating_data['review_count'];
                                    $avg = $count > 0 ? round($rating_data['avg_rating'], 1) : (float)$row['star_rating'];
                                } else {
                                    $avg = (float)$row['star_rating']; $count = 0;
                                }
                                ?>
                                <div class="mb-2 d-flex align-items-center gap-1" style="font-size:0.9rem;">
                                    <div class="text-warning">
                                        <?php for($s=1;$s<=5;$s++) echo $s<=$avg ? '★' : '☆'; ?>
                                    </div>
                                    <span class="text-muted ms-1">(<?=$count?>)</span>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block" style="font-size: 0.8rem;">MRP: <span class="text-decoration-line-through">₹<?php echo number_format($row['price'], 2); ?></span></small>
                                    <span class="text-accent fw-bold fs-5">₹<?php echo number_format($row['selling_price'], 2); ?></span>
                                    <?php 
                                    $discount_percent = $row['price'] > 0 ? round((1 - ($row['selling_price'] / $row['price'])) * 100) : 0;
                                    if($discount_percent > 0) {
                                    ?>
                                        <span class="badge bg-danger-subtle text-danger ms-2" style="font-size: 0.75rem;"><?php echo $discount_percent; ?>% OFF</span>
                                    <?php } ?>
                                </div>
                                <p class="card-text text-muted mb-4 fs-6">
                                    <?php echo substr($row['description'], 0, 80); ?>...
                                </p>
                                <div class="d-grid gap-2 mt-auto">
                                    <?php
                                    $wa_card_msg = rawurlencode("Hello Raj Kathiyawadi Mukhwash! 👋\nI would like to order: *" . $row['name'] . "* (₹" . number_format($row['selling_price'], 2) . ")");
                                    ?>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-add-to-cart flex-grow-1 py-2 rounded-pill" style="font-size: 0.85rem;" onclick="addToCartWithFly(document.getElementById('shopImg-<?php echo $row['id']; ?>'), <?php echo $row['id']; ?>, this, 'cart/add.php')">
                                            <i class="bi bi-cart-plus-fill me-1"></i> Add to Cart
                                        </button>
                                        <form action="cart/add.php" method="POST" class="flex-grow-1">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="buy_now" class="btn btn-accent w-100 py-2 rounded-pill fw-bold" style="font-size: 0.85rem;">
                                                <i class="bi bi-lightning-fill me-1"></i> Buy Now
                                            </button>
                                        </form>
                                    </div>
                                    <a href="product.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-view-details w-100 py-2 rounded-pill fw-bold" style="font-size: 0.85rem;">
                                        <i class="bi bi-eye-fill me-1"></i> View Details
                                    </a>
                                    <a href="https://wa.me/918140265904?text=<?= $wa_card_msg ?>" target="_blank" class="btn btn-success w-100 py-2 rounded-pill fw-bold d-flex align-items-center justify-content-center" style="background-color: #25d366 !important; border-color: #25d366 !important; font-size: 0.88rem;">
                                        <span class="wa-circle-badge me-2" style="width: 22px; height: 22px; font-size: 0.75rem;"><i class="bi bi-whatsapp"></i></span> Order via WhatsApp
                                    </a>
                                    <a href="javascript:void(0)" onclick="orderOnInstagram('<?php echo addslashes($row['name']); ?>', '<?php echo number_format($row['selling_price'], 2); ?>')" class="btn btn-danger w-100 py-2 rounded-pill fw-bold d-flex align-items-center justify-content-center" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%) !important; border: none !important; color: #fff !important; font-size: 0.88rem;">
                                        <span class="ig-circle-badge me-2" style="width: 22px; height: 22px; font-size: 0.75rem;"><i class="bi bi-instagram"></i></span> Order via Instagram
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                } else {
                ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-box-seam display-1 text-muted"></i>
                        <h4 class="mt-3 fw-bold">No Products Found</h4>
                        <p class="text-muted">We couldn't find any products matching your criteria.</p>
                        <a href="shop.php" class="btn btn-primary rounded-pill px-4 py-2 mt-3">Reset Filters</a>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<!-- Fly-to-Cart Animation -->
<script src="assets/js/fly-to-cart.js?v=<?php echo time(); ?>"></script>

</body>
</html>
