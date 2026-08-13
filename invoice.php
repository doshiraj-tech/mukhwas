<?php
include("config/db.php");

// Auth check: Admin or Order Owner
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    die("Invalid Order ID.");
}

// Fetch Order
$order_q = mysqli_query($conn, "
    SELECT o.*, u.name AS user_name, u.email AS user_email, u.mobile AS user_phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = '$order_id'
");

if (!$order_q || mysqli_num_rows($order_q) == 0) {
    die("Order not found.");
}

$order = mysqli_fetch_assoc($order_q);

// Access check: User must be logged in as order owner OR admin
$is_admin = isset($_SESSION['admin']) && !empty($_SESSION['admin']);
$is_owner = isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $order['user_id']);

if (!$is_admin && !$is_owner) {
    header("Location: user/login.php");
    exit();
}

// Fetch Order Items
$items_q = mysqli_query($conn, "
    SELECT oi.*, p.name AS product_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = '$order_id'
");

// Store Settings
$store_name     = $_SESSION['settings']['site_name'] ?? 'Raj Kathiyawadi Mukhwash';
$store_phone    = $_SESSION['settings']['store_phone'] ?? '+91 8140265904';
$store_email    = $_SESSION['settings']['admin_email'] ?? 'info@rajkathiyawadimukhwash.com';
$store_address  = $_SESSION['settings']['store_address'] ?? 'Shop No. 56, Near Bus Stand, Rajkot, Gujarat - 360001, India';
$gstin          = $_SESSION['settings']['gstin'] ?? '24AAAAA0000A1Z5';
$gst_rate       = floatval($_SESSION['settings']['gst_rate'] ?? 18);

// Calculations
$subtotal = 0;
$items = [];
while ($item = mysqli_fetch_assoc($items_q)) {
    $item['line_total'] = $item['price'] * $item['quantity'];
    $subtotal += $item['line_total'];
    $items[] = $item;
}

$discount = floatval($order['discount_amount'] ?? 0);
$shipping = floatval($_SESSION['settings']['ship_standard'] ?? 0);
if ($subtotal >= floatval($_SESSION['settings']['ship_free_above'] ?? 500)) {
    $shipping = 0;
}

// Calculate Tax components (GST included in selling price or calculated)
$taxable_val = max(0, $subtotal - $discount);
$cgst_amount = ($taxable_val * ($gst_rate / 2)) / 100;
$sgst_amount = ($taxable_val * ($gst_rate / 2)) / 100;
$total_gst   = $cgst_amount + $sgst_amount;
$grand_total = floatval($order['total_amount']);

// Helper to convert number to words
function numberToWords($number) {
    $no = floor($number);
    $point = round(($number - $no) * 100);
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four',
        '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
        '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen',
        '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
        '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty',
        '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy',
        '80' => 'Eighty', '90' => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : '';
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : '';
            $str [] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred
                : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point) ? "." . $words[floor($point / 10) * 10] . " " . $words[$point % 10] . " Paise" : '';
    return trim($result) . " Rupees" . $points;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice #RKM-INV-<?= $order_id ?> - Raj Kathiyawadi Mukhwash</title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body, html {
            font-family: 'Outfit', sans-serif;
            background-color: #f1f5f9 !important;
            background-image: none !important;
            color: #1e293b;
            padding: 30px 0;
        }
        body::before, body::after {
            display: none !important;
            content: none !important;
            background-image: none !important;
        }
        .invoice-card {
            background: #ffffff !important;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            max-width: 850px;
            margin: 0 auto;
            padding: 40px;
            position: relative;
        }
        .brand-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .brand-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: #1b4d3e;
            font-size: 1.8rem;
        }
        .invoice-title-badge {
            background: #1b4d3e;
            color: #ffffff;
            font-weight: 700;
            padding: 6px 18px;
            border-radius: 30px;
            letter-spacing: 1px;
            font-size: 0.85rem;
            display: inline-block;
        }
        .table-invoice th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            border-bottom: 2px solid #cbd5e1;
        }
        .table-invoice td {
            vertical-align: middle;
            font-size: 0.92rem;
        }
        .action-bar {
            max-width: 850px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        @media print {
            body, html {
                background: #ffffff !important;
                background-image: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            body::before, body::after {
                display: none !important;
                content: none !important;
                background-image: none !important;
            }
            .action-bar {
                display: none !important;
            }
            .invoice-card {
                box-shadow: none !important;
                padding: 20px !important;
                border-radius: 0 !important;
                max-width: 100% !important;
                border: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Top Action Bar (Hidden on Print) -->
<div class="action-bar">
    <a href="<?= $is_admin ? 'admin/order_details.php?id=' . $order_id : 'user/track_order.php?id=' . $order_id ?>" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left me-1"></i> Back to Order Details
    </a>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold">
            <i class="bi bi-printer-fill me-2"></i>Print Tax Invoice
        </button>
        <button onclick="window.print()" class="btn btn-success rounded-pill px-4 fw-bold" style="background-color: #1b4d3e;">
            <i class="bi bi-download me-2"></i>Save as PDF
        </button>
    </div>
</div>

<!-- Tax Invoice Card -->
<div class="invoice-card">

    <!-- Header Section -->
    <div class="brand-header d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <img src="assets/images/logo.jpg" alt="Logo" style="height: 64px; width: auto; border-radius: 8px;" class="border shadow-sm">
            <div>
                <h1 class="brand-title mb-1"><?= htmlspecialchars($store_name) ?></h1>
                <p class="text-muted small mb-0"><?= htmlspecialchars($store_address) ?></p>
                <p class="text-muted small mb-0">📞 <?= htmlspecialchars($store_phone) ?> | ✉ <?= htmlspecialchars($store_email) ?></p>
                <p class="text-dark small fw-bold mb-0">GSTIN: <span class="font-monospace text-primary"><?= htmlspecialchars($gstin) ?></span></p>
            </div>
        </div>
        <div class="text-end">
            <span class="invoice-title-badge mb-2">TAX INVOICE</span>
            <h5 class="fw-bold text-dark mb-1">Invoice #: <span class="text-primary font-monospace">RKM-INV-<?= $order_id ?></span></h5>
            <p class="small text-muted mb-1">Date: <strong><?= date("d M Y", strtotime($order['created_at'])) ?></strong></p>
            <p class="small text-muted mb-0">Status: <span class="badge bg-success px-2 py-1"><?= htmlspecialchars($order['status']) ?></span></p>
        </div>
    </div>

    <!-- Customer & Billing Section -->
    <div class="row mb-4">
        <div class="col-6">
            <div class="p-3 bg-light rounded-3 h-100 border">
                <h6 class="fw-bold text-uppercase small text-muted mb-2"><i class="bi bi-person-fill text-primary me-1"></i>Billed & Shipped To:</h6>
                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($order['fullname']) ?></h6>
                <p class="small text-muted mb-1"><?= nl2br(htmlspecialchars($order['address'])) ?></p>
                <p class="small text-dark mb-1"><strong>City:</strong> <?= htmlspecialchars($order['city']) ?> | <strong>Pincode:</strong> <?= htmlspecialchars($order['pincode']) ?></p>
                <p class="small text-muted mb-0">📱 Mobile: <strong><?= htmlspecialchars($order['mobile']) ?></strong></p>
            </div>
        </div>
        <div class="col-6">
            <div class="p-3 bg-light rounded-3 h-100 border">
                <h6 class="fw-bold text-uppercase small text-muted mb-2"><i class="bi bi-credit-card-fill text-success me-1"></i>Payment & Delivery Summary:</h6>
                <p class="small text-dark mb-1"><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                <p class="small text-dark mb-1"><strong>Payment Status:</strong> <span class="badge bg-success-subtle text-success border border-success"><?= htmlspecialchars($order['payment_status'] ?? 'Paid') ?></span></p>
                <?php if (!empty($order['coupon_code'])): ?>
                <p class="small text-dark mb-1"><strong>Coupon Applied:</strong> <span class="badge bg-info-subtle text-info border border-info"><?= htmlspecialchars($order['coupon_code']) ?></span></p>
                <?php endif; ?>
                <p class="small text-muted mb-0">Order Ref ID: <strong>#ORD-<?= $order_id ?></strong></p>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-invoice align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Item Description</th>
                    <th class="text-center" style="width: 100px;">Rate (₹)</th>
                    <th class="text-center" style="width: 80px;">Qty</th>
                    <th class="text-end" style="width: 120px;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sr = 1;
                foreach ($items as $item):
                ?>
                <tr>
                    <td class="text-center fw-bold text-muted"><?= $sr++ ?></td>
                    <td>
                        <strong class="text-dark d-block"><?= htmlspecialchars($item['product_name']) ?></strong>
                        <small class="text-muted">HSN Code: 21069099 (Mukhwas / Mouth Freshener)</small>
                    </td>
                    <td class="text-center">₹<?= number_format($item['price'], 2) ?></td>
                    <td class="text-center fw-bold"><?= $item['quantity'] ?></td>
                    <td class="text-end fw-bold">₹<?= number_format($item['line_total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Calculations & Totals -->
    <div class="row align-items-start mb-4">
        <div class="col-7">
            <div class="p-3 bg-light rounded-3 border mb-3">
                <small class="fw-bold text-muted d-block uppercase mb-1">Amount in Words:</small>
                <p class="fw-bold text-dark mb-0 style='font-size:0.95rem;'"><?= numberToWords($grand_total) ?></p>
            </div>
            <div class="p-3 border rounded-3 bg-white">
                <small class="fw-bold text-dark d-block mb-1">📜 Terms & Conditions:</small>
                <ul class="small text-muted mb-0 ps-3">
                    <li>Goods once sold are non-refundable except in case of damage.</li>
                    <li>Subject to Rajkot Jurisdiction only.</li>
                    <li>This is a computer-generated invoice and requires no physical signature.</li>
                </ul>
            </div>
        </div>

        <div class="col-5">
            <table class="table table-sm table-borderless small mb-0">
                <tr>
                    <td class="text-muted">Subtotal:</td>
                    <td class="text-end fw-semibold">₹<?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php if ($discount > 0): ?>
                <tr class="text-success">
                    <td>Coupon Discount:</td>
                    <td class="text-end fw-bold">- ₹<?= number_format($discount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="text-muted">CGST (<?= $gst_rate/2 ?>%):</td>
                    <td class="text-end font-monospace">₹<?= number_format($cgst_amount, 2) ?></td>
                </tr>
                <tr>
                    <td class="text-muted">SGST (<?= $gst_rate/2 ?>%):</td>
                    <td class="text-end font-monospace">₹<?= number_format($sgst_amount, 2) ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Shipping Charges:</td>
                    <td class="text-end"><?= $shipping > 0 ? '₹' . number_format($shipping, 2) : '<span class="text-success fw-bold">FREE</span>' ?></td>
                </tr>
                <tr class="border-top border-2">
                    <td class="fw-bold fs-6 text-dark py-2">Grand Total:</td>
                    <td class="text-end fw-bold fs-5 text-primary py-2">₹<?= number_format($grand_total, 2) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Authorized Signatory Footer -->
    <div class="d-flex justify-content-between align-items-end pt-4 border-top">
        <div>
            <small class="text-muted d-block">Thank you for choosing <strong><?= htmlspecialchars($store_name) ?></strong>! 🌿</small>
            <small class="text-muted">Visit again: www.rajkathiyawadimukhwash.com</small>
        </div>
        <div class="text-center" style="width: 200px;">
            <div class="border-bottom pb-4 mb-1">
                <span class="badge bg-success-subtle text-success border border-success font-monospace px-3 py-1">VERIFIED SEAL</span>
            </div>
            <small class="fw-bold text-dark d-block">Authorized Signatory</small>
            <small class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($store_name) ?></small>
        </div>
    </div>

</div>

</body>
</html>
