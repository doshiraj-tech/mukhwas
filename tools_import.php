<?php
include("../config/db.php");
require_once('auth_guard.php');

$success = '';
$errors  = [];
$imported = 0;

// ── Handle CSV Template Download ──────────────────────────────
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="products_import_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['name','category_id','price','selling_price','stock','description']);
    fputcsv($out, ['Sample Mukhwas','1','120','90','50','A tasty mouth freshener blend.']);
    fclose($out);
    exit();
}

// ── Handle CSV Upload ─────────────────────────────────────────
if (isset($_POST['import_products']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed. Please try again.';
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
        $errors[] = 'Only .csv files are accepted.';
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle); // skip header row

        // Validate columns
        $required = ['name','category_id','price','selling_price','stock','description'];
        $header_lc = array_map('strtolower', array_map('trim', $header));
        $missing = array_diff($required, $header_lc);

        if (!empty($missing)) {
            $errors[] = 'Missing columns: ' . implode(', ', $missing);
        } else {
            $row_num = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $row_num++;
                if (count($row) < 6) { $errors[] = "Row $row_num: not enough columns, skipped."; continue; }

                $name          = mysqli_real_escape_string($conn, trim($row[0]));
                $category_id   = (int)trim($row[1]);
                $price         = (float)trim($row[2]);
                $selling_price = (float)trim($row[3]);
                $stock         = (int)trim($row[4]);
                $description   = mysqli_real_escape_string($conn, trim($row[5]));
                $image         = 'default.jpg';

                if (empty($name)) { $errors[] = "Row $row_num: name is empty, skipped."; continue; }
                if ($price <= 0)  { $errors[] = "Row $row_num: invalid price, skipped.";  continue; }

                $q = mysqli_query($conn, "INSERT INTO products
                    (category_id, name, price, selling_price, description, image, stock)
                    VALUES ('$category_id','$name','$price','$selling_price','$description','$image','$stock')");

                if ($q) {
                    $product_id = mysqli_insert_id($conn);
                    mysqli_query($conn, "INSERT INTO product_categories (product_id, category_id)
                                        VALUES ('$product_id','$category_id')");
                    $imported++;
                } else {
                    $errors[] = "Row $row_num: DB error — " . mysqli_error($conn);
                }
            }
            fclose($handle);
            if ($imported > 0) $success = "$imported product(s) imported successfully!";
        }
    }
}

