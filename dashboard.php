<?php
include("../config/db.php");
require_once('auth_guard.php');


// Total Products
$product_query = mysqli_query($conn,"SELECT * FROM products");
$total_products = mysqli_num_rows($product_query);

// Total Customers
$user_query = mysqli_query($conn,"SELECT * FROM users");
$total_users = mysqli_num_rows($user_query);

// Total Orders
$order_query = mysqli_query($conn,"SELECT * FROM orders");
$total_orders = mysqli_num_rows($order_query);

// Total Revenue
$revenue_query = mysqli_query($conn,"SELECT SUM(total_amount) as revenue FROM orders");
$revenue_data = mysqli_fetch_assoc($revenue_query);
$total_revenue = $revenue_data['revenue'] ?? 0;

// Pending Orders
$pending_query = mysqli_query($conn,"SELECT COUNT(*) as cnt FROM orders WHERE status='Pending'");
$pending_orders = mysqli_fetch_assoc($pending_query)['cnt'] ?? 0;

// Delivered Orders
$delivered_query = mysqli_query($conn,"SELECT COUNT(*) as cnt FROM orders WHERE status='Delivered'");
$delivered_orders = mysqli_fetch_assoc($delivered_query)['cnt'] ?? 0;

// Low Stock Products Warning (stock <= 5)
$low_stock_q = mysqli_query($conn, "SELECT id, name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 4");
$low_stock_count = $low_stock_q ? mysqli_num_rows($low_stock_q) : 0;
$low_stock_items = [];
if ($low_stock_count > 0) {
    while ($ls = mysqli_fetch_assoc($low_stock_q)) {
        $low_stock_items[] = $ls;
    }
}

// Latest Order ID for Live Polling
$max_order_q = mysqli_query($conn, "SELECT MAX(id) AS max_id FROM orders");
$latest_order_id = (int)(mysqli_fetch_assoc($max_order_q)['max_id'] ?? 0);

// Recent Orders
$recent_orders = mysqli_query($conn,"SELECT * FROM orders ORDER BY id DESC LIMIT 5");

// Chart 1: Monthly Revenue & Order Volume
$monthly_chart_q = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month_label,
           COUNT(id) AS total_orders,
           SUM(total_amount) AS monthly_revenue
    FROM orders
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY MIN(created_at) ASC
    LIMIT 6
");

$chart_months = [];
$chart_revenue = [];
$chart_orders = [];

if ($monthly_chart_q && mysqli_num_rows($monthly_chart_q) > 0) {
    while ($m = mysqli_fetch_assoc($monthly_chart_q)) {
        $chart_months[] = $m['month_label'];
        $chart_revenue[] = floatval($m['monthly_revenue']);
        $chart_orders[] = intval($m['total_orders']);
    }
} else {
    $chart_months = [date('M Y')];
    $chart_revenue = [floatval($total_revenue)];
    $chart_orders = [intval($total_orders)];
}

