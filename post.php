<?php
include("config/db.php");
$base_path = "";

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$query = mysqli_query($conn, "SELECT p.*, c.name as category_name FROM posts p LEFT JOIN post_cats c ON p.cat_id = c.id WHERE p.id='$id' AND p.status='published'");

if(mysqli_num_rows($query) == 0){
    die("Post Not Found");
}
$post = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($post['body']), 0, 150)); ?>">
    <title><?php echo htmlspecialchars($post['title']); ?> - Raj Kathiyawadi Mukhwash</title>
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
    <style>
    .post-cover { width: 100%; max-height: 400px; object-fit: cover; border-radius: var(--border-radius-lg); margin-bottom: 2rem; }
    .post-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 15px 0; }
    .post-card { border-radius: var(--border-radius-md); overflow: hidden; }
    </style>
</head>
<body class="fade-in">
<?php include("includes/navbar.php"); ?>

<div class="container py-5 mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($post['title']); ?></li>
                </ol>
            </nav>

            <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <div class="d-flex align-items-center text-muted mb-4">
                <span class="badge bg-primary-subtle text-primary me-3 px-3 py-2 rounded-pill"><i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorised'); ?></span>
                <?php 
                // Check if created_at column exists in result
                if(array_key_exists('created_at', $post) && !empty($post['created_at'])) { ?>
                <span><i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                <?php } ?>
            </div>

            <?php if(!empty($post['cover_image'])) { ?>
                <img src="assets/uploads/<?php echo $post['cover_image']; ?>" class="post-cover shadow-sm" alt="<?php echo htmlspecialchars($post['title']); ?>">
            <?php } ?>

            <div class="post-content fs-5 text-dark" style="line-height: 1.8;">
                <?php echo $post['body']; // Render HTML content natively ?>
            </div>
            
            <hr class="my-5 opacity-25">
            <a href="index.php" class="btn btn-outline-primary rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i>Back to Home</a>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
</body>
</html>
