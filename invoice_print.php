<?php
/**
 * Printable Invoice & Packing Slip Generator for Admin Panel
 * Usage: invoice_print.php?id=123&type=invoice (or type=packing_slip)
 */
include("../config/db.php");
require_once('auth_guard.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Order ID");
}

$order_id = (int)$_GET['id'];
$type = isset($_GET['type']) && $_GET['type'] === 'packing_slip' ? 'packing_slip' : 'invoice';

// Fetch Order Details
$order_query = mysqli_query($conn, "
    SELECT orders.*, users.name AS customer_name, users.email AS customer_email 
    FROM orders 
    LEFT JOIN users ON orders.user_id = users.id 
    WHERE orders.id = '$order_id'
");

if (!$order_query || mysqli_num_rows($order_query) == 0) {
    die("Order Not Found");
}

$order = mysqli_fetch_assoc($order_query);

// Fetch Order Items
$items_query = mysqli_query($conn, "
    SELECT order_items.*, products.name AS product_name 
    FROM order_items 
    LEFT JOIN products ON order_items.product_id = products.id 
    WHERE order_items.order_id = '$order_id'
");

$items = [];
$subtotal = 0;
while ($item = mysqli_fetch_assoc($items_query)) {
    $items[] = $item;
    $subtotal += ($item['price'] * $item['quantity']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo ucfirst($type); ?> #<?php echo $order['id']; ?> - Raj Kathiyawadi Mukhwash</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
    body { background: #f8fafc; color: #1e293b; padding: 20px; }
    
    .print-container {
        max-width: 800px;
        margin: 0 auto;
        background: #ffffff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 1px solid #e2e8f0;
    }

    .no-print-bar {
        max-width: 800px;
        margin: 0 auto 20px auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .btn-print {
        background: #0066ff;
        color: #fff;
        border: none;
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .btn-print:hover { background: #0044cc; }
    .btn-back {
        color: #64748b;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .header-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 24px;
        margin-bottom: 24px;
    }

    .brand-logo {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .brand-logo img {
        height: 60px;
        width: auto;
        border-radius: 8px;
    }
    .brand-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f172a;
    }
    .brand-sub {
        font-size: 0.8rem;
        color: #64748b;
    }

    .doc-title {
        text-align: right;
    }
    .doc-title h1 {
        font-size: 1.8rem;
        font-weight: 800;
        color: #0066ff;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .doc-meta {
        font-size: 0.85rem;
        color: #64748b;
        margin-top: 4px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 30px;
    }

    .info-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px 20px;
    }
    .info-box h3 {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        font-weight: 700;
    }
    .info-box p {
        font-size: 0.9rem;
        line-height: 1.5;
        color: #0f172a;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    .items-table th {
        background: #f1f5f9;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #cbd5e1;
    }
    .items-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.9rem;
    }

    .totals-row {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 30px;
    }
    .totals-table {
        width: 280px;
    }
    .totals-table td {
        padding: 6px 0;
        font-size: 0.9rem;
    }
    .totals-table tr.grand-total td {
        font-size: 1.1rem;
        font-weight: 800;
        color: #0066ff;
        border-top: 2px solid #0066ff;
        padding-top: 10px;
    }

    .footer-note {
        text-align: center;
        border-top: 1px solid #f1f5f9;
        padding-top: 20px;
        font-size: 0.82rem;
        color: #94a3b8;
    }

    @media print {
        body { background: #fff; padding: 0; }
        .print-container { box-shadow: none; border: none; padding: 0; }
        .no-print-bar { display: none !important; }
    }
</style>
</head>
<body>

<div class="no-print-bar">
    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn-back">← Back to Order Details</a>
    <div>
        <a href="invoice_print.php?id=<?php echo $order['id']; ?>&type=<?php echo $type==='invoice'?'packing_slip':'invoice'; ?>" class="btn-back" style="margin-right: 15px;">
            Switch to <?php echo $type==='invoice'?'Packing Slip':'Invoice'; ?>
        </a>
        <button onclick="window.print()" class="btn-print">🖨️ Print <?php echo ucfirst($type); ?></button>
    </div>
</div>

<div class="print-container">

    <!-- Header -->
    <div class="header-row">
        <div class="brand-logo">
            <img src="../assets/images/logo.jpg" alt="Logo">
            <div>
                <div class="brand-title">Raj Kathiyawadi Mukhwash</div>
                <div class="brand-sub">Premium Mouth Fresheners & Spices</div>
            </div>
        </div>
        <div class="doc-title">
            <h1><?php echo $type === 'packing_slip' ? 'Packing Slip' : 'Tax Invoice'; ?></h1>
            <div class="doc-meta">Order #<?php echo $order['id']; ?></div>
            <div class="doc-meta">Date: <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
        </div>
    </div>

    <!-- Info Boxes -->
    <div class="info-grid">
        <div class="info-box">
            <h3>Customer & Shipping Address</h3>
            <p><strong><?php echo htmlspecialchars($order['fullname'] ?: $order['customer_name']); ?></strong></p>
            <p><?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
            <?php if (!empty($order['city'])): ?>
                <p><?php echo htmlspecialchars($order['city']); ?> - <?php echo htmlspecialchars($order['pincode']); ?></p>
            <?php endif; ?>
            <p style="margin-top: 6px; font-size: 0.85rem; color: #64748b;">
                📞 Mobile: <?php echo htmlspecialchars($order['mobile'] ?: 'N/A'); ?><br>
                ✉️ Email: <?php echo htmlspecialchars($order['customer_email']); ?>
            </p>
        </div>
        <div class="info-box">
            <h3>Order & Payment Status</h3>
            <p><strong>Payment Method:</strong> Cash on Delivery (COD)</p>
            <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
            <?php if (!empty($order['coupon_code'])): ?>
                <p><strong>Coupon Applied:</strong> <?php echo htmlspecialchars($order['coupon_code']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="40">#</th>
                <th>Item Description</th>
                <th width="80" style="text-align: center;">Qty</th>
                <?php if ($type === 'invoice'): ?>
                    <th width="110" style="text-align: right;">Unit Price</th>
                    <th width="120" style="text-align: right;">Total</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $item): ?>
            <tr>
                <td><?php echo $idx + 1; ?></td>
                <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                <td style="text-align: center; font-weight: 700;"><?php echo $item['quantity']; ?></td>
                <?php if ($type === 'invoice'): ?>
                    <td style="text-align: right;">₹<?php echo number_format($item['price'], 2); ?></td>
                    <td style="text-align: right; font-weight: 600;">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Invoice Totals -->
    <?php if ($type === 'invoice'): ?>
    <div class="totals-row">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td style="text-align: right;">₹<?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
            <tr style="color: #16a34a;">
                <td>Discount:</td>
                <td style="text-align: right;">-₹<?php echo number_format($order['discount_amount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Shipping Charge:</td>
                <td style="text-align: right;">₹0.00 (FREE)</td>
            </tr>
            <tr class="grand-total">
                <td>Grand Total:</td>
                <td style="text-align: right;">₹<?php echo number_format($order['total_amount'], 2); ?></td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <!-- Footer Note -->
    <div class="footer-note">
        Thank you for shopping with Raj Kathiyawadi Mukhwash! For support, contact us at support@rajkathiyawadi.com
    </div>

</div>

</body>
</html>
