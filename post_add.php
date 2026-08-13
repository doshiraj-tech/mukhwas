<?php
include('../config/db.php');
require_once('auth_guard.php');

// Ensure tables
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS post_cats (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, slug VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS posts (id INT AUTO_INCREMENT PRIMARY KEY, cat_id INT DEFAULT 1, title VARCHAR(255) NOT NULL, slug VARCHAR(255), body TEXT, status VARCHAR(20) DEFAULT 'Published', cover_image VARCHAR(255) DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

$success = ''; $error = '';

// Handle submit
if (isset($_POST['save_post'])) {
    $title  = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $body   = mysqli_real_escape_string($conn, trim($_POST['body']  ?? ''));
    $cat_id = (int)($_POST['cat_id'] ?? 1);
    $status = in_array($_POST['status'] ?? '', ['Published','Draft']) ? $_POST['status'] : 'Published';
    $slug   = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    $cover  = '';

    if (empty($title)) { $error = 'Post title is required.'; }
    else {
        // Handle image upload (secure)
        if (!empty($_FILES['cover_image']['name'])) {
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($_FILES['cover_image']['tmp_name']);
            if (in_array($ext, $allowed_exts) && in_array($mime, $allowed_mimes)) {
                $safe_name = 'post_' . uniqid() . '.' . $ext;
                $dest = '../assets/uploads/' . $safe_name;
                move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest);
                $cover = mysqli_real_escape_string($conn, $safe_name);
            } else {
                $error = 'Invalid image file. Only JPG, PNG, WEBP, GIF allowed.';
            }
        }
        if (empty($error)) {
            $q = mysqli_query($conn, "INSERT INTO posts (cat_id,title,slug,body,status,cover_image)
                VALUES ('$cat_id','$title','$slug','$body','$status','$cover')");
            if ($q) { header('Location: posts.php'); exit(); }
            else    { $error = 'Database error: ' . mysqli_error($conn); }
        }
    }
}

$cats = mysqli_query($conn, "SELECT * FROM post_cats ORDER BY name ASC");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add Post - Admin Panel</title>
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
.layout{display:grid;grid-template-columns:1fr 300px;gap:22px;align-items:start;}
@media(max-width:900px){.layout{grid-template-columns:1fr;}}
.card{background:var(--g);border:1px solid var(--b);border-radius:18px;padding:26px 28px;margin-bottom:20px;}
[data-theme=light] .card{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.05);}
.card-title{font-size:1rem;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--b);display:flex;align-items:center;gap:8px;}
.fl{display:block;font-size:.78rem;font-weight:600;color:var(--m);text-transform:uppercase;letter-spacing:.6px;margin-bottom:7px;}
.fc{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--b);border-radius:10px;padding:11px 14px;color:var(--t);font-size:.92rem;outline:none;transition:border-color .25s,box-shadow .25s;font-family:inherit;}
.fc:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(0,102,255,.15);}
[data-theme=light] .fc{background:#f8fafc;border-color:#e2e8f0;color:#1e293b;}
[data-theme=light] .fc:focus{background:#fff;}
textarea.fc{resize:vertical;min-height:280px;}
.fg{margin-bottom:18px;}
.fg:last-child{margin-bottom:0;}
.btn-save{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0066ff,#0044cc);
    color:#fff;border:none;padding:12px 26px;border-radius:12px;font-size:.95rem;font-weight:600;cursor:pointer;
    transition:all .3s;box-shadow:0 4px 14px rgba(0,102,255,.28);width:100%%;justify-content:center;}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,102,255,.42);}
