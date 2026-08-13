<?php
include('../config/db.php');
require_once('auth_guard.php');

$success = ''; $error = '';

// Create
if (isset($_POST['add_category'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    if (empty($name)) { $error = 'Category name is required.'; }
    else {
        mysqli_query($conn, "INSERT INTO post_cats (name, slug) VALUES ('$name', '$slug')");
        $success = 'Category added successfully!';
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== 1) { // prevent deleting default
        mysqli_query($conn, "DELETE FROM post_cats WHERE id='$id'");
        mysqli_query($conn, "UPDATE posts SET cat_id=1 WHERE cat_id='$id'"); // reassign posts
        header('Location: post_categories.php'); exit();
    }
}

$cats = mysqli_query($conn, "SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.cat_id = c.id) as post_count FROM post_cats c ORDER BY c.id ASC");
$total = mysqli_num_rows($cats);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Post Categories - Admin Panel</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<style>
:root{--g:rgba(255,255,255,.025);--b:rgba(255,255,255,.07);--t:#fff;--m:#94a3b8;--a:#0066ff;--ag:rgba(0,102,255,.35);}
[data-theme=light]{--g:#fff;--b:#e2e8f0;--t:#1e293b;--m:#64748b;}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#070b19;color:var(--t);font-family:'Segoe UI',system-ui,sans-serif;line-height:1.6;-webkit-font-smoothing:antialiased;transition:background .35s,color .35s;}
[data-theme=light] body{background:#f1f5f9!important;}
.dash-bg{position:fixed;inset:0;z-index:0;pointer-events:none;}
.dash-blob{position:absolute;border-radius:50%;filter:blur(140px);opacity:.14;animation:bF 18s infinite alternate ease-in-out;}
.b1{width:380px;height:380px;top:5%;right:-4%;background:linear-gradient(135deg,#0066ff,#7c3aed);}
.b2{width:440px;height:440px;bottom:-8%;left:-4%;background:linear-gradient(135deg,#00c6ff,#0044cc);animation-delay:-9s;}
@keyframes bF{0%{transform:translate(0,0)scale(1);}100%{transform:translate(-28px,38px)scale(1.08);}}
.main{margin-left:255px;padding:36px 40px;position:relative;z-index:10;min-height:100vh;}
@media(max-width:900px){.main{margin-left:0;padding:20px 16px;}}
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.ph h1{font-size:1.75rem;font-weight:700;display:flex;align-items:center;gap:10px;}
.ttb{background:var(--g);border:1px solid var(--b);color:var(--t);padding:9px 16px;border-radius:10px;cursor:pointer;font-size:.88rem;font-weight:600;display:flex;align-items:center;gap:7px;transition:all .3s;}
.ttb:hover{border-color:var(--a);}
.layout{display:grid;grid-template-columns:300px 1fr;gap:22px;align-items:start;}
@media(max-width:900px){.layout{grid-template-columns:1fr;}}
.card{background:var(--g);border:1px solid var(--b);border-radius:18px;padding:26px 28px;margin-bottom:20px;overflow:hidden;}
[data-theme=light] .card{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.05);}
.card-title{font-size:1.1rem;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--b);display:flex;align-items:center;gap:8px;}
.fl{display:block;font-size:.78rem;font-weight:600;color:var(--m);text-transform:uppercase;letter-spacing:.6px;margin-bottom:7px;}
.fc{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--b);border-radius:10px;padding:11px 14px;color:var(--t);font-size:.92rem;outline:none;transition:border-color .25s;font-family:inherit;}
.fc:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(0,102,255,.15);}
[data-theme=light] .fc{background:#f8fafc;border-color:#e2e8f0;color:#1e293b;}
.fg{margin-bottom:18px;}
.btn-save{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0066ff,#0044cc);color:#fff;border:none;padding:12px 26px;border-radius:12px;font-size:.95rem;font-weight:600;cursor:pointer;transition:all .3s;box-shadow:0 4px 14px rgba(0,102,255,.28);width:100%;justify-content:center;}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,102,255,.42);}
.adm-alert{padding:14px 20px;border-radius:12px;margin-bottom:20px;font-weight:600;font-size:.9rem;display:flex;align-items:center;gap:10px;}
.as{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#4ade80;}
.ae{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;}
[data-theme=light] .as{background:#f0fdf4;color:#15803d;border-color:#bbf7d0;}
[data-theme=light] .ae{background:#fef2f2;color:#b91c1c;border-color:#fecaca;}

/* table */
.tbl{width:100%;border-collapse:collapse;margin:-26px -28px;width:calc(100% + 56px);}
.tbl thead tr{background:rgba(0,102,255,.06);}
.tbl th{padding:12px 28px;text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.7px;color:var(--m);font-weight:700;}
.tbl td{padding:12px 28px;border-top:1px solid var(--b);font-size:.87rem;vertical-align:middle;}
[data-theme=light] .tbl td{border-top-color:#f1f5f9;}
.tbl tr:hover td{background:rgba(255,255,255,.02);}
[data-theme=light] .tbl tr:hover td{background:#f8fafc;}
.ab{padding:5px 12px;border-radius:7px;font-size:.78rem;font-weight:600;text-decoration:none;
    border:1px solid var(--b);color:var(--m);transition:all .25s;cursor:pointer;background:transparent;display:inline-block;}
.ab:hover{border-color:var(--a);color:#60a5fa;}
.ab-del{border-color:rgba(239,68,68,.3);color:#f87171;}
.ab-del:hover{background:rgba(239,68,68,.1);border-color:#ef4444;}
</style>
</head>
<body id="adminBody">
<div class="dash-bg"><div class="dash-blob b1"></div><div class="dash-blob b2"></div></div>
<?php include('includes/sidebar.php'); ?>
<div class="main">
    <div class="ph">
        <h1>📂 Post Categories</h1>
        <button class="ttb" id="themeToggleBtn"><span id="tIcon">☀️</span><span id="tText">Light Mode</span></button>
    </div>

    <?php if($success): ?><div class="adm-alert as">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if($error): ?><div class="adm-alert ae">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="layout">
        <!-- Add Category Form -->
        <div>
            <form method="POST">
                <div class="card">
                    <div class="card-title">Add New Category</div>
                    <div class="fg">
                        <label class="fl">Category Name</label>
                        <input type="text" name="name" class="fc" placeholder="e.g. News" required>
                    </div>
                    <div class="fg" style="margin-top:24px;">
                        <button type="submit" name="add_category" class="btn-save">➕ Add Category</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Categories Table -->
        <div>
            <div class="card">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Posts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($c = mysqli_fetch_assoc($cats)): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></td>
                            <td><span style="background:rgba(0,102,255,.1);color:#60a5fa;padding:2px 8px;border-radius:10px;font-size:12px;"><?= $c['post_count'] ?></span></td>
                            <td>
                                <?php if ($c['id'] != 1): ?>
                                <a href="?delete=<?= $c['id'] ?>" class="ab ab-del" onclick="return confirm('Delete this category? Posts will be moved to Uncategorised.')">🗑️ Delete</a>
                                <?php else: ?>
                                <span style="font-size:0.75rem; color:var(--m);">Default</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
const b=document.getElementById('adminBody'),t=document.getElementById('themeToggleBtn'),i=document.getElementById('tIcon'),x=document.getElementById('tText');
if((localStorage.getItem('adminTheme')||'dark')==='light')L();
t.addEventListener('click',()=>{ if(b.dataset.theme==='light'){b.removeAttribute('data-theme');localStorage.setItem('adminTheme','dark');i.textContent='☀️';x.textContent='Light Mode';}else L(); });
function L(){b.dataset.theme='light';localStorage.setItem('adminTheme','light');i.textContent='🌙';x.textContent='Dark Mode';}
</script>
</body></html>
