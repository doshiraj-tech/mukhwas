<?php
/**
 * AJAX Endpoint: Check for new incoming orders for real-time dashboard notifications
 * Returns JSON response with new orders and updated statistics.
 */
include("../config/db.php");
require_once('auth_guard.php');

header('Content-Type: application/json');

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Query new orders greater than last known ID
$new_orders_q = mysqli_query($conn, "
    SELECT o.id, o.user_id, o.total_amount, o.status, o.created_at,
           IFNULL(u.name, 'Guest User') AS customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id > $last_id
    ORDER BY o.id ASC
");

$new_orders = [];
$max_id = $last_id;

if ($new_orders_q && mysqli_num_rows($new_orders_q) > 0) {
    while ($row = mysqli_fetch_assoc($new_orders_q)) {
        $row['id'] = (int)$row['id'];
        $row['total_amount'] = floatval($row['total_amount']);
        $new_orders[] = $row;
        if ($row['id'] > $max_id) {
            $max_id = $row['id'];
        }
    }
}

// Fetch updated live statistics
$tot_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products"))['cnt'] ?? 0;
$tot_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users"))['cnt'] ?? 0;
$tot_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders"))['cnt'] ?? 0;
$tot_pending  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE status='Pending'"))['cnt'] ?? 0;
$tot_deliv    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE status='Delivered'"))['cnt'] ?? 0;
$tot_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as rev FROM orders"))['rev'] ?? 0;

echo json_encode([
    'success'      => true,
    'has_new'      => count($new_orders) > 0,
    'new_orders'   => $new_orders,
    'max_id'       => $max_id,
    'stats'        => [
        'products'  => (int)$tot_products,
        'customers' => (int)$tot_users,
        'pending'   => (int)$tot_pending,
        'delivered' => (int)$tot_deliv,
        'total'     => (int)$tot_orders,
        'revenue'   => floatval($tot_revenue),
    ]
]);
exit();
