<?php
include("../config/db.php");
require_once('auth_guard.php');

// Handle Admin Points Adjustment
if (isset($_POST['adjust_points'])) {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        header("Location: customers.php?err=csrf");
        exit();
    }

    $uid = (int)$_POST['user_id'];
    $pts = (int)$_POST['points_amount'];
    $reason = mysqli_real_escape_string($conn, trim($_POST['reason'] ?? ''));
    if (empty($reason)) { $reason = 'Admin points adjustment'; }

    if ($uid > 0) {
        if ($pts > 0) {
            add_user_reward_points($conn, $uid, $pts, 'bonus', null, $reason);
        } elseif ($pts < 0) {
            deduct_user_reward_points($conn, $uid, abs($pts), 'admin_deduction', null, $reason);
        }
    }
    header("Location: customers.php?msg=adjusted");
    exit();
}

// Fetch Customers
$customers = mysqli_query($conn,"
SELECT *
FROM users
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users & Loyalty - Raj Kathiyawadi Mukhwash</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
body{
    background:#070b19;
    color:#e2e8f0;
}
.main-content{
    margin-left:255px;
    padding:30px;
}
.card{
    border:1px solid rgba(255,255,255,0.08);
    border-radius:15px;
    background:rgba(255,255,255,0.03);
}
</style>

</head>
<body>

<?php include('includes/sidebar.php'); ?>

<!-- Main Content -->
<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-white"><i class="bi bi-people-fill me-2 text-primary"></i>Users & Loyalty Management</h2>
    </div>

    <div class="card">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-dark table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Joined Date</th>
                            <th>Orders</th>
                            <th>Reward Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php
                    if(mysqli_num_rows($customers) > 0)
                    {
                        while($row = mysqli_fetch_assoc($customers))
                        {
                            $user_id = $row['id'];

                            $order_count = mysqli_query($conn,"
                            SELECT COUNT(*) AS cnt
                            FROM orders
                            WHERE user_id='$user_id'
                            ");

                            $total_orders = mysqli_fetch_assoc($order_count)['cnt'] ?? 0;
                            $pts = (int)($row['reward_points'] ?? 0);
                    ?>

                    <tr>

                        <td class="font-monospace text-muted">
                            #<?php echo $row['id']; ?>
                        </td>

                        <td class="fw-bold text-white">
                            <?php echo htmlspecialchars($row['name']); ?>
                        </td>

                        <td class="text-slate-300">
                            <?php echo htmlspecialchars($row['email']); ?>
                        </td>

                        <td class="text-slate-300">
                            <?php echo htmlspecialchars($row['mobile']); ?>
                        </td>

                        <td class="text-muted small">
                            <?php echo date("d M Y", strtotime($row['created_at'])); ?>
                        </td>

                        <td>
                            <span class="badge bg-primary rounded-pill">
                                <?php echo $total_orders; ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-warning text-dark fw-bold px-2.5 py-1.5 font-monospace" style="font-size:0.85rem;">
                                🪙 <?= $pts ?> Pts
                            </span>
                        </td>

                        <td>
                            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#ptsModal<?= $row['id'] ?>">
                                ⚡ Adjust Points
                            </button>

                            <!-- Adjust Points Modal -->
                            <div class="modal fade" id="ptsModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content bg-dark text-white border border-secondary">
                                        <div class="modal-header border-secondary">
                                            <h5 class="modal-header-title text-warning mb-0">🪙 Adjust Reward Points</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                            <div class="modal-body text-start">
                                                <p class="small text-muted mb-2">Customer: <strong class="text-white"><?= htmlspecialchars($row['name']) ?></strong> (Current: <strong class="text-warning"><?= $pts ?> Pts</strong>)</p>
                                                <div class="mb-3">
                                                    <label class="form-label small text-white-50">Points Amount (Positive to add, Negative to deduct)</label>
                                                    <input type="number" name="points_amount" class="form-control bg-secondary text-white border-0" placeholder="e.g. 50 or -20" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small text-white-50">Reason / Description</label>
                                                    <input type="text" name="reason" class="form-control bg-secondary text-white border-0" placeholder="e.g. Festival Bonus, Customer support credit">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-secondary">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="adjust_points" class="btn btn-sm btn-warning fw-bold">Save Adjustment</button>
                                            </div>
                                        </form>
                                    </div>
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
                            <td colspan='8' class='text-center py-4 text-muted'>
                                No Customers Found
                            </td>
                        </tr>";
                    }
                    ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
