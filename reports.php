<?php
include("../config/db.php");
require_once('auth_guard.php');


// Date Range Filter Logic
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where_clause = " WHERE status != 'Cancelled'";
$where_clause_orders = " WHERE 1=1";
$where_items = " WHERE orders.status != 'Cancelled'";

if ($start_date != '') {
    $s_date = mysqli_real_escape_string($conn, $start_date);
    $where_clause .= " AND DATE(created_at) >= '$s_date'";
    $where_clause_orders .= " AND DATE(created_at) >= '$s_date'";
    $where_items .= " AND DATE(orders.created_at) >= '$s_date'";
}

if ($end_date != '') {
    $e_date = mysqli_real_escape_string($conn, $end_date);
    $where_clause .= " AND DATE(created_at) <= '$e_date'";
    $where_clause_orders .= " AND DATE(created_at) <= '$e_date'";
    $where_items .= " AND DATE(orders.created_at) <= '$e_date'";
}

// ── CSV Export Handler ──
if (isset($_GET['export_csv']) && $_GET['export_csv'] == '1') {
    $filename = "sales_report_" . date('Y-m-d_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');

    // CSV Header row
    fputcsv($output, ['Order ID', 'Date & Time', 'Customer Name', 'Mobile', 'Order Status', 'Total Amount (INR)']);

    $export_q = mysqli_query($conn, "
        SELECT orders.id, orders.created_at, IFNULL(orders.fullname, users.name) AS cname, orders.mobile, orders.status, orders.total_amount 
        FROM orders 
        LEFT JOIN users ON orders.user_id = users.id 
        " . $where_clause_orders . "
        ORDER BY orders.id DESC
    ");

    if ($export_q && mysqli_num_rows($export_q) > 0) {
        while ($row = mysqli_fetch_assoc($export_q)) {
            fputcsv($output, [
                '#' . $row['id'],
                date('Y-m-d H:i', strtotime($row['created_at'])),
                $row['cname'] ?: 'Guest Customer',
                $row['mobile'] ?: 'N/A',
                $row['status'],
                number_format($row['total_amount'], 2, '.', '')
            ]);
        }
    }
    fclose($output);
    exit();
}

// Fetch Key Performance Indicators (KPIs)
$revenue_query = mysqli_query($conn, "SELECT SUM(total_amount) AS total_revenue FROM orders" . $where_clause);
$revenue_data = mysqli_fetch_assoc($revenue_query);
$total_revenue = $revenue_data['total_revenue'] ?: 0.00;

$orders_query = mysqli_query($conn, "SELECT COUNT(*) AS total_orders FROM orders" . $where_clause_orders);
$orders_data = mysqli_fetch_assoc($orders_query);
$total_orders = $orders_data['total_orders'] ?: 0;

$completed_query = mysqli_query($conn, "SELECT COUNT(*) AS completed_orders FROM orders" . $where_clause_orders . " AND status = 'Delivered'");
$completed_data = mysqli_fetch_assoc($completed_query);
$completed_orders = $completed_data['completed_orders'] ?: 0;

$avg_order_value = $total_orders > 0 ? ($total_revenue / $total_orders) : 0.00;

// Total Customers
$cust_query = mysqli_query($conn, "SELECT COUNT(*) AS total_customers FROM users");
$total_customers = mysqli_fetch_assoc($cust_query)['total_customers'] ?: 0;

// Fetch Top Selling Products
$top_products_query = mysqli_query($conn, "
    SELECT products.name, products.image, products.price, products.selling_price, SUM(order_items.quantity) AS qty_sold, SUM(order_items.price * order_items.quantity) AS revenue 
    FROM order_items 
    JOIN products ON order_items.product_id = products.id 
    JOIN orders ON order_items.order_id = orders.id
    " . $where_items . "
    GROUP BY products.id 
    ORDER BY qty_sold DESC 
    LIMIT 5
");

// Fetch Sales by Category
$category_sales_query = mysqli_query($conn, "
    SELECT categories.category_name, SUM(order_items.quantity) AS qty_sold, SUM(order_items.price * order_items.quantity) AS revenue 
    FROM order_items 
    JOIN products ON order_items.product_id = products.id 
    JOIN product_categories ON products.id = product_categories.product_id 
    JOIN categories ON product_categories.category_id = categories.id 
    JOIN orders ON order_items.order_id = orders.id
    " . $where_items . "
    GROUP BY categories.id 
    ORDER BY revenue DESC
");

// Collect category data for chart
$cat_labels = [];
$cat_values = [];
$cat_colors = ['#16a34a','#2563eb','#f59e0b','#7c3aed','#ec4899','#00c6ff','#ef4444'];
if(mysqli_num_rows($category_sales_query) > 0) {
    while($cc = mysqli_fetch_assoc($category_sales_query)) {
        $cat_labels[] = $cc['category_name'];
        $cat_values[] = (float)$cc['revenue'];
    }
    mysqli_data_seek($category_sales_query, 0);
}

// Fetch Monthly Sales Breakdown (for chart + table)
$monthly_sales_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%M %Y') AS month, DATE_FORMAT(created_at, '%b') AS short_month, COUNT(id) AS monthly_orders, SUM(total_amount) AS monthly_revenue 
    FROM orders 
    " . $where_clause . "
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
    ORDER BY created_at ASC
    LIMIT 12
");

$chart_labels = [];
$chart_revenue = [];
$chart_orders = [];
$monthly_rows = [];
if(mysqli_num_rows($monthly_sales_query) > 0) {
    while($m = mysqli_fetch_assoc($monthly_sales_query)) {
        $chart_labels[] = $m['short_month'];
        $chart_revenue[] = (float)$m['monthly_revenue'];
        $chart_orders[] = (int)$m['monthly_orders'];
        $monthly_rows[] = $m;
    }
}

// Recent orders for the "Last Orders" table
$recent_orders = mysqli_query($conn, "
    SELECT orders.*, users.name AS customer_name 
    FROM orders 
    JOIN users ON orders.user_id = users.id 
    ORDER BY orders.id DESC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Reports - Raj Kathiyawadi Mukhwash</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
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

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; }

    body {
        background: #070b19;
        color: var(--dash-text-main);
        line-height: 1.6;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
        transition: background 0.4s var(--dash-smooth), color 0.4s var(--dash-smooth);
    }

    /* Blobs */
    .dash-bg-animation { position: fixed; width: 100vw; height: 100vh; top: 0; left: 0; z-index: 0; pointer-events: none; }
    .dash-blob { position: absolute; border-radius: 50%; filter: blur(140px); opacity: var(--dash-blob-opacity); animation: dashFloat 16s infinite alternate ease-in-out; transition: opacity 0.4s ease; }
    .dash-blob-1 { width: 400px; height: 400px; top: 5%; right: -5%; background: linear-gradient(135deg, #0066ff, #7c3aed); }
    .dash-blob-2 { width: 500px; height: 500px; bottom: -10%; left: 10%; background: linear-gradient(135deg, #00c6ff, #0044cc); }
    @keyframes dashFloat { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(-40px, 40px) scale(1.1); } }

    /* Sidebar */
    .sidebar { position: fixed; left: 0; top: 0; width: 250px; height: 100vh; background: rgba(7, 11, 25, 0.95); backdrop-filter: blur(20px); border-right: 1px solid var(--dash-glass-border); z-index: 100; display: flex; flex-direction: column; transition: background 0.4s; }
    [data-theme="light"] .sidebar { background: rgba(255, 255, 255, 0.95); border-right-color: rgba(0,0,0,0.08); }
    .sidebar-brand { padding: 28px 24px; text-align: center; border-bottom: 1px solid var(--dash-glass-border); }
    .sidebar-brand h3 { color: var(--dash-text-main); font-size: 1.15rem; font-weight: 700; margin: 0; transition: color 0.4s; }
    .sidebar-brand span { color: #0066ff; }
    .sidebar-nav { flex: 1; padding: 16px 0; }
    .sidebar-nav a { display: flex; align-items: center; gap: 14px; color: var(--dash-text-muted); text-decoration: none; padding: 14px 28px; font-size: 0.95rem; font-weight: 500; transition: all 0.3s ease; border-left: 3px solid transparent; }
    .sidebar-nav a:hover, .sidebar-nav a.active { color: var(--dash-text-main); background: var(--dash-glass-bg); border-left-color: #0066ff; padding-left: 32px; }
    .sidebar-nav a .nav-icon { font-size: 1.1rem; width: 24px; text-align: center; }

    /* Main */
    .main-area { margin-left: 250px; padding: 30px 36px; position: relative; z-index: 10; min-height: 100vh; }

    /* Theme Toggle */
    .top-bar { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 10px; opacity: 0; transform: translateY(-10px); animation: dashFadeDown 0.6s var(--dash-smooth) 0.1s forwards; }
    .theme-toggle-btn { background: var(--dash-glass-bg); border: 1px solid var(--dash-glass-border); color: var(--dash-text-main); padding: 10px 16px; border-radius: 12px; cursor: pointer; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px var(--dash-card-shadow); transition: all 0.3s var(--dash-smooth); }
    .theme-toggle-btn:hover { transform: translateY(-2px); border-color: #0066ff; }

    /* Header */
    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; opacity: 0; transform: translateY(-20px); animation: dashFadeDown 0.6s var(--dash-smooth) forwards; }
    .dash-title { font-size: 2rem; font-weight: 700; color: var(--dash-text-main); animation: dashNeonPulse 3s ease-in-out infinite alternate; transition: color 0.4s; }
    @keyframes dashNeonPulse { 0%, 100% { text-shadow: 0 0 4px rgba(0,102,255,0.1); } 50% { text-shadow: 0 0 12px #00c6ff, 0 0 20px #0066ff; } }

    /* KPI Stats Row */
    .kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .kpi-card { background: var(--dash-glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--dash-glass-border); border-radius: 20px; padding: 22px 24px; display: flex; align-items: center; gap: 16px; box-shadow: 0 12px 30px -5px var(--dash-card-shadow); transition: all 0.4s var(--dash-elastic); opacity: 0; transform: scale(0.9) translateY(20px); animation: dashRevealCard 0.6s var(--dash-elastic) forwards; }
    .kpi-card:hover { transform: translateY(-4px) scale(1.02); border-color: #0066ff; box-shadow: 0 18px 35px -8px rgba(0,102,255,0.2); }
    .kpi-row .kpi-card:nth-child(1) { animation-delay: 0.1s; }
    .kpi-row .kpi-card:nth-child(2) { animation-delay: 0.15s; }
    .kpi-row .kpi-card:nth-child(3) { animation-delay: 0.2s; }
    .kpi-row .kpi-card:nth-child(4) { animation-delay: 0.25s; }
    .kpi-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
    .kpi-icon.green { background: rgba(22, 163, 74, 0.15); color: #4ade80; }
    .kpi-icon.blue { background: rgba(37, 99, 235, 0.15); color: #60a5fa; }
    .kpi-icon.purple { background: rgba(124, 58, 237, 0.15); color: #a78bfa; }
    .kpi-icon.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
    [data-theme="light"] .kpi-icon.green { color: #16a34a; }
    [data-theme="light"] .kpi-icon.blue { color: #2563eb; }
    [data-theme="light"] .kpi-icon.purple { color: #7c3aed; }
    [data-theme="light"] .kpi-icon.amber { color: #d97706; }
    .kpi-info .kpi-label { font-size: 0.82rem; color: var(--dash-text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; transition: color 0.4s; }
    .kpi-info .kpi-value { font-size: 1.6rem; font-weight: 700; color: var(--dash-text-main); letter-spacing: -0.5px; transition: color 0.4s; }
    .kpi-info .kpi-sub { font-size: 0.78rem; color: #4ade80; font-weight: 600; margin-top: 2px; }
    [data-theme="light"] .kpi-info .kpi-sub { color: #16a34a; }

    /* Filter Card */
    .filter-card { background: var(--dash-glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--dash-glass-border); border-radius: 18px; padding: 22px 28px; margin-bottom: 30px; box-shadow: 0 12px 30px -5px var(--dash-card-shadow); opacity: 0; transform: translateY(20px); animation: dashRevealContainer 0.7s var(--dash-smooth) 0.25s forwards; transition: background 0.4s, border 0.4s; }
    .filter-form { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; }
    .filter-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 180px; }
    .filter-group label { font-size: 0.82rem; font-weight: 600; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .filter-group input { padding: 11px 14px; background: rgba(255,255,255,0.04); border: 1px solid var(--dash-glass-border); border-radius: 10px; color: var(--dash-text-main); font-size: 0.92rem; transition: all 0.3s; }
    [data-theme="light"] .filter-group input { background: rgba(0,0,0,0.03); color: #1e293b; }
    .filter-group input:focus { outline: none; border-color: #0066ff; box-shadow: 0 0 0 3px var(--dash-glow-primary); }
    .filter-btn { padding: 11px 22px; border-radius: 10px; border: none; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s var(--dash-smooth); white-space: nowrap; }
    .filter-btn.primary { background: linear-gradient(90deg, #0066ff, #0044cc); color: #fff; box-shadow: 0 4px 12px rgba(0,102,255,0.25); }
    .filter-btn.primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,102,255,0.4); }
    .filter-btn.reset { background: var(--dash-glass-bg); color: var(--dash-text-muted); border: 1px solid var(--dash-glass-border); text-decoration: none; display: inline-flex; align-items: center; }
    .filter-btn.reset:hover { color: var(--dash-text-main); border-color: #0066ff; }

    /* Dashboard Grid Layout (like screenshot) */
    .grid-main { display: grid; grid-template-columns: 1fr 360px; gap: 24px; margin-bottom: 30px; }

    /* Glass Card */
    .card { background: var(--dash-glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--dash-glass-border); border-radius: 22px; padding: 28px; box-shadow: 0 16px 35px -10px var(--dash-card-shadow); opacity: 0; transform: translateY(25px); animation: dashRevealContainer 0.8s var(--dash-smooth) 0.35s forwards; transition: background 0.4s, border 0.4s, box-shadow 0.4s; }
    .card h2 { font-size: 1.25rem; font-weight: 700; color: var(--dash-text-main); margin-bottom: 20px; letter-spacing: -0.3px; transition: color 0.4s; }
    .card h2 .icon { margin-right: 8px; }

    /* Chart Container */
    .chart-container { position: relative; width: 100%; height: 280px; }

    /* Glassmorphic Table */
    .tbl-wrap { overflow-x: auto; border-radius: 14px; border: 1px solid var(--dash-glass-border); }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.92rem; }
    th { background: var(--dash-table-header); color: var(--dash-text-main); font-weight: 600; padding: 14px 18px; border-bottom: 1px solid var(--dash-glass-border); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; transition: color 0.4s, background 0.4s; }
    td { padding: 14px 18px; color: var(--dash-text-main); opacity: 0.9; border-bottom: 1px solid var(--dash-glass-border); transition: background 0.2s, color 0.4s; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(128,128,128,0.04); }

    /* Trending Product List */
    .product-list { list-style: none; }
    .product-item { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--dash-glass-border); transition: transform 0.2s; }
    .product-item:last-child { border-bottom: none; }
    .product-item:hover { transform: translateX(4px); }
    .product-thumb { width: 44px; height: 44px; border-radius: 10px; object-fit: cover; background: rgba(255,255,255,0.05); flex-shrink: 0; }
    .product-info { flex: 1; }
    .product-name { font-weight: 600; font-size: 0.92rem; color: var(--dash-text-main); margin-bottom: 2px; transition: color 0.4s; }
    .product-qty { font-size: 0.78rem; color: var(--dash-text-muted); transition: color 0.4s; }
    .product-price { font-weight: 700; color: var(--dash-text-main); font-size: 0.95rem; transition: color 0.4s; }

    /* Category Chart */
    .donut-container { display: flex; align-items: center; gap: 30px; flex-wrap: wrap; justify-content: center; }
    .donut-wrap { width: 180px; height: 180px; flex-shrink: 0; }
    .cat-legend { list-style: none; }
    .cat-legend li { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 0.88rem; color: var(--dash-text-main); font-weight: 500; transition: color 0.4s; }
    .cat-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }

    /* Status Badges */
    .status-badge { display: inline-block; padding: 4px 10px; font-size: 0.73rem; font-weight: 700; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-pending { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.25); }
    .status-shipped { background: rgba(37,99,235,0.15); color: #60a5fa; border: 1px solid rgba(37,99,235,0.25); }
    .status-delivered { background: rgba(22,163,74,0.15); color: #4ade80; border: 1px solid rgba(22,163,74,0.25); }
    .status-cancelled { background: rgba(220,38,38,0.12); color: #f87171; border: 1px solid rgba(220,38,38,0.22); }
    [data-theme="light"] .status-pending { background: rgba(245,158,11,0.1); color: #b45309; }
    [data-theme="light"] .status-shipped { background: rgba(37,99,235,0.1); color: #1d4ed8; }
    [data-theme="light"] .status-delivered { background: rgba(22,163,74,0.1); color: #15803d; }
    [data-theme="light"] .status-cancelled { background: rgba(220,38,38,0.08); color: #b91c1c; }

    table a { color: #60a5fa; text-decoration: none; font-weight: 600; transition: color 0.2s; }
    table a:hover { color: #00c6ff; }
    [data-theme="light"] table a { color: #2563eb; }

    /* Animations */
    @keyframes dashFadeDown { to { opacity: 1; transform: translateY(0); } }
    @keyframes dashRevealCard { to { opacity: 1; transform: scale(1) translateY(0); } }
    @keyframes dashRevealContainer { to { opacity: 1; transform: translateY(0); } }

    /* Responsive */
    @media(max-width: 1100px) { .grid-main { grid-template-columns: 1fr; } }
    @media(max-width: 992px) { .sidebar { width: 220px; } .main-area { margin-left: 220px; padding: 20px; } }
    @media(max-width: 768px) {
        .sidebar { width: 100%; height: auto; position: relative; }
        .sidebar-nav { display: flex; flex-wrap: wrap; padding: 10px; }
        .sidebar-nav a { padding: 10px 14px; border-left: none; }
        .main-area { margin-left: 0; padding: 20px 15px; }
        .kpi-row { grid-template-columns: repeat(2, 1fr); }
        .filter-form { flex-direction: column; }
        .dash-header { flex-direction: column; align-items: flex-start; }
    }
</style>

</head>
<body>

<div class="dash-bg-animation">
    <div class="dash-blob dash-blob-1"></div>
    <div class="dash-blob dash-blob-2"></div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand"><h3>Raj <span>Kathiyawadi</span></h3></div>
    <div class="sidebar-nav">
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="products.php"><span class="nav-icon">📦</span> Products</a>
        <a href="categories.php"><span class="nav-icon">📂</span> Categories</a>
        <a href="orders.php"><span class="nav-icon">🛒</span> Orders</a>
        <a href="customers.php"><span class="nav-icon">👥</span> Customers</a>
        <a href="reports.php" class="active"><span class="nav-icon">📊</span> Reports</a>
        <a href="logout.php"><span class="nav-icon">🚪</span> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-area">

    <div class="top-bar">
        <button class="theme-toggle-btn" id="themeToggleBtn">
            <span id="themeToggleIcon">☀️</span>
            <span id="themeToggleText">Light Mode</span>
        </button>
    </div>

    <div class="dash-header">
        <h1 class="dash-title">📊 Sales Reports & Analytics</h1>
        <a href="dashboard.php" style="color: var(--dash-text-muted); text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: color 0.2s;">← Back to Dashboard</a>
    </div>

    <!-- Date Filter -->
    <div class="filter-card">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="filter-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <button type="submit" class="filter-btn primary">🔍 Filter</button>
            <a href="reports.php" class="filter-btn reset">Reset</a>
            <a href="reports.php?export_csv=1<?php echo !empty($start_date)?'&start_date='.urlencode($start_date):''; ?><?php echo !empty($end_date)?'&end_date='.urlencode($end_date):''; ?>" class="filter-btn" style="background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">📥 Export CSV</a>
        </form>
    </div>

    <!-- KPI Stats Row (like screenshot top row) -->
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon green">💰</div>
            <div class="kpi-info">
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value">₹<?php echo number_format($total_revenue, 2); ?></div>
                <div class="kpi-sub"><?php echo $completed_orders; ?> Delivered</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon blue">👥</div>
            <div class="kpi-info">
                <div class="kpi-label">Total Customers</div>
                <div class="kpi-value"><?php echo number_format($total_customers); ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple">📦</div>
            <div class="kpi-info">
                <div class="kpi-label">Total Orders</div>
                <div class="kpi-value"><?php echo number_format($total_orders); ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon amber">📈</div>
            <div class="kpi-info">
                <div class="kpi-label">Avg. Order Value</div>
                <div class="kpi-value">₹<?php echo number_format($avg_order_value, 2); ?></div>
            </div>
        </div>
    </div>

    <!-- Main Grid: Chart + Trending Products (like screenshot) -->
    <div class="grid-main">
        <!-- Sales Growth Chart -->
        <div class="card">
            <h2><span class="icon">📈</span> Sales Growth</h2>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Trending Products Sidebar (like screenshot right panel) -->
        <div class="card">
            <h2><span class="icon">🔥</span> Trending Products</h2>
            <?php
            if(mysqli_num_rows($top_products_query) > 0) {
            ?>
            <ul class="product-list">
                <?php while($p = mysqli_fetch_assoc($top_products_query)): ?>
                <li class="product-item">
                    <img src="../assets/uploads/<?php echo $p['image']; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="product-thumb">
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="product-qty">⭐ <?php echo $p['qty_sold']; ?> sold</div>
                    </div>
                    <div class="product-price">₹<?php echo number_format($p['selling_price'], 0); ?></div>
                </li>
                <?php endwhile; ?>
            </ul>
            <?php } else { ?>
                <p style="color: var(--dash-text-muted); text-align: center; padding: 30px 0;">No sales data yet.</p>
            <?php } ?>
        </div>
    </div>

    <!-- Second Grid: Last Orders + Category Donut (like screenshot bottom) -->
    <div class="grid-main">
        <!-- Last Orders Table -->
        <div class="card">
            <h2><span class="icon">📋</span> Last Orders</h2>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(mysqli_num_rows($recent_orders) > 0): ?>
                        <?php while($o = mysqli_fetch_assoc($recent_orders)): ?>
                        <tr>
                            <td><a href="order_details.php?id=<?php echo $o['id']; ?>">#<?php echo $o['id']; ?></a></td>
                            <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($o['created_at'])); ?></td>
                            <td><span class="status-badge status-<?php echo strtolower($o['status']); ?>"><?php echo $o['status']; ?></span></td>
                            <td style="font-weight: 600;">₹<?php echo number_format($o['total_amount'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--dash-text-muted);">No orders found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Category Sales Donut Chart (like screenshot "Top Countries") -->
        <div class="card">
            <h2><span class="icon">🍩</span> Sales by Category</h2>
            <?php if(!empty($cat_labels)): ?>
            <div class="donut-container">
                <div class="donut-wrap">
                    <canvas id="categoryChart"></canvas>
                </div>
                <ul class="cat-legend">
                    <?php foreach($cat_labels as $i => $label): ?>
                    <li>
                        <span class="cat-dot" style="background: <?php echo $cat_colors[$i % count($cat_colors)]; ?>;"></span>
                        <?php echo htmlspecialchars($label); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php else: ?>
                <p style="color: var(--dash-text-muted); text-align: center; padding: 30px 0;">No category data yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Monthly Performance Table -->
    <div class="card" style="margin-bottom: 40px;">
        <h2><span class="icon">📅</span> Monthly Sales History</h2>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Orders</th>
                        <th style="text-align: right;">Monthly Revenue</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(!empty($monthly_rows)): ?>
                    <?php foreach(array_reverse($monthly_rows) as $m): ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($m['month']); ?></td>
                        <td><?php echo $m['monthly_orders']; ?></td>
                        <td style="text-align: right; font-weight: 700; color: #4ade80;"><?php echo '₹' . number_format($m['monthly_revenue'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; padding: 30px; color: var(--dash-text-muted);">No monthly data available.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    // ===== Theme Toggle =====
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeToggleIcon = document.getElementById('themeToggleIcon');
    const themeToggleText = document.getElementById('themeToggleText');
    const currentTheme = localStorage.getItem('admin-theme') || 'dark';
    
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
            localStorage.setItem('admin-theme', 'dark');
            themeToggleIcon.textContent = '☀️';
            themeToggleText.textContent = 'Light Mode';
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            document.body.style.background = '#f8fafc';
            document.body.style.color = '#1e293b';
            localStorage.setItem('admin-theme', 'light');
            themeToggleIcon.textContent = '🌙';
            themeToggleText.textContent = 'Dark Mode';
        }
    });

    // ===== Sales Growth Line Chart =====
    const isLight = currentTheme === 'light';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.06)';
    const tickColor = isLight ? '#64748b' : '#94a3b8';

    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const gradient1 = salesCtx.createLinearGradient(0, 0, 0, 280);
    gradient1.addColorStop(0, 'rgba(0, 102, 255, 0.3)');
    gradient1.addColorStop(1, 'rgba(0, 102, 255, 0)');
    const gradient2 = salesCtx.createLinearGradient(0, 0, 0, 280);
    gradient2.addColorStop(0, 'rgba(245, 158, 11, 0.3)');
    gradient2.addColorStop(1, 'rgba(245, 158, 11, 0)');

    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($chart_revenue); ?>,
                    borderColor: '#0066ff',
                    backgroundColor: gradient1,
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#0066ff',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                },
                {
                    label: 'Orders',
                    data: <?php echo json_encode($chart_orders); ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: gradient2,
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { position: 'top', labels: { color: tickColor, usePointStyle: true, padding: 20, font: { weight: 600 } } },
                tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', titleFont: { weight: 700 }, bodyFont: { size: 13 }, padding: 12, cornerRadius: 10 }
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: tickColor, font: { weight: 500 } } },
                y: { grid: { color: gridColor }, ticks: { color: tickColor, font: { weight: 500 } } }
            }
        }
    });

    // ===== Category Donut Chart =====
    <?php if(!empty($cat_labels)): ?>
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($cat_values); ?>,
                backgroundColor: <?php echo json_encode(array_slice($cat_colors, 0, count($cat_labels))); ?>,
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', padding: 10, cornerRadius: 8, bodyFont: { size: 13 } }
            }
        }
    });
    <?php endif; ?>
</script>

</body>
</html>
