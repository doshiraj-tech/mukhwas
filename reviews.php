<?php
include('../config/db.php');
require_once('auth_guard.php');

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM reviews WHERE id='$id'");
    header("Location: reviews.php?msg=deleted");
    exit;
}

// Toggle Status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    mysqli_query($conn, "UPDATE reviews SET status = IF(status='approved','pending','approved') WHERE id='$id'");
    header("Location: reviews.php?msg=updated");
    exit;
}

// Reply
if (isset($_POST['reply_id'])) {
    $id = (int)$_POST['reply_id'];
    $reply_text = mysqli_real_escape_string($conn, trim($_POST['reply_text']));
    $reply_text = $reply_text === '' ? 'NULL' : "'$reply_text'";
    mysqli_query($conn, "UPDATE reviews SET admin_reply = $reply_text WHERE id='$id'");
    header("Location: reviews.php?msg=replied");
    exit;
}

$reviews = mysqli_query($conn,
    "SELECT r.*, u.name as user_name, p.name as product_name
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     JOIN products p ON r.product_id = p.id
     ORDER BY r.created_at DESC"
);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reviews - Admin</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<style>
:root{--g:rgba(255,255,255,.025);--b:rgba(255,255,255,.07);--t:#fff;--m:#94a3b8;--a:#0066ff;}
[data-theme=light]{--g:#fff;--b:#e2e8f0;--t:#1e293b;--m:#64748b;}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#070b19;color:var(--t);font-family:'Segoe UI',system-ui,sans-serif;line-height:1.6;-webkit-font-smoothing:antialiased;}
.main{margin-left:255px;padding:36px 40px;min-height:100vh;}
@media(max-width:900px){.main{margin-left:0;padding:20px 16px;}}
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.ph h1{font-size:1.75rem;font-weight:700;display:flex;align-items:center;gap:10px;}
.ttb{background:var(--g);border:1px solid var(--b);color:var(--t);padding:9px 16px;border-radius:10px;cursor:pointer;font-size:.88rem;font-weight:600;display:flex;align-items:center;gap:7px;transition:all .3s;}
.ttb:hover{border-color:var(--a);}
.card{background:var(--g);border:1px solid var(--b);border-radius:18px;padding:26px 28px;margin-bottom:20px;overflow-x:auto;}
[data-theme=light].card,.card[data-theme=light]{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.05);}
[data-theme=light] .card{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.05);}
table{width:100%;border-collapse:collapse;}
th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--b);font-size:.9rem;}
th{font-weight:700;color:var(--m);text-transform:uppercase;font-size:.75rem;letter-spacing:.5px;}
.badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600;}
.badge-green{background:rgba(34,197,94,.15);color:#4ade80;}
.badge-gray{background:rgba(148,163,184,.15);color:#94a3b8;}
.stars{color:#f59e0b;}
.btn-sm{padding:5px 12px;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;border:none;transition:all .2s;}
.btn-danger{background:rgba(239,68,68,.15);color:#f87171;}
.btn-info{background:rgba(0,102,255,.15);color:#60a5fa;}
.btn-danger:hover{background:rgba(239,68,68,.3);}
.btn-info:hover{background:rgba(0,102,255,.3);}
.adm-alert{padding:14px 20px;border-radius:12px;margin-bottom:20px;font-weight:600;font-size:.9rem;}
.as{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#4ade80;}
.dash-bg{position:fixed;inset:0;z-index:0;pointer-events:none;}
.dash-blob{position:absolute;border-radius:50%;filter:blur(140px);opacity:.14;animation:bF 18s infinite alternate ease-in-out;}
.b1{width:380px;height:380px;top:5%;right:-4%;background:linear-gradient(135deg,#0066ff,#7c3aed);}
.b2{width:440px;height:440px;bottom:-8%;left:-4%;background:linear-gradient(135deg,#00c6ff,#0044cc);animation-delay:-9s;}
@keyframes bF{0%{transform:translate(0,0)scale(1);}100%{transform:translate(-28px,38px)scale(1.08);}}
.main{position:relative;z-index:10;}
.btn-gray{background:rgba(148,163,184,.15);color:#94a3b8;}
.btn-gray:hover{background:rgba(148,163,184,.3);}
.admin-reply-preview {margin-top: 5px; font-size: 0.8rem; color: #4ade80; border-left: 2px solid #4ade80; padding-left: 8px;}
[data-theme=light] .admin-reply-preview {color: #16a34a; border-left-color: #16a34a;}
.modal-overlay {display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center; backdrop-filter: blur(3px);}
</style>
</head>
<body id="adminBody">
<div class="dash-bg"><div class="dash-blob b1"></div><div class="dash-blob b2"></div></div>
<?php include('includes/sidebar.php'); ?>
<div class="main">
    <div class="ph">
        <h1>⭐ Product Reviews</h1>
        <button class="ttb" id="themeToggleBtn"><span id="tIcon">☀️</span><span id="tText">Light Mode</span></button>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="adm-alert as">✅ Review <?= $_GET['msg'] === 'deleted' ? 'deleted' : ($_GET['msg'] === 'replied' ? 'replied to' : 'updated') ?> successfully.</div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($reviews) === 0): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--m);padding:30px;">No reviews yet.</td></tr>
            <?php else: ?>
            <?php while($r = mysqli_fetch_assoc($reviews)): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($r['product_name']) ?></td>
                    <td><?= htmlspecialchars($r['user_name']) ?></td>
                    <td class="stars">
                        <?php for($s=1;$s<=5;$s++) echo $s<=$r['rating'] ? '★' : '☆'; ?>
                        <span style="color:var(--m);font-size:.8rem;"> (<?= $r['rating'] ?>/5)</span>
                    </td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= $r['review_text'] ? htmlspecialchars($r['review_text']) : '<em style="color:var(--m)">No text</em>' ?>
                        <?php if($r['admin_reply']): ?>
                            <div class="admin-reply-preview">Reply: <?= htmlspecialchars($r['admin_reply']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $r['status']==='approved' ? 'badge-green' : 'badge-gray' ?>">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="reviews.php?toggle=<?= $r['id'] ?>" class="btn-sm btn-info">Toggle</a>
                        <button class="btn-sm btn-info" onclick="openReply(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['admin_reply'] ?? '')) ?>')">Reply</button>
                        <a href="reviews.php?delete=<?= $r['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this review?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="replyModal" class="modal-overlay">
    <div class="card" style="width:400px; max-width:90%; margin:0;">
        <h3 style="margin-bottom:15px; font-size:1.2rem;">Reply to Review</h3>
        <form method="POST">
            <input type="hidden" name="reply_id" id="replyId">
            <textarea name="reply_text" id="replyText" rows="4" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--b); background:var(--g); color:var(--t); margin-bottom:15px;" placeholder="Leave empty to remove reply..."></textarea>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-sm btn-gray" onclick="document.getElementById('replyModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-sm btn-info">Send Reply</button>
            </div>
        </form>
    </div>
</div>

<script>
const b=document.getElementById('adminBody'),t=document.getElementById('themeToggleBtn'),i=document.getElementById('tIcon'),x=document.getElementById('tText');
if((localStorage.getItem('adminTheme')||'dark')==='light')L();
t.addEventListener('click',()=>{ if(b.dataset.theme==='light'){b.removeAttribute('data-theme');localStorage.setItem('adminTheme','dark');i.textContent='☀️';x.textContent='Light Mode';}else L(); });
function L(){b.dataset.theme='light';localStorage.setItem('adminTheme','light');i.textContent='🌙';x.textContent='Dark Mode';}

function openReply(id, currentReply) {
    document.getElementById('replyId').value = id;
    document.getElementById('replyText').value = currentReply;
    document.getElementById('replyModal').style.display = 'flex';
}
</script>
</body></html>
