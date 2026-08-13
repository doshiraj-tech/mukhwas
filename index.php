<?php
include("config/db.php");

$base_path = "";

$products = mysqli_query(
    $conn,
    "SELECT * FROM products ORDER BY id DESC LIMIT 8"
);

$categories_list = mysqli_query($conn, "SELECT * FROM categories ORDER BY id ASC");

// Fetch Latest Posts
$posts_query = mysqli_query($conn, "
    SELECT p.*, c.name as category_name 
    FROM posts p 
    LEFT JOIN post_cats c ON p.cat_id = c.id 
    WHERE p.status = 'Published' 
    ORDER BY p.id DESC LIMIT 3
");

$cat_icons = [
    1 => "🌿",
    2 => "🍃",
    3 => "🍬",
    4 => "📦",
    5 => "🎁",
    6 => "🌶️"
];
$cat_descs = [
    1 => "Healthy, toasted seeds and grains blended with traditional spices.",
    2 => "Natural, herbal mixtures designed to improve digestion and wellness.",
    3 => "Traditional sweet blends with rose petals, fennel, and sugar glaze.",
    4 => "Compact packs, perfect for carrying on-the-go or sample tastings.",
    5 => "Value packs, ideal for families and sweet lovers who want more.",
    6 => "Spicy and tangy traditional digestifs containing ginger, salt, and spices."
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover the ultimate collection of premium traditional Indian mukhwas (mouth fresheners) crafted from fresh, natural ingredients.">
    <title>Raj Kathiyawadi Mukhwash - Premium Mouth Freshener Store</title>
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo file_exists('assets/css/style.css') ? filemtime('assets/css/style.css') : '1.0'; ?>">
</head>
<body class="fade-in">

<?php include("includes/navbar.php"); ?>

<!-- Hero Banner -->
<section class="hero mb-5">
    <div class="container py-5 slide-up">
        <h1 class="display-3 fw-bold mb-3">Raj Kathiyawadi Mukhwash Collection</h1>
        <p class="fs-3 fw-semibold mb-1 text-warning" style="letter-spacing: 0.02em;">પરંપરાગત સ્વાદનો અહેસાસ</p>
        <p class="fs-5 mb-4 opacity-90">(Experience Traditional Taste)</p>
        <a href="shop.php" class="btn btn-accent btn-lg px-5 py-3 rounded-pill">
            <i class="bi bi-bag-fill me-2"></i>Shop Now
        </a>
    </div>
</section>

<!-- Categories -->
<section class="container py-5">
    <div class="section-title">
        <h2>Shop By Category</h2>
        <p class="text-muted">Choose from our curated range of authentic mouth fresheners</p>
    </div>

    <div class="row">
        <?php
        if (mysqli_num_rows($categories_list) > 0) {
            while ($cat = mysqli_fetch_assoc($categories_list)) {
                $cat_id = $cat['id'];
                $icon = isset($cat_icons[$cat_id]) ? $cat_icons[$cat_id] : "🌿";
                $desc = isset($cat_descs[$cat_id]) ? $cat_descs[$cat_id] : "Premium quality mouth fresheners in custom pack sizes.";
        ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card category-card border-0 h-100 p-4">
                    <div class="card-body text-center d-flex flex-column h-100">
                        <div class="fs-1 text-primary mb-3"><span class="blinking-emoji"><?php echo $icon; ?></span></div>
                        <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($cat['category_name']); ?></h4>
                        <p class="text-muted mb-4"><?php echo htmlspecialchars($desc); ?></p>
                        <a href="category.php?id=<?php echo $cat['id']; ?>" class="btn btn-outline-primary mt-auto rounded-pill px-4">
                            View Products
                        </a>
                    </div>
                </div>
            </div>
        <?php
            }
        }
        ?>
    </div>
</section>

<!-- Promotional Banner Section -->
<section class="py-5 my-5" style="background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%); position: relative; overflow: hidden;">
    <!-- Abstract background blobs -->
    <div style="position: absolute; top: -50%; left: -10%; width: 500px; height: 500px; background: rgba(197, 160, 89, 0.1); border-radius: 50%; filter: blur(80px);"></div>
    <div style="position: absolute; bottom: -50%; right: -10%; width: 400px; height: 400px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; filter: blur(60px);"></div>
    
    <div class="container position-relative z-1">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-2 order-lg-1 text-center text-lg-start">
                <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill fw-bold" style="letter-spacing: 1px;">PREMIUM COLLECTION</span>
                <h2 class="display-4 fw-bold text-white mb-4" style="font-family: var(--font-heading);">Authentic Flavor,<br><span class="text-accent">Timeless Tradition.</span></h2>
                <p class="lead text-white-50 mb-4">Discover our complete range of retail and wholesale Mukhwas. Handcrafted with the finest natural ingredients to deliver a perfect, refreshing after-meal experience.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="shop.php" class="btn btn-accent btn-lg rounded-pill px-4 py-3 fw-bold shadow-sm">
                        <i class="bi bi-cart3 me-2"></i>Shop the Collection
                    </a>
                </div>
            </div>
            <div class="col-lg-6 order-1 order-lg-2 text-center">
                <div class="position-relative d-inline-block">
                    <!-- Decorative border behind image -->
                    <div class="position-absolute w-100 h-100 rounded-4" style="border: 2px solid var(--color-accent); top: 15px; left: 15px; z-index: 0; opacity: 0.5;"></div>
                    <img src="assets/uploads/promo_poster.jpg" alt="Raj Kathiyawadi All Types of Mukhwash" class="img-fluid rounded-4 shadow-lg position-relative z-1" style="max-height: 550px; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="container py-5">
    <div class="section-title">
        <h2>Featured Products</h2>
        <p class="text-muted">Hand-selected best sellers that our customers absolute adore</p>
    </div>

    <div class="row">
        <?php
        if (mysqli_num_rows($products) > 0) {
            while($row = mysqli_fetch_assoc($products)) {
        ?>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card product-card h-100 border-0">
                    <div class="position-relative overflow-hidden">
                        <a href="product.php?id=<?php echo $row['id']; ?>">
                            <img src="assets/uploads/<?php echo htmlspecialchars($row['image']); ?>" id="img-<?php echo $row['id']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>">
                        </a>
                        <span class="badge bg-primary position-absolute top-3 start-3">Fresh</span>
                        <button class="fav-btn" data-id="<?php echo $row['id']; ?>" title="Add to Favourites" aria-label="Add to Favourites">
                            <i class="bi bi-heart-fill fav-icon"></i>
                        </button>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold mb-2">
                            <a href="product.php?id=<?php echo $row['id']; ?>" class="text-dark text-decoration-none"><?php echo htmlspecialchars($row['name']); ?></a>
                        </h5>
                        
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

                        <div class="mb-2">
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
                            <?php echo substr($row['description'], 0, 70); ?>...
                        </p>
                        <div class="d-grid gap-2 mt-auto">
                            <?php
                            $wa_card_msg = rawurlencode("Hello Raj Kathiyawadi Mukhwash! 👋\nI would like to order: *" . $row['name'] . "* (₹" . number_format($row['selling_price'], 2) . ")");
                            ?>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-add-to-cart flex-grow-1 py-2 rounded-pill" style="font-size: 0.85rem;" onclick="addToCartWithFly(document.getElementById('img-<?php echo $row['id']; ?>'), <?php echo $row['id']; ?>, this, 'cart/add.php')">
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
            <div class="col-12 text-center py-4">
                <p class="text-muted fs-5">No featured products found. Add products from Admin Dashboard.</p>
            </div>
        <?php
        }
        ?>
    </div>
    
    <!-- More Shop Button -->
    <div class="text-center mt-4 mb-2">
        <a href="shop.php" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" style="background-image: none; background-color: var(--color-primary);">
            Shop More <i class="bi bi-arrow-right ms-2"></i>
        </a>
    </div>
</section>

<!-- Latest Posts -->
<?php if(isset($posts_query) && mysqli_num_rows($posts_query) > 0) { ?>
<section class="container py-5">
    <div class="section-title">
        <h2>Latest News & Articles</h2>
        <p class="text-muted">Stay updated with our newest recipes, offers, and stories</p>
    </div>
    
    <div class="row">
        <?php while($post = mysqli_fetch_assoc($posts_query)) { ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: var(--border-radius-md); overflow: hidden; transition: var(--transition-smooth);">
                <?php if(!empty($post['cover_image'])) { ?>
                <div style="height: 200px; overflow: hidden;">
                    <img src="assets/uploads/<?php echo $post['cover_image']; ?>" class="w-100 h-100" style="object-fit: cover; transition: transform 0.5s ease;" alt="<?php echo htmlspecialchars($post['title']); ?>" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                </div>
                <?php } ?>
                <div class="card-body p-4 d-flex flex-column">
                    <span class="badge bg-primary-subtle text-primary rounded-pill mb-3 align-self-start"><i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorised'); ?></span>
                    <h5 class="card-title fw-bold text-dark mb-3" style="line-height: 1.4;"><?php echo htmlspecialchars($post['title']); ?></h5>
                    <p class="card-text text-muted mb-4 flex-grow-1" style="font-size: 0.95rem;">
                        <?php echo htmlspecialchars(substr(strip_tags($post['body']), 0, 110)) . '...'; ?>
                    </p>
                    <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary rounded-pill w-100 mt-auto">Read Article <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>

<!-- Why Choose Us -->
<section class="why-choose-section py-5 mb-0">
    <!-- Decorative background pattern (visible in dark theme) -->
    <div class="why-choose-pattern"></div>
    
    <div class="container my-4 position-relative">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3 why-choose-title" style="font-family: var(--font-heading);">Why Choose Raj Kathiyawadi Mukhwash?</h2>
            <p class="fs-5 mb-0 why-choose-subtitle">We ensure the highest quality and traditional flavors in every batch</p>
            <div class="why-choose-divider"></div>
        </div>

        <div class="row text-center mt-4 g-4">
            <div class="col-md-4 mb-4">
                <div class="p-4 h-100 why-choose-card">
                    <div class="mb-3 why-choose-icon-box">
                        <i class="bi bi-patch-check-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-2 why-choose-card-title">Premium Ingredients</h4>
                    <p class="mb-0 why-choose-card-text">We use only high-grade seeds, herbs, and sweeteners with no artificial colors.</p>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="p-4 h-100 why-choose-card">
                    <div class="mb-3 why-choose-icon-box">
                        <i class="bi bi-truck fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-2 why-choose-card-title">Fast Delivery</h4>
                    <p class="mb-0 why-choose-card-text">Secure and prompt shipping across major towns and cities in India.</p>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="p-4 h-100 why-choose-card">
                    <div class="mb-3 why-choose-icon-box">
                        <i class="bi bi-shield-heart-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-2 why-choose-card-title">100% Hygienic</h4>
                    <p class="mb-0 why-choose-card-text">Prepared and packed carefully in a sanitized facility to maintain optimal freshness.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include("includes/footer.php"); ?>

<!-- Favourites Script -->
<script>
(function () {
    const STORAGE_KEY = 'mukhwas_favourites';

    function getFavs() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; }
        catch(e) { return []; }
    }
    function saveFavs(arr) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(arr));
    }

    function initButtons() {
        const favs = getFavs();
        document.querySelectorAll('.fav-btn').forEach(btn => {
            const id = btn.dataset.id;
            if (favs.includes(id)) btn.classList.add('active');

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const current = getFavs();
                const idx = current.indexOf(id);
                if (idx === -1) {
                    current.push(id);
                    btn.classList.add('active');
                } else {
                    current.splice(idx, 1);
                    btn.classList.remove('active');
                }
                saveFavs(current);

                /* Pop animation */
                const icon = btn.querySelector('.fav-icon');
                icon.style.animation = 'none';
                icon.offsetHeight; /* reflow */
                icon.style.animation = 'favPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initButtons);
})();
</script>

</body>
</html>