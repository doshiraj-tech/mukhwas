<?php
include("config/db.php");

$base_path = "";

// Check Product ID
if(!isset($_GET['id']))
{
    header("Location: shop.php");
    exit();
}

$product_id = (int)$_GET['id'];

$product_query = mysqli_query(
    $conn,
    "SELECT * FROM products WHERE id='$product_id'"
);

if(mysqli_num_rows($product_query) == 0)
{
    die("Product Not Found");
}

$product = mysqli_fetch_assoc($product_query);

// Fetch reviews + avg rating
$reviews_query = mysqli_query($conn, "SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = '$product_id' AND r.status = 'approved' ORDER BY r.created_at DESC");
$review_count = mysqli_num_rows($reviews_query);
$avg_rating = isset($product['star_rating']) ? (float)$product['star_rating'] : 0.0; // Default to admin set rating if exists
if ($review_count > 0) {
    $sum = 0;
    while($r = mysqli_fetch_assoc($reviews_query)) $sum += $r['rating'];
    $avg_rating = round($sum / $review_count, 1);
    mysqli_data_seek($reviews_query, 0); // reset pointer
}
$reviews = [];
while ($row = mysqli_fetch_assoc($reviews_query)) {
    $reviews[] = $row;
}

// Check if logged in user already reviewed
$user_reviewed = false;
$user_bought = false;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $dup_check = mysqli_query($conn, "SELECT id FROM reviews WHERE product_id='$product_id' AND user_id='$uid'");
    $user_reviewed = mysqli_num_rows($dup_check) > 0;
}