// Chart 2: Order Status Distribution
$status_chart_q = mysqli_query($conn, "
    SELECT status, COUNT(*) AS count
    FROM orders
    GROUP BY status
");

$status_labels = [];
$status_counts = [];
while ($st = mysqli_fetch_assoc($status_chart_q)) {
    $status_labels[] = $st['status'];
    $status_counts[] = intval($st['count']);
}
if (empty($status_labels)) {
    $status_labels = ['Pending', 'Delivered'];
    $status_counts = [$pending_orders, $delivered_orders];
}

// Admin Name
$admin_name = $_SESSION['admin'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Raj Kathiyawadi Mukhwash</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

<style>
    /* CSS Root Styling with Dark Mode Base (Default) */
    :root {
        --dash-glow-primary: rgba(0, 102, 255, 0.4);
        --dash-glass-bg: rgba(255, 255, 255, 0.02);
        --dash-glass-border: rgba(255, 255, 255, 0.07);
        --dash-text-main: #ffffff;
        --dash-text-muted: #94a3b8;
        --dash-elastic: cubic-bezier(0.34, 1.56, 0.64, 1);
        --dash-smooth: cubic-bezier(0.25, 1, 0.5, 1);
        --dash-table-header: rgba(255, 255, 255, 0.03);
        --dash-card-shadow: rgba(0, 0, 0, 0.6);
        --dash-blob-opacity: 0.18;
    }

    /* Light Mode Design Token Overrides */
    [data-theme="light"] {
        --dash-glow-primary: rgba(0, 102, 255, 0.15);
        --dash-glass-bg: rgba(0, 0, 0, 0.02);
        --dash-glass-border: rgba(0, 0, 0, 0.08);
        --dash-text-main: #1e293b;
        --dash-text-muted: #64748b;
        --dash-table-header: rgba(0, 0, 0, 0.03);
        --dash-card-shadow: rgba(0, 0, 0, 0.08);
        --dash-blob-opacity: 0.12;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    body {
        background: #070b19;
        color: var(--dash-text-main);
        line-height: 1.6;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
        transition: background 0.4s var(--dash-smooth), color 0.4s var(--dash-smooth);
    }

    [data-theme="light"] body {
        background: #f8fafc !important;
        color: #1e293b !important;
    }

    /* Theme Floating Toggle Engine Controller */
    .theme-toggle-container {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 10px;
        opacity: 0;
        transform: translateY(-10px);
        animation: dashFadeDown 0.6s var(--dash-smooth) 0.1s forwards;
    }

    .theme-toggle-btn {
        background: var(--dash-glass-bg);
        border: 1px solid var(--dash-glass-border);
        color: var(--dash-text-main);
        padding: 10px 16px;
        border-radius: 12px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px var(--dash-card-shadow);
        transition: all 0.3s var(--dash-smooth);
    }

    .theme-toggle-btn:hover {
        transform: translateY(-2px);
        border-color: #0066ff;
        box-shadow: 0 6px 16px var(--dash-glow-primary);
    }

    /* Outer layout constraint */
    .wrapper {
        max-width: 1200px;
        margin: 20px auto 40px auto;
        padding: 0 20px;
        position: relative;
        z-index: 10;
    }

    /* Ambient animated lighting blobs */
    .dash-bg-animation {
        position: fixed;
        width: 100vw;
        height: 100vh;
        top: 0;
        left: 0;
        z-index: 1;
        pointer-events: none;
    }

    .dash-blob {
        position: absolute;
        background: linear-gradient(135deg, #0066ff, #7c3aed);
        border-radius: 50%;
        filter: blur(140px);
        opacity: var(--dash-blob-opacity);
        animation: dashFloat 16s infinite alternate ease-in-out;
        transition: opacity 0.4s ease;
    }

    .dash-blob-1 { width: 400px; height: 400px; top: 10%; right: -5%; }
    .dash-blob-2 { width: 500px; height: 500px; bottom: -10%; left: -5%; background: linear-gradient(135deg, #00c6ff, #0044cc); }

    @keyframes dashFloat {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(-40px, 40px) scale(1.1); }
    }

    /* Header Welcome Section */
    .dash-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 35px;
        flex-wrap: wrap;
        gap: 15px;
        opacity: 0;
        transform: translateY(-20px);
        animation: dashFadeDown 0.6s var(--dash-smooth) forwards;
    }

    .dash-title {
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--dash-text-main);
        animation: dashNeonPulse 3s ease-in-out infinite alternate;
        transition: color 0.4s;
    }

    @keyframes dashNeonPulse {
        0%, 100% { text-shadow: 0 0 4px rgba(0,102,255,0.1); }
        50% { text-shadow: 0 0 12px #00c6ff, 0 0 20px #0066ff; }
    }

    /* Core Dynamic Dashboard Grid Layout */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    /* Dynamic Shimmering Grid Cards */
    .stat-card {
        background: var(--dash-glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--dash-glass-border);
        border-radius: 20px;
        padding: 24px 20px;
        text-align: center;
        box-shadow: 0 15px 35px -5px var(--dash-card-shadow);
        transition: all 0.4s var(--dash-elastic), background 0.4s, border 0.4s, box-shadow 0.4s;
        opacity: 0;
        transform: scale(0.9) translateY(20px);
        animation: dashRevealCard 0.6s var(--dash-elastic) forwards;
    }

    .stat-card:hover {
        transform: translateY(-5px) scale(1.03);
        border-color: #0066ff;
        box-shadow: 0 20px 40px -10px rgba(0, 102, 255, 0.25), 0 0 15px rgba(0, 102, 255, 0.15);
    }

    /* Staggered entry pipeline for stats */
    .stats-grid .stat-card:nth-child(1) { animation-delay: 0.1s; border-top: 3px solid #0066ff; }
    .stats-grid .stat-card:nth-child(2) { animation-delay: 0.15s; border-top: 3px solid #f59e0b; }
    .stats-grid .stat-card:nth-child(3) { animation-delay: 0.2s; border-top: 3px solid #2563eb; }
    .stats-grid .stat-card:nth-child(4) { animation-delay: 0.25s; border-top: 3px solid #16a34a; }
    .stats-grid .stat-card:nth-child(5) { animation-delay: 0.3s; border-top: 3px solid #7c3aed; }
    .stats-grid .stat-card:nth-child(6) { animation-delay: 0.35s; border-top: 3px solid #00c6ff; }

    .stat-card .number {
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--dash-text-main);
        margin-bottom: 6px;
        letter-spacing: -1px;
        transition: color 0.4s;
    }

    .stat-card .label {
        font-size: 0.88rem;
        color: var(--dash-text-muted);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: color 0.4s;
    }

    /* Content Area Containers */
    .card {
        background: var(--dash-glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--dash-glass-border);
        border-radius: 24px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 20px 40px -15px var(--dash-card-shadow);
        opacity: 0;
        transform: translateY(30px);
        animation: dashRevealContainer 0.8s var(--dash-smooth) 0.4s forwards;
        transition: background 0.4s, border 0.4s, box-shadow 0.4s;
    }

    .card h2 {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--dash-text-main);
        margin-bottom: 24px;
        letter-spacing: -0.3px;
        transition: color 0.4s;
    }

    /* Glassmorphic Responsive Data Table Components */
    .tbl-wrap {
        overflow-x: auto;
        border-radius: 14px;
        border: 1px solid var(--dash-glass-border);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.95rem;
    }

    th {
        background: var(--dash-table-header);
        color: var(--dash-text-main);
        font-weight: 600;
        padding: 16px 20px;
        border-bottom: 1px solid var(--dash-glass-border);
        transition: color 0.4s, background 0.4s, border 0.4s;
    }

    td {
        padding: 16px 20px;
        color: var(--dash-text-main);
        opacity: 0.9;
        border-bottom: 1px solid var(--dash-glass-border);
        transition: background 0.2s, color 0.4s;
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:hover td {
        background: rgba(128, 128, 128, 0.04);
    }

    /* Global Dynamic Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 10px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s var(--dash-smooth);
        border: none;
    }

    .btn-sm {
        padding: 7px 14px;
        font-size: 0.82rem;
        border-radius: 8px;
    }

    .btn-primary {
        background: linear-gradient(90deg, #0066ff, #0044cc);
        color: #fff;
        box-shadow: 0 4px 15px rgba(0, 102, 255, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 102, 255, 0.45), 0 0 10px #00c6ff;
    }

    .btn-success {
        background: rgba(22, 163, 74, 0.15);
        color: #4ade80;
        border: 1px solid rgba(22, 163, 74, 0.25);
    }

    [data-theme="light"] .btn-success {
        background: rgba(22, 163, 74, 0.1);
        color: #15803d;
        border: 1px solid rgba(22, 163, 74, 0.2);
    }

    .btn-success:hover {
        background: #16a34a;
        color: #fff;
        box-shadow: 0 0 15px rgba(22, 163, 74, 0.4);
    }

    .btn-danger {
        background: rgba(220, 38, 38, 0.12);
        color: #f87171;
        border: 1px solid rgba(220, 38, 38, 0.22);
    }

    [data-theme="light"] .btn-danger {
        background: rgba(220, 38, 38, 0.08);
        color: #b91c1c;
        border: 1px solid rgba(220, 38, 38, 0.18);
    }

    .btn-danger:hover {
        background: #dc2626;
        color: #fff;
        box-shadow: 0 0 15px rgba(220, 38, 38, 0.4);
    }

    /* Contextual Badges styling */
    .badge {
        display: inline-block;
        padding: 4px 10px;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25); }
    .badge-shipped { background: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.25); }
    .badge-delivered { background: rgba(22, 163, 74, 0.15); color: #4ade80; border: 1px solid rgba(22, 163, 74, 0.25); }
    .badge-cancelled { background: rgba(220, 38, 38, 0.12); color: #f87171; border: 1px solid rgba(220, 38, 38, 0.22); }

    [data-theme="light"] .badge-pending { background: rgba(245, 158, 11, 0.1); color: #b45309; }
    [data-theme="light"] .badge-shipped { background: rgba(37, 99, 235, 0.1); color: #1d4ed8; }
    [data-theme="light"] .badge-delivered { background: rgba(22, 163, 74, 0.1); color: #15803d; }
    [data-theme="light"] .badge-cancelled { background: rgba(220, 38, 38, 0.08); color: #b91c1c; }

    /* Sidebar Navigation — inherited from includes/sidebar.php */

    /* Main content offset */
    .main-area {
        margin-left: 255px;
        padding: 30px 36px;
        position: relative;
        z-index: 10;
        min-height: 100vh;
    }

    .alert-info {
        background: rgba(37, 99, 235, 0.1);
        color: #60a5fa;
        border: 1px solid rgba(37, 99, 235, 0.15);
        padding: 16px;
        border-radius: 12px;
        font-size: 0.9rem;
    }

    [data-theme="light"] .alert-info {
        color: #1d4ed8;
    }

    /* Quick action links */
    .quick-actions {
        margin-top: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .quick-actions a {
        background: var(--dash-table-header);
        color: var(--dash-text-main);
        border: 1px solid var(--dash-glass-border);
        padding: 8px 16px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 600;
        transition: all 0.3s var(--dash-smooth);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .quick-actions a:hover {
        transform: translateY(-2px);
        border-color: #0066ff;
        box-shadow: 0 4px 12px var(--dash-glow-primary);
    }

    /* Table links */
    table a {
        color: #60a5fa;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
    }

    table a:hover {
        color: #00c6ff;
    }

    [data-theme="light"] table a { color: #2563eb; }
    [data-theme="light"] table a:hover { color: #0066ff; }

    /* Keyframe Execution Pipeline definitions */
    @keyframes dashFadeDown { to { opacity: 1; transform: translateY(0); } }
    @keyframes dashRevealCard { to { opacity: 1; transform: scale(1) translateY(0); } }
    @keyframes dashRevealContainer { to { opacity: 1; transform: translateY(0); } }

    @media(max-width: 992px) {
        .main-area { margin-left: 220px; padding: 20px; }
    }

    @media(max-width: 768px) {
        .main-area { margin-left: 0; padding: 20px 15px; }
        .dash-header { flex-direction: column; align-items: flex-start; }
        td, th { padding: 12px 14px; font-size: 0.88rem; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

</head>
<body>

<div class="dash-bg-animation">
    <div class="dash-blob dash-blob-1"></div>
    <div class="dash-blob dash-blob-2"></div>
</div>

<?php include('includes/sidebar.php'); ?>

<!-- Main Content -->
<div class="main-area">

    <div class="theme-toggle-container">
        <button class="theme-toggle-btn" id="themeToggleBtn">
            <span id="themeToggleIcon">☀️</span>
            <span id="themeToggleText">Light Mode</span>
        </button>
    </div>

    <div class="dash-header">
        <h1 class="dash-title">👋 Welcome, <?php echo htmlspecialchars($admin_name); ?></h1>
        <div style="display:flex; gap:10px; align-items:center;">
            <span id="liveStatusBadge" style="font-size:0.75rem; background:rgba(34,197,94,0.15); color:#4ade80; border:1px solid rgba(34,197,94,0.3); padding:5px 12px; border-radius:20px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <span style="width:7px; height:7px; border-radius:50%; background:#4ade80; display:inline-block; box-shadow:0 0 8px #4ade80; animation: blink 1.5s infinite;"></span> Live Monitored
            </span>
            <button id="soundToggleBtn" class="btn btn-sm" style="font-size:0.75rem; background:rgba(255,255,255,0.06); border:1px solid var(--dash-glass-border); color:var(--dash-text-main); border-radius:20px; font-weight:600;" title="Toggle Order Sound Notification">
                🔔 Sound ON
            </button>
            <a href="add_product.php" class="btn btn-primary btn-sm">+ Add Product</a>
        </div>
    </div>

    <?php if ($low_stock_count > 0): ?>
    <div class="card mb-4" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); padding: 18px 24px; border-radius: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.5rem;">⚠️</span>
                <div>
                    <h4 style="font-size: 0.98rem; margin: 0; color: #fbbf24; font-weight: 700;">Low Stock Inventory Warning</h4>
                    <p style="font-size: 0.84rem; margin: 2px 0 0 0; color: var(--dash-text-muted);">
                        <?php 
                        $names = array_map(fn($item) => htmlspecialchars($item['name']) . " (" . $item['stock'] . " left)", $low_stock_items);
                        echo implode(' • ', $names);
                        ?>
                    </p>
                </div>
            </div>
            <a href="products.php" class="btn btn-sm" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4);">
                📦 Restock Inventory →
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="number" id="statProducts"><?php echo $total_products; ?></div>
            <div class="label">Products</div>
        </div>
        <div class="stat-card">
            <div class="number" id="statCustomers" style="color:#f59e0b;"><?php echo $total_users; ?></div>
            <div class="label">Customers</div>
        </div>
        <div class="stat-card">
            <div class="number" id="statPending" style="color:#2563eb;"><?php echo $pending_orders; ?></div>
            <div class="label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="number" id="statDelivered" style="color:#16a34a;"><?php echo $delivered_orders; ?></div>
            <div class="label">Delivered</div>
        </div>
        <div class="stat-card">
            <div class="number" id="statTotalOrders" style="color:#7c3aed;"><?php echo $total_orders; ?></div>
            <div class="label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="number" id="statRevenue" style="color:#00c6ff;">₹<?php echo number_format($total_revenue, 0); ?></div>
            <div class="label">Revenue</div>
        </div>
    </div>

    <!-- Interactive Visual Analytics Charts Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div class="card" style="margin-bottom: 0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                <h2 style="font-size:1.1rem; margin:0;">📈 Monthly Revenue &amp; Orders</h2>
                <span style="font-size:0.75rem; background:rgba(0,198,255,0.15); color:#00c6ff; padding:3px 10px; border-radius:20px; font-weight:600;">Real-Time</span>
            </div>
            <div style="height: 250px; position: relative;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                <h2 style="font-size:1.1rem; margin:0;">🍩 Order Status Breakdown</h2>
                <span style="font-size:0.75rem; background:rgba(124,58,237,0.15); color:#a78bfa; padding:3px 10px; border-radius:20px; font-weight:600;">Distribution</span>
            </div>
            <div style="height: 250px; position: relative; display:flex; justify-content:center; align-items:center;">
                <canvas id="statusDoughnutChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Recent Orders</h2>
        <?php if(mysqli_num_rows($recent_orders) == 0): ?>
            <div class="alert-info">No orders yet.</div>
        <?php else: ?>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>User ID</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="recentOrdersTableBody">
                <?php while($row = mysqli_fetch_assoc($recent_orders)): ?>
                <tr>
                    <td><a href="order_details.php?id=<?php echo $row['id']; ?>">#<?php echo $row['id']; ?></a></td>
                    <td><?php echo $row['user_id']; ?></td>
                    <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td><span class="badge badge-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="quick-actions">
            <a href="orders.php">📋 View All Orders →</a>
            <a href="products.php">📦 Manage Products</a>
            <a href="add_product.php">➕ Add Product</a>
            <a href="reports.php">📊 View Reports</a>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
    // Theme Switcher Engine Initialization
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeToggleIcon = document.getElementById('themeToggleIcon');
    const themeToggleText = document.getElementById('themeToggleText');
    
    // Check for saved user preference, otherwise default to dark mode
    const currentTheme = localStorage.getItem('admin_theme') || 'dark';
    
    if (currentTheme === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
        document.body.style.background = '#f8fafc';
        document.body.style.color = '#1e293b';
        themeToggleIcon.textContent = '🌙';
        themeToggleText.textContent = 'Dark Mode';
    }

    themeToggleBtn.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme');
        if (theme === 'light') {
            document.documentElement.removeAttribute('data-theme');
            document.body.style.background = '#070b19';
            document.body.style.color = '#ffffff';
            localStorage.setItem('admin_theme', 'dark');
            themeToggleIcon.textContent = '☀️';
            themeToggleText.textContent = 'Light Mode';
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            document.body.style.background = '#f8fafc';
            document.body.style.color = '#1e293b';
            localStorage.setItem('admin_theme', 'light');
            themeToggleIcon.textContent = '🌙';
            themeToggleText.textContent = 'Dark Mode';
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.06)';

    // Chart 1: Revenue & Orders Dual Axis Chart
    const ctx1 = document.getElementById('revenueTrendChart');
    if (ctx1) {
        new Chart(ctx1.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_months) ?>,
                datasets: [
                    {
                        label: 'Revenue (₹)',
                        data: <?= json_encode($chart_revenue) ?>,
                        backgroundColor: 'rgba(0, 198, 255, 0.65)',
                        borderColor: '#00c6ff',
                        borderWidth: 2,
                        borderRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: <?= json_encode($chart_orders) ?>,
                        type: 'line',
                        borderColor: '#f59e0b',
                        backgroundColor: '#f59e0b',
                        borderWidth: 3,
                        tension: 0.35,
                        pointRadius: 5,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: textColor, font: { family: 'Segoe UI', size: 12 } } }
                },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: gridColor } },
                    y: {
                        type: 'linear',
                        position: 'left',
                        ticks: { color: textColor, callback: value => '₹' + value },
                        grid: { color: gridColor }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        ticks: { color: '#f59e0b', precision: 0 },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    // Chart 2: Order Status Doughnut Chart
    const ctx2 = document.getElementById('statusDoughnutChart');
    if (ctx2) {
        new Chart(ctx2.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($status_labels) ?>,
                datasets: [{
                    data: <?= json_encode($status_counts) ?>,
                    backgroundColor: [
                        '#f59e0b', // Pending
                        '#2563eb', // Processing
                        '#7c3aed', // Shipped
                        '#16a34a', // Delivered
                        '#ef4444'  // Cancelled
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { color: textColor, font: { family: 'Segoe UI', size: 12 } } }
                },
                cutout: '68%'
            }
        });
    }
});
</script>

<!-- Live Order Toast Container -->
<div id="liveOrderToastContainer" style="position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; max-width: 380px; width: 90%;"></div>

<script>
(function() {
    let currentLastId = <?= (int)$latest_order_id ?>;
    let isMuted = localStorage.getItem('admin_sound_muted') === '1';

    const soundBtn = document.getElementById('soundToggleBtn');
    if (soundBtn) {
        function updateSoundBtnUI() {
            if (isMuted) {
                soundBtn.textContent = '🔕 Sound OFF';
                soundBtn.style.color = '#f87171';
            } else {
                soundBtn.textContent = '🔔 Sound ON';
                soundBtn.style.color = 'var(--dash-text-main)';
            }
        }
        updateSoundBtnUI();

        soundBtn.addEventListener('click', function() {
            isMuted = !isMuted;
            localStorage.setItem('admin_sound_muted', isMuted ? '1' : '0');
            updateSoundBtnUI();
        });
    }

    // Web Audio Synthesizer Sound Chime
    function playOrderChime() {
        if (isMuted) return;
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();

            const osc1 = ctx.createOscillator();
            const osc2 = ctx.createOscillator();
            const gain = ctx.createGain();

            osc1.type = 'sine';
            osc2.type = 'triangle';

            osc1.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
            osc1.frequency.exponentialRampToValueAtTime(659.25, ctx.currentTime + 0.12); // E5

            osc2.frequency.setValueAtTime(659.25, ctx.currentTime + 0.12);
            osc2.frequency.exponentialRampToValueAtTime(783.99, ctx.currentTime + 0.28); // G5

            gain.gain.setValueAtTime(0.25, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);

            osc1.connect(gain);
            osc2.connect(gain);
            gain.connect(ctx.destination);

            osc1.start();
            osc2.start();
            osc1.stop(ctx.currentTime + 0.5);
            osc2.stop(ctx.currentTime + 0.5);
        } catch (e) {}
    }

    function prependOrderRow(order) {
        const tbody = document.getElementById('recentOrdersTableBody');
        if (!tbody) return;

        const tr = document.createElement('tr');
        tr.style.cssText = 'background: rgba(34, 197, 94, 0.15); transition: background 2s ease;';
        tr.innerHTML = `
            <td><a href="order_details.php?id=${order.id}">#${order.id}</a></td>
            <td>${order.user_id || 'Guest'}</td>
            <td>₹${order.total_amount.toFixed(2)}</td>
            <td><span class="badge badge-pending">Pending</span></td>
        `;

        if (tbody.firstChild) {
            tbody.insertBefore(tr, tbody.firstChild);
        } else {
            tbody.appendChild(tr);
        }

        setTimeout(() => {
            tr.style.background = 'transparent';
        }, 2500);

        if (tbody.children.length > 5) {
            tbody.removeChild(tbody.lastChild);
        }
    }

    function showOrderToast(order) {
        const container = document.getElementById('liveOrderToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.style.cssText = `
            background: rgba(10, 14, 26, 0.92);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 212, 255, 0.4);
            border-left: 4px solid #00d4ff;
            border-radius: 14px;
            padding: 16px 20px;
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(0, 212, 255, 0.2);
            animation: toastSlideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            font-family: 'Segoe UI', sans-serif;
        `;

        toast.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="background: rgba(0, 212, 255, 0.15); width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #00d4ff;">🔔</div>
                    <div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: #ffffff;">New Order #${order.id} Received!</div>
                        <div style="font-size: 0.82rem; color: #94a3b8;">Customer: ${order.customer_name} • ₹${order.total_amount.toFixed(2)}</div>
                    </div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: #94a3b8; font-size: 16px; cursor: pointer; padding: 0;">✕</button>
            </div>
            <div style="margin-top: 10px; display: flex; justify-content: flex-end;">
                <a href="order_details.php?id=${order.id}" style="color: #00d4ff; text-decoration: none; font-size: 0.82rem; font-weight: 600;">View Details →</a>
            </div>
        `;

        container.appendChild(toast);

        // Play sound chime if enabled
        playOrderChime();

        // Prepend row in recent orders table
        prependOrderRow(order);

        // Auto remove toast after 7 seconds
        setTimeout(() => {
            toast.style.animation = 'toastFadeOut 0.5s ease forwards';
            setTimeout(() => toast.remove(), 500);
        }, 7000);
    }

    function checkNewOrders() {
        fetch('check_new_orders.php?last_id=' + currentLastId)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.has_new && data.new_orders) {
                        data.new_orders.forEach(order => {
                            showOrderToast(order);
                        });
                        currentLastId = data.max_id;
                    }

                    // Dynamically update stat numbers
                    if (data.stats) {
                        const s = data.stats;
                        const elProd = document.getElementById('statProducts');
                        const elCust = document.getElementById('statCustomers');
                        const elPend = document.getElementById('statPending');
                        const elDelv = document.getElementById('statDelivered');
                        const elTot  = document.getElementById('statTotalOrders');
                        const elRev  = document.getElementById('statRevenue');

                        if (elProd) elProd.textContent = s.products;
                        if (elCust) elCust.textContent = s.customers;
                        if (elPend) elPend.textContent = s.pending;
                        if (elDelv) elDelv.textContent = s.delivered;
                        if (elTot)  elTot.textContent  = s.total;
                        if (elRev)  elRev.textContent  = '₹' + Math.round(s.revenue).toLocaleString();
                    }
                }
            })
            .catch(() => {});
    }

    // Poll every 10 seconds for new orders
    setInterval(checkNewOrders, 10000);
})();
</script>

<style>
@keyframes toastSlideIn {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes toastFadeOut {
    from { opacity: 1; transform: translateY(0) scale(1); }
    to   { opacity: 0; transform: translateY(20px) scale(0.95); }
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>

</body>
</html>
