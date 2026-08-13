<?php
include('../config/db.php');
require_once('auth_guard.php');

$success = ''; $error = '';

if (isset($_POST['save_colors'])) {
    if (isset($_FILES['brand_texture']) && $_FILES['brand_texture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/';
        $filename = 'brand_texture.png'; // Overwrite the existing texture
        move_uploaded_file($_FILES['brand_texture']['tmp_name'], $upload_dir . $filename);
    }

    $_SESSION['appearance']['secondary_color'] = htmlspecialchars($_POST['secondary_color'] ?? '#c5a059');
    $_SESSION['appearance']['font_family'] = htmlspecialchars($_POST['font_family'] ?? 'Outfit');
    
    // In a full implementation, you would write these settings to a custom.css file
    // For now, it updates the session and uploads the new texture.
    $success = 'Template changes & image saved successfully!';
}

$primary_color = $_SESSION['appearance']['primary_color'] ?? '#1b4d3e';
$secondary_color = $_SESSION['appearance']['secondary_color'] ?? '#c5a059';
$font_family = $_SESSION['appearance']['font_family'] ?? 'Outfit';

?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Colors & Fonts - Appearance</title>
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
.card{background:var(--g);border:1px solid var(--b);border-radius:18px;padding:26px 28px;margin-bottom:20px;max-width:800px;}
[data-theme=light] .card{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.05);}
.card-title{font-size:1.1rem;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--b);display:flex;align-items:center;gap:8px;}
.fl{display:block;font-size:.78rem;font-weight:600;color:var(--m);text-transform:uppercase;letter-spacing:.6px;margin-bottom:7px;}
.fc{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--b);border-radius:10px;padding:11px 14px;color:var(--t);font-size:.92rem;outline:none;transition:border-color .25s;font-family:inherit;}
.fc:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(0,102,255,.15);}
[data-theme=light] .fc{background:#f8fafc;border-color:#e2e8f0;color:#1e293b;}
.fg{margin-bottom:18px;}
.btn-save{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0066ff,#0044cc);color:#fff;border:none;padding:12px 26px;border-radius:12px;font-size:.95rem;font-weight:600;cursor:pointer;transition:all .3s;box-shadow:0 4px 14px rgba(0,102,255,.28);}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,102,255,.42);}
.adm-alert{padding:14px 20px;border-radius:12px;margin-bottom:20px;font-weight:600;font-size:.9rem;display:flex;align-items:center;gap:10px;}
.as{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#4ade80;}
.ae{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;}
[data-theme=light] .as{background:#f0fdf4;color:#15803d;border-color:#bbf7d0;}
[data-theme=light] .ae{background:#fef2f2;color:#b91c1c;border-color:#fecaca;}
</style>
</head>
<body id="adminBody">
<div class="dash-bg"><div class="dash-blob b1"></div><div class="dash-blob b2"></div></div>
<?php include('includes/sidebar.php'); ?>
<div class="main">
    <div class="ph">
        <h1>🎨 Colors & Fonts</h1>
        <button class="ttb" id="themeToggleBtn"><span id="tIcon">☀️</span><span id="tText">Light Mode</span></button>
    </div>

    <?php if($success): ?><div class="adm-alert as">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if($error): ?><div class="adm-alert ae">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="card">
            <div class="card-title">Theme Assets</div>
            
            <div class="fg" style="display:flex; align-items:center; gap: 15px;">
                <input type="file" name="brand_texture" accept="image/*" class="fc" style="width: auto;">
                <div>
                    <label class="fl">Brand Texture Image</label>
                    <div style="font-size: 0.8rem; color: var(--m);">Background image used for buttons and headers instead of a solid color.</div>
                </div>
            </div>
            
            <div class="fg" style="display:flex; align-items:center; gap: 15px; margin-top:20px;">
                <input type="color" name="secondary_color" value="<?= $secondary_color ?>" style="width: 50px; height: 50px; border:none; border-radius:10px; cursor:pointer;">
                <div>
                    <label class="fl">Secondary Color</label>
                    <div style="font-size: 0.8rem; color: var(--m);">Accent color used for highlights and accents. Default: #c5a059</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Typography</div>
            
            <div class="fg">
                <label class="fl">Body Font Family</label>
                <select name="font_family" class="fc">
                    <option value="Outfit" <?= $font_family == 'Outfit' ? 'selected' : '' ?>>Outfit (Sans-serif)</option>
                    <option value="Playfair Display" <?= $font_family == 'Playfair Display' ? 'selected' : '' ?>>Playfair Display (Serif)</option>
                    <option value="Inter" <?= $font_family == 'Inter' ? 'selected' : '' ?>>Inter (Sans-serif)</option>
                    <option value="Roboto" <?= $font_family == 'Roboto' ? 'selected' : '' ?>>Roboto (Sans-serif)</option>
                </select>
            </div>
            
            <div class="fg" style="margin-top: 30px;">
                <button type="submit" name="save_colors" class="btn-save">💾 Save Template Changes</button>
            </div>
        </div>
    </form>
</div>
<script>
const b=document.getElementById('adminBody'),t=document.getElementById('themeToggleBtn'),i=document.getElementById('tIcon'),x=document.getElementById('tText');
if((localStorage.getItem('adminTheme')||'dark')==='light')L();
t.addEventListener('click',()=>{ if(b.dataset.theme==='light'){b.removeAttribute('data-theme');localStorage.setItem('adminTheme','dark');i.textContent='☀️';x.textContent='Light Mode';}else L(); });
function L(){b.dataset.theme='light';localStorage.setItem('adminTheme','light');i.textContent='🌙';x.textContent='Dark Mode';}
</script>
</body></html>
