<?php
include("../config/db.php");
require_once('auth_guard.php');
include_once("../includes/whatsapp_cloud.php");

$success = '';
$error   = '';

// ── Handle POST actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        save_site_setting($conn, 'wa_enabled',          isset($_POST['wa_enabled'])       ? 'on' : 'off');
        save_site_setting($conn, 'wa_phone_number_id',  trim($_POST['wa_phone_number_id']  ?? ''));
        save_site_setting($conn, 'wa_access_token',     trim($_POST['wa_access_token']     ?? ''));
        save_site_setting($conn, 'wa_api_version',      trim($_POST['wa_api_version']      ?? 'v19.0'));
        save_site_setting($conn, 'wa_order_template',   trim($_POST['wa_order_template']   ?? ''));
        save_site_setting($conn, 'wa_template_lang',    trim($_POST['wa_template_lang']    ?? 'en'));
        save_site_setting($conn, 'wa_notify_on_order',  isset($_POST['wa_notify_on_order']) ? 'on' : 'off');
        save_site_setting($conn, 'wa_notify_on_status', isset($_POST['wa_notify_on_status'])? 'on' : 'off');
        $success = 'WhatsApp Cloud API settings saved successfully!';

    } elseif ($action === 'send_test') {
        $test_phone = preg_replace('/[^0-9]/', '', trim($_POST['test_phone'] ?? ''));
        $test_msg   = trim($_POST['test_message'] ?? '');
        if (empty($test_phone) || empty($test_msg)) {
            $error = 'Phone number and message are required for a test send.';
        } else {
            $result = sendWhatsAppText($test_phone, $test_msg);
            if ($result['success']) {
                $success = '🚀 Test message sent! Message ID: ' . $result['message_id'];
            } else {
                $error = 'Send failed: ' . $result['error'];
            }
        }

    } elseif ($action === 'clear_logs') {
        $_SESSION['wa_logs'] = [];
        $success = 'Activity logs cleared.';
    }
}

// Helper: pull setting
function wa_s($key, $default = '') {
    return htmlspecialchars($_SESSION['settings'][$key] ?? $default);
}

$is_enabled = wa_s('wa_enabled', 'off') === 'on';
$wa_logs    = $_SESSION['wa_logs'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WhatsApp Cloud API - Admin | Raj Kathiyawadi Mukhwash</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --bg-dark:   #070b19;
    --card-bg:   rgba(255,255,255,0.03);
    --card-brd:  rgba(255,255,255,0.07);
    --text:      #f1f5f9;
    --muted:     #64748b;
    --wa-green:  #25D366;
    --wa-dark:   #128C7E;
    --wa-glow:   rgba(37,211,102,0.25);
    --accent:    #0066ff;
    --danger:    #ef4444;
    --success:   #22c55e;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg-dark); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; font-size: 14px; line-height: 1.6; }

/* Ambient blobs */
.dash-bg { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
.blob {
    position: absolute; border-radius: 50%; filter: blur(140px); opacity: .12;
    animation: blobFloat 18s infinite alternate ease-in-out;
}
.blob-1 { width: 380px; height: 380px; top: 0; right: -5%; background: linear-gradient(135deg,#25D366,#128C7E); }
.blob-2 { width: 340px; height: 340px; bottom: -8%; left: -4%; background: linear-gradient(135deg,#0066ff,#7c3aed); animation-delay: -9s; }
@keyframes blobFloat { 0%{transform:translate(0,0) scale(1);} 100%{transform:translate(-28px,36px) scale(1.08);} }

.main-area { margin-left: 255px; padding: 36px 40px; position: relative; z-index: 10; min-height: 100vh; }
@media(max-width:900px){ .main-area{ margin-left:0; padding: 20px 16px; } }

/* ── Page header ── */
.page-hdr { display: flex; align-items: center; gap: 14px; margin-bottom: 30px; flex-wrap: wrap; }
.wa-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(37,211,102,0.12); border: 1px solid rgba(37,211,102,0.3);
    color: var(--wa-green); border-radius: 999px; padding: 6px 18px;
    font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
}
.wa-status-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--wa-green); box-shadow: 0 0 8px var(--wa-glow);
    animation: pulse-dot 2s infinite;
}
.wa-status-dot.off { background: var(--danger); box-shadow: none; animation: none; }
@keyframes pulse-dot { 0%,100%{opacity:1;} 50%{opacity:.4;} }

/* ── Alerts ── */
.alert-custom {
    border-radius: 12px; padding: 14px 18px;
    margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    font-size: 0.9rem; font-weight: 500;
}
.alert-success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.25); color: #4ade80; }
.alert-error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.25);  color: #f87171; }

/* ── Cards ── */
.wa-card {
    background: var(--card-bg); border: 1px solid var(--card-brd);
    border-radius: 16px; padding: 28px; margin-bottom: 24px;
    backdrop-filter: blur(8px); transition: border-color .2s;
}
.wa-card:hover { border-color: rgba(37,211,102,.2); }
.wa-card-title {
    font-size: 0.92rem; font-weight: 700; color: var(--wa-green);
    display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
    padding-bottom: 14px; border-bottom: 1px solid var(--card-brd);
}
.wa-card-title .bi { font-size: 1.1rem; }

/* ── Form elements ── */
.form-label { font-size: 0.82rem; color: var(--muted); font-weight: 600; margin-bottom: 6px; display: block; }
.form-control, .form-select {
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
    color: var(--text); border-radius: 10px; padding: 10px 14px; font-size: 0.88rem;
    width: 100%; transition: border-color .2s, box-shadow .2s;
}
.form-control:focus, .form-select:focus {
    outline: none; border-color: var(--wa-green); box-shadow: 0 0 0 3px var(--wa-glow);
    background: rgba(37,211,102,.04);
}
.form-control::placeholder { color: rgba(148,163,184,.4); }
.form-control option, .form-select option { background: #1e293b; }
textarea.form-control { resize: vertical; min-height: 100px; }

/* ── Toggle switch ── */
.toggle-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 0; border-bottom: 1px solid var(--card-brd);
}
.toggle-row:last-child { border-bottom: none; }
.toggle-label { font-size: 0.88rem; font-weight: 600; color: var(--text); }
.toggle-desc  { font-size: 0.78rem; color: var(--muted); margin-top: 2px; }
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute; inset: 0; cursor: pointer;
    background: rgba(255,255,255,.1); border-radius: 24px;
    transition: .3s;
}
.toggle-slider::before {
    content: ''; position: absolute;
    height: 18px; width: 18px; left: 3px; bottom: 3px;
    background: #fff; border-radius: 50%; transition: .3s;
}
.toggle-switch input:checked + .toggle-slider { background: var(--wa-green); box-shadow: 0 0 10px var(--wa-glow); }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }

