<?php
include("config/db.php");
include_once("includes/abstract_api.php");

$base_path = "";
$message = "";

if(isset($_POST['send_message']))
{
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = "Security token validation failed. Please try again.";
    } else {
    $name = mysqli_real_escape_string($conn,$_POST['name']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $mobile = mysqli_real_escape_string($conn,$_POST['mobile']);
    $subject = mysqli_real_escape_string($conn,$_POST['subject']);
    $msg = mysqli_real_escape_string($conn,$_POST['message']);

    // Check Abstract API Email Verification / Anti-Fraud
    $email_check = verify_email_abstract($_POST['email']);

    if (!$email_check['is_valid']) {
        $message = "Message Not Sent: " . $email_check['reason'];
    } else {
    $insert = mysqli_query($conn,"
    INSERT INTO contact_messages
    (
        name,
        email,
        mobile,
        subject,
        message
    )
    VALUES
    (
        '$name',
        '$email',
        '$mobile',
        '$subject',
        '$msg'
    )
    ");

    if($insert)
    {
        $message = "Your message has been sent successfully.";
    }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Get in touch with Raj Kathiyawadi Mukhwash. Send us a message, email us, or call us for any support, suggestions, or wholesale inquiries.">
    <title>Contact Us - Raj Kathiyawadi Mukhwash</title>
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
        <h1 class="display-4 fw-bold text-white mb-2">Contact Us</h1>
        <p class="text-white-50 fs-5 mb-0">We would love to hear from you</p>
    </div>
</section>

<div class="container py-5">
    <div class="row">
        <!-- Contact Form -->
        <div class="col-md-8 mb-5 mb-md-0">
            <div class="card contact-card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-primary text-white py-3 border-0" style="background-color: var(--color-primary) !important;">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-envelope-fill me-2"></i>Send Us A Message</h5>
                </div>

                <div class="card-body p-4">
                    <?php if($message != "") { ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div><?php echo $message; ?></div>
                        </div>
                    <?php } ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold text-dark">Name</label>
                                <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold text-dark">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Mobile Number</label>
                            <input type="text" name="mobile" maxlength="10" pattern="[0-9]{10}" class="form-control" placeholder="9876543210" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Subject</label>
                            <input type="text" name="subject" class="form-control" placeholder="Wholesale Inquiry / Feedback" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Message</label>
                            <textarea name="message" rows="5" class="form-control" placeholder="Write your message here..." required></textarea>
                        </div>

                        <button type="submit" name="send_message" class="btn btn-primary px-5 py-3 rounded-pill">
                            <i class="bi bi-send-fill me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="col-md-4">
            <div class="info-box p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2 text-accent"></i>Address</h5>
                <p class="text-muted mb-0 leading-relaxed">
                    Raj Kathiyawadi Mukhwash Office,<br>
                    Shop No. 56, Near Bus Stand,<br>
                    Rajkot, Gujarat - 360001, India
                </p>
            </div>

            <div class="info-box p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-telephone-fill me-2 text-accent"></i>Phone</h5>
                <p class="text-muted mb-0 font-monospace">+91 9876543210</p>
            </div>

            <div class="info-box p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-envelope-at-fill me-2 text-accent"></i>Email</h5>
                <p class="text-muted mb-0 font-monospace">info@Raj Kathiyawadi Mukhwash.com</p>
            </div>

            <div class="info-box p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-clock-fill me-2 text-accent"></i>Working Hours</h5>
                <p class="text-muted mb-0 leading-relaxed">
                    Monday - Saturday<br>
                    9:00 AM - 7:00 PM<br>
                    <span class="text-danger-emphasis fw-medium">Sunday Closed</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Google Map -->
<div class="container pb-5">
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="card-body p-0">
            <?php
            // Get Google Maps settings from session (saved via admin settings)
            $gmap_api_key    = $_SESSION['settings']['google_maps_api_key'] ?? '';
            $gmap_enabled    = $_SESSION['settings']['enable_gmap_api'] ?? '';
            $gmap_lat        = $_SESSION['settings']['map_latitude'] ?? '22.3039';
            $gmap_lng        = $_SESSION['settings']['map_longitude'] ?? '70.8022';
            $gmap_marker     = $_SESSION['settings']['map_marker_title'] ?? 'Raj Kathiyawadi Mukhwash';

            if (!empty($gmap_api_key) && $gmap_enabled === 'on'):
            ?>
            <!-- Interactive Google Map (JavaScript API) -->
            <div id="google-map" style="width:100%; height:380px;"></div>
            <script>
            function initMap() {
                const location = { lat: <?= floatval($gmap_lat) ?>, lng: <?= floatval($gmap_lng) ?> };

                const map = new google.maps.Map(document.getElementById('google-map'), {
                    zoom: 15,
                    center: location,
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true,
                    zoomControl: true,
                    styles: [
                        { featureType: "poi", stylers: [{ visibility: "simplified" }] },
                        { featureType: "transit", stylers: [{ visibility: "simplified" }] }
                    ]
                });

                const marker = new google.maps.Marker({
                    position: location,
                    map: map,
                    title: '<?= addslashes($gmap_marker) ?>',
                    animation: google.maps.Animation.DROP
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: '<div style="padding:8px 12px;font-family:Outfit,sans-serif;">' +
                             '<h6 style="margin:0 0 4px;font-weight:700;color:#1a1a2e;"><?= addslashes($gmap_marker) ?></h6>' +
                             '<p style="margin:0;font-size:13px;color:#666;">📍 Click for directions</p></div>'
                });

                marker.addListener('click', function() {
                    infoWindow.open(map, marker);
                });

                // Open info window by default
                infoWindow.open(map, marker);
            }
            </script>
            <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode($gmap_api_key) ?>&callback=initMap">
            </script>

            <?php else: ?>
            <!-- Fallback: Free Google Maps Embed -->
            <iframe
            src="https://maps.google.com/maps?q=Bus%20Stand%2C%20Rajkot&t=&z=13&ie=UTF8&iwloc=&output=embed"
            width="100%"
            height="380"
            style="border:0;"
            allowfullscreen=""
            loading="lazy">
            </iframe>
            <?php endif; ?>
        </div>
    </div>

    <!-- API Geolocation Embed Section -->
    <div class="row my-4">
        <div class="col-12">
            <?php include("includes/api_embed.php"); ?>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script src="assets/js/email_verifier.js"></script>
</body>
</html>
