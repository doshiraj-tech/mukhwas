<?php
include("../config/db.php");
require_once('auth_guard.php');


// Update Order Status
if(isset($_POST['update_status']))
{
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        // Invalid CSRF — silently ignore
    } else {
        $order_id = (int)$_POST['order_id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        // Whitelist allowed statuses
        $allowed_statuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
        if (in_array($status, $allowed_statuses)) {
            mysqli_query($conn,"
            UPDATE orders
            SET status='$status'
            WHERE id='$order_id'
            ");
        }
    }
}

// Bulk Order Actions
if (isset($_POST['bulk_action']) && !empty($_POST['selected_orders']) && is_array($_POST['selected_orders'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        // Invalid CSRF
    } else {
        $bulk_status = mysqli_real_escape_string($conn, $_POST['bulk_action']);
        $allowed_statuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
        $selected_ids = array_map('intval', $_POST['selected_orders']);
        $id_list = implode(',', $selected_ids);

        if (in_array($bulk_status, $allowed_statuses) && !empty($id_list)) {
            mysqli_query($conn, "UPDATE orders SET status='$bulk_status' WHERE id IN ($id_list)");
        }
    }
}

// Quick status change via links (Accept / Cancel)
if (isset($_GET['action']) && isset($_GET['id'])) {
    // CSRF validation for GET actions
    if (!isset($_GET['tok']) || !validate_csrf_token($_GET['tok'])) {
        header("Location: orders.php");
        exit();
    }

    $order_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'accept') {
        mysqli_query($conn, "UPDATE orders SET status='Shipped' WHERE id='$order_id'");
    } elseif ($action === 'cancel') {
        // Restore stock for all items in this order before cancelling
        $prev_status_q = mysqli_query($conn, "SELECT status FROM orders WHERE id='$order_id'");
        $prev_status_row = mysqli_fetch_assoc($prev_status_q);
        $prev_status = $prev_status_row['status'] ?? '';
        
        if ($prev_status !== 'Cancelled') {
            // Only restore stock if not already cancelled
            $items_q = mysqli_query($conn, "SELECT product_id, quantity FROM order_items WHERE order_id='$order_id'");
            if ($items_q) {
                while ($item = mysqli_fetch_assoc($items_q)) {
                    $pid = (int)$item['product_id'];
                    $qty = (int)$item['quantity'];
                    mysqli_query($conn, "UPDATE products SET stock = stock + $qty WHERE id='$pid'");
                }
            }
        }
        mysqli_query($conn, "UPDATE orders SET status='Cancelled' WHERE id='$order_id'");
    }
    
    header("Location: orders.php");
    exit();
}

// Filter and Search logic
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query  = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];
if ($status_filter !== 'all' && in_array($status_filter, ['Pending', 'Shipped', 'Delivered', 'Cancelled'])) {
    $where_clauses[] = "orders.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}
if (!empty($search_query)) {
    $sq_esc = mysqli_real_escape_string($conn, $search_query);
    $where_clauses[] = "(orders.id LIKE '%$sq_esc%' OR users.name LIKE '%$sq_esc%' OR orders.fullname LIKE '%$sq_esc%' OR orders.mobile LIKE '%$sq_esc%')";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch Orders
$orders = mysqli_query($conn,"
SELECT orders.*, users.name AS customer_name
FROM orders
LEFT JOIN users ON orders.user_id = users.id
$where_sql
ORDER BY orders.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Orders - Raj Kathiyawadi Mukhwash</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
body{
    background:#f5f7fa;
}

.main-content{
    margin-left:255px;
    padding:30px;
}

.card{
    border:none;
    box-shadow:0 3px 10px rgba(0,0,0,0.08);
    border-radius:15px;
}

.filter-pill {
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    color: #64748b;
    background: #fff;
    border: 1px solid #cbd5e1;
    transition: all 0.2s;
}
.filter-pill:hover, .filter-pill.active {
    background: #0066ff;
    color: #fff;
    border-color: #0066ff;
}
</style>

</head>
<body>

<!-- Sidebar -->
<?php include('includes/sidebar.php'); ?>

<!-- Main Content -->
<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0">Manage Orders</h2>
        
        <!-- Search Form -->
        <form method="GET" class="d-flex gap-2" style="max-width: 320px; width: 100%;">
            <?php if($status_filter !== 'all'): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <?php endif; ?>
            <div class="input-group">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Order # or Customer..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
            </div>
            <?php if(!empty($search_query)): ?>
                <a href="orders.php" class="btn btn-outline-secondary btn-sm" title="Clear Search">✕</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Filter Pills Bar -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="orders.php" class="filter-pill <?php echo $status_filter==='all'?'active':''; ?>">All Orders</a>
        <a href="orders.php?status=Pending" class="filter-pill <?php echo $status_filter==='Pending'?'active':''; ?>">⏳ Pending</a>
        <a href="orders.php?status=Shipped" class="filter-pill <?php echo $status_filter==='Shipped'?'active':''; ?>">🚚 Shipped</a>
        <a href="orders.php?status=Delivered" class="filter-pill <?php echo $status_filter==='Delivered'?'active':''; ?>">✅ Delivered</a>
        <a href="orders.php?status=Cancelled" class="filter-pill <?php echo $status_filter==='Cancelled'?'active':''; ?>">❌ Cancelled</a>
    </div>

    <div class="card">
        <div class="card-body">

            <!-- Bulk Actions Form Wrap -->
            <form method="POST" id="bulkOrdersForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <select name="bulk_action" class="form-select form-select-sm" style="width: auto; font-size: 0.85rem;" required>
                            <option value="">-- Select Bulk Action --</option>
                            <option value="Shipped">Mark Selected as Shipped</option>
                            <option value="Delivered">Mark Selected as Delivered</option>
                            <option value="Cancelled">Mark Selected as Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary btn-sm fw-semibold" onclick="return confirm('Apply bulk action to selected orders?')">
                            Apply Action
                        </button>
                    </div>
                    <span class="text-muted small">Showing <?php echo mysqli_num_rows($orders); ?> orders</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-success">
                            <tr>
                                <th width="40" class="text-center">
                                    <input type="checkbox" id="selectAllOrders" class="form-check-input">
                                </th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th width="240">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php
                        if(mysqli_num_rows($orders) > 0)
                        {
                            while($row = mysqli_fetch_assoc($orders))
                            {
                        ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="selected_orders[]" value="<?php echo $row['id']; ?>" class="form-check-input order-checkbox">
                            </td>

                            <td><strong class="text-primary">#<?php echo $row['id']; ?></strong></td>

                            <td>
                                <?php echo htmlspecialchars($row['customer_name'] ?: 'Guest'); ?>
                            </td>

                            <td>
                                <strong>₹<?php echo number_format($row['total_amount'],2); ?></strong>
                            </td>

                            <td>
                                <?php
                                if($row['status']=="Pending")
                                {
                                    $csrf_tok = generate_csrf_token();
                                    echo "<div class='d-flex flex-column gap-1 align-items-start'>";
                                    echo "<span class='badge bg-warning text-dark'>Pending</span>";
                                    echo "<div class='d-flex gap-1 mt-1'>";
                                    echo "<a href='orders.php?action=accept&id=".$row['id']."&tok=".urlencode($csrf_tok)."' class='btn btn-success btn-xs py-0 px-2 fw-semibold' style='font-size: 0.7rem;'>Accept</a>";
                                    echo "<a href='orders.php?action=cancel&id=".$row['id']."&tok=".urlencode($csrf_tok)."' class='btn btn-danger btn-xs py-0 px-2 fw-semibold' style='font-size: 0.7rem;' onclick='return confirm(\"Are you sure you want to cancel this order?\")'>Cancel</a>";
                                    echo "</div>";
                                    echo "</div>";
                                }
                                elseif($row['status']=="Shipped")
                                {
                                    echo "<span class='badge bg-primary'>Shipped</span>";
                                }
                                elseif($row['status']=="Delivered")
                                {
                                    echo "<span class='badge bg-success'>Delivered</span>";
                                }
                                else
                                {
                                    echo "<span class='badge bg-danger'>Cancelled</span>";
                                }
                                ?>
                            </td>

                            <td class="text-muted small">
                                <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?>
                            </td>

                            <td>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex gap-1">
                                        <a href="order_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm rounded-pill flex-grow-1 fw-semibold">
                                            <i class="bi bi-info-circle me-1"></i>Details
                                        </a>
                                        <a href="invoice_print.php?id=<?php echo $row['id']; ?>&type=invoice" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill fw-semibold" title="Print Tax Invoice">
                                            <i class="bi bi-printer-fill"></i>
                                        </a>
                                        <a href="order_details.php?id=<?php echo $row['id']; ?>#deliveryMapCard" class="btn btn-outline-danger btn-sm rounded-pill fw-semibold" title="View Customer Location Map">
                                            <i class="bi bi-geo-alt-fill"></i>
                                        </a>
                                    </div>
                                    
                                    <div class="d-flex gap-1 align-items-center">
                                        <select onchange="this.form.submit()" name="status_quick" class="form-select form-select-sm status-quick-select" data-order-id="<?php echo $row['id']; ?>" style="font-size: 0.82rem;">
                                            <option value="Pending" <?php if($row['status']=="Pending") echo 'selected'; ?>>Pending</option>
                                            <option value="Shipped" <?php if($row['status']=="Shipped") echo 'selected'; ?>>Shipped</option>
                                            <option value="Delivered" <?php if($row['status']=="Delivered") echo 'selected'; ?>>Delivered</option>
                                            <option value="Cancelled" <?php if($row['status']=="Cancelled") echo 'selected'; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php
                            }
                        }
                        else
                        {
                            echo "
                            <tr>
                            <td colspan='7' class='text-center py-4 text-muted'>
                                No Orders Found
                            </td>
                            </tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </form>

        </div>
    </div>

</div>

<!-- Single Order Status Quick Change Helper Form -->
<form id="singleStatusForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="update_status" value="1">
    <input type="hidden" name="order_id" id="singleOrderId">
    <input type="hidden" name="status" id="singleOrderStatus">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select All Checkboxes
    const selectAll = document.getElementById('selectAllOrders');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            orderCheckboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    // Quick Status Dropdown Change Trigger
    document.querySelectorAll('.status-quick-select').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = this.value;
            document.getElementById('singleOrderId').value = orderId;
            document.getElementById('singleOrderStatus').value = newStatus;
            document.getElementById('singleStatusForm').submit();
        });
    });
});
</script>

</body>
</html>