// Add To Cart or Buy Now
if(isset($_POST['add_to_cart']) || isset($_POST['buy_now']))
{
    if(!isset($_SESSION['cart']))
    {
        $_SESSION['cart'] = [];
    }

    if(!isset($_SESSION['cart'][$product_id]))
    {
        $_SESSION['cart'][$product_id] = 1;
    }
    else
    {
        $_SESSION['cart'][$product_id]++;
    }

    if (isset($_POST['buy_now'])) {
        header("Location: cart/checkout.php");
        exit();
    } else {
        $success = "Product added to cart successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Buy <?php echo htmlspecialchars($product['name']); ?> online. Premium quality, fresh mouth freshener at Raj Kathiyawadi Mukhwash.">
    <title><?php echo htmlspecialchars($product['name']); ?> - Raj Kathiyawadi Mukhwash</title>
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

<div class="container my-5 py-3">
    <div class="row g-5">
        <!-- Product Image -->
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <img id="productMainImage" src="assets/uploads/<?php echo $product['image']; ?>" class="img-fluid w-100" style="max-height: 480px; object-fit: cover;" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-6 col-lg-7">
            <div class="card profile-card border-0 p-4">
                <div class="card-body p-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill mb-3">Premium Quality</span>
                    <?php if ($avg_rating > 0): ?>
                    <div class="mb-2 d-flex align-items-center gap-2">
                        <div class="text-warning" style="font-size:1.1rem;">
                            <?php for($s=1;$s<=5;$s++) echo $s<=$avg_rating ? '★' : '☆'; ?>
                        </div>
                        <span class="fw-bold"><?= number_format($avg_rating, 1) ?></span>
                        <span class="text-muted small">(<?= $review_count ?> review<?= $review_count != 1 ? 's' : '' ?>)</span>
                    </div>
                    <?php endif; ?>
                    
                    <h1 class="fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="mb-4">
                        <small class="text-muted d-block fs-6 mb-1">MRP: <span class="text-decoration-line-through">₹<?php echo number_format($product['price'], 2); ?></span></small>
                        <span class="text-accent fw-bold fs-2">₹<?php echo number_format($product['selling_price'], 2); ?></span>
                        <?php 
                        $discount_percent = $product['price'] > 0 ? round((1 - ($product['selling_price'] / $product['price'])) * 100) : 0;
                        if($discount_percent > 0) {
                        ?>
                            <span class="badge bg-danger-subtle text-danger ms-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.85rem;"><?php echo $discount_percent; ?>% OFF</span>
                        <?php } ?>
                    </div>
                    
                    <hr class="bg-light opacity-50 mb-4">

                    <h5 class="fw-bold mb-3 text-dark">Description</h5>
                    <p class="text-muted leading-relaxed mb-4">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>

                    <div class="mb-4">
                        <span class="fw-bold text-dark me-2">Availability:</span>
                        <?php
                        if($product['stock'] > 0)
                        {
                            echo "<span class='badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold'>In Stock</span>";
                        }
                        else
                        {
                            echo "<span class='badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold'>Out of Stock</span>";
                        }
                        ?>
                    </div>

                    <!-- Top Star Rater -->
                    <div class="mb-4">
                        <span class="fw-bold text-dark d-block mb-2" style="font-size: 1.1rem;">Rate this product</span>
                        <div id="topStarPicker" class="d-flex gap-2 mb-2" style="font-size: 2.2rem; cursor: pointer; color: #C59B36;">
                            <i class="bi bi-star top-star" data-val="1"></i>
                            <i class="bi bi-star top-star" data-val="2"></i>
                            <i class="bi bi-star top-star" data-val="3"></i>
                            <i class="bi bi-star top-star" data-val="4"></i>
                            <i class="bi bi-star top-star" data-val="5"></i>
                        </div>
                        <button id="topSubmitBtn" class="btn btn-primary btn-sm rounded-pill px-4 d-none">Submit Rating</button>
                        <div id="topReviewAlert" class="mt-2 d-none small"></div>
                    </div>

                    <?php if(isset($success)) { ?>
                        <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div><?php echo $success; ?></div>
                        </div>
                    <?php } ?>

                    <?php 
                    $is_wishlisted = false;
                    if (isset($_SESSION['user_id'])) {
                        $uid = (int)$_SESSION['user_id'];
                        $wq = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id='$uid' AND product_id='$product_id'");
                        if ($wq && mysqli_num_rows($wq) > 0) {
                            $is_wishlisted = true;
                        }
                    }

                    if($product['stock'] > 0) { 
                        $wa_order_msg = rawurlencode("Hello Raj Kathiyawadi Mukhwash! 👋\nI would like to order:\n\n📦 *Product:* " . $product['name'] . "\n💰 *Price:* ₹" . number_format($product['selling_price'], 2) . "\n\nPlease share payment and delivery details.");
                    ?>
                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <button type="button" id="addToCartBtn" class="btn btn-primary btn-add-to-cart btn-lg px-4 py-3 rounded-pill" onclick="addToCartWithFly(document.getElementById('productMainImage'), <?php echo $product_id; ?>, this, 'cart/add.php')">
                                <i class="bi bi-cart-plus-fill me-2"></i>Add To Cart
                            </button>

                            <form method="POST" action="cart/add.php" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <button type="submit" name="buy_now" class="btn btn-accent btn-lg px-4 py-3 rounded-pill">
                                    <i class="bi bi-lightning-fill me-2"></i>Buy Now
                                </button>
                            </form>

                            <button type="button" id="productWishlistBtn" class="btn btn-outline-danger btn-lg px-4 py-3 rounded-pill" onclick="toggleProductWishlist(<?= $product_id ?>, this)">
                                <i class="bi <?= $is_wishlisted ? 'bi-heart-fill' : 'bi-heart' ?> me-2"></i>
                                <span class="wishlist-btn-text"><?= $is_wishlisted ? 'Saved in Wishlist' : 'Wishlist' ?></span>
                            </button>

                            <a href="https://wa.me/918140265904?text=<?= $wa_order_msg ?>" target="_blank" class="btn btn-success btn-lg px-4 py-3 rounded-pill d-inline-flex align-items-center" style="background-color: #25d366 !important; border-color: #25d366 !important;">
                                <span class="wa-circle-badge me-2"><i class="bi bi-whatsapp"></i></span>Order via WhatsApp
                            </a>

                            <a href="javascript:void(0)" onclick="orderOnInstagram('<?php echo addslashes($product['name']); ?>', '<?php echo number_format($product['selling_price'], 2); ?>')" class="btn btn-danger btn-lg px-4 py-3 rounded-pill d-inline-flex align-items-center" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%) !important; border: none !important; color: #fff !important;">
                                <span class="ig-circle-badge me-2"><i class="bi bi-instagram"></i></span>Order via Instagram
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<!-- ===== Reviews Section ===== -->
<div id="reviewsSection" class="container my-5 pb-5">
    <h2 class="fw-bold mb-4" style="font-size:1.6rem;">⭐ Customer Reviews</h2>

    <!-- Review Form -->
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="alert alert-info rounded-3">Please <a href="user/login.php">log in</a> to leave a review.</div>
    <?php elseif ($user_reviewed): ?>
        <div class="alert alert-success rounded-3">✅ You have already reviewed this product.</div>
    <?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="max-width:600px;">
        <h5 class="fw-bold mb-3">Write a Review</h5>
        <div id="reviewAlert" class="d-none mb-3"></div>
        <form id="reviewForm">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">Your Rating</label>
                <div id="starPicker" class="d-flex gap-1" style="font-size:2rem; cursor:pointer;">
                    <?php for($s=1;$s<=5;$s++): ?>
                    <span class="star" data-val="<?=$s?>" style="color:#ccc; transition:color .15s;">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="0">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Your Review <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="review_text" class="form-control rounded-3" rows="4" placeholder="Share your experience..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill">Submit Review</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Reviews List -->
    <?php if (count($reviews) === 0): ?>
        <p class="text-muted">No reviews yet. Be the first to review this product!</p>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach($reviews as $rev): ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold"><?= htmlspecialchars($rev['user_name']) ?></span>
                    <span class="text-muted small"><?= date('d M Y', strtotime($rev['created_at'])) ?></span>
                </div>
                <div class="text-warning mb-2" style="font-size:1.1rem;">
                    <?php for($s=1;$s<=5;$s++) echo $s<=$rev['rating'] ? '★' : '☆'; ?>
                </div>
                <?php if (!empty($rev['review_text'])): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars($rev['review_text']) ?></p>
                <?php endif; ?>
                
                <?php if (!empty($rev['admin_reply'])): ?>
                <div class="mt-3 p-3 rounded-3" style="background-color: #f8f9fa; border-left: 4px solid #198754;">
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-shield-fill-check text-success me-2"></i>
                        <span class="fw-bold text-dark" style="font-size:0.9rem;">Store Reply</span>
                    </div>
                    <p class="text-muted mb-0" style="font-size:0.95rem;"><?= nl2br(htmlspecialchars($rev['admin_reply'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Review JS -->
<script>
// Bottom star picker logic
const stars = document.querySelectorAll('#starPicker .star');
stars.forEach(s => {
    s.addEventListener('mouseover', () => highlight(s.dataset.val));
    s.addEventListener('mouseout',  () => highlight(document.getElementById('ratingInput').value));
    s.addEventListener('click',     () => { document.getElementById('ratingInput').value = s.dataset.val; highlight(s.dataset.val); });
});
function highlight(val) {
    stars.forEach(s => s.style.color = s.dataset.val <= val ? '#f59e0b' : '#ccc');
}

// Top star picker logic
const topStars = document.querySelectorAll('.top-star');
topStars.forEach(s => {
    s.addEventListener('mouseover', () => {
        let val = s.dataset.val;
        topStars.forEach(ts => {
            ts.className = ts.dataset.val <= val ? 'bi bi-star-fill top-star' : 'bi bi-star top-star';
        });
    });
    s.addEventListener('mouseout', () => {
        let currentRating = document.getElementById('ratingInput') ? document.getElementById('ratingInput').value : 0;
        topStars.forEach(ts => {
            ts.className = ts.dataset.val <= currentRating ? 'bi bi-star-fill top-star' : 'bi bi-star top-star';
        });
    });
    s.addEventListener('click', () => {
        let val = s.dataset.val;
        let ratingInput = document.getElementById('ratingInput');
        if (ratingInput) {
            ratingInput.value = val;
            highlight(val);
            
            // Keep top stars filled
            topStars.forEach(ts => {
                ts.className = ts.dataset.val <= val ? 'bi bi-star-fill top-star' : 'bi bi-star top-star';
            });
            
            // Show the top submit button
            const topSubmit = document.getElementById('topSubmitBtn');
            if(topSubmit) {
                topSubmit.classList.remove('d-none');
            }
        } else {
            // Scroll to the review section which will tell them to log in or that they already reviewed
            document.getElementById('reviewsSection').scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    });
});

const topSubmitBtn = document.getElementById('topSubmitBtn');
if (topSubmitBtn) {
    topSubmitBtn.addEventListener('click', async function() {
        const rating = document.getElementById('ratingInput').value;
        const productId = document.querySelector('input[name="product_id"]').value;
        
        const fd = new FormData();
        fd.append('product_id', productId);
        fd.append('rating', rating);
        fd.append('review_text', ''); // Empty text review
        
        topSubmitBtn.disabled = true;
        topSubmitBtn.textContent = 'Submitting...';
        
        const res = await fetch('submit_review.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        const alertBox = document.getElementById('topReviewAlert');
        alertBox.classList.remove('d-none');
        
        if (data.success) {
            alertBox.className = 'mt-2 small text-success fw-bold';
            alertBox.textContent = data.message;
            setTimeout(() => location.reload(), 1500);
        } else {
            alertBox.className = 'mt-2 small text-danger fw-bold';
            alertBox.textContent = data.message;
            topSubmitBtn.disabled = false;
            topSubmitBtn.textContent = 'Submit Rating';
        }
    });
}

const rf = document.getElementById('reviewForm');
if (rf) rf.addEventListener('submit', async function(e) {
    e.preventDefault();
    const rating = document.getElementById('ratingInput').value;
    if (parseInt(rating) === 0) { showAlert('warning', 'Please select a star rating.'); return; }
    const fd = new FormData(this);
    const res = await fetch('submit_review.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        showAlert('success', data.message);
        this.style.display = 'none';
        setTimeout(() => location.reload(), 1800);
    } else {
        showAlert('danger', data.message);
    }
});
function showAlert(type, msg) {
    const el = document.getElementById('reviewAlert');
    el.className = `alert alert-${type} rounded-3`;
    el.textContent = msg;
    el.classList.remove('d-none');
}

function toggleProductWishlist(productId, btnEl) {
    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('user/ajax_wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const icon = btnEl.querySelector('i');
            const text = btnEl.querySelector('.wishlist-btn-text');
            if (data.action === 'added') {
                icon.className = 'bi bi-heart-fill me-2 text-danger';
                if (text) text.textContent = 'Saved in Wishlist';
            } else {
                icon.className = 'bi bi-heart me-2';
                if (text) text.textContent = 'Wishlist';
            }
            const badge = document.querySelector('#navWishlistBtn .wishlist-badge');
            if (badge) badge.textContent = data.wishlist_count;
        } else if (data.require_login) {
            window.location.href = 'user/login.php';
        }
    });
}
</script>

<!-- Fly-to-Cart Animation -->
<script src="assets/js/fly-to-cart.js?v=<?php echo time(); ?>"></script>

</body>
</html>
