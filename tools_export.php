<?php
include("../config/db.php");
require_once('auth_guard.php');

// ── Handle CSV export downloads ─────────────────────────────
if (isset($_GET['export'])) {
    $type = $_GET['export'];

    if ($type === 'products') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Name','Category','MRP (₹)','Selling Price (₹)','Stock','Description','Image']);
        $q = mysqli_query($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
        while ($r = mysqli_fetch_assoc($q)) {
            fputcsv($out, [$r['id'],$r['name'],$r['category_name']??'',$r['price'],$r['selling_price'],$r['stock'],$r['description'],$r['image']]);
        }
        fclose($out); exit();
    }

    if ($type === 'orders') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Order ID','Customer','Email','Total (₹)','Status','Payment','Address','Date']);
        $q = mysqli_query($conn, "SELECT o.*, u.name AS cname, u.email AS cemail FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
        while ($r = mysqli_fetch_assoc($q)) {
            fputcsv($out, [$r['id'],$r['cname']??'Guest',$r['cemail']??'',$r['total_amount'],$r['status'],$r['payment_method']??'COD',$r['address']??'',$r['created_at']??'']);
        }
        fclose($out); exit();
    }

    if ($type === 'customers') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Name','Email','Mobile','Registered']);
        $q = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
        while ($r = mysqli_fetch_assoc($q)) {
            fputcsv($out, [$r['id'],$r['name'],$r['email'],$r['mobile']??'',$r['created_at']??'']);
        }
        fclose($out); exit();
    }

    if ($type === 'categories') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="categories_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Category Name']);
        $q = mysqli_query($conn, "SELECT * FROM categories ORDER BY id ASC");
        while ($r = mysqli_fetch_assoc($q)) {
            fputcsv($out, [$r['id'],$r['category_name']]);
        }
        fclose($out); exit();
    }
}

// ── Stats for cards ─────────────────────────────────────────
$total_products  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products"))['c'];
$total_orders    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders"))['c'];
$total_customers = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users"))['c'];
$total_cats      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM categories"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Export Data - Admin Panel</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<style>
:root{
    --glass-bg:rgba(255,255,255,.025);
    --glass-border:rgba(255,255,255,.07);
    --text-main:#fff;
    --text-muted:#94a3b8;
    --smooth:cubic-bezier(.25,1,.5,1);
    --accent:#0066ff;
    --accent-glow:rgba(0,102,255,.35);
}
[data-theme="light"]{
    --glass-bg:#fff;
    --glass-border:#e2e8f0;
    --text-main:#1e293b;
    --text-muted:#64748b;
    --accent-glow:rgba(0,102,255,.15);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#070b19;color:var(--text-main);font-family:'Segoe UI',system-ui,sans-serif;
     line-height:1.6;-webkit-font-smoothing:antialiased;transition:background .35s,color .35s;}
[data-theme="light"] body{background:#f1f5f9!important;}

/* blobs */
.dash-bg{position:fixed;inset:0;z-index:0;pointer-events:none;}
.dash-blob{position:absolute;border-radius:50%;filter:blur(140px);opacity:.14;animation:blobF 18s infinite alternate ease-in-out;}
.blob-1{width:380px;height:380px;top:5%;right:-4%;background:linear-gradient(135deg,#0066ff,#7c3aed);}
.blob-2{width:440px;height:440px;bottom:-8%;left:-4%;background:linear-gradient(135deg,#00c6ff,#0044cc);animation-delay:-9s;}
@keyframes blobF{0%{transform:translate(0,0)scale(1);}100%{transform:translate(-28px,38px)scale(1.08);}}

.main-area{margin-left:255px;padding:36px 40px;position:relative;z-index:10;min-height:100vh;}
@media(max-width:900px){.main-area{margin-left:0;padding:20px 16px;}}

/* header */
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px;flex-wrap:wrap;gap:12px;}
.page-header h1{font-size:1.75rem;font-weight:700;display:flex;align-items:center;gap:10px;}
.theme-toggle-btn{background:var(--glass-bg);border:1px solid var(--glass-border);color:var(--text-main);
    padding:9px 16px;border-radius:10px;cursor:pointer;font-size:.88rem;font-weight:600;
    display:flex;align-items:center;gap:7px;transition:all .3s;}
.theme-toggle-btn:hover{border-color:var(--accent);box-shadow:0 0 12px var(--accent-glow);}

/* stat cards row */
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:32px;}
.stat-mini{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:14px;
    padding:18px 20px;display:flex;align-items:center;gap:14px;transition:all .3s;}
.stat-mini:hover{transform:translateY(-3px);border-color:rgba(0,102,255,.3);
    box-shadow:0 8px 24px rgba(0,102,255,.15);}
.stat-mini-icon{font-size:24px;width:44px;height:44px;border-radius:12px;display:flex;
    align-items:center;justify-content:center;flex-shrink:0;}
.stat-mini-num{font-size:1.4rem;font-weight:700;color:var(--text-main);}
.stat-mini-label{font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;}

/* export cards grid */
.export-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:22px;}
.export-card{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:18px;
    padding:28px;transition:all .35s var(--smooth);position:relative;overflow:hidden;}
.export-card::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,
    rgba(0,102,255,.04) 0%,transparent 60%);pointer-events:none;}