/* ── Buttons ── */
.btn-wa {
    background: linear-gradient(135deg, #25D366, #128C7E);
    color: #fff; border: none; border-radius: 10px;
    padding: 11px 24px; font-size: 0.88rem; font-weight: 700;
    cursor: pointer; transition: all .2s; display: inline-flex; align-items: center; gap: 8px;
}
.btn-wa:hover { transform: translateY(-1px); box-shadow: 0 8px 24px var(--wa-glow); }
.btn-secondary-wa {
    background: rgba(255,255,255,.05); color: var(--text);
    border: 1px solid rgba(255,255,255,.1); border-radius: 10px;
    padding: 9px 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-secondary-wa:hover { background: rgba(255,255,255,.09); border-color: rgba(255,255,255,.2); }

/* ── Info callout ── */
.callout {
    border-radius: 12px; padding: 14px 18px; margin-bottom: 20px;
    font-size: 0.82rem; line-height: 1.7; color: var(--muted);
}
.callout-wa  { background: rgba(37,211,102,.07); border-left: 3px solid var(--wa-green); }
.callout-tip { background: rgba(0,102,255,.07);  border-left: 3px solid var(--accent); }
.callout strong { color: var(--text); }

/* ── Credential field ── */
.cred-wrap { position: relative; }
.cred-wrap .form-control { padding-right: 40px; font-family: monospace; font-size: 0.82rem; }
.cred-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1rem;
    padding: 2px;
}
.cred-toggle:hover { color: var(--text); }

/* ── Setup steps ── */
.setup-steps { counter-reset: step; }
.setup-step {
    display: flex; gap: 14px; padding: 14px 0;
    border-bottom: 1px solid var(--card-brd);
}
.setup-step:last-child { border-bottom: none; }
.step-num {
    width: 28px; height: 28px; flex-shrink: 0; border-radius: 50%;
    background: rgba(37,211,102,.15); border: 1px solid rgba(37,211,102,.3);
    color: var(--wa-green); font-size: 0.8rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center; margin-top: 2px;
}
.step-body { font-size: 0.85rem; color: var(--muted); line-height: 1.7; }
.step-body strong { color: var(--text); }
.step-body a { color: var(--wa-green); text-decoration: none; }
.step-body a:hover { text-decoration: underline; }

/* ── Logs table ── */
.log-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.log-table th { background: rgba(255,255,255,.04); color: var(--muted); font-weight: 600; padding: 10px 12px; text-align: left; }
.log-table td { padding: 10px 12px; border-top: 1px solid var(--card-brd); color: var(--muted); }
.log-table tr:hover td { background: rgba(255,255,255,.02); }
.log-table .phone { font-family: monospace; color: var(--text); }
.log-table .msg-preview { max-width: 280px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; color: rgba(241,245,249,.6); }
.badge-sent   { background: rgba(34,197,94,.15); color: #4ade80; border: 1px solid rgba(34,197,94,.25); padding: 2px 10px; border-radius: 999px; font-size: .75rem; font-weight: 600; }
.badge-failed { background: rgba(239,68,68,.15); color: #f87171; border: 1px solid rgba(239,68,68,.25); padding: 2px 10px; border-radius: 999px; font-size: .75rem; font-weight: 600; }

/* ── Tabs ── */
.wa-tabs { display: flex; gap: 4px; background: rgba(255,255,255,.03); border: 1px solid var(--card-brd); border-radius: 12px; padding: 4px; margin-bottom: 24px; }
.wa-tab { flex: 1; padding: 9px 16px; text-align: center; border-radius: 9px; font-size: 0.83rem; font-weight: 600; color: var(--muted); cursor: pointer; transition: all .2s; border: none; background: none; }
.wa-tab.active { background: rgba(37,211,102,.12); color: var(--wa-green); border: 1px solid rgba(37,211,102,.2); }
.wa-tab:not(.active):hover { color: var(--text); }
.wa-panel { display: none; }
.wa-panel.active { display: block; }

/* ── Grid helpers ── */
.g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
@media(max-width:640px){ .g2 { grid-template-columns: 1fr; } }
.mb18 { margin-bottom: 18px; }
</style>
</head>
<body>

<!-- Background blobs -->
<div class="dash-bg">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
</div>

<?php include('includes/sidebar.php'); ?>

<div class="main-area">

    <!-- Page Header -->
    <div class="page-hdr">
        <div>
            <h1 style="font-size:1.65rem;font-weight:800;display:flex;align-items:center;gap:10px;">
                <span style="font-size:2rem;">💬</span> WhatsApp Cloud API
            </h1>
            <p style="color:var(--muted);margin-top:4px;font-size:0.88rem;">Meta Business Cloud API — automated order notifications via WhatsApp</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="wa-badge">
                <span class="wa-status-dot <?= $is_enabled ? '' : 'off' ?>"></span>
                <?= $is_enabled ? 'Active' : 'Inactive' ?>
            </span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert-custom alert-success"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert-custom alert-error"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="wa-tabs">
        <button class="wa-tab active" onclick="showTab('setup',this)">⚙️ Setup &amp; Config</button>
        <button class="wa-tab" onclick="showTab('notifications',this)">🔔 Notifications</button>
        <button class="wa-tab" onclick="showTab('test',this)">🧪 Test &amp; Debug</button>
        <button class="wa-tab" onclick="showTab('logs',this)">📋 Activity Logs</button>
        <button class="wa-tab" onclick="showTab('guide',this)">📖 Setup Guide</button>
    </div>

    <!-- ════════════ TAB: SETUP ════════════ -->
    <div class="wa-panel active" id="tab-setup">
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">

            <div class="wa-card">
                <div class="wa-card-title"><i class="bi bi-gear-fill"></i> WhatsApp Cloud API Credentials</div>

                <div class="callout callout-wa mb18">
                    <strong>Meta Business requirement:</strong> You need a verified Meta Business account with an approved WhatsApp Business App.
                    All credentials are obtained from <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener">Meta Developer Console</a>.
                </div>

                <div class="toggle-row mb18">
                    <div>
                        <div class="toggle-label">Enable WhatsApp Cloud API</div>
                        <div class="toggle-desc">Send automated order &amp; status messages to customers</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="wa_enabled" <?= wa_s('wa_enabled','off')==='on' ? 'checked':'' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="g2 mb18">
                    <div>
                        <label class="form-label">Phone Number ID <span style="color:#ef4444">*</span></label>
                        <input type="text" name="wa_phone_number_id" class="form-control"
                               value="<?= wa_s('wa_phone_number_id') ?>"
                               placeholder="123456789012345"
                               autocomplete="off">
                        <small style="color:var(--muted);font-size:.75rem;margin-top:4px;display:block;">
                            Found in your App Dashboard &rarr; WhatsApp &rarr; API Setup &rarr; <em>Phone number ID</em>
                        </small>
                    </div>
                    <div>
                        <label class="form-label">Graph API Version</label>
                        <select name="wa_api_version" class="form-select">
                            <?php foreach(['v19.0','v20.0','v21.0','v22.0'] as $v): ?>
                            <option value="<?= $v ?>" <?= wa_s('wa_api_version','v19.0') === $v ? 'selected':'' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb18">
                    <label class="form-label">Permanent Access Token <span style="color:#ef4444">*</span></label>
                    <div class="cred-wrap">
                        <input type="password" name="wa_access_token" id="waToken" class="form-control"
                               value="<?= wa_s('wa_access_token') ?>"
                               placeholder="EAAxxxxxxxxx..."
                               autocomplete="new-password">
                        <button type="button" class="cred-toggle" onclick="toggleSecret('waToken',this)" title="Show/Hide">👁</button>
                    </div>
                    <small style="color:var(--muted);font-size:.75rem;margin-top:4px;display:block;">
                        Generate a <strong>System User</strong> token in Meta Business Manager for non-expiring access.
                        Temporary tokens expire in 24 h.
                    </small>
                </div>

                <button type="submit" class="btn-wa"><i class="bi bi-check-circle"></i> Save Credentials</button>
            </div>
        </form>
    </div>

    <!-- ════════════ TAB: NOTIFICATIONS ════════════ -->
    <div class="wa-panel" id="tab-notifications">
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">

            <div class="wa-card">
                <div class="wa-card-title"><i class="bi bi-bell-fill"></i> Notification Triggers</div>

                <div class="toggle-row">
                    <div>
                        <div class="toggle-label">Order Confirmation</div>
                        <div class="toggle-desc">Send a WhatsApp message to customers immediately after they place an order</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="wa_notify_on_order" <?= wa_s('wa_notify_on_order','on')==='on'?'checked':'' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-row">
                    <div>
                        <div class="toggle-label">Order Status Updates</div>
                        <div class="toggle-desc">Notify customers when admin updates order status (Processing / Shipped / Delivered)</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="wa_notify_on_status" <?= wa_s('wa_notify_on_status','on')==='on'?'checked':'' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="wa-card">
                <div class="wa-card-title"><i class="bi bi-file-earmark-text"></i> Template Message (Optional)</div>

                <div class="callout callout-tip mb18">
                    <strong>📋 Templates</strong> must be pre-approved in Meta Business Manager before use.
                    Leave blank to use free-form text messages (only works within the 24-hour customer service window).
                </div>

                <div class="g2 mb18">
                    <div>
                        <label class="form-label">Order Confirmation Template Name</label>
                        <input type="text" name="wa_order_template" class="form-control"
                               value="<?= wa_s('wa_order_template') ?>"
                               placeholder="order_confirmation">
                        <small style="color:var(--muted);font-size:.75rem;margin-top:4px;display:block;">
                            Expected template variables: {{1}} order_id, {{2}} customer_name, {{3}} total, {{4}} payment_method
                        </small>
                    </div>
                    <div>
                        <label class="form-label">Template Language Code</label>
                        <select name="wa_template_lang" class="form-select">
                            <?php foreach(['en'=>'English (en)','en_US'=>'English US (en_US)','hi'=>'Hindi (hi)','gu'=>'Gujarati (gu)'] as $code=>$label): ?>
                            <option value="<?= $code ?>" <?= wa_s('wa_template_lang','en')===$code?'selected':'' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-wa"><i class="bi bi-check-circle"></i> Save Notification Settings</button>
            </div>
        </form>
    </div>

    <!-- ════════════ TAB: TEST ════════════ -->
    <div class="wa-panel" id="tab-test">
        <div class="wa-card">
            <div class="wa-card-title"><i class="bi bi-send-fill"></i> Send a Test Message</div>

            <div class="callout callout-wa mb18">
                The recipient must have opted in to receive WhatsApp messages from your business number,
                or the send must happen within a 24-hour conversation window. Use your own verified test number.
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="send_test">
                <div class="mb18">
                    <label class="form-label">Recipient Phone (E.164 format, no '+') <span style="color:#ef4444">*</span></label>
                    <input type="text" name="test_phone" class="form-control"
                           placeholder="919876543210 (country code + number, no +)"
                           value="<?= htmlspecialchars($_POST['test_phone'] ?? '') ?>">
                </div>
                <div class="mb18">
                    <label class="form-label">Message <span style="color:#ef4444">*</span></label>
                    <textarea name="test_message" class="form-control" rows="5"
                              placeholder="Hello! This is a test message from Raj Kathiyawadi Mukhwash WhatsApp Cloud API. 🌿"><?= htmlspecialchars($_POST['test_message'] ?? "Hello! This is a test message from Raj Kathiyawadi Mukhwash WhatsApp Cloud API. 🌿\n\nIf you received this, the integration is working correctly!") ?></textarea>
                </div>
                <button type="submit" class="btn-wa"><i class="bi bi-whatsapp"></i> Send Test Message</button>
            </form>
        </div>

        <div class="wa-card">
            <div class="wa-card-title"><i class="bi bi-link-45deg"></i> Webhook Endpoint</div>
            <div class="callout callout-tip">
                <strong>Webhook URL</strong> (for incoming messages / delivery receipts):<br>
                <code style="color:var(--wa-green);font-family:monospace;">
                    <?= htmlspecialchars(
                        ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https':'http')
                        . '://' . ($_SERVER['HTTP_HOST']??'yourdomain.com')
                        . '/includes/whatsapp_webhook.php'
                    ) ?>
                </code><br><br>
                Set this URL in your Meta App Dashboard &rarr; WhatsApp &rarr; Configuration &rarr; Webhook.<br>
                Verify Token: <code style="color:var(--wa-green);font-family:monospace;"><?= wa_s('wa_verify_token','mukhwas_wa_token_2026') ?></code>
            </div>
            <div class="mb18">
                <label class="form-label">Webhook Verify Token</label>
                <div class="cred-wrap">
                    <input type="text" id="verifyToken" class="form-control"
                           value="<?= wa_s('wa_verify_token','mukhwas_wa_token_2026') ?>"
                           readonly>
                    <button type="button" class="cred-toggle" onclick="copyToken()" title="Copy">📋</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════ TAB: LOGS ════════════ -->
    <div class="wa-panel" id="tab-logs">
        <div class="wa-card" style="padding: 0; overflow: hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--card-brd);">
                <div class="wa-card-title" style="margin-bottom:0;border:none;padding:0;">
                    <i class="bi bi-journal-text"></i> Message Activity Log
                    <span style="background:rgba(37,211,102,.15);color:var(--wa-green);border-radius:999px;padding:2px 10px;font-size:.75rem;margin-left:6px;">
                        <?= count($wa_logs) ?> entries
                    </span>
                </div>
                <?php if (!empty($wa_logs)): ?>
                <form method="POST" onsubmit="return confirm('Clear all logs?')">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn-secondary-wa" style="font-size:.78rem;padding:6px 14px;">
                        <i class="bi bi-trash3"></i> Clear
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php if (empty($wa_logs)): ?>
            <div style="text-align:center;padding:48px;color:var(--muted);">
                <div style="font-size:3rem;margin-bottom:12px;">💬</div>
                No messages sent yet. Use the Test tab to send your first message.
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Recipient</th>
                            <th>Message Preview</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wa_logs as $log): ?>
                        <tr>
                            <td style="white-space:nowrap;"><?= htmlspecialchars($log['timestamp']) ?></td>
                            <td class="phone">+<?= htmlspecialchars($log['to']) ?></td>
                            <td class="msg-preview" title="<?= htmlspecialchars($log['preview']) ?>"><?= htmlspecialchars($log['preview']) ?></td>
                            <td>
                                <span class="<?= $log['status']==='Sent' ? 'badge-sent' : 'badge-failed' ?>">
                                    <?= $log['status']==='Sent' ? '✓ Sent' : '✗ Failed' ?>
                                </span>
                            </td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="<?= htmlspecialchars($log['details']) ?>">
                                <?= htmlspecialchars($log['details']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ════════════ TAB: GUIDE ════════════ -->
    <div class="wa-panel" id="tab-guide">
        <div class="wa-card">
            <div class="wa-card-title"><i class="bi bi-book-fill"></i> WhatsApp Cloud API Setup Guide</div>

            <div class="callout callout-wa mb18">
                <strong>Important:</strong> WhatsApp Business Cloud API requires a verified Meta Business account.
                You cannot use a regular WhatsApp or WhatsApp Business number directly — you need API access.
            </div>

            <div class="setup-steps">
                <div class="setup-step">
                    <div class="step-num">1</div>
                    <div class="step-body">
                        <strong>Create a Meta Developer App</strong><br>
                        Go to <a href="https://developers.facebook.com/apps/" target="_blank">developers.facebook.com/apps</a>
                        and create a new app. Choose <strong>Business</strong> as the app type.
                    </div>
                </div>
                <div class="setup-step">
                    <div class="step-num">2</div>
                    <div class="step-body">
                        <strong>Add WhatsApp Product</strong><br>
                        In your app dashboard, click <strong>Add Product</strong> and add <strong>WhatsApp</strong>.
                        This gives you access to the WhatsApp Cloud API.
                    </div>
                </div>
                <div class="setup-step">
                    <div class="step-num">3</div>
                    <div class="step-body">
                        <strong>Get your Phone Number ID</strong><br>
                        Navigate to <strong>WhatsApp &rarr; API Setup</strong>. You'll see your test phone number
                        with its <em>Phone Number ID</em>. Copy it and paste it in the Config tab above.
                    </div>
                </div>
                <div class="setup-step">
                    <div class="step-num">4</div>
                    <div class="step-body">
                        <strong>Generate a Permanent Access Token</strong><br>
                        Go to <a href="https://business.facebook.com/settings/system-users" target="_blank">Meta Business Manager &rarr; System Users</a>.
                        Create a <strong>System User</strong>, assign it the App as an asset, then generate a <strong>Never-Expiring Token</strong>
                        with <code>whatsapp_business_messaging</code> permission.
                    </div>
                </div>
                <div class="setup-step">
                    <div class="step-num">5</div>
                    <div class="step-body">
                        <strong>Add a Real Business Phone Number</strong><br>
                        In WhatsApp &rarr; Phone Numbers, add your business number. Meta will send a verification OTP.
                        After verification, your number will have a new <em>Phone Number ID</em> — update the setting above.
                    </div>
                </div>
                <div class="setup-step">
                    <div class="step-num">6</div>
                    <div class="step-body">
                        <strong>(Optional) Create Approved Templates</strong><br>
                        Go to <a href="https://business.facebook.com/wa/manage/message-templates/" target="_blank">WhatsApp Manager &rarr; Message Templates</a>.
                        Create an order confirmation template with body variables <code>{{1}}</code> (order_id),
                        <code>{{2}}</code> (name), <code>{{3}}</code> (total), <code>{{4}}</code> (payment).
                        After approval (&lt;24h), enter the template name in the Notifications tab.
                    </div>
                </div>
                <div class="setup-step">
                    <div class="step-num">7</div>
                    <div class="step-body">
                        <strong>Configure Webhook</strong><br>
                        In App Dashboard &rarr; WhatsApp &rarr; Configuration &rarr; Webhook, enter the Webhook URL
                        shown in the Test tab. Use the Verify Token shown there. Subscribe to
                        <code>messages</code> and <code>message_status_updates</code>.
                    </div>
                </div>
                <div class="setup-step">
                    <div class="step-num">8</div>
                    <div class="step-body">
                        <strong>Test &amp; Go Live</strong><br>
                        Use the <strong>Test tab</strong> above to send a test message to your number.
                        Once confirmed working, enable <strong>Order Confirmation</strong> in the Notifications tab
                        and your customers will receive automatic WhatsApp confirmations when they order.
                    </div>
                </div>
            </div>

            <div class="callout callout-tip" style="margin-top:20px;">
                <strong>💡 Pricing:</strong> WhatsApp Cloud API is free for up to 1,000 business-initiated conversations per month.
                After that, pricing is per conversation — typically <strong>₹0.50–₹2.00</strong> per conversation depending on category.
                See <a href="https://developers.facebook.com/docs/whatsapp/pricing/" target="_blank" style="color:var(--accent);">Meta's pricing page</a>.
            </div>
        </div>
    </div>

</div><!-- /main-area -->

<script>
function showTab(id, btn) {
    document.querySelectorAll('.wa-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.wa-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}

function toggleSecret(inputId, btn) {
    const el = document.getElementById(inputId);
    if (el.type === 'password') { el.type = 'text'; btn.textContent = '🙈'; }
    else                        { el.type = 'password'; btn.textContent = '👁'; }
}

function copyToken() {
    const el = document.getElementById('verifyToken');
    navigator.clipboard.writeText(el.value).then(() => {
        const btn = el.nextElementSibling;
        btn.textContent = '✅';
        setTimeout(() => btn.textContent = '📋', 1500);
    });
}
</script>

</body>
</html>
