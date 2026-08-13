<?php
include("../config/db.php");
require_once('auth_guard.php');
include_once("../includes/whatsapp_cloud.php");


if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Fetch Order Details
$order_query = mysqli_query($conn, "
    SELECT orders.*, users.name AS customer_name, users.email AS customer_email 
    FROM orders 
    LEFT JOIN users ON orders.user_id = users.id 
    WHERE orders.id = '$order_id'
");

if (mysqli_num_rows($order_query) == 0) {
    die("Order Not Found");
}

$order = mysqli_fetch_assoc($order_query);

// Extract latitude and longitude from DB or address string
$order_lat = !empty($order['latitude']) ? floatval($order['latitude']) : null;
$order_lng = !empty($order['longitude']) ? floatval($order['longitude']) : null;

if (empty($order_lat) || empty($order_lng)) {
    if (preg_match('/Lat:?\s*([0-9.-]+).*?Lng:?\s*([0-9.-]+)/i', $order['address'], $matches)) {
        $order_lat = floatval($matches[1]);
        $order_lng = floatval($matches[2]);
    }
}

// Fetch Order Items
$items_query = mysqli_query($conn, "
    SELECT order_items.*, products.name AS product_name, products.image AS product_image 
    FROM order_items 
    LEFT JOIN products ON order_items.product_id = products.id 
    WHERE order_items.order_id = '$order_id'
");

// Handle status update
$status_success = '';
if (isset($_POST['update_status'])) {
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $note       = mysqli_real_escape_string($conn, trim($_POST['status_note'] ?? ''));
    $valid = ['Pending','Processing','Shipped','Out for Delivery','Delivered','Cancelled'];
    if (in_array($new_status, $valid)) {
        mysqli_query($conn, "UPDATE orders SET status='$new_status' WHERE id='$order_id'");
        mysqli_query($conn, "INSERT INTO order_status_history (order_id, status, note) VALUES ('$order_id','$new_status','$note')");
        $order['status'] = $new_status;
        $status_success = "Order status updated to <strong>$new_status</strong>.";

        // ── WhatsApp Status Notification ──
        if (get_setting_val('wa_notify_on_status','off') === 'on' && !empty($order['mobile'])) {
            triggerWhatsAppStatusUpdate(
                $order_id,
                $order['mobile'],
                $order['fullname'],
                $new_status,
                $note
            );
        }
    }
}

// Fetch status history
$history_q = mysqli_query($conn, "SELECT * FROM order_status_history WHERE order_id='$order_id' ORDER BY updated_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $order['id']; ?> - Raj Kathiyawadi Mukhwash</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS & JS for Admin Delivery Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        :root {
            --font-heading: 'Playfair Display', serif;
            --font-body: 'Outfit', sans-serif;
            --color-primary: #1b4d3e;
            --color-primary-dark: #123329;
            --color-accent: #c5a059;
            --color-accent-dark: #b08d4b;
            --bg-light: #f8faf9;
            --shadow-subtle: 0 4px 20px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: var(--font-body);
            background: var(--bg-light);
            color: #2b3a35;
        }

        .main-content {
            margin-left: 255px;
            padding: 40px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-subtle);
            background: white;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: white;
            border-bottom: none;
            padding: 20px 24px;
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 1.15rem;
        }

        .card-body {
            padding: 24px;
        }

        .badge-pending { background-color: #ffe8cc; color: #ff922b; }
        .badge-shipped { background-color: #e7f5ff; color: #228be6; }
        .badge-delivered { background-color: #ebfbee; color: #40c057; }
        .badge-cancelled { background-color: #fff5f5; color: #fa5252; }

        .btn-outline-primary {
            color: var(--color-primary);
            border-color: var(--color-primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #5c6d66;
            background: var(--bg-light);
            border-bottom: 2px solid #e1e7e4;
        }

        .table td {
            vertical-align: middle;
            border-bottom: 1px solid #f1f4f3;
        }

        .order-meta-label {
            font-weight: 600;
            color: #5c6d66;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<!-- Main Content -->
<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1 text-dark" style="font-family: var(--font-heading);">Order Details</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item"><a href="orders.php" class="text-decoration-none text-muted">Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">#<?php echo $order['id']; ?></li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="invoice_print.php?id=<?php echo $order['id']; ?>&type=invoice" target="_blank" class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-bold" style="background-color:#1b4d3e; border-color:#1b4d3e;">
                <i class="bi bi-printer-fill me-1"></i> Print Invoice
            </a>
            <a href="invoice_print.php?id=<?php echo $order['id']; ?>&type=packing_slip" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 fw-semibold">
                <i class="bi bi-box-seam me-1"></i> Packing Slip
            </a>
            <a href="orders.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2">
                <i class="bi bi-arrow-left me-1"></i> Back to Orders
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Order Info Card -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-basket me-2"></i>Ordered Items</span>
                    <span class="badge rounded-pill <?php 
                        if ($order['status'] == 'Pending') echo 'badge-pending';
                        elseif ($order['status'] == 'Shipped') echo 'badge-shipped';
                        elseif ($order['status'] == 'Delivered') echo 'badge-delivered';
                        else echo 'badge-cancelled';
                    ?> px-3 py-2">
                        <?php echo $order['status']; ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th class="text-end pe-4">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                while ($item = mysqli_fetch_assoc($items_query)) {
                                    $item_total = $item['price'] * $item['quantity'];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3 py-2">
                                            <img src="../assets/uploads/<?php echo $item['product_image'] ?: 'placeholder.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                 style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px;"
                                                 class="border shadow-sm">
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                <small class="text-muted">ID: #<?php echo $item['product_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="text-end pe-4 fw-bold">₹<?php echo number_format($item_total, 2); ?></td>
                                </tr>
                                <?php } ?>
                                <tr class="bg-light">
                                    <td colspan="3" class="ps-4 py-3 fw-bold text-end">
                                        <?php if (!empty($order['coupon_code'])): ?>
                                            <div class="small text-success mb-1">🎟️ Coupon (<?= htmlspecialchars($order['coupon_code']) ?>): -₹<?= number_format($order['discount_amount'], 2) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($order['points_redeemed']) && $order['points_redeemed'] > 0): ?>
                                            <div class="small text-warning mb-1">🪙 Points Redeemed (<?= (int)$order['points_redeemed'] ?> Pts): -₹<?= number_format($order['points_discount'], 2) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($order['points_earned']) && $order['points_earned'] > 0): ?>
                                            <div class="small text-success mb-1">✨ Reward Points Earned: +<?= (int)$order['points_earned'] ?> Pts</div>
                                        <?php endif; ?>
                                        Total Paid Amount:
                                    </td>
                                    <td class="pe-4 py-3 fw-bold text-end text-primary fs-5">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer & Shipping Card -->
            <div class="card">
                <div class="card-header"><i class="bi bi-truck me-2"></i>Shipping & Delivery Address</div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3"><i class="bi bi-person me-2 text-muted"></i>Contact Person</h6>
                            <p class="mb-1 fw-semibold text-dark"><?php echo htmlspecialchars($order['fullname']); ?></p>
                            <p class="text-muted mb-0"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($order['mobile']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2 text-muted"></i>Delivery Address</h6>
                            <p class="mb-1 text-dark"><?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                            <p class="text-dark mb-0">
                                <strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?><br>
                                <strong>Pincode:</strong> <?php echo htmlspecialchars($order['pincode']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Delivery Location Map Card for Admin -->
            <div class="card mt-4" id="deliveryMapCard">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-geo-alt-fill text-danger me-2"></i>Customer Order Location Map</span>
                    <?php if (!empty($order_lat) && !empty($order_lng)): ?>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $order_lat ?>,<?= $order_lng ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 fw-semibold">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Open in Google Maps
                    </a>
                    <?php else: ?>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($order['address'] . ', ' . $order['city'] . ' ' . $order['pincode']) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 fw-semibold">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Open in Google Maps
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="adminOrderMap" style="width: 100%; height: 320px; z-index: 1;"></div>
                    <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="fw-bold text-dark"><i class="bi bi-pin-map-fill text-danger me-1"></i>Delivery GPS Coordinates:</span>
                            <span id="coordsDisplay" class="font-monospace text-muted ms-1"><?= (!empty($order_lat) && !empty($order_lng)) ? "$order_lat, $order_lng" : "Detecting map pin from address..." ?></span>
                        </div>
                        <?php if (!empty($order_lat) && !empty($order_lng)): ?>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $order_lat ?>,<?= $order_lng ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-bold">
                            <i class="bi bi-sign-turn-right-fill me-1"></i>Start GPS Navigation
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Meta Info -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-info-square me-2"></i>Order Summary</div>
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <span class="order-meta-label">Order ID:</span>
                        <span class="fw-bold">#<?php echo $order['id']; ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <span class="order-meta-label">Order Date:</span>
                        <span><?php echo date("d M Y h:i A", strtotime($order['created_at'])); ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <span class="order-meta-label">Payment Method:</span>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <span class="order-meta-label">Payment Status:</span>
                        <span class="badge rounded-pill <?php echo $order['payment_status'] == 'Paid' ? 'bg-success-subtle text-success border border-success' : 'bg-warning-subtle text-warning border border-warning'; ?> px-2 py-1">
                            <?php echo htmlspecialchars($order['payment_status']); ?>
                        </span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="order-meta-label">Registered Account:</span>
                        <div class="text-end">
                            <span class="fw-semibold d-block small"><?php echo htmlspecialchars($order['customer_name'] ?: 'Guest'); ?></span>
                            <span class="text-muted small"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Status Form -->
            <div class="card mt-3">
                <div class="card-header"><i class="bi bi-arrow-repeat me-2"></i>Update Order Status</div>
                <div class="card-body">
                    <?php if ($status_success): ?>
                    <div class="alert alert-success small py-2"><?= $status_success ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">New Status</label>
                            <select name="new_status" class="form-select form-select-sm rounded-3">
                                <?php
                                $statuses = ['Pending','Processing','Shipped','Out for Delivery','Delivered','Cancelled'];
                                foreach ($statuses as $s) {
                                    $sel = $order['status'] === $s ? 'selected' : '';
                                    echo "<option value=\"$s\" $sel>$s</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Note (optional)</label>
                            <textarea name="status_note" class="form-control form-control-sm rounded-3" rows="2" placeholder="e.g. Shipped via Delhivery, tracking #123"></textarea>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary btn-sm rounded-pill px-4 w-100">
                            <i class="bi bi-check2-circle me-1"></i>Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- Status History -->
            <div class="card mt-3">
                <div class="card-header"><i class="bi bi-clock-history me-2"></i>Status History</div>
                <div class="card-body p-3">
                    <?php if (!$history_q || mysqli_num_rows($history_q) === 0): ?>
                        <p class="text-muted small mb-0">No history yet.</p>
                    <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php while ($h = mysqli_fetch_assoc($history_q)): ?>
                        <li class="mb-3 pb-3 border-bottom">
                            <span class="badge bg-primary-subtle text-primary fw-bold"><?= htmlspecialchars($h['status']) ?></span>
                            <?php if (!empty($h['note'])): ?>
                            <p class="text-muted small mb-0 mt-1"><?= htmlspecialchars($h['note']) ?></p>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-1"><?= date('d M Y, h:i A', strtotime($h['updated_at'])) ?></small>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let lat = <?= (!empty($order_lat)) ? floatval($order_lat) : 'null' ?>;
    let lng = <?= (!empty($order_lng)) ? floatval($order_lng) : 'null' ?>;
    const addressStr = <?= json_encode($order['address'] . ', ' . $order['city'] . ' ' . $order['pincode']) ?>;
    const customerName = <?= json_encode($order['fullname']) ?>;

    const mapEl = document.getElementById('adminOrderMap');
    if (!mapEl || typeof L === 'undefined') return;

    function renderAdminMap(l1, l2) {
        const map = L.map('adminOrderMap').setView([l1, l2], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const customIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const marker = L.marker([l1, l2], { icon: customIcon }).addTo(map);
        marker.bindPopup(`<b>📍 Delivery Location for ${customerName}</b><br>${addressStr}`).openPopup();
        setTimeout(() => map.invalidateSize(), 300);
    }

    if (lat && lng) {
        renderAdminMap(lat, lng);
    } else {
        // Geocode address if coordinates not stored directly
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(addressStr)}&limit=1`, {
                headers: {
                    'Accept-Language': 'en',
                    'User-Agent': 'MukhwasMart/1.0 (https://rajkathiyawadimukhwash.com; contact@rajkathiyawadimukhwash.com)'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    lat = parseFloat(data[0].lat);
                    lng = parseFloat(data[0].lon);
                    renderAdminMap(lat, lng);
                    const cd = document.getElementById('coordsDisplay');
                    if (cd) cd.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)} (Address Geocoded)`;
                } else {
                    renderAdminMap(22.3039, 70.8022);
                    const cd = document.getElementById('coordsDisplay');
                    if (cd) cd.textContent = 'City Area: <?= addslashes($order['city']) ?>';
                }
            })
            .catch(() => {
                renderAdminMap(22.3039, 70.8022);
            });
    }
});
</script>

</body>
</html>
