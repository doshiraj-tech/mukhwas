<?php
include("../config/db.php");
require_once('auth_guard.php');

$success = '';
$error   = '';

// ── Handle form saves ─────────────────────────────────────────
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Determine which tab was submitted
    $submitted_tab = $_POST['submitted_tab'] ?? 'general';

    // Save each setting key→value into a "settings" table if it exists,
    // otherwise just show success (extend with DB later)
    // For now we store in session as a demo
    foreach ($_POST as $key => $val) {
        if ($key === 'submitted_tab') continue;
        $_SESSION['settings'][$key] = htmlspecialchars(strip_tags($val));
    }

    $success      = 'Settings saved successfully!';
    $active_tab   = $submitted_tab;
    header("Location: settings_general.php?tab=$active_tab&saved=1");
    exit();
}

if (isset($_GET['saved'])) {
    $success = 'Settings saved successfully!';
}

// Pull saved values (session demo — swap for DB query in production)
function setting($key, $default = '') {
    return htmlspecialchars($_SESSION['settings'][$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Raj Kathiyawadi Mukhwash Admin</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

<style>
/* ── Root & Reset ── */
:root {
    --dash-glass-bg:     rgba(255,255,255,0.02);
    --dash-glass-border: rgba(255,255,255,0.07);
    --dash-text-main:    #ffffff;
    --dash-text-muted:   #94a3b8;
    --dash-smooth:       cubic-bezier(0.25,1,0.5,1);
    --dash-card-shadow:  rgba(0,0,0,0.6);
    --accent:            #0066ff;
    --accent-glow:       rgba(0,102,255,0.35);
    --danger:            #ef4444;
    --success:           #22c55e;
}
[data-theme="light"] {
    --dash-glass-bg:     rgba(0,0,0,0.02);
    --dash-glass-border: rgba(0,0,0,0.08);
    --dash-text-main:    #1e293b;
    --dash-text-muted:   #64748b;
    --dash-card-shadow:  rgba(0,0,0,0.08);
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

body {
    background: #070b19;
    color: var(--dash-text-main);
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 15px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    transition: background 0.35s, color 0.35s;
}
[data-theme="light"] body { background: #f1f5f9 !important; }

/* Ambient blobs */
.dash-bg { position:fixed; inset:0; z-index:0; pointer-events:none; }
.dash-blob {
    position:absolute;
    background: linear-gradient(135deg,#0066ff,#7c3aed);
    border-radius:50%;
    filter:blur(140px);
    opacity:.15;
    animation: blobFloat 18s infinite alternate ease-in-out;
}
.dash-blob-1 { width:380px; height:380px; top:5%; right:-4%; }
.dash-blob-2 { width:450px; height:450px; bottom:-8%; left:-4%;
               background:linear-gradient(135deg,#00c6ff,#0044cc); animation-delay:-9s; }
@keyframes blobFloat { 0%{transform:translate(0,0) scale(1);} 100%{transform:translate(-30px,40px) scale(1.08);} }

/* ── Main area ── */
.main-area {
    margin-left: 255px;
    padding: 36px 40px;
    position: relative;
    z-index: 10;
    min-height: 100vh;
}
@media(max-width:900px){ .main-area{ margin-left:0; padding:20px 16px; } }

/* ── Page header ── */
.page-header {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom: 30px; flex-wrap:wrap; gap:12px;
}
.page-header h1 {
    font-size: 1.75rem; font-weight:700;
    color: var(--dash-text-main);
    display:flex; align-items:center; gap:10px;
}

/* Theme toggle */
.theme-toggle-btn {
    background: var(--dash-glass-bg);
    border: 1px solid var(--dash-glass-border);
    color: var(--dash-text-main);
    padding: 9px 16px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 0.88rem;
    font-weight: 600;
    display:flex; align-items:center; gap:7px;
    transition: all 0.3s;
}
.theme-toggle-btn:hover { border-color:var(--accent); box-shadow:0 0 12px var(--accent-glow); }

/* ── Alert banners ── */
.adm-alert {
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 22px;
    font-weight: 600;
    font-size: 0.9rem;
    display:flex; align-items:center; gap:10px;
    animation: slideDown 0.4s ease;
}
@keyframes slideDown { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.adm-alert-success { background:rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.25); color:#4ade80; }
.adm-alert-error   { background:rgba(239,68,68,.12);  border:1px solid rgba(239,68,68,.25);  color:#f87171; }
[data-theme="light"] .adm-alert-success { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
[data-theme="light"] .adm-alert-error   { background:#fef2f2; color:#b91c1c; border-color:#fecaca; }

/* ── Settings layout ── */
.settings-layout {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 24px;
    align-items: start;
}
@media(max-width:768px){ .settings-layout{ grid-template-columns:1fr; } }

/* ── Tab nav ── */
.settings-nav {
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--dash-glass-border);
    border-radius: 16px;
    overflow: hidden;
    position: sticky;
    top: 24px;
}
[data-theme="light"] .settings-nav { background:#fff; border-color:#e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,.06); }

.settings-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    color: var(--dash-text-muted);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    border-left: 3px solid transparent;
    transition: all 0.25s ease;
    cursor: pointer;
}
.settings-nav-item:hover {
    color: var(--dash-text-main);
    background: rgba(255,255,255,.04);
    border-left-color: rgba(0,102,255,.4);
    padding-left: 24px;
}
[data-theme="light"] .settings-nav-item:hover { background:#f8fafc; color:#111827; }

.settings-nav-item.active {
    color: #fff;
    background: rgba(0,102,255,.12);
    border-left-color: var(--accent);
    padding-left: 24px;
}
[data-theme="light"] .settings-nav-item.active { color:#1d4ed8; background:#eff6ff; }

.settings-nav-icon { font-size:16px; width:20px; text-align:center; flex-shrink:0; }
.settings-nav-divider { border:none; border-top:1px solid var(--dash-glass-border); margin:4px 0; }

/* ── Card ── */
.s-card {
    background: rgba(255,255,255,.025);
    border: 1px solid var(--dash-glass-border);
    border-radius: 18px;
    padding: 30px 32px;
    margin-bottom: 20px;
    transition: border-color .3s;
}
.s-card:hover { border-color: rgba(0,102,255,.2); }
[data-theme="light"] .s-card { background:#fff; border-color:#e2e8f0; box-shadow:0 2px 10px rgba(0,0,0,.05); }

.s-card-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dash-text-main);
    margin-bottom: 22px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--dash-glass-border);
    display:flex; align-items:center; gap:10px;
}
[data-theme="light"] .s-card-title { border-bottom-color:#e2e8f0; }

/* ── Form elements ── */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-bottom: 18px;
}
@media(max-width:600px){ .form-row{ grid-template-columns:1fr; } }

.form-group { margin-bottom: 18px; }
.form-group:last-child { margin-bottom: 0; }

.form-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dash-text-muted);
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 8px;
}
.form-control {
    width: 100%;
    background: rgba(255,255,255,.04);
    border: 1px solid var(--dash-glass-border);
    border-radius: 10px;
    padding: 11px 14px;
    color: var(--dash-text-main);
    font-size: 0.92rem;
    outline: none;
    transition: border-color .25s, box-shadow .25s;
    font-family: inherit;
}
.form-control:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0,102,255,.15);
}
[data-theme="light"] .form-control {
    background:#f8fafc;
    border-color:#e2e8f0;
    color:#1e293b;
}
[data-theme="light"] .form-control:focus { background:#fff; }

textarea.form-control { resize:vertical; min-height:90px; }
select.form-control { cursor:pointer; }

/* Toggle switch */
.toggle-row {
    display:flex; align-items:center; justify-content:space-between;
    padding: 14px 0;
    border-bottom: 1px solid var(--dash-glass-border);
}
.toggle-row:last-child { border-bottom:none; padding-bottom:0; }
[data-theme="light"] .toggle-row { border-bottom-color:#f1f5f9; }

.toggle-label { font-weight:500; color:var(--dash-text-main); }
.toggle-desc   { font-size:.8rem; color:var(--dash-text-muted); margin-top:2px; }

.toggle-switch { position:relative; width:44px; height:24px; flex-shrink:0; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider {
    position:absolute; inset:0;
    background: rgba(255,255,255,.15);
    border-radius:24px;
    cursor:pointer;
    transition:.3s;
}
.toggle-slider::before {
    content:'';
    position:absolute;
    width:18px; height:18px;
    left:3px; top:3px;
    background:#fff;
    border-radius:50%;
    transition:.3s;
}
.toggle-switch input:checked + .toggle-slider { background:var(--accent); }
.toggle-switch input:checked + .toggle-slider::before { transform:translateX(20px); }

/* ── Save button ── */
.btn-save {
    display:inline-flex; align-items:center; gap:8px;
    background: linear-gradient(135deg,#0066ff,#0044cc);
    color:#fff;
    border:none;
    padding: 12px 28px;
    border-radius: 12px;
    font-size:.95rem;
    font-weight:600;
    cursor:pointer;
    transition: all .3s var(--dash-smooth);
    box-shadow: 0 4px 14px rgba(0,102,255,.3);
}
.btn-save:hover {
    transform:translateY(-2px);
    box-shadow: 0 8px 22px rgba(0,102,255,.45);
}
.btn-save:active { transform:scale(.97); }

/* Hidden tab panels */
.tab-panel { display:none; }
.tab-panel.active { display:block; animation:tabFadeIn .3s ease; }
@keyframes tabFadeIn { from{opacity:0;transform:translateY(6px);} to{opacity:1;transform:translateY(0);} }
</style>
</head>

<body id="adminBody">
<div class="dash-bg">
    <div class="dash-blob dash-blob-1"></div>
    <div class="dash-blob dash-blob-2"></div>
</div>

<?php include('includes/sidebar.php'); ?>

<div class="main-area">

    <!-- Header -->
    <div class="page-header">
        <h1>⚙️ Settings</h1>
        <button class="theme-toggle-btn" id="themeToggleBtn">
            <span id="themeToggleIcon">☀️</span>
            <span id="themeToggleText">Light Mode</span>
        </button>
    </div>

    <?php if($success): ?>
    <div class="adm-alert adm-alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="adm-alert adm-alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="settings-layout">

        <!-- ── Tab Navigation ── -->
        <nav class="settings-nav">
            <a class="settings-nav-item <?= $active_tab==='general'  ? 'active':'' ?>"  href="?tab=general"  onclick="switchTab('general',event)">
                <span class="settings-nav-icon">🏠</span> General
            </a>
            <a class="settings-nav-item <?= $active_tab==='store'    ? 'active':'' ?>"  href="?tab=store"    onclick="switchTab('store',event)">
                <span class="settings-nav-icon">🏪</span> Store Info
            </a>
            <hr class="settings-nav-divider">
            <a class="settings-nav-item <?= $active_tab==='shipping' ? 'active':'' ?>"  href="?tab=shipping" onclick="switchTab('shipping',event)">
                <span class="settings-nav-icon">🚚</span> Shipping
            </a>
            <a class="settings-nav-item <?= $active_tab==='payments' ? 'active':'' ?>"  href="?tab=payments" onclick="switchTab('payments',event)">
                <span class="settings-nav-icon">💳</span> Payments
            </a>
            <hr class="settings-nav-divider">
            <a class="settings-nav-item <?= $active_tab==='seo'      ? 'active':'' ?>"  href="?tab=seo"      onclick="switchTab('seo',event)">
                <span class="settings-nav-icon">🔍</span> SEO &amp; Meta
            </a>
            <a class="settings-nav-item <?= $active_tab==='abstract_api' ? 'active':'' ?>" href="?tab=abstract_api" onclick="switchTab('abstract_api',event)">
                <span class="settings-nav-icon">🛡️</span> Email Anti-Fraud API
            </a>
        </nav>

        <!-- ── Panel Area ── -->
        <div class="panels">

            <!-- ══════════ GENERAL ══════════ -->
            <div class="tab-panel <?= $active_tab==='general' ? 'active':'' ?>" id="panel-general">
                <form method="POST">
                <input type="hidden" name="submitted_tab" value="general">

                <div class="s-card">
                    <div class="s-card-title">🌐 Site Settings</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-control"
                                   value="<?= setting('site_name','Raj Kathiyawadi Mukhwash') ?>"
                                   placeholder="Your site name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Admin Email</label>
                            <input type="email" name="admin_email" class="form-control"
                                   value="<?= setting('admin_email','admin@mukhwash.com') ?>"
                                   placeholder="admin@example.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Site Tagline</label>
                        <input type="text" name="site_tagline" class="form-control"
                               value="<?= setting('site_tagline','Premium Traditional Mouth Fresheners') ?>"
                               placeholder="Short tagline">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Currency Symbol</label>
                            <select name="currency" class="form-control">
                                <option value="INR" <?= setting('currency','INR')==='INR'?'selected':'' ?>>₹ Indian Rupee (INR)</option>
                                <option value="USD" <?= setting('currency','INR')==='USD'?'selected':'' ?>>$ US Dollar (USD)</option>
                                <option value="EUR" <?= setting('currency','INR')==='EUR'?'selected':'' ?>>€ Euro (EUR)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Timezone</label>
                            <select name="timezone" class="form-control">
                                <option value="Asia/Kolkata"  <?= setting('timezone','Asia/Kolkata')==='Asia/Kolkata'?'selected':'' ?>>Asia/Kolkata (IST)</option>
                                <option value="UTC"           <?= setting('timezone','Asia/Kolkata')==='UTC'?'selected':'' ?>>UTC</option>
                                <option value="America/New_York" <?= setting('timezone','Asia/Kolkata')==='America/New_York'?'selected':'' ?>>America/New_York</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="s-card">
                    <div class="s-card-title">🔔 Notifications</div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Order Notifications</div>
                             <div class="toggle-desc">Email when a new order is placed</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="notif_orders" <?= setting('notif_orders','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Low Stock Alerts</div>
                             <div class="toggle-desc">Alert when product stock falls below 5</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="notif_stock" <?= setting('notif_stock','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Customer Signup Alerts</div>
                             <div class="toggle-desc">Notify when a new customer registers</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="notif_signup" <?= setting('notif_signup','')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-save">💾 Save General Settings</button>
                </form>
            </div>

            <!-- ══════════ STORE INFO ══════════ -->
            <div class="tab-panel <?= $active_tab==='store' ? 'active':'' ?>" id="panel-store">
                <form method="POST">
                <input type="hidden" name="submitted_tab" value="store">

                <div class="s-card">
                    <div class="s-card-title">🏪 Store Details</div>
                    <div class="form-group">
                        <label class="form-label">Owner / Business Name</label>
                        <input type="text" name="owner_name" class="form-control"
                               value="<?= setting('owner_name','Raj Kathiyawadi Mukhwash') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="store_phone" class="form-control"
                                   value="<?= setting('store_phone','+91 8140265904') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="text" name="store_whatsapp" class="form-control"
                                   value="<?= setting('store_whatsapp','+91 8140265904') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Store Address</label>
                        <textarea name="store_address" class="form-control"><?= setting('store_address','Surat, Gujarat, India') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="store_city" class="form-control"
                                   value="<?= setting('store_city','Surat') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">PIN Code</label>
                            <input type="text" name="store_pin" class="form-control"
                                   value="<?= setting('store_pin','395001') ?>">
                        </div>
                    </div>
                </div>

                <div class="s-card">
                    <div class="s-card-title">🕐 Business Hours</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Opening Time</label>
                            <input type="time" name="open_time" class="form-control"
                                   value="<?= setting('open_time','09:00') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Closing Time</label>
                            <input type="time" name="close_time" class="form-control"
                                   value="<?= setting('close_time','21:00') ?>">
                        </div>
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Store Open on Sundays</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="open_sunday" <?= setting('open_sunday','')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-save">💾 Save Store Info</button>
                </form>
            </div>

            <!-- ══════════ SHIPPING ══════════ -->
            <div class="tab-panel <?= $active_tab==='shipping' ? 'active':'' ?>" id="panel-shipping">
                <form method="POST">
                <input type="hidden" name="submitted_tab" value="shipping">

                <div class="s-card">
                    <div class="s-card-title">🚚 Shipping Rates</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Standard Shipping Rate (₹)</label>
                            <input type="number" name="ship_standard" class="form-control"
                                   value="<?= setting('ship_standard','50') ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Express Shipping Rate (₹)</label>
                            <input type="number" name="ship_express" class="form-control"
                                   value="<?= setting('ship_express','120') ?>" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Free Shipping Above Order Value (₹)</label>
                        <input type="number" name="ship_free_above" class="form-control"
                               value="<?= setting('ship_free_above','500') ?>" min="0">
                    </div>
                </div>

                <div class="s-card">
                    <div class="s-card-title">⏱️ Delivery Times</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Standard Delivery (days)</label>
                            <input type="number" name="delivery_standard_days" class="form-control"
                                   value="<?= setting('delivery_standard_days','5') ?>" min="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Express Delivery (days)</label>
                            <input type="number" name="delivery_express_days" class="form-control"
                                   value="<?= setting('delivery_express_days','2') ?>" min="1">
                        </div>
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Enable Cash on Delivery</div>
                             <div class="toggle-desc">Allow COD for all orders</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="enable_cod" <?= setting('enable_cod','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Show Estimated Delivery Date</div>
                             <div class="toggle-desc">Display ETA on product & checkout pages</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_eta" <?= setting('show_eta','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-save">💾 Save Shipping Settings</button>
                </form>
            </div>

            <!-- ══════════ PAYMENTS ══════════ -->
            <div class="tab-panel <?= $active_tab==='payments' ? 'active':'' ?>" id="panel-payments">
                <form method="POST">
                <input type="hidden" name="submitted_tab" value="payments">

                <div class="s-card">
                    <div class="s-card-title">💳 Payment Methods</div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Cash on Delivery (COD)</div>
                             <div class="toggle-desc">Accept payments on delivery</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="pay_cod" <?= setting('pay_cod','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Razorpay (UPI / Cards)</div>
                             <div class="toggle-desc">Online payment gateway</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="pay_razorpay" <?= setting('pay_razorpay','')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">PhonePe / GPay QR</div>
                             <div class="toggle-desc">Display QR code on checkout</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="pay_qr" <?= setting('pay_qr','')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="s-card">
                    <div class="s-card-title">🔑 Razorpay API Keys</div>
                    <div class="form-group">
                        <label class="form-label">Razorpay Key ID</label>
                        <input type="text" name="rz_key_id" class="form-control"
                               value="<?= setting('rz_key_id') ?>"
                               placeholder="rzp_live_xxxxxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Razorpay Key Secret</label>
                        <input type="password" name="rz_key_secret" class="form-control"
                               value="<?= setting('rz_key_secret') ?>"
                               placeholder="••••••••••••••••">
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Test Mode</div>
                             <div class="toggle-desc">Use test keys (no real transactions)</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="rz_test_mode" <?= setting('rz_test_mode','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="s-card">
                    <div class="s-card-title">🧾 Tax Settings</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">GST Rate (%)</label>
                            <input type="number" name="gst_rate" class="form-control"
                                   value="<?= setting('gst_rate','18') ?>" min="0" max="100">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GSTIN Number</label>
                            <input type="text" name="gstin" class="form-control"
                                   value="<?= setting('gstin') ?>" placeholder="22AAAAA0000A1Z5">
                        </div>
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Show GST on Invoice</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_gst" <?= setting('show_gst','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-save">💾 Save Payment Settings</button>
                </form>
            </div>

            <!-- ══════════ SEO ══════════ -->
            <div class="tab-panel <?= $active_tab==='seo' ? 'active':'' ?>" id="panel-seo">
                <form method="POST">
                <input type="hidden" name="submitted_tab" value="seo">

                <div class="s-card">
                    <div class="s-card-title">🔍 SEO Basics</div>
                    <div class="form-group">
                        <label class="form-label">Homepage Meta Title</label>
                        <input type="text" name="meta_title" class="form-control"
                               value="<?= setting('meta_title','Raj Kathiyawadi Mukhwash - Premium Mouth Fresheners') ?>"
                               maxlength="70">
                        <small style="color:var(--dash-text-muted);font-size:.75rem;">Recommended: under 60 characters</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Homepage Meta Description</label>
                        <textarea name="meta_description" class="form-control"
                                  maxlength="160"><?= setting('meta_description','Discover premium traditional Indian mukhwas mouth fresheners. Shop Raj Kathiyawadi Mukhwash — natural, hygienic, and delicious.') ?></textarea>
                        <small style="color:var(--dash-text-muted);font-size:.75rem;">Recommended: under 160 characters</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keywords (comma separated)</label>
                        <input type="text" name="meta_keywords" class="form-control"
                               value="<?= setting('meta_keywords','mukhwas, mouth freshener, kathiyawadi, indian mukhwash') ?>">
                    </div>
                </div>

                <div class="s-card">
                    <div class="s-card-title">📱 Social Media & Instagram Ordering</div>
                    <div class="form-group">
                        <label class="form-label">Instagram Handle</label>
                        <input type="text" name="social_instagram" class="form-control"
                               value="<?= setting('social_instagram', 'raj_kadhiyawadi_mukhwas') ?>" placeholder="@yourhandle">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Instagram Order Message Greeting</label>
                        <textarea name="social_instagram_msg" class="form-control" rows="2" placeholder="Hello Raj Kathiyawadi Mukhwash! 👋&#10;I would like to order:"><?= setting('social_instagram_msg', "Hello Raj Kathiyawadi Mukhwash! 👋\nI would like to order:") ?></textarea>
                        <small style="color:var(--dash-text-muted);font-size:.75rem;">Custom message greeting pre-filled when customers click 'Order via Instagram'.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Facebook Page URL</label>
                        <input type="url" name="social_facebook" class="form-control"
                               value="<?= setting('social_facebook') ?>" placeholder="https://facebook.com/yourpage">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WhatsApp Business Number</label>
                        <input type="text" name="social_whatsapp" class="form-control"
                               value="<?= setting('social_whatsapp') ?>" placeholder="+91 00000 00000">
                    </div>
                </div>

                <div class="s-card">
                    <div class="s-card-title">🤖 Google Analytics</div>
                    <div class="form-group">
                        <label class="form-label">Google Analytics Measurement ID</label>
                        <input type="text" name="ga_id" class="form-control"
                               value="<?= setting('ga_id') ?>" placeholder="G-XXXXXXXXXX">
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Enable Google Analytics</div>
                             <div class="toggle-desc">Track visitor data and conversions</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="ga_enabled" <?= setting('ga_enabled','')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="s-card">
                    <div class="s-card-title">📍 Google Maps</div>
                    <div class="form-group">
                        <label class="form-label">Google Maps API Key</label>
                        <input type="text" name="google_maps_api_key" class="form-control"
                               value="<?= setting('google_maps_api_key') ?>"
                               placeholder="AIzaSy...your-api-key">
                        <small style="color:var(--dash-text-muted);font-size:.75rem;">Get your API key from <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color:var(--accent);">Google Cloud Console</a>. Enable "Maps JavaScript API".</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Map Latitude</label>
                            <input type="text" name="map_latitude" class="form-control"
                                   value="<?= setting('map_latitude','22.3039') ?>"
                                   placeholder="22.3039">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Map Longitude</label>
                            <input type="text" name="map_longitude" class="form-control"
                                   value="<?= setting('map_longitude','70.8022') ?>"
                                   placeholder="70.8022">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Map Marker Title</label>
                        <input type="text" name="map_marker_title" class="form-control"
                               value="<?= setting('map_marker_title','Raj Kathiyawadi Mukhwash') ?>"
                               placeholder="Your Business Name">
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Enable Interactive Map</div>
                             <div class="toggle-desc">Use Google Maps JavaScript API instead of basic embed on Contact page</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="enable_gmap_api" <?= setting('enable_gmap_api','')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                <div class="s-card">
                    <div class="s-card-title">🌐 External API Iframe Integration</div>
                    <div class="form-group">
                        <label class="form-label">API Iframe Source URL (src)</label>
                        <input type="url" name="api_iframe_src" class="form-control"
                               value="<?= setting('api_iframe_src', 'https://b.example.com') ?>"
                               placeholder="https://b.example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Permissions (allow attribute)</label>
                        <input type="text" name="api_iframe_allow" class="form-control"
                               value="<?= setting('api_iframe_allow', 'geolocation') ?>"
                               placeholder="geolocation">
                        <small style="color:var(--dash-text-muted);font-size:.75rem;">Specifies feature policies like geolocation, camera, microphone.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Custom Full Iframe Code (Optional)</label>
                        <textarea name="api_iframe_code" class="form-control" rows="3" placeholder='<iframe src="https://b.example.com" allow="geolocation"></iframe>'><?= setting('api_iframe_code', '<iframe src="https://b.example.com" allow="geolocation"></iframe>') ?></textarea>
                    </div>
                    <div class="toggle-row">
                        <div><div class="toggle-label">Enable API Iframe Embed</div>
                             <div class="toggle-desc">Display API iframe on frontend pages and dedicated API embed route</div></div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="api_iframe_enabled" <?= setting('api_iframe_enabled','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-save">💾 Save SEO Settings</button>
                </form>
            </div>

            <!-- ══════════ ABSTRACT API ANTI-FRAUD ══════════ -->
            <div class="tab-panel <?= $active_tab==='abstract_api' ? 'active':'' ?>" id="panel-abstract_api">
                <form method="POST">
                <input type="hidden" name="submitted_tab" value="abstract_api">

                <div class="s-card">
                    <div class="s-card-title">🛡️ Abstract API Configuration & Fraud Rules</div>
                    
                    <div class="form-group">
                        <label class="form-label">Abstract API Key</label>
                        <input type="text" name="abstract_api_key" class="form-control"
                               value="<?= setting('abstract_api_key') ?>"
                               placeholder="e.g. 74b8a...12948a">
                        <small style="color:var(--dash-text-muted);font-size:.75rem;">Get your API key from <a href="https://www.abstractapi.com/api/email-verification-validation-api" target="_blank" style="color:var(--accent);">Abstract API Dashboard</a>.</small>
                    </div>

                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Enable Abstract API Verification</div>
                            <div class="toggle-desc">Enable automated fraud detection & email validation on registration, checkout, & contact forms</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="abstract_api_enabled" <?= setting('abstract_api_enabled','off')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="form-row" style="margin-top:15px;">
                        <div class="form-group">
                            <label class="form-label">Minimum Quality Score (0.00 - 1.00)</label>
                            <input type="number" step="0.05" min="0.0" max="1.0" name="abstract_api_min_score" class="form-control"
                                   value="<?= setting('abstract_api_min_score','0.50') ?>" placeholder="0.50">
                            <small style="color:var(--dash-text-muted);font-size:.75rem;">Emails with a quality score below this threshold will be flagged as fraud.</small>
                        </div>
                    </div>

                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Block Disposable / Temporary Emails</div>
                            <div class="toggle-desc">Automatically reject emails from disposable domain providers (mailinator, 10minutemail, etc.)</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="abstract_api_block_disposable" <?= setting('abstract_api_block_disposable','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Block Undeliverable Emails</div>
                            <div class="toggle-desc">Reject email addresses whose domains fail MX lookup or SMTP verification</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="abstract_api_block_undeliverable" <?= setting('abstract_api_block_undeliverable','on')==='on'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <button type="submit" class="btn-save" style="margin-top:20px;">💾 Save Anti-Fraud Settings</button>
                </div>
                </form>

                <!-- Interactive Live API Tester Card -->
                <div class="s-card" style="margin-top:20px;">
                    <div class="s-card-title">🧪 Interactive Live API Tester</div>
                    <p style="color:var(--dash-text-muted);font-size:.85rem;margin-bottom:15px;">Test your Abstract API integration and view live verification metrics in real-time.</p>

                    <div class="form-row" style="align-items:flex-end;">
                        <div class="form-group" style="flex:1;">
                            <label class="form-label">Email Address to Test</label>
                            <input type="email" id="adminTestEmail" class="form-control" placeholder="e.g. test@mailinator.com or user@gmail.com" value="user@gmail.com">
                        </div>
                        <div class="form-group">
                            <button type="button" id="runTestApiBtn" class="btn-save" style="padding:11px 20px;">⚡ Run Live API Test</button>
                        </div>
                    </div>

                    <div id="testResultBox" style="display:none;margin-top:20px;padding:20px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid var(--dash-glass-border);">
                        <!-- Dynamic output will render here -->
                    </div>
                </div>

            </div>

        </div><!-- /panels -->
    </div><!-- /settings-layout -->

</div><!-- /main-area -->

<script>
/* ── Tab switching (SPA-style, no reload) ── */
function switchTab(tabId, e) {
    if (e) e.preventDefault();

    // Update nav
    document.querySelectorAll('.settings-nav-item').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

    document.querySelector('[href="?tab=' + tabId + '"]').classList.add('active');
    document.getElementById('panel-' + tabId).classList.add('active');

    // Update URL without reload
    history.replaceState(null,'','?tab=' + tabId);
}

/* ── Theme toggle (same as dashboard) ── */
const body    = document.getElementById('adminBody');
const tBtn    = document.getElementById('themeToggleBtn');
const tIcon   = document.getElementById('themeToggleIcon');
const tText   = document.getElementById('themeToggleText');

const saved = localStorage.getItem('adminTheme') || 'dark';
if (saved === 'light') applyLight();

tBtn.addEventListener('click', () => {
    if (body.dataset.theme === 'light') {
        body.removeAttribute('data-theme');
        localStorage.setItem('adminTheme','dark');
        tIcon.textContent = '☀️';
        tText.textContent = 'Light Mode';
    } else {
        applyLight();
    }
});

function applyLight() {
    body.dataset.theme = 'light';
    localStorage.setItem('adminTheme','light');
    tIcon.textContent = '🌙';
    tText.textContent = 'Dark Mode';
}

/* ── Interactive Abstract API Tester ── */
document.getElementById('runTestApiBtn')?.addEventListener('click', function() {
    const email = document.getElementById('adminTestEmail').value.trim();
    const resultBox = document.getElementById('testResultBox');
    
    if (!email) {
        alert('Please enter an email address to test.');
        return;
    }

    this.disabled = true;
    this.innerHTML = '⌛ Testing...';
    resultBox.style.display = 'block';
    resultBox.innerHTML = '<div style="color:var(--dash-text-muted);">Querying Abstract API verification engine...</div>';

    const formData = new FormData();
    formData.append('email', email);

    fetch('../api/verify_email.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('runTestApiBtn').disabled = false;
        document.getElementById('runTestApiBtn').innerHTML = '⚡ Run Live API Test';

        if (!data || !data.details) {
            resultBox.innerHTML = '<div style="color:var(--danger);">Error executing verification test.</div>';
            return;
        }

        const d = data.details;
        const scorePct = Math.round((d.quality_score || 0) * 100);
        const isPass = data.is_valid && !data.is_fraud;

        let html = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:10px; border-bottom:1px solid var(--dash-glass-border);">
                <div>
                    <strong style="font-size:1.1rem; color:var(--dash-text-main);">${email}</strong>
                    <div style="font-size:0.8rem; color:var(--dash-text-muted);">Status: ${data.message}</div>
                </div>
                <span style="padding:6px 14px; border-radius:20px; font-weight:600; font-size:0.85rem; ${isPass ? 'background:rgba(34,197,94,0.15); color:#22c55e;' : 'background:rgba(239,68,68,0.15); color:#ef4444;'}">
                    ${isPass ? '✅ ALLOWED (TRUSTED)' : '❌ BLOCKED (FRAUD/INVALID)'}
                </span>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:12px; margin-bottom:15px;">
                <div style="background:rgba(255,255,255,0.02); padding:10px; border-radius:8px; border:1px solid var(--dash-glass-border);">
                    <div style="font-size:0.75rem; color:var(--dash-text-muted);">Quality Score</div>
                    <div style="font-size:1.1rem; font-weight:700; color:${scorePct >= 70 ? '#22c55e' : (scorePct >= 50 ? '#f59e0b' : '#ef4444')};">${scorePct}%</div>
                </div>
                <div style="background:rgba(255,255,255,0.02); padding:10px; border-radius:8px; border:1px solid var(--dash-glass-border);">
                    <div style="font-size:0.75rem; color:var(--dash-text-muted);">Deliverability</div>
                    <div style="font-size:1rem; font-weight:600; color:var(--dash-text-main);">${d.deliverability || 'N/A'}</div>
                </div>
                <div style="background:rgba(255,255,255,0.02); padding:10px; border-radius:8px; border:1px solid var(--dash-glass-border);">
                    <div style="font-size:0.75rem; color:var(--dash-text-muted);">Disposable Domain</div>
                    <div style="font-size:1rem; font-weight:600; color:${d.is_disposable ? '#ef4444' : '#22c55e'};">${d.is_disposable ? 'YES (Risk)' : 'NO (Safe)'}</div>
                </div>
                <div style="background:rgba(255,255,255,0.02); padding:10px; border-radius:8px; border:1px solid var(--dash-glass-border);">
                    <div style="font-size:0.75rem; color:var(--dash-text-muted);">MX Server Found</div>
                    <div style="font-size:1rem; font-weight:600; color:${d.is_mx_found ? '#22c55e' : '#ef4444'};">${d.is_mx_found ? 'YES' : 'NO'}</div>
                </div>
            </div>
        `;

        if (d.autocorrect) {
            html += `
                <div style="background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.3); padding:10px 14px; border-radius:8px; margin-bottom:15px; color:#60a5fa; font-size:0.85rem;">
                    💡 <strong>Autocorrect Suggestion:</strong> ${d.autocorrect}
                </div>
            `;
        }

        html += `
            <details style="font-size:0.8rem; color:var(--dash-text-muted); cursor:pointer;">
                <summary style="font-weight:600; color:var(--accent);">Inspect Full API JSON Payload</summary>
                <pre style="background:#0f172a; color:#cbd5e1; padding:12px; border-radius:8px; margin-top:8px; overflow-x:auto; font-size:0.75rem;">${JSON.stringify(d, null, 2)}</pre>
            </details>
        `;

        resultBox.innerHTML = html;
    })
    .catch(err => {
        document.getElementById('runTestApiBtn').disabled = false;
        document.getElementById('runTestApiBtn').innerHTML = '⚡ Run Live API Test';
        resultBox.innerHTML = '<div style="color:var(--danger);">Network or Server Error: ' + err.message + '</div>';
    });
});
</script>

</body>
</html>