.export-card:hover{transform:translateY(-5px);border-color:rgba(0,102,255,.3);
    box-shadow:0 16px 40px rgba(0,102,255,.12);}
[data-theme="light"] .export-card{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.05);}

.export-card-icon{font-size:36px;margin-bottom:14px;display:block;}
.export-card-title{font-size:1.1rem;font-weight:700;color:var(--text-main);margin-bottom:6px;}
.export-card-desc{font-size:.85rem;color:var(--text-muted);margin-bottom:20px;line-height:1.5;}
.export-card-meta{display:flex;align-items:center;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.export-badge{background:rgba(0,102,255,.1);border:1px solid rgba(0,102,255,.2);color:#60a5fa;
    font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;}
[data-theme="light"] .export-badge{background:#eff6ff;color:#2563eb;border-color:#bfdbfe;}

.btn-export{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0066ff,#0044cc);
    color:#fff;padding:11px 22px;border-radius:10px;text-decoration:none;font-size:.9rem;font-weight:600;
    transition:all .3s var(--smooth);box-shadow:0 4px 14px rgba(0,102,255,.28);border:none;cursor:pointer;}
.btn-export:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,102,255,.42);color:#fff;}

.btn-export-outline{background:transparent;border:1px solid var(--glass-border);color:var(--text-muted);
    padding:11px 22px;border-radius:10px;text-decoration:none;font-size:.9rem;font-weight:600;
    display:inline-flex;align-items:center;gap:8px;transition:all .3s;cursor:pointer;}
.btn-export-outline:hover{border-color:var(--accent);color:#60a5fa;}

/* section title */
.s-title{font-size:1rem;font-weight:700;color:var(--text-main);margin-bottom:18px;
    display:flex;align-items:center;gap:8px;padding-bottom:12px;
    border-bottom:1px solid var(--glass-border);}

/* tip card */
.tip-card{background:rgba(0,102,255,.06);border:1px solid rgba(0,102,255,.14);border-radius:14px;
    padding:18px 22px;margin-top:28px;display:flex;gap:14px;align-items:flex-start;}
[data-theme="light"] .tip-card{background:#eff6ff;border-color:#bfdbfe;}
.tip-icon{font-size:20px;flex-shrink:0;margin-top:2px;}
.tip-text{font-size:.85rem;color:var(--text-muted);line-height:1.55;}
.tip-text strong{color:var(--text-main);}
</style>
</head>
<body id="adminBody">
<div class="dash-bg">
    <div class="dash-blob blob-1"></div>
    <div class="dash-blob blob-2"></div>
</div>

<?php include('includes/sidebar.php'); ?>

<div class="main-area">

    <div class="page-header">
        <h1>📤 Export Data</h1>
        <button class="theme-toggle-btn" id="themeToggleBtn">
            <span id="themeToggleIcon">☀️</span>
            <span id="themeToggleText">Light Mode</span>
        </button>
    </div>

    <!-- Quick Stats -->
    <div class="stat-row">
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background:rgba(0,102,255,.12);">📦</div>
            <div><div class="stat-mini-num"><?= $total_products ?></div>
                 <div class="stat-mini-label">Products</div></div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background:rgba(245,158,11,.12);">🛒</div>
            <div><div class="stat-mini-num"><?= $total_orders ?></div>
                 <div class="stat-mini-label">Orders</div></div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background:rgba(34,197,94,.12);">👥</div>
            <div><div class="stat-mini-num"><?= $total_customers ?></div>
                 <div class="stat-mini-label">Customers</div></div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background:rgba(124,58,237,.12);">📂</div>
            <div><div class="stat-mini-num"><?= $total_cats ?></div>
                 <div class="stat-mini-label">Categories</div></div>
        </div>
    </div>

    <div class="s-title">📥 Download as CSV</div>

    <div class="export-grid">

        <!-- Products -->
        <div class="export-card">
            <span class="export-card-icon">📦</span>
            <div class="export-card-title">Products</div>
            <div class="export-card-desc">Export all product listings including name, category, MRP, selling price, stock, and description.</div>
            <div class="export-card-meta">
                <span class="export-badge">CSV</span>
                <span class="export-badge"><?= $total_products ?> rows</span>
            </div>
            <a href="?export=products" class="btn-export">⬇️ Download Products CSV</a>
        </div>

        <!-- Orders -->
        <div class="export-card">
            <span class="export-card-icon">🛒</span>
            <div class="export-card-title">Orders</div>
            <div class="export-card-desc">Export all orders with customer name, email, total amount, status, payment method, and date.</div>
            <div class="export-card-meta">
                <span class="export-badge">CSV</span>
                <span class="export-badge"><?= $total_orders ?> rows</span>
            </div>
            <a href="?export=orders" class="btn-export">⬇️ Download Orders CSV</a>
        </div>

        <!-- Customers -->
        <div class="export-card">
            <span class="export-card-icon">👥</span>
            <div class="export-card-title">Customers</div>
            <div class="export-card-desc">Export all registered customers with name, email, phone number, and registration date.</div>
            <div class="export-card-meta">
                <span class="export-badge">CSV</span>
                <span class="export-badge"><?= $total_customers ?> rows</span>
            </div>
            <a href="?export=customers" class="btn-export">⬇️ Download Customers CSV</a>
        </div>

        <!-- Categories -->
        <div class="export-card">
            <span class="export-card-icon">📂</span>
            <div class="export-card-title">Categories</div>
            <div class="export-card-desc">Export all product categories with their IDs and names for reference or migration.</div>
            <div class="export-card-meta">
                <span class="export-badge">CSV</span>
                <span class="export-badge"><?= $total_cats ?> rows</span>
            </div>
            <a href="?export=categories" class="btn-export">⬇️ Download Categories CSV</a>
        </div>

    </div>

    <div class="tip-card">
        <span class="tip-icon">💡</span>
        <div class="tip-text">
            <strong>Tip:</strong> CSV files can be opened in Microsoft Excel, Google Sheets, or any spreadsheet application.
            Use exported data for backups, analysis, or to migrate to another platform.
        </div>
    </div>

</div>

<script>
const body=document.getElementById('adminBody');
const tBtn=document.getElementById('themeToggleBtn');
const tIcon=document.getElementById('themeToggleIcon');
const tText=document.getElementById('themeToggleText');
const saved=localStorage.getItem('adminTheme')||'dark';
if(saved==='light')applyLight();
tBtn.addEventListener('click',()=>{
    if(body.dataset.theme==='light'){body.removeAttribute('data-theme');localStorage.setItem('adminTheme','dark');tIcon.textContent='☀️';tText.textContent='Light Mode';}
    else applyLight();
});
function applyLight(){body.dataset.theme='light';localStorage.setItem('adminTheme','light');tIcon.textContent='🌙';tText.textContent='Dark Mode';}
</script>
</body>
</html>