.btn-back{display:inline-flex;align-items:center;gap:6px;color:var(--m);text-decoration:none;font-size:.85rem;font-weight:600;padding:8px 0;margin-bottom:6px;transition:color .25s;}
.btn-back:hover{color:#60a5fa;}
.adm-alert{padding:14px 20px;border-radius:12px;margin-bottom:20px;font-weight:600;font-size:.9rem;display:flex;align-items:center;gap:10px;animation:sD .4s ease;}
@keyframes sD{from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);}}
.ae{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;}
[data-theme=light] .ae{background:#fef2f2;color:#b91c1c;border-color:#fecaca;}
.radio-group{display:flex;gap:14px;}
.radio-opt{display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.9rem;color:var(--t);}
.radio-opt input{accent-color:var(--a);}
/* preview box */
.img-preview{width:100%;height:140px;border-radius:10px;border:2px dashed var(--b);display:flex;
    align-items:center;justify-content:center;font-size:32px;margin-bottom:14px;overflow:hidden;
    background:rgba(255,255,255,.02);}
.img-preview img{width:100%;height:100%;object-fit:cover;}

/* Inline Editor Toolbar */
.editor-toolbar { background: rgba(255,255,255,0.02); border: 1px solid var(--b); border-bottom: none; border-radius: 10px 10px 0 0; padding: 8px 14px; display: flex; gap: 8px; }
[data-theme=light] .editor-toolbar { background: #f8fafc; border-color: #e2e8f0; }
.btn-inline-img { background: transparent; border: 1px solid var(--b); color: var(--t); padding: 5px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: 0.2s; display: flex; align-items: center; gap: 5px; }
.btn-inline-img:hover { background: rgba(0,102,255,0.1); border-color: var(--a); color: #60a5fa; }
[data-theme=light] .btn-inline-img { border-color: #e2e8f0; color: #475569; }
[data-theme=light] .btn-inline-img:hover { background: #eff6ff; color: #2563eb; border-color: #93c5fd; }
textarea.fc.has-toolbar { border-top-left-radius: 0; border-top-right-radius: 0; }
</style>
</head>
<body id="adminBody">
<div class="dash-bg"><div class="dash-blob b1"></div><div class="dash-blob b2"></div></div>
<?php include('includes/sidebar.php'); ?>
<div class="main">
    <div class="ph">
        <h1>✏️ Add New Post</h1>
        <button class="ttb" id="themeToggleBtn"><span id="tIcon">☀️</span><span id="tText">Light Mode</span></button>
    </div>

    <a href="posts.php" class="btn-back">← Back to All Posts</a>

    <?php if($error): ?><div class="adm-alert ae">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
    <div class="layout">

        <!-- Main content -->
        <div>
            <div class="card">
                <div class="fg">
                    <label class="fl">Post Title *</label>
                    <input type="text" name="title" class="fc" placeholder="Enter post title..." required
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label class="fl">Content / Body</label>
                    <div class="editor-toolbar">
                        <button type="button" class="btn-inline-img" onclick="document.getElementById('inlineImgInput').click()" id="btnInlineImg">
                            🖼️ Insert Image
                        </button>
                    </div>
                    <input type="file" id="inlineImgInput" style="display:none" accept="image/*">
                    <textarea name="body" id="postBody" class="fc has-toolbar" placeholder="Write your post content here..."><?= htmlspecialchars($_POST['body'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Sidebar options -->
        <div>
            <div class="card">
                <div class="card-title">📋 Publish</div>
                <div class="fg">
                    <label class="fl">Status</label>
                    <div class="radio-group">
                        <label class="radio-opt">
                            <input type="radio" name="status" value="Published"
                                <?= ($_POST['status'] ?? 'Published') === 'Published' ? 'checked' : '' ?>>
                            Published
                        </label>
                        <label class="radio-opt">
                            <input type="radio" name="status" value="Draft"
                                <?= ($_POST['status'] ?? '') === 'Draft' ? 'checked' : '' ?>>
                            Draft
                        </label>
                    </div>
                </div>
                <div class="fg">
                    <button type="submit" name="save_post" class="btn-save">💾 Save Post</button>
                </div>
            </div>

            <div class="card">
                <div class="card-title">📂 Category</div>
                <div class="fg">
                    <label class="fl">Post Category</label>
                    <select name="cat_id" class="fc">
                        <option value="">— Uncategorised —</option>
                        <?php mysqli_data_seek($cats, 0); while($c = mysqli_fetch_assoc($cats)): ?>
                        <option value="<?= $c['id'] ?>" <?= ($_POST['cat_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="margin-top:8px;">
                    <a href="post_categories.php" style="font-size:.78rem;color:#60a5fa;">+ Add new category</a>
                </div>
            </div>

            <div class="card">
                <div class="card-title">🖼️ Cover Image</div>
                <div class="img-preview" id="imgPreview">🖼️</div>
                <div class="fg">
                    <label class="fl">Upload Image</label>
                    <input type="file" name="cover_image" class="fc" accept="image/*" id="imgInput">
                </div>
            </div>
        </div>
    </div>
    </form>
</div>
<script>
// Image preview
document.getElementById('imgInput').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imgPreview').innerHTML = `<img src="${e.target.result}" alt="preview">`;
        };
        reader.readAsDataURL(file);
    }
});

// Inline Image Upload
document.getElementById('inlineImgInput').addEventListener('change', async function() {
    if (!this.files.length) return;
    const file = this.files[0];
    const btn = document.getElementById('btnInlineImg');
    const oldHtml = btn.innerHTML;
    
    btn.innerHTML = '⏳ Uploading...';
    btn.disabled = true;
    
    const fd = new FormData();
    fd.append('inline_image', file);
    
    try {
        const res = await fetch('upload_inline_image.php', { method: 'POST', body: fd });
        const json = await res.json();
        
        if (json.success) {
            const ta = document.getElementById('postBody');
            const imgTag = `\n<img src="${json.url}" alt="Image" style="max-width:100%; border-radius:8px; margin:14px 0;">\n`;
            
            const start = ta.selectionStart;
            const end = ta.selectionEnd;
            ta.value = ta.value.substring(0, start) + imgTag + ta.value.substring(end);
            
            ta.selectionStart = ta.selectionEnd = start + imgTag.length;
            ta.focus();
        } else {
            alert(json.error || 'Upload failed');
        }
    } catch (e) {
        alert('An error occurred during upload.');
    }
    
    btn.innerHTML = oldHtml;
    btn.disabled = false;
    this.value = ''; // Reset input
});

const b=document.getElementById('adminBody'),t=document.getElementById('themeToggleBtn'),i=document.getElementById('tIcon'),x=document.getElementById('tText');
if((localStorage.getItem('adminTheme')||'dark')==='light')L();
t.addEventListener('click',()=>{ if(b.dataset.theme==='light'){b.removeAttribute('data-theme');localStorage.setItem('adminTheme','dark');i.textContent='☀️';x.textContent='Light Mode';}else L(); });
function L(){b.dataset.theme='light';localStorage.setItem('adminTheme','light');i.textContent='🌙';x.textContent='Dark Mode';}
</script>
</body></html>
