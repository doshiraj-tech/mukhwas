<?php
// about.php doesn't include db.php — start session with secure params manually
$_sc = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$_sc,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
unset($_sc);
$base_path = "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Learn more about Raj Kathiyawadi Mukhwash. We offer authentic, premium-quality mouth fresheners prepared with traditional recipes and high-quality ingredients.">
    <title>About Us - Raj Kathiyawadi Mukhwash</title>
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

<!-- Banner -->
<section class="subpage-banner text-center py-5 mb-5">
    <div class="container py-3">
        <h1 class="display-4 fw-bold text-white mb-2">About Raj Kathiyawadi Mukhwash</h1>
        <p class="text-white-50 fs-5 mb-0">Experience Traditional Taste & Ultimate Freshness</p>
    </div>
</section>

<!-- About Content -->
<div class="container py-5">
    <div class="row align-items-center">
        <div class="col-md-6 mb-4 mb-md-0">
            <img src="assets/images/about.jpg" class="img-fluid rounded-4 shadow-lg border" alt="About Raj Kathiyawadi Mukhwash">
        </div>
        <div class="col-md-6 ps-md-5">
            <h2 class="fw-bold mb-4">Who We Are</h2>
            <p class="text-muted leading-relaxed">
                Raj Kathiyawadi Mukhwash is a premium online store dedicated to providing authentic and flavorful mouth fresheners. Our products are carefully crafted using time-honored traditional recipes and high-quality natural ingredients.
            </p>
            <p class="text-muted leading-relaxed">
                From crispy Roasted Mukhwas to herbal Ayurvedic and indulgent Sweet Mukhwas, we bring you an exquisite variety of fresh options that enhance taste and support digestion naturally.
            </p>
            <p class="text-muted leading-relaxed">
                Our mission is to preserve the rich tradition of Indian mouth fresheners while delivering freshness and premium hygiene to customers across the country.
            </p>
        </div>
    </div>
</div>

<!-- Features -->
<section class="bg-light py-5">
    <div class="container py-3">
        <div class="section-title">
            <h2>Why Choose Us</h2>
            <p class="text-muted">A legacy of taste, purity, and exceptional customer experience</p>
        </div>

        <div class="row mt-5">
            <div class="col-md-4 mb-4">
                <div class="feature-box text-center p-5 bg-white border-0">
                    <div class="fs-1 text-primary mb-3">🌿</div>
                    <h3 class="fw-bold fs-4 mb-3">Natural Ingredients</h3>
                    <p class="text-muted mb-0">Carefully selected premium seeds, herbs, and oils for the best health benefits and authentic taste.</p>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="feature-box text-center p-5 bg-white border-0">
                    <div class="fs-1 text-primary mb-3">🚚</div>
                    <h3 class="fw-bold fs-4 mb-3">Fast Delivery</h3>
                    <p class="text-muted mb-0">Quick, securely sealed packaging and express shipping to deliver freshness right to your doorstep.</p>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="feature-box text-center p-5 bg-white border-0">
                    <div class="fs-1 text-primary mb-3">💯</div>
                    <h3 class="fw-bold fs-4 mb-3">Fresh Products</h3>
                    <p class="text-muted mb-0">Hygienically packed in air-tight pouches to keep the aroma and taste alive for months.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row text-center">
            <div class="col-md-3 mb-4 mb-md-0">
                <h2 class="text-accent display-4 fw-bold mb-2">5,000+</h2>
                <p class="text-muted fw-semibold">Happy Customers</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h2 class="text-accent display-4 fw-bold mb-2">100+</h2>
                <p class="text-muted fw-semibold">Premium Products</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h2 class="text-accent display-4 fw-bold mb-2">50+</h2>
                <p class="text-muted fw-semibold">Cities Served</p>
            </div>
            <div class="col-md-3">
                <h2 class="text-accent display-4 fw-bold mb-2">24/7</h2>
                <p class="text-muted fw-semibold">Customer Support</p>
            </div>
        </div>
    </div>
</section>

<!-- Call To Action -->
<section class="bg-primary text-white py-5 text-center position-relative" style="background-color: var(--color-primary) !important;">
    <div class="container py-4">
        <h2 class="fw-bold text-white mb-3">Taste Tradition with Every Bite</h2>
        <p class="text-white-50 fs-5 mb-4 max-width-600 mx-auto">Explore our premium collection of mouth fresheners and find your signature blend today.</p>
        <a href="shop.php" class="btn btn-accent btn-lg px-5 py-3 rounded-pill">
            <i class="bi bi-cart-fill me-2"></i>Shop Now
        </a>
    </div>
</section>

<?php include("includes/footer.php"); ?>

</body>
</html>
