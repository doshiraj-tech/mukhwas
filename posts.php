<?php
include('../config/db.php');
require_once('auth_guard.php');

// Ensure tables exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS post_cats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cat_id INT DEFAULT 1,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    body TEXT,
    status VARCHAR(20) DEFAULT 'Published',
    cover_image VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $img_res = mysqli_query($conn, "SELECT cover_image FROM posts WHERE id='$id'");
    if ($img_res && mysqli_num_rows($img_res) > 0) {
        $row = mysqli_fetch_assoc($img_res);
        $cover = $row['cover_image'] ?? '';
        if (!empty($cover) && file_exists('../assets/uploads/' . $cover)) {
            @unlink('../assets/uploads/' . $cover);
        }
    }
    mysqli_query($conn, "DELETE FROM posts WHERE id='$id'");
    header('Location: posts.php'); exit();
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $r  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM posts WHERE id='$id'"));
    $new = ($r['status'] ?? 'Published') === 'Published' ? 'Draft' : 'Published';
    mysqli_query($conn, "UPDATE posts SET status='$new' WHERE id='$id'");
    header('Location: posts.php'); exit();
}

$filter = $_GET['status'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE p.status='$filter'" : '';
$posts  = mysqli_query($conn, "SELECT p.*, IFNULL(c.name,'Uncategorised') AS cat_name
    FROM posts p LEFT JOIN post_cats c ON p.cat_id=c.id $where ORDER BY p.id DESC");
$total  = mysqli_num_rows($posts);

$count_pub   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM posts WHERE status='Published'"))['c'];
$count_draft = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM posts WHERE status='Draft'"))['c'];
$count_all   = $count_pub + $count_draft;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Posts - Admin Panel</title>
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
.btn-add{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0066ff,#0044cc);
    color:#fff;padding:10px 22px;border-radius:10px;text-decoration:none;font-size:.9rem;font-weight:600;
    transition:all .3s;box-shadow:0 4px 14px rgba(0,102,255,.28);}
.btn-add:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,102,255,.42);color:#fff;}
.ttb{background:var(--g);border:1px solid var(--b);color:var(--t);padding:9px 16px;border-radius:10px;
    cursor:pointer;font-size:.88rem;font-weight:600;display:flex;align-items:center;gap:7px;transition:all .3s;}
.ttb:hover{border-color:var(--a);box-shadow:0 0 12px var(--ag);}
.header-r{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
/* stat mini */
.sr{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;margin-bottom:24px;}
.sm{background:var(--g);border:1px solid var(--b);border-radius:14px;padding:16px 18px;
    display:flex;align-items:center;gap:12px;text-decoration:none;transition:all .3s;}
.sm:hover{transform:translateY(-3px);border-color:rgba(0,102,255,.3);}
.sm.af{border-color:var(--a);background:rgba(0,102,255,.08);}
.sm-icon{font-size:20px;width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;}
.sm-num{font-size:1.25rem;font-weight:700;color:var(--t);}
.sm-lbl{font-size:.7rem;color:var(--m);text-transform:uppercase;letter-spacing:.5px;}
/* card */
.card{background:var(--g);border:1px solid var(--b);border-radius:18px;overflow:hidden;}
[data-theme=light] .card{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.05);}
/* table */
.tbl{width:100%;border-collapse:collapse;}
.tbl thead tr{background:rgba(0,102,255,.06);}
.tbl th{padding:12px 16px;text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.7px;color:var(--m);font-weight:700;}
.tbl td{padding:12px 16px;border-top:1px solid var(--b);font-size:.87rem;vertical-align:middle;}
[data-theme=light] .tbl td{border-top-color:#f1f5f9;}
.tbl tr:hover td{background:rgba(255,255,255,.02);}
[data-theme=light] .tbl tr:hover td{background:#f8fafc;}
/* badges */
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;}
.bp{background:rgba(34,197,94,.1);color:#4ade80;border:1px solid rgba(34,197,94,.2);}
.bd{background:rgba(148,163,184,.1);color:#94a3b8;border:1px solid rgba(148,163,184,.2);}
[data-theme=light] .bp{background:#f0fdf4;color:#15803d;border-color:#bbf7d0;}
[data-theme=light] .bd{background:#f8fafc;color:#64748b;border-color:#e2e8f0;}
/* actions */
.act-btns{display:flex;gap:6px;flex-wrap:wrap;}
.ab{padding:5px 12px;border-radius:7px;font-size:.78rem;font-weight:600;text-decoration:none;
    border:1px solid var(--b);color:var(--m);transition:all .25s;cursor:pointer;background:transparent;}
.ab:hover{border-color:var(--a);color:#60a5fa;}
.ab-del{border-color:rgba(239,68,68,.3);color:#f87171;}
.ab-del:hover{background:rgba(239,68,68,.1);border-color:#ef4444;}
/* empty */
.empty{text-align:center;padding:60px 20px;color:var(--m);}
.empty-ico{font-size:48px;margin-bottom:12px;}
</style>
</head>
<body id="adminBody">
<div class="dash-bg"><div class="dash-blob b1"></div><div class="dash-blob b2"></div></div>
<?php include('includes/sidebar.php'); ?>
<div class="main">
    <div class="ph">
        <h1>📝 Posts</h1>
        <div class="header-r">
            <a href="post_add.php" class="btn-add">✏️ Add New Post</a>
            <button class="ttb" id="themeToggleBtn"><span id="tIcon">☀️</span><span id="tText">Light Mode</span></button>
        </div>
    </div>

    <!-- Stats / filter -->
    <div class="sr">
        <a href="?status=all" class="sm <?= $filter==='all'?'af':'' ?>">
            <div class="sm-icon" style="background:rgba(255,255,255,.06);">📝</div>
            <div><div class="sm-num"><?= $count_all ?></div><div class="sm-lbl">All Posts</div></div>
        </a>
        <a href="?status=Published" class="sm <?= $filter==='Published'?'af':'' ?>">
            <div class="sm-icon" style="background:rgba(34,197,94,.1);">✅</div>
            <div><div class="sm-num"><?= $count_pub ?></div><div class="sm-lbl">Published</div></div>
        </a>
        <a href="?status=Draft" class="sm <?= $filter==='Draft'?'af':'' ?>">
            <div class="sm-icon" style="background:rgba(148,163,184,.1);">📄</div>
            <div><div class="sm-num"><?= $count_draft ?></div><div class="sm-lbl">Drafts</div></div>
        </a>
    </div>

    <div class="card">
        <?php if ($total === 0): ?>
        <div class="empty">
            <div class="empty-ico">📭</div>
            <p>No posts yet. <a href="post_add.php" style="color:#60a5fa;">Create your first post →</a></p>
        </div>
        <?php else: ?>
        <table class="tbl">
            <thead><tr>
                <th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Date</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php $i=0; while($p = mysqli_fetch_assoc($posts)): $i++; ?>
            <tr>
                <td style="color:var(--m);font-size:.75rem;"><?= $i ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($p['title']) ?></td>
                <td style="color:var(--m);"><?= htmlspecialchars($p['cat_name']) ?></td>
                <td><span class="badge <?= $p['status']==='Published'?'bp':'bd' ?>"><?= $p['status'] ?></span></td>
                <td style="color:var(--m);font-size:.78rem;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                <td>
                    <div class="act-btns">
                        <a href="post_edit.php?id=<?= $p['id'] ?>" class="ab">✏️ Edit</a>
                        <a href="?toggle=<?= $p['id'] ?>" class="ab">🔄 <?= $p['status']==='Published'?'Draft':'Publish' ?></a>
                        <a href="?delete=<?= $p['id'] ?>" class="ab ab-del"
                           onclick="return confirm('Delete this post?')">🗑️ Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<script>
const b=document.getElementById('adminBody'),t=document.getElementById('themeToggleBtn'),
      i=document.getElementById('tIcon'),x=document.getElementById('tText');
if((localStorage.getItem('adminTheme')||'dark')==='light')L();
t.addEventListener('click',()=>{ if(b.dataset.theme==='light'){b.removeAttribute('data-theme');localStorage.setItem('adminTheme','dark');i.textContent='☀️';x.textContent='Light Mode';}else L(); });
function L(){b.dataset.theme='light';localStorage.setItem('adminTheme','light');i.textContent='🌙';x.textContent='Dark Mode';}
</script>
</body></html>
