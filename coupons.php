<?php
include('../config/db.php');
require_once('auth_guard.php');

$success = ''; $error = '';

// Delete coupon
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM coupons WHERE id='$id'");
    header("Location: coupons.php?msg=deleted");
    exit;
}

// Toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    mysqli_query($conn, "UPDATE coupons SET is_active = IF(is_active=1, 0, 1) WHERE id='$id'");
    header("Location: coupons.php?msg=toggled");
    exit;
}

// Add new coupon
if (isset($_POST['add_coupon'])) {
    $code          = strtoupper(mysqli_real_escape_string($conn, trim($_POST['code'])));
    $discount_type = in_array($_POST['discount_type'], ['percent','fixed']) ? $_POST['discount_type'] : 'percent';
    $discount_value= (float)$_POST['discount_value'];
    $min_order     = (float)($_POST['min_order_amount'] ?? 0);
    $usage_limit   = (int)($_POST['usage_limit'] ?? 100);
    $expires_at    = !empty($_POST['expires_at']) ? "'".$_POST['expires_at']."'" : 'NULL';

    if (empty($code) || $discount_value <= 0) {
        $error = 'Code and discount value are required.';
    } else {
        $res = mysqli_query($conn,
            "INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, usage_limit, expires_at)
             VALUES ('$code','$discount_type','$discount_value','$min_order','$usage_limit',$expires_at)"
        );
        if ($res) $success = "Coupon '$code' created!";
        else $error = "Code already exists or DB error.";
    }
}

