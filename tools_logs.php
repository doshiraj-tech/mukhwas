<?php
include("../config/db.php");
require_once('auth_guard.php');

// ── In-memory activity log (session-based — extend to DB for production) ──
// Log entries are stored as: [timestamp, admin, action, detail, type]

// Auto-log current page visit for demo purposes
if (!isset($_SESSION['activity_log'])) $_SESSION['activity_log'] = [];

// Add current admin's session activity if triggered via action param
if (isset($_GET['log_action'])) {
    $admin = htmlspecialchars($_SESSION['admin'] ?? 'Admin');
    $map = [
        'clear' => ['Admin','Cleared activity log','danger'],
    ];
    if ($_GET['log_action'] === 'clear') {
        $_SESSION['activity_log'] = [];
        array_unshift($_SESSION['activity_log'], [
            date('Y-m-d H:i:s'), $admin,
            'Cleared activity log', 'All previous log entries were cleared', 'danger'
        ]);
        header("Location: tools_logs.php");
        exit();
    }
}

// ── Build combined log: session + recent DB events ─────────────────────────
$log_entries = [];

// Recent orders from DB
$recent_orders = mysqli_query($conn, "SELECT o.id, u.name AS cname, o.total_amount, o.status,
    IFNULL(o.created_at, NOW()) AS created_at
    FROM orders o LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.id DESC LIMIT 20");
while ($r = mysqli_fetch_assoc($recent_orders)) {
    $log_entries[] = [
        'time'   => $r['created_at'] ?: date('Y-m-d H:i:s'),
        'actor'  => $r['cname'] ?: 'Guest',
        'action' => 'New Order Placed',
        'detail' => 'Order #' . $r['id'] . ' — ₹' . number_format($r['total_amount'], 2) . ' — Status: ' . $r['status'],
        'type'   => 'order',
    ];
}

// Recent customers from DB
$recent_users = mysqli_query($conn, "SELECT id, name, email,
    IFNULL(created_at, NOW()) AS created_at
    FROM users ORDER BY id DESC LIMIT 10");
while ($r = mysqli_fetch_assoc($recent_users)) {
    $log_entries[] = [
        'time'   => $r['created_at'] ?: date('Y-m-d H:i:s'),
        'actor'  => $r['name'],
        'action' => 'Customer Registered',
        'detail' => 'New account — ' . $r['email'],
        'type'   => 'user',
    ];
}

// Recent products from DB (use SHOW COLUMNS to detect if created_at exists)
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'created_at'");
$has_created = $col_check && mysqli_num_rows($col_check) > 0;
$prod_col = $has_created ? "IFNULL(created_at, NOW()) AS created_at" : "NOW() AS created_at";
$recent_products = mysqli_query($conn, "SELECT id, name, $prod_col FROM products ORDER BY id DESC LIMIT 10");
if ($recent_products) {
    while ($r = mysqli_fetch_assoc($recent_products)) {
        $log_entries[] = [
            'time'   => $r['created_at'] ?: date('Y-m-d H:i:s'),
            'actor'  => $_SESSION['admin'] ?? 'Admin',
            'action' => 'Product Added',
            'detail' => 'Product #' . $r['id'] . ' — ' . htmlspecialchars($r['name']),
            'type'   => 'product',
        ];
    }
}

// Recent admin login attempts from DB
$recent_logins = mysqli_query($conn, "SELECT * FROM admin_login_logs ORDER BY id DESC LIMIT 20");
if ($recent_logins) {
    while ($r = mysqli_fetch_assoc($recent_logins)) {
        $log_entries[] = [
            'time'   => $r['created_at'],
            'actor'  => $r['username'],
            'action' => 'Admin Login ' . ($r['status'] === 'SUCCESS' ? 'Successful' : ($r['status'] === 'BLOCKED' ? 'Blocked' : 'Failed')),
            'detail' => 'IP: ' . $r['ip_address'] . ' — Status: ' . $r['status'] . ' — Agent: ' . substr($r['user_agent'], 0, 50) . '...',
            'type'   => 'auth',
        ];
    }
}

// Merge session manual entries
foreach ($_SESSION['activity_log'] as $e) {
    $log_entries[] = [
        'time'   => $e[0],
        'actor'  => $e[1],
        'action' => $e[2],
        'detail' => $e[3],
        'type'   => $e[4],
    ];
}

// Sort all by time descending
usort($log_entries, fn($a,$b) => strtotime($b['time']) - strtotime($a['time']));

// Filter
$filter = $_GET['filter'] ?? 'all';
if ($filter !== 'all') {
    $log_entries = array_filter($log_entries, fn($e) => $e['type'] === $filter);
    $log_entries = array_values($log_entries);
}

// Pagination
$per_page     = 15;
$total_entries = count($log_entries);
$total_pages   = max(1, ceil($total_entries / $per_page));
$page          = max(1, min((int)($_GET['page'] ?? 1), $total_pages));
$offset        = ($page - 1) * $per_page;
$paged_entries = array_slice($log_entries, $offset, $per_page);

// Summary counts (unfiltered)
$all_entries = $log_entries;
$counts = ['order'=>0,'user'=>0,'product'=>0,'danger'=>0];
foreach ($log_entries as $e) {
    if (isset($counts[$e['type']])) $counts[$e['type']]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs - Admin Panel</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<style>
:root{
    --glass-bg:rgba(255,255,255,.025); --glass-border:rgba(255,255,255,.07);
    --text-main:#fff; --text-muted:#94a3b8;
    --smooth:cubic-bezier(.25,1,.5,1);
    --accent:#0066ff; --accent-glow:rgba(0,102,255,.35);
}
[data-theme="light"]{
    --glass-bg:#fff; --glass-border:#e2e8f0;
    --text-main:#1e293b; --text-muted:#64748b;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#070b19;color:var(--text-main);font-family:'Segoe UI',system-ui,sans-serif;
     line-height:1.6;-webkit-font-smoothing:antialiased;transition:background .35s,color .35s;}
[data-theme="light"] body{background:#f1f5f9!important;}

.dash-bg{position:fixed;inset:0;z-index:0;pointer-events:none;}
.dash-blob{position:absolute;border-radius:50%;filter:blur(140px);opacity:.14;animation:bF 18s infinite alternate ease-in-out;}
.blob-1{width:380px;height:380px;top:5%;right:-4%;background:linear-gradient(135deg,#0066ff,#7c3aed);}
.blob-2{width:440px;height:440px;bottom:-8%;left:-4%;background:linear-gradient(135deg,#00c6ff,#0044cc);animation-delay:-9s;}
@keyframes bF{0%{transform:translate(0,0)scale(1);}100%{transform:translate(-28px,38px)scale(1.08);}}

.main-area{margin-left:255px;padding:36px 40px;position:relative;z-index:10;min-height:100vh;}
@media(max-width:900px){.main-area{margin-left:0;padding:20px 16px;}}

.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.page-header h1{font-size:1.75rem;font-weight:700;display:flex;align-items:center;gap:10px;}
.header-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}

.theme-toggle-btn{background:var(--glass-bg);border:1px solid var(--glass-border);color:var(--text-main);
    padding:9px 16px;border-radius:10px;cursor:pointer;font-size:.88rem;font-weight:600;
    display:flex;align-items:center;gap:7px;transition:all .3s;}
.theme-toggle-btn:hover{border-color:var(--accent);box-shadow:0 0 12px var(--accent-glow);}

.btn-danger-sm{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;
    padding:9px 16px;border-radius:10px;cursor:pointer;font-size:.85rem;font-weight:600;
    display:flex;align-items:center;gap:6px;text-decoration:none;transition:all .3s;}
.btn-danger-sm:hover{background:rgba(239,68,68,.18);color:#fca5a5;}

/* stat mini row */
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:26px;}
.stat-mini{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:14px;
    padding:16px 18px;display:flex;align-items:center;gap:12px;transition:all .3s;
    cursor:pointer;text-decoration:none;}
.stat-mini:hover{transform:translateY(-3px);border-color:rgba(0,102,255,.3);}
.stat-mini.active-filter{border-color:var(--accent);background:rgba(0,102,255,.08);}
.stat-mini-icon{font-size:22px;width:40px;height:40px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-mini-num{font-size:1.3rem;font-weight:700;color:var(--text-main);}
.stat-mini-label{font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;}

/* filter bar */
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;align-items:center;}
.filter-pill{padding:6px 16px;border-radius:20px;font-size:.8rem;font-weight:600;text-decoration:none;
    padding:16px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;transition:all .25s;}
.stat-mini:hover,.stat-mini.active-filter{border-color:var(--accent);transform:translateY(-2px);}
.stat-mini-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;
    justify-content:center;font-size:20px;flex-shrink:0;}
.stat-mini-num{font-size:1.3rem;font-weight:800;}
.stat-mini-label{font-size:.78rem;color:var(--text-muted);}

.filter-bar{display:flex;align-items:center;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.filter-label{font-size:.82rem;color:var(--text-muted);margin-right:4px;}
.filter-pill{padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600;
    border:1px solid var(--glass-border);color:var(--text-muted);text-decoration:none;transition:all .2s;}
.filter-pill:hover{border-color:var(--accent);color:var(--text-main);}
.filter-pill.active{background:var(--accent);border-color:var(--accent);color:#fff;}

.log-card{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:16px;overflow:hidden;}
.log-table{width:100%;border-collapse:collapse;}
.log-table th{padding:14px 16px;text-align:left;font-size:.72rem;text-transform:uppercase;
    letter-spacing:.7px;color:var(--text-muted);font-weight:700;white-space:nowrap;}
.log-table td{padding:13px 16px;border-top:1px solid var(--glass-border);font-size:.85rem;
    vertical-align:middle;}
[data-theme="light"] .log-table td{border-top-color:#f1f5f9;}
.log-table tr:hover td{background:rgba(255,255,255,.02);}
[data-theme="light"] .log-table tr:hover td{background:#f8fafc;}

/* type badges */
.log-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;
    font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap;}
.badge-order  {background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.2);}
.badge-user   {background:rgba(34,197,94,.1); color:#4ade80;border:1px solid rgba(34,197,94,.2);}
.badge-product{background:rgba(0,102,255,.1); color:#60a5fa;border:1px solid rgba(0,102,255,.2);}
.badge-auth   {background:rgba(168,85,247,.12); color:#c084fc;border:1px solid rgba(168,85,247,.25);}
.badge-danger {background:rgba(239,68,68,.1); color:#f87171;border:1px solid rgba(239,68,68,.2);}
[data-theme="light"] .badge-order  {background:#fffbeb;color:#b45309;border-color:#fde68a;}
[data-theme="light"] .badge-user   {background:#f0fdf4;color:#15803d;border-color:#bbf7d0;}
[data-theme="light"] .badge-product{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;}
[data-theme="light"] .badge-auth   {background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff;}
[data-theme="light"] .badge-danger {background:#fef2f2;color:#b91c1c;border-color:#fecaca;}

/* actor */
.actor-cell{display:flex;align-items:center;gap:8px;}
.actor-avatar{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#0066ff,#7c3aed);
    display:flex;align-items:center;justify-content:center;font-size:11px;color:#fff;font-weight:700;flex-shrink:0;}

/* time */
.time-cell{color:var(--text-muted);font-size:.78rem;white-space:nowrap;}

/* empty state */
.empty-state{text-align:center;padding:60px 20px;color:var(--text-muted);}
.empty-state-icon{font-size:48px;margin-bottom:14px;}
.empty-state p{font-size:.92rem;}

/* pagination */
.pagination{display:flex;justify-content:space-between;align-items:center;
    padding:16px 20px;border-top:1px solid var(--glass-border);flex-wrap:wrap;gap:10px;}
[data-theme="light"] .pagination{border-top-color:#e2e8f0;}
.page-info{font-size:.82rem;color:var(--text-muted);}
.page-btns{display:flex;gap:6px;}
.page-btn{padding:6px 14px;border-radius:8px;font-size:.82rem;font-weight:600;
    border:1px solid var(--glass-border);color:var(--text-muted);text-decoration:none;transition:all .25s;}
.page-btn:hover{border-color:var(--accent);color:#60a5fa;}
.page-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.page-btn.disabled{opacity:.35;pointer-events:none;}
</style>
</head>
<body id="adminBody">
<div class="dash-bg">
    <div class="dash-blob blob-1"></div>
    <div class="dash-blob blob-2"></div>
</div>

<?php include('includes/sidebar.php'); ?>

<div class="main-area">

    <!-- Header -->
    <div class="page-header">
        <h1>📋 Activity Logs</h1>
        <div class="header-actions">
            <a href="?log_action=clear" class="btn-danger-sm"
               onclick="return confirm('Clear all manual log entries?')">
                🗑️ Clear Logs
            </a>
            <button class="theme-toggle-btn" id="themeToggleBtn">
                <span id="themeToggleIcon">☀️</span>
                <span id="themeToggleText">Light Mode</span>
            </button>
        </div>
    </div>

    <!-- Summary Cards (clickable filters) -->
    <div class="stat-row">
        <a href="?filter=all" class="stat-mini <?= $filter==='all'?'active-filter':'' ?>">
            <div class="stat-mini-icon" style="background:rgba(255,255,255,.06);">📋</div>
            <div><div class="stat-mini-num"><?= $total_entries ?></div>
                 <div class="stat-mini-label">All Events</div></div>
        </a>
        <a href="?filter=order" class="stat-mini <?= $filter==='order'?'active-filter':'' ?>">
            <div class="stat-mini-icon" style="background:rgba(245,158,11,.1);">🛒</div>
            <div><div class="stat-mini-num"><?= $counts['order'] ?></div>
                 <div class="stat-mini-label">Orders</div></div>
        </a>
        <a href="?filter=user" class="stat-mini <?= $filter==='user'?'active-filter':'' ?>">
            <div class="stat-mini-icon" style="background:rgba(34,197,94,.1);">👥</div>
            <div><div class="stat-mini-num"><?= $counts['user'] ?></div>
                 <div class="stat-mini-label">Customers</div></div>
        </a>
        <a href="?filter=product" class="stat-mini <?= $filter==='product'?'active-filter':'' ?>">
            <div class="stat-mini-icon" style="background:rgba(0,102,255,.1);">📦</div>
            <div><div class="stat-mini-num"><?= $counts['product'] ?></div>
                 <div class="stat-mini-label">Products</div></div>
        </a>
        <a href="?filter=auth" class="stat-mini <?= $filter==='auth'?'active-filter':'' ?>">
            <div class="stat-mini-icon" style="background:rgba(168,85,247,.1);">🔐</div>
            <div><div class="stat-mini-num"><?= $counts['auth'] ?></div>
                 <div class="stat-mini-label">Logins</div></div>
        </a>
    </div>

    <!-- Filter Pills -->
    <div class="filter-bar">
        <span class="filter-label">Filter:</span>
        <a href="?filter=all"     class="filter-pill <?= $filter==='all'    ?'active':'' ?>">All</a>
        <a href="?filter=order"   class="filter-pill <?= $filter==='order'  ?'active':'' ?>">🛒 Orders</a>
        <a href="?filter=user"    class="filter-pill <?= $filter==='user'   ?'active':'' ?>">👥 Customers</a>
        <a href="?filter=product" class="filter-pill <?= $filter==='product'?'active':'' ?>">📦 Products</a>
        <a href="?filter=auth"    class="filter-pill <?= $filter==='auth'   ?'active':'' ?>">🔐 Admin Logins</a>
    </div>

    <!-- Log Table -->
    <div class="log-card">
        <?php if (empty($paged_entries)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🕵️</div>
            <p>No activity logs found for this filter.</p>
        </div>
        <?php else: ?>
        <table class="log-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Time</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Detail</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($paged_entries as $i => $e):
                $badge_class = 'badge-' . ($e['type'] ?? 'product');
                $icons = ['order'=>'🛒','user'=>'👥','product'=>'📦','danger'=>'🗑️'];
                $icon  = $icons[$e['type']] ?? '📌';
                $initials = strtoupper(substr($e['actor'],0,1));
                $num = $offset + $i + 1;
            ?>
            <tr>
                <td style="color:var(--text-muted);font-size:.78rem;"><?= $num ?></td>
                <td class="time-cell"><?= date('d M Y', strtotime($e['time'])) ?><br>
                    <span style="font-size:.7rem;"><?= date('H:i:s', strtotime($e['time'])) ?></span></td>
                <td>
                    <div class="actor-cell">
                        <div class="actor-avatar"><?= $initials ?></div>
                        <span style="font-size:.85rem;"><?= htmlspecialchars($e['actor']) ?></span>
                    </div>
                </td>
                <td style="font-weight:600;color:var(--text-main);"><?= htmlspecialchars($e['action']) ?></td>
                <td style="color:var(--text-muted);font-size:.82rem;"><?= htmlspecialchars($e['detail']) ?></td>
                <td><span class="log-badge <?= $badge_class ?>"><?= $icon ?> <?= ucfirst($e['type']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <div class="page-info">
                Showing <?= $offset+1 ?>–<?= min($offset+$per_page,$total_entries) ?> of <?= $total_entries ?> events
            </div>
            <div class="page-btns">
                <a href="?filter=<?= $filter ?>&page=<?= $page-1 ?>" class="page-btn <?= $page<=1?'disabled':'' ?>">← Prev</a>
                <?php for($p=1;$p<=$total_pages;$p++): ?>
                    <a href="?filter=<?= $filter ?>&page=<?= $p ?>" class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <a href="?filter=<?= $filter ?>&page=<?= $page+1 ?>" class="page-btn <?= $page>=$total_pages?'disabled':'' ?>">Next →</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /main-area -->

<script>
/* Live search filter */
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('.log-table tbody tr');
    // (search box can be added later)
});

/* Theme */
const body=document.getElementById('adminBody');
const tBtn=document.getElementById('themeToggleBtn');
const tIcon=document.getElementById('themeToggleIcon');
const tText=document.getElementById('themeToggleText');
if((localStorage.getItem('adminTheme')||'dark')==='light')applyLight();
tBtn.addEventListener('click',()=>{
    if(body.dataset.theme==='light'){body.removeAttribute('data-theme');localStorage.setItem('adminTheme','dark');tIcon.textContent='☀️';tText.textContent='Light Mode';}
    else applyLight();
});
function applyLight(){body.dataset.theme='light';localStorage.setItem('adminTheme','light');tIcon.textContent='🌙';tText.textContent='Dark Mode';}
</script>
</body>
</html>
