<?php
// Detect current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- ========== ADMIN SIDEBAR ========== -->
<style>
/* ── Sidebar Shell ── */
.adm-sidebar {
    position: fixed;
    left: 0; top: 0;
    width: 255px;
    height: 100vh;
    background: #0d1117;
    border-right: 1px solid rgba(255,255,255,0.07);
    display: flex;
    flex-direction: column;
    z-index: 200;
    transition: transform 0.35s cubic-bezier(0.25,1,0.5,1),
                background 0.35s ease;
    overflow: hidden;
}
[data-theme="light"] .adm-sidebar {
    background: #ffffff;
    border-right-color: #e5e7eb;
}

/* ── Brand / Logo ── */
.adm-brand {
    padding: 22px 20px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
[data-theme="light"] .adm-brand { border-bottom-color: #e5e7eb; }

.adm-brand-logo {
    width: 42px; height: 42px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}
.adm-brand-logo img {
    width: 100%; height: 100%;
    object-fit: cover;
    border-radius: 8px;
}
.adm-brand-text { line-height: 1.2; }
.adm-brand-text strong {
    display: block;
    color: #fff;
    font-size: 0.92rem;
    font-weight: 700;
    transition: color 0.3s;
}
[data-theme="light"] .adm-brand-text strong { color: #111827; }
.adm-brand-text small {
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 500;
}

/* ── Admin badge ── */
.adm-user-pill {
    margin: 14px 16px 4px;
    background: rgba(0,102,255,0.1);
    border: 1px solid rgba(0,102,255,0.18);
    border-radius: 12px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
[data-theme="light"] .adm-user-pill {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.adm-avatar {
    width: 32px; height: 32px;
    background: linear-gradient(135deg, #0066ff, #7c3aed);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.adm-user-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: #94a3b8;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
[data-theme="light"] .adm-user-name { color: #374151; }
.adm-user-role {
    font-size: 0.68rem;
    color: #0066ff;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ── Scrollable Nav ── */
.adm-nav {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 10px 0 20px;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
}
.adm-nav::-webkit-scrollbar { width: 4px; }
.adm-nav::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.12);
    border-radius: 4px;
}

/* ── Section Labels ── */
.adm-section-label {
    padding: 16px 20px 6px;
    font-size: 0.66rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: #475569;
}

/* ── Nav Item ── */
.adm-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #64748b;
    text-decoration: none;
    padding: 11px 20px;
    font-size: 0.88rem;
    font-weight: 500;
    border-left: 3px solid transparent;
    transition: all 0.25s ease;
    position: relative;
    cursor: pointer;
    user-select: none;
}
[data-theme="light"] .adm-nav-item { color: #6b7280; }

.adm-nav-item:hover {
    color: #fff;
    background: rgba(255,255,255,0.04);
    border-left-color: rgba(0,102,255,0.4);
    padding-left: 24px;
}
[data-theme="light"] .adm-nav-item:hover {
    color: #111827;
    background: #f3f4f6;
    border-left-color: #3b82f6;
}

.adm-nav-item.active {
    color: #fff;
    background: rgba(0,102,255,0.12);
    border-left-color: #0066ff;
    padding-left: 24px;
}
[data-theme="light"] .adm-nav-item.active {
    color: #1d4ed8;
    background: #eff6ff;
    border-left-color: #3b82f6;
}

.adm-nav-icon {
    width: 22px;
    text-align: center;
    font-size: 15px;
    flex-shrink: 0;
}
.adm-nav-badge {
    margin-left: auto;
    background: #0066ff;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    min-width: 20px;
    text-align: center;
}
.adm-nav-badge.danger { background: #ef4444; }
.adm-nav-badge.warning { background: #f59e0b; color: #111; }

/* ── Submenu caret ── */
.adm-caret {
    margin-left: auto;
    font-size: 11px;
    transition: transform 0.3s ease;
    color: #475569;
}
.adm-nav-item.open .adm-caret { transform: rotate(90deg); }

/* ── Submenu ── */
.adm-submenu {
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.35s cubic-bezier(0.25,1,0.5,1);
}
.adm-submenu.open { max-height: 400px; }

.adm-submenu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #4b5563;
    text-decoration: none;
    padding: 9px 20px 9px 48px;
    font-size: 0.83rem;
    font-weight: 500;
    border-left: 3px solid transparent;
    transition: all 0.22s ease;
}
[data-theme="light"] .adm-submenu-item { color: #6b7280; }

.adm-submenu-item::before {
    content: '•';
    font-size: 0.6rem;
    color: #374151;
    flex-shrink: 0;
}
.adm-submenu-item:hover {
    color: #fff;
    background: rgba(255,255,255,0.04);
    border-left-color: rgba(0,102,255,0.35);
    padding-left: 52px;
}
[data-theme="light"] .adm-submenu-item:hover {
    color: #111827;
    background: #f9fafb;
}
.adm-submenu-item.active {
    color: #60a5fa;
    border-left-color: #0066ff;
    padding-left: 52px;
}
[data-theme="light"] .adm-submenu-item.active { color: #2563eb; }

/* ── Divider ── */
.adm-divider {
    margin: 8px 20px;
    border: none;
    border-top: 1px solid rgba(255,255,255,0.07);
}
[data-theme="light"] .adm-divider { border-top-color: #e5e7eb; }

/* ── Sidebar Footer ── */
.adm-sidebar-footer {
    padding: 14px 16px;
    border-top: 1px solid rgba(255,255,255,0.07);
    flex-shrink: 0;
}
[data-theme="light"] .adm-sidebar-footer { border-top-color: #e5e7eb; }

.adm-logout-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 14px;
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.2);
    border-radius: 10px;
    color: #f87171;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
}
.adm-logout-btn:hover {
    background: rgba(239,68,68,0.15);
    color: #fca5a5;
    transform: translateX(2px);
}
[data-theme="light"] .adm-logout-btn {
    color: #dc2626;
    background: #fef2f2;
    border-color: #fecaca;
}

/* ── Mobile Toggle ── */
.adm-toggle-btn {
    display: none;
    position: fixed;
    top: 14px;
    left: 14px;
    z-index: 300;
    background: #0066ff;
    border: none;
    border-radius: 10px;
    width: 40px; height: 40px;
    align-items: center; justify-content: center;
    cursor: pointer;
    color: #fff;
    font-size: 18px;
    box-shadow: 0 4px 12px rgba(0,102,255,0.4);
}

.adm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 190;
}

@media (max-width: 900px) {
    .adm-toggle-btn { display: flex; }
    .adm-sidebar { transform: translateX(-100%); }
    .adm-sidebar.mobile-open { transform: translateX(0); }
    .adm-overlay.active { display: block; }
}
</style>

<button class="adm-toggle-btn" id="admSidebarToggle">&#9776;</button>
<div class="adm-overlay" id="admOverlay"></div>

<div class="adm-sidebar" id="admSidebar">

    <!-- Brand -->
    <div class="adm-brand">
        <div class="adm-brand-logo"><img src="../assets/images/logo.jpg" alt="Raj Kathiyawadi Mukhwash Logo"></div>
        <div class="adm-brand-text">
            <strong>Raj Kathiyawadi</strong>
            <small>Admin Panel</small>
        </div>
    </div>

    <!-- Admin User Pill -->
    <?php
    $admin_uname = $_SESSION['admin'] ?? '';
    $photo_html = '<div class="adm-avatar">👤</div>';
    if (!empty($admin_uname) && isset($conn)) {
        $q = mysqli_query($conn, "SELECT profile_photo FROM admin WHERE username='$admin_uname'");
        if ($q && mysqli_num_rows($q) > 0) {
            $row = mysqli_fetch_assoc($q);
            if (!empty($row['profile_photo']) && file_exists('../assets/images/admin/' . $row['profile_photo'])) {
                $photo_html = '<img src="../assets/images/admin/' . htmlspecialchars($row['profile_photo']) . '" alt="Admin Photo" style="width:32px; height:32px; border-radius:50%; object-fit:cover; flex-shrink:0;">';
            }
        }
    }
    ?>
    <a href="admin_profile.php" class="adm-user-pill" style="text-decoration:none;">
        <?= $photo_html ?>
        <div>
            <div class="adm-user-name" style="transition: color 0.2s;"><?php echo htmlspecialchars($admin_uname ?: 'Admin'); ?> <i class="bi bi-pencil-square ms-1" style="font-size:0.7rem;"></i></div>
            <div class="adm-user-role">Administrator</div>
        </div>
    </a>

    <!-- Nav -->
    <?php $is_main_admin = ($_SESSION['admin_role'] ?? 'subadmin') === 'admin'; ?>
    <nav class="adm-nav">

        <!-- MAIN -->
        <div class="adm-section-label">Main</div>

        <?php if ($is_main_admin) { ?>
        <a href="manage_admins.php" class="adm-nav-item <?= $current_page === 'manage_admins.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">🛡️</span> Admin Management
        </a>
        <?php } ?>

        <a href="dashboard.php" class="adm-nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">🏠</span> Dashboard
        </a>

        <!-- CATALOGUE -->
        <div class="adm-section-label">Catalogue</div>

        <a href="products.php" class="adm-nav-item <?= $current_page === 'products.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">📦</span> Products
        </a>

        <a href="add_product.php" class="adm-nav-item <?= $current_page === 'add_product.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">➕</span> Add Product
        </a>

        <a href="categories.php" class="adm-nav-item <?= $current_page === 'categories.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">📂</span> Categories
        </a>

        <!-- Reviews / Rating Star -->
        <a href="reviews.php" class="adm-nav-item <?= $current_page === 'reviews.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">⭐</span> Reviews
        </a>

        <!-- SALES -->
        <div class="adm-section-label">Sales</div>

        <a href="orders.php" class="adm-nav-item <?= $current_page === 'orders.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">🛒</span> Orders
            <?php
                // Badge for pending orders
                if (isset($conn)) {
                    $pq = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='Pending'");
                    $pc = mysqli_fetch_assoc($pq)['c'] ?? 0;
                    if ($pc > 0) echo "<span class='adm-nav-badge danger'>$pc</span>";
                }
            ?>
        </a>

        <a href="customers.php" class="adm-nav-item <?= $current_page === 'customers.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">👥</span> Users
        </a>

        <?php if ($is_main_admin) { ?>

        <!-- MARKETING -->
        <div class="adm-section-label">Marketing</div>

        <a href="coupons.php" class="adm-nav-item <?= $current_page === 'coupons.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">🎟️</span> Coupons
        </a>

        <a href="instagram_automation.php" class="adm-nav-item <?= $current_page === 'instagram_automation.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">📸</span> Instagram Auto-DM
        </a>

        <a href="whatsapp_automation.php" class="adm-nav-item <?= $current_page === 'whatsapp_automation.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">💬</span> WhatsApp Cloud API
        </a>

        <!-- ANALYTICS -->
        <div class="adm-section-label">Analytics</div>

        <a href="reports.php" class="adm-nav-item <?= $current_page === 'reports.php' ? 'active' : '' ?>">
            <span class="adm-nav-icon">📊</span> Reports &amp; Analytics
        </a>

        <!-- SETTINGS (collapsible submenu) -->
        <hr class="adm-divider">
        <div class="adm-section-label">Configuration</div>

        <!-- Settings parent (accordion trigger) -->
        <div class="adm-nav-item adm-has-submenu <?= in_array($current_page, ['settings_general.php','settings_store.php','settings_shipping.php','settings_payments.php','settings_seo.php']) ? 'open active' : '' ?>"
             onclick="toggleSubmenu(this)">
            <span class="adm-nav-icon">⚙️</span>
            Settings
            <span class="adm-caret">▶</span>
        </div>
        <div class="adm-submenu <?= in_array($current_page, ['settings_general.php','settings_store.php','settings_shipping.php','settings_payments.php','settings_seo.php']) ? 'open' : '' ?>">
            <a href="settings_general.php?tab=general"  class="adm-submenu-item <?= ($current_page==='settings_general.php' && ($_GET['tab']??'general')==='general')  ? 'active' : '' ?>">General</a>
            <a href="settings_general.php?tab=store"    class="adm-submenu-item <?= ($current_page==='settings_general.php' && ($_GET['tab']??'')==='store')    ? 'active' : '' ?>">Store Info</a>
            <a href="settings_general.php?tab=shipping" class="adm-submenu-item <?= ($current_page==='settings_general.php' && ($_GET['tab']??'')==='shipping') ? 'active' : '' ?>">Shipping</a>
            <a href="settings_general.php?tab=payments" class="adm-submenu-item <?= ($current_page==='settings_general.php' && ($_GET['tab']??'')==='payments') ? 'active' : '' ?>">Payments</a>
            <a href="settings_general.php?tab=seo"      class="adm-submenu-item <?= ($current_page==='settings_general.php' && ($_GET['tab']??'')==='seo')      ? 'active' : '' ?>">SEO &amp; Meta</a>
        </div>

        <!-- Tools parent -->
        <div class="adm-nav-item adm-has-submenu <?= in_array($current_page, ['tools_export.php','tools_import.php','tools_logs.php']) ? 'open active' : '' ?>"
             onclick="toggleSubmenu(this)">
            <span class="adm-nav-icon">🔧</span>
            Tools
            <span class="adm-caret">▶</span>
        </div>
        <div class="adm-submenu <?= in_array($current_page, ['tools_export.php','tools_import.php','tools_logs.php']) ? 'open' : '' ?>">
            <a href="tools_export.php" class="adm-submenu-item <?= $current_page==='tools_export.php' ? 'active' : '' ?>">Export Data</a>
            <a href="tools_import.php" class="adm-submenu-item <?= $current_page==='tools_import.php' ? 'active' : '' ?>">Import Data</a>
            <a href="tools_logs.php"   class="adm-submenu-item <?= $current_page==='tools_logs.php'   ? 'active' : '' ?>">Activity Logs</a>
        </div>

        <hr class="adm-divider">

        <!-- CONTENT -->
        <div class="adm-section-label">Content</div>

        <!-- Posts parent -->
        <?php $posts_pages = ['posts.php','post_add.php','post_edit.php','post_categories.php']; ?>
        <div class="adm-nav-item adm-has-submenu <?= in_array($current_page, $posts_pages) ? 'open active' : '' ?>"
             onclick="toggleSubmenu(this)">
            <span class="adm-nav-icon">📝</span>
            Posts
            <span class="adm-caret">▶</span>
        </div>
        <div class="adm-submenu <?= in_array($current_page, $posts_pages) ? 'open' : '' ?>">
            <a href="posts.php"           class="adm-submenu-item <?= $current_page==='posts.php'           ? 'active' : '' ?>">All Posts</a>
            <a href="post_add.php"        class="adm-submenu-item <?= $current_page==='post_add.php'        ? 'active' : '' ?>">Add New Post</a>
            <a href="post_categories.php" class="adm-submenu-item <?= $current_page==='post_categories.php' ? 'active' : '' ?>">Post Categories</a>
        </div>

        <?php } ?>

        <hr class="adm-divider">


        <!-- Store front link -->
        <a href="../index.php" target="_blank" class="adm-nav-item">
            <span class="adm-nav-icon">🌐</span> View Store
        </a>

    </nav>

    <!-- Footer Logout -->
    <div class="adm-sidebar-footer">
        <a href="logout.php" class="adm-logout-btn">
            <span>🚪</span> Logout
        </a>
    </div>
</div>

<script>
/* Submenu accordion */
function toggleSubmenu(el) {
    const sub = el.nextElementSibling;
    const isOpen = sub.classList.contains('open');

    // Close all open submenus
    document.querySelectorAll('.adm-submenu.open').forEach(s => {
        s.classList.remove('open');
        s.previousElementSibling.classList.remove('open');
    });

    // Toggle clicked one
    if (!isOpen) {
        sub.classList.add('open');
        el.classList.add('open');
    }
}

/* Mobile toggle */
const sidebarEl   = document.getElementById('admSidebar');
const overlayEl   = document.getElementById('admOverlay');
const toggleBtn   = document.getElementById('admSidebarToggle');

toggleBtn.addEventListener('click', () => {
    sidebarEl.classList.toggle('mobile-open');
    overlayEl.classList.toggle('active');
});
overlayEl.addEventListener('click', () => {
    sidebarEl.classList.remove('mobile-open');
    overlayEl.classList.remove('active');
});
</script>