$coupons = mysqli_query($conn, "SELECT * FROM coupons ORDER BY id DESC");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Coupons - Admin</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<style>
:root{--g:rgba(255,255,255,.025);--b:rgba(255,255,255,.07);--t:#fff;--m:#94a3b8;--a:#0066ff;}
[data-theme=light]{--g:#fff;--b:#e2e8f0;--t:#1e293b;--m:#64748b;}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#070b19;color:var(--t);font-family:'Segoe UI',system-ui,sans-serif;line-height:1.6;}
.main{margin-left:255px;padding:36px 40px;min-height:100vh;position:relative;z-index:10;}
@media(max-width:900px){.main{margin-left:0;padding:20px 16px;}}
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.ph h1{font-size:1.75rem;font-weight:700;display:flex;align-items:center;gap:10px;}
.ttb{background:var(--g);border:1px solid var(--b);color:var(--t);padding:9px 16px;border-radius:10px;cursor:pointer;font-size:.88rem;font-weight:600;display:flex;align-items:center;gap:7px;transition:all .3s;}
.ttb:hover{border-color:var(--a);}
.card{background:var(--g);border:1px solid var(--b);border-radius:18px;padding:26px 28px;margin-bottom:20px;}
[data-theme=light] .card{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.05);}
.card-title{font-size:1.1rem;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--b);}
.fl{display:block;font-size:.78rem;font-weight:600;color:var(--m);text-transform:uppercase;letter-spacing:.6px;margin-bottom:7px;}
.fc{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--b);border-radius:10px;padding:11px 14px;color:var(--t);font-size:.92rem;outline:none;transition:border-color .25s;font-family:inherit;}
.fc:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(0,102,255,.15);}
[data-theme=light] .fc{background:#f8fafc;border-color:#e2e8f0;color:#1e293b;}
.fg{margin-bottom:18px;}
.fg-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-bottom:18px;}
.btn-save{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0066ff,#0044cc);color:#fff;border:none;padding:12px 26px;border-radius:12px;font-size:.95rem;font-weight:600;cursor:pointer;transition:all .3s;box-shadow:0 4px 14px rgba(0,102,255,.28);}
.btn-save:hover{transform:translateY(-2px);}
table{width:100%;border-collapse:collapse;overflow-x:auto;display:block;}
th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--b);font-size:.9rem;white-space:nowrap;}
th{font-weight:700;color:var(--m);text-transform:uppercase;font-size:.75rem;letter-spacing:.5px;}
.badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600;}
.badge-green{background:rgba(34,197,94,.15);color:#4ade80;}
.badge-red{background:rgba(239,68,68,.15);color:#f87171;}
.badge-blue{background:rgba(0,102,255,.15);color:#60a5fa;}
.badge-yellow{background:rgba(245,158,11,.15);color:#fbbf24;}
.btn-sm{padding:5px 12px;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;border:none;transition:all .2s;margin-right:4px;}
.btn-danger{background:rgba(239,68,68,.15);color:#f87171;}
.btn-info{background:rgba(0,102,255,.15);color:#60a5fa;}
.btn-danger:hover{background:rgba(239,68,68,.3);}
.btn-info:hover{background:rgba(0,102,255,.3);}
.adm-alert{padding:14px 20px;border-radius:12px;margin-bottom:20px;font-weight:600;font-size:.9rem;}
.as{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#4ade80;}
.ae{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;}
.dash-bg{position:fixed;inset:0;z-index:0;pointer-events:none;}
.dash-blob{position:absolute;border-radius:50%;filter:blur(140px);opacity:.14;animation:bF 18s infinite alternate ease-in-out;}
.b1{width:380px;height:380px;top:5%;right:-4%;background:linear-gradient(135deg,#0066ff,#7c3aed);}
.b2{width:440px;height:440px;bottom:-8%;left:-4%;background:linear-gradient(135deg,#00c6ff,#0044cc);animation-delay:-9s;}
@keyframes bF{0%{transform:translate(0,0)scale(1);}100%{transform:translate(-28px,38px)scale(1.08);}}
code.cpn{background:rgba(0,102,255,.1);color:#60a5fa;padding:4px 8px;border-radius:6px;font-family:monospace;font-weight:700;font-size:.9rem;}
</style>
</head>
<body id="adminBody">
<div class="dash-bg"><div class="dash-blob b1"></div><div class="dash-blob b2"></div></div>
<?php include('includes/sidebar.php'); ?>
<div class="main">
    <div class="ph">
        <h1>🎟️ Coupon / Discount Codes</h1>
        <button class="ttb" id="themeToggleBtn"><span id="tIcon">☀️</span><span id="tText">Light Mode</span></button>
    </div>

    <?php if($success): ?><div class="adm-alert as">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if($error): ?><div class="adm-alert ae">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if(isset($_GET['msg'])): ?><div class="adm-alert as">✅ Coupon <?= $_GET['msg'] === 'deleted' ? 'deleted' : 'status updated' ?>.</div><?php endif; ?>

    <!-- Create Coupon Form -->
    <div class="card">
        <div class="card-title">➕ Create New Coupon</div>
        <form method="POST">
            <div class="fg-row">
                <div class="fg">
                    <label class="fl">Coupon Code</label>
                    <input type="text" name="code" class="fc" placeholder="e.g. SAVE20" required style="text-transform:uppercase;">
                </div>
                <div class="fg">
                    <label class="fl">Discount Type</label>
                    <select name="discount_type" class="fc">
                        <option value="percent">Percent (%)</option>
                        <option value="fixed">Fixed (₹)</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Discount Value</label>
                    <input type="number" name="discount_value" class="fc" placeholder="e.g. 20" step="0.01" required>
                </div>
                <div class="fg">
                    <label class="fl">Min Order Amount (₹)</label>
                    <input type="number" name="min_order_amount" class="fc" placeholder="0" step="0.01" value="0">
                </div>
                <div class="fg">
                    <label class="fl">Usage Limit</label>
                    <input type="number" name="usage_limit" class="fc" placeholder="100" value="100">
                </div>
                <div class="fg">
                    <label class="fl">Expires At</label>
                    <input type="date" name="expires_at" class="fc">
                </div>
            </div>
            <button type="submit" name="add_coupon" class="btn-save">🎟️ Create Coupon</button>
        </form>
    </div>

    <!-- Coupons List -->
    <div class="card">
        <div class="card-title">All Coupons</div>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Min Order</th>
                    <th>Used / Limit</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($coupons) === 0): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--m);padding:30px;">No coupons yet. Create your first one above!</td></tr>
            <?php else: ?>
            <?php while ($c = mysqli_fetch_assoc($coupons)): ?>
                <tr>
                    <td><code class="cpn"><?= htmlspecialchars($c['code']) ?></code></td>
                    <td><?= $c['discount_type'] === 'percent' ? '% Off' : '₹ Off' ?></td>
                    <td><?= $c['discount_type'] === 'percent' ? $c['discount_value'].'%' : '₹'.number_format($c['discount_value'],2) ?></td>
                    <td><?= $c['min_order_amount'] > 0 ? '₹'.number_format($c['min_order_amount'],2) : 'None' ?></td>
                    <td><?= $c['used_count'] ?> / <?= $c['usage_limit'] ?></td>
                    <td><?= $c['expires_at'] ? date('d M Y', strtotime($c['expires_at'])) : '—' ?></td>
                    <td>
                        <span class="badge <?= $c['is_active'] ? 'badge-green' : 'badge-red' ?>">
                            <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                        <?php if ($c['expires_at'] && strtotime($c['expires_at']) < time()): ?>
                        <span class="badge badge-yellow" style="margin-left:4px;">Expired</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="coupons.php?toggle=<?= $c['id'] ?>" class="btn-sm btn-info"><?= $c['is_active'] ? 'Deactivate' : 'Activate' ?></a>
                        <a href="coupons.php?delete=<?= $c['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete coupon <?= htmlspecialchars($c['code']) ?>?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
const b=document.getElementById('adminBody'),t=document.getElementById('themeToggleBtn'),i=document.getElementById('tIcon'),x=document.getElementById('tText');
if((localStorage.getItem('adminTheme')||'dark')==='light')L();
t.addEventListener('click',()=>{ if(b.dataset.theme==='light'){b.removeAttribute('data-theme');localStorage.setItem('adminTheme','dark');i.textContent='☀️';x.textContent='Light Mode';}else L(); });
function L(){b.dataset.theme='light';localStorage.setItem('adminTheme','light');i.textContent='🌙';x.textContent='Dark Mode';}
document.querySelector('input[name="code"]').addEventListener('input',function(){this.value=this.value.toUpperCase();});
</script>
</body></html>