// Fetch categories for reference table
$cats = mysqli_query($conn, "SELECT * FROM categories ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import Data - Admin Panel</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<style>
:root{
    --glass-bg:rgba(255,255,255,.025);
    --glass-border:rgba(255,255,255,.07);
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

/* blobs */
.dash-bg{position:fixed;inset:0;z-index:0;pointer-events:none;}
.dash-blob{position:absolute;border-radius:50%;filter:blur(140px);opacity:.14;animation:bF 18s infinite alternate ease-in-out;}
.blob-1{width:380px;height:380px;top:5%;right:-4%;background:linear-gradient(135deg,#0066ff,#7c3aed);}
.blob-2{width:440px;height:440px;bottom:-8%;left:-4%;background:linear-gradient(135deg,#00c6ff,#0044cc);animation-delay:-9s;}
@keyframes bF{0%{transform:translate(0,0)scale(1);}100%{transform:translate(-28px,38px)scale(1.08);}}

.main-area{margin-left:255px;padding:36px 40px;position:relative;z-index:10;min-height:100vh;}
@media(max-width:900px){.main-area{margin-left:0;padding:20px 16px;}}

.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px;flex-wrap:wrap;gap:12px;}
.page-header h1{font-size:1.75rem;font-weight:700;display:flex;align-items:center;gap:10px;}
.theme-toggle-btn{background:var(--glass-bg);border:1px solid var(--glass-border);color:var(--text-main);
    padding:9px 16px;border-radius:10px;cursor:pointer;font-size:.88rem;font-weight:600;
    display:flex;align-items:center;gap:7px;transition:all .3s;}
.theme-toggle-btn:hover{border-color:var(--accent);box-shadow:0 0 12px var(--accent-glow);}

/* alerts */
.adm-alert{padding:14px 20px;border-radius:12px;margin-bottom:20px;font-weight:600;font-size:.9rem;
    display:flex;align-items:flex-start;gap:10px;animation:slideD .4s ease;}
@keyframes slideD{from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);}}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#4ade80;}
.alert-error  {background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.25); color:#f87171;}
[data-theme="light"] .alert-success{background:#f0fdf4;color:#15803d;border-color:#bbf7d0;}
[data-theme="light"] .alert-error  {background:#fef2f2;color:#b91c1c;border-color:#fecaca;}
.alert-list{margin:6px 0 0 18px;}
.alert-list li{font-size:.82rem;font-weight:400;margin-bottom:2px;}

/* layout */
.import-layout{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;}
@media(max-width:900px){.import-layout{grid-template-columns:1fr;}}

/* card */
.s-card{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:18px;
    padding:28px 30px;margin-bottom:22px;transition:border-color .3s;}
.s-card:hover{border-color:rgba(0,102,255,.2);}
[data-theme="light"] .s-card{background:#fff;border-color:#e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.05);}

.s-card-title{font-size:1.05rem;font-weight:700;color:var(--text-main);margin-bottom:22px;
    padding-bottom:12px;border-bottom:1px solid var(--glass-border);
    display:flex;align-items:center;gap:10px;}
[data-theme="light"] .s-card-title{border-bottom-color:#e2e8f0;}

/* steps */
.step-list{counter-reset:step;}
.step-item{display:flex;gap:14px;margin-bottom:18px;align-items:flex-start;}
.step-num{width:28px;height:28px;background:linear-gradient(135deg,#0066ff,#0044cc);border-radius:50%;
    display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;
    color:#fff;flex-shrink:0;margin-top:2px;}
.step-body{font-size:.88rem;color:var(--text-muted);line-height:1.55;}
.step-body strong{color:var(--text-main);display:block;margin-bottom:2px;}

/* dropzone */
.dropzone{border:2px dashed var(--glass-border);border-radius:14px;padding:40px 24px;
    text-align:center;cursor:pointer;transition:all .3s;position:relative;}
.dropzone.drag-over{border-color:var(--accent);background:rgba(0,102,255,.06);}
.dropzone:hover{border-color:rgba(0,102,255,.4);}
.dropzone input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.dz-icon{font-size:40px;margin-bottom:12px;}
.dz-text{font-size:.95rem;color:var(--text-muted);}
.dz-text strong{color:var(--text-main);}
.dz-accepted{font-size:.75rem;color:var(--text-muted);margin-top:6px;}
#file-name-display{margin-top:12px;font-size:.82rem;color:#60a5fa;font-weight:600;display:none;}

/* form */
.form-label{display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);
    text-transform:uppercase;letter-spacing:.6px;margin-bottom:7px;}
.form-control{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--glass-border);
    border-radius:10px;padding:11px 14px;color:var(--text-main);font-size:.92rem;outline:none;
    transition:border-color .25s,box-shadow .25s;font-family:inherit;}
.form-control:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(0,102,255,.15);}
[data-theme="light"] .form-control{background:#f8fafc;border-color:#e2e8f0;color:#1e293b;}

.form-group{margin-bottom:18px;}

/* buttons */
.btn-primary{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0066ff,#0044cc);
    color:#fff;border:none;padding:12px 26px;border-radius:12px;font-size:.95rem;font-weight:600;
    cursor:pointer;transition:all .3s;box-shadow:0 4px 14px rgba(0,102,255,.28);}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,102,255,.42);}
.btn-outline{display:inline-flex;align-items:center;gap:8px;background:transparent;
    border:1px solid var(--glass-border);color:var(--text-muted);padding:10px 20px;
    border-radius:10px;font-size:.88rem;font-weight:600;text-decoration:none;
    transition:all .3s;cursor:pointer;}
.btn-outline:hover{border-color:var(--accent);color:#60a5fa;}

/* category table */
.ref-table{width:100%;border-collapse:collapse;font-size:.83rem;}
.ref-table th{background:rgba(0,102,255,.08);color:var(--text-muted);text-transform:uppercase;
    font-size:.7rem;letter-spacing:.6px;padding:10px 14px;text-align:left;}
.ref-table td{padding:9px 14px;border-bottom:1px solid var(--glass-border);color:var(--text-main);}
.ref-table tr:last-child td{border-bottom:none;}
[data-theme="light"] .ref-table th{background:#f1f5f9;color:#64748b;}
[data-theme="light"] .ref-table td{border-bottom-color:#f1f5f9;}

/* warning banner */
.warn-banner{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);
    border-radius:12px;padding:14px 18px;margin-bottom:22px;display:flex;gap:10px;
    font-size:.85rem;color:#fbbf24;align-items:flex-start;}
[data-theme="light"] .warn-banner{background:#fffbeb;color:#b45309;border-color:#fde68a;}
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
        <h1>📥 Import Data</h1>
        <button class="theme-toggle-btn" id="themeToggleBtn">
            <span id="themeToggleIcon">☀️</span>
            <span id="themeToggleText">Light Mode</span>
        </button>
    </div>

    <?php if ($success): ?>
    <div class="adm-alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="adm-alert alert-error">
        <div>
            ⚠️ Import completed with <?= count($errors) ?> issue(s):
            <ul class="alert-list">
                <?php foreach($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="warn-banner">
        ⚠️ <span><strong>Important:</strong> Importing will <strong>add new records</strong> to the database. It will NOT update existing products. Make sure your CSV matches the required format exactly.</span>
    </div>

    <div class="import-layout">

        <!-- LEFT: Upload form -->
        <div>
            <div class="s-card">
                <div class="s-card-title">📋 How to Import Products</div>

                <div class="step-item">
                    <div class="step-num">1</div>
                    <div class="step-body">
                        <strong>Download the CSV Template</strong>
                        Get the correct column structure to fill in your data.
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-num">2</div>
                    <div class="step-body">
                        <strong>Fill in Your Products</strong>
                        Open in Excel or Google Sheets. Use category IDs from the reference table on the right.
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-num">3</div>
                    <div class="step-body">
                        <strong>Upload &amp; Import</strong>
                        Select your filled CSV file and click Import Products.
                    </div>
                </div>

                <a href="?template=1" class="btn-outline" style="margin-bottom:26px;">
                    ⬇️ Download CSV Template
                </a>
            </div>

            <div class="s-card">
                <div class="s-card-title">📤 Upload CSV File</div>

                <form method="POST" enctype="multipart/form-data" id="importForm">

                    <!-- Dropzone -->
                    <div class="dropzone" id="dropzone">
                        <input type="file" name="csv_file" id="csvInput" accept=".csv" required>
                        <div class="dz-icon">📄</div>
                        <div class="dz-text"><strong>Click to choose</strong> or drag &amp; drop your CSV here</div>
                        <div class="dz-accepted">Accepted: .csv files only</div>
                        <div id="file-name-display"></div>
                    </div>

                    <div class="form-group" style="margin-top:18px;">
                        <label class="form-label">Import Mode</label>
                        <select name="import_mode" class="form-control">
                            <option value="add">Add New Products Only (skip duplicates)</option>
                            <option value="overwrite">Add All (may create duplicates)</option>
                        </select>
                    </div>

                    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                        <button type="submit" name="import_products" class="btn-primary">
                            📥 Import Products
                        </button>
                        <a href="tools_export.php" class="btn-outline">
                            📤 Go to Export
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- RIGHT: Category reference -->
        <div>
            <div class="s-card">
                <div class="s-card-title">📂 Category IDs Reference</div>
                <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:16px;">
                    Use these IDs in the <code style="background:rgba(0,102,255,.1);padding:2px 6px;border-radius:4px;">category_id</code> column of your CSV.
                </p>
                <table class="ref-table">
                    <thead>
                        <tr><th>ID</th><th>Category Name</th></tr>
                    </thead>
                    <tbody>
                        <?php while($cat = mysqli_fetch_assoc($cats)): ?>
                        <tr>
                            <td><strong><?= $cat['id'] ?></strong></td>
                            <td><?= htmlspecialchars($cat['category_name']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="s-card">
                <div class="s-card-title">📝 Required CSV Columns</div>
                <?php
                $cols = [
                    ['name','Text','Product display name'],
                    ['category_id','Number','Use ID from table above'],
                    ['price','Number','MRP / original price'],
                    ['selling_price','Number','Discounted price'],
                    ['stock','Number','Quantity available'],
                    ['description','Text','Short product description'],
                ];
                ?>
                <table class="ref-table">
                    <thead><tr><th>Column</th><th>Type</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php foreach($cols as $c): ?>
                        <tr>
                            <td><code style="color:#60a5fa;"><?= $c[0] ?></code></td>
                            <td style="color:var(--text-muted);"><?= $c[1] ?></td>
                            <td style="color:var(--text-muted);font-size:.78rem;"><?= $c[2] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /import-layout -->
</div>

<script>
/* Dropzone file name display */
const input = document.getElementById('csvInput');
const display = document.getElementById('file-name-display');
const dz = document.getElementById('dropzone');

input.addEventListener('change', () => {
    if (input.files.length) {
        display.textContent = '📎 ' + input.files[0].name;
        display.style.display = 'block';
        dz.style.borderColor = '#0066ff';
    }
});

dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag-over'); });
dz.addEventListener('dragleave',  () => dz.classList.remove('drag-over'));
dz.addEventListener('drop', e => {
    e.preventDefault(); dz.classList.remove('drag-over');
    if (e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        display.textContent = '📎 ' + e.dataTransfer.files[0].name;
        display.style.display = 'block';
    }
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
