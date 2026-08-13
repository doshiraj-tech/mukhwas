<?php
include("config/db.php");

$base_path = "";
$page_title = "API Geolocation Embed - Raj Kathiyawadi Mukhwash";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="fade-in">

<?php include("includes/navbar.php"); ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center mb-4">
                <h2 class="fw-bold display-6">🌐 External API Integration</h2>
                <p class="text-muted">Live Geolocation API Embed Interface</p>
            </div>

            <?php include("includes/api_embed.php"); ?>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

</body>
</html>
