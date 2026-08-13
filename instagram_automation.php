<?php
include("../config/db.php");
require_once('auth_guard.php');
include_once("../includes/instagram_auto_dm.php");

$success = '';
$error   = '';

// ── Handle Form Submissions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $_SESSION['settings']['ig_auto_dm_enabled']    = $_POST['ig_auto_dm_enabled'] ?? 'off';
        $_SESSION['settings']['ig_page_access_token']  = trim($_POST['ig_page_access_token'] ?? '');
        $_SESSION['settings']['ig_account_id']         = trim($_POST['ig_account_id'] ?? '');
        $_SESSION['settings']['ig_verify_token']       = trim($_POST['ig_verify_token'] ?? 'mukhwas_ig_secret_token');
        $_SESSION['settings']['ig_default_auto_reply'] = trim($_POST['ig_default_auto_reply'] ?? '');

        $success = "Instagram Auto-DM settings updated successfully!";
    } elseif ($action === 'send_test_dm') {
        $test_recipient = trim($_POST['test_recipient'] ?? '');
        $test_message   = trim($_POST['test_message'] ?? '');

        if (empty($test_recipient) || empty($test_message)) {
            $error = "Recipient ID and Message content are required to send a test DM.";
        } else {
            $res = sendInstagramDM($test_recipient, $test_message);
            if ($res['success']) {
                $success = "🚀 Test Instagram DM sent successfully! (Message ID: " . $res['message_id'] . ")";
            } else {
                $error = "Failed to send Instagram DM: " . $res['error'];
            }
        }
    } elseif ($action === 'clear_logs') {
        $_SESSION['ig_dm_logs'] = [];
        $success = "Auto-DM activity logs cleared.";
    }
}

// Pull Settings
function ig_setting($key, $default = '') {
    return htmlspecialchars($_SESSION['settings'][$key] ?? $default);
}

$is_enabled = ig_setting('ig_auto_dm_enabled', 'off') === 'on';
$dm_logs    = $_SESSION['ig_dm_logs'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instagram Auto-DM Automation - Raj Kathiyawadi Mukhwash Admin</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --dash-bg: #0b0f19;
    --dash-card: #151c2c;
    --dash-border: rgba(255,255,255,0.08);
    --dash-text: #f8fafc;
    --dash-muted: #94a3b8;
    --ig-gradient: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

body {
    background-color: var(--dash-bg);
    color: var(--dash-text);
    font-family: system-ui, -apple-system, sans-serif;
    min-height: 100vh;
}

.main-content {
    margin-left: 255px;
    padding: 30px;
    transition: margin-left 0.35s ease;
}

@media (max-width: 991px) {
    .main-content { margin-left: 0; padding: 20px; }
}

.s-card {
    background: var(--dash-card);
    border: 1px solid var(--dash-border);
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.s-card-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    border-bottom: 1px solid var(--dash-border);
    padding-bottom: 12px;
}

.form-label {
    font-weight: 600;
    font-size: 0.88rem;
    color: var(--dash-muted);
    margin-bottom: 6px;
}

.form-control, .form-select {
    background: #0d1322;
    border: 1px solid #232d42;
    color: #fff;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.9rem;
}

.form-control:focus {
    background: #0d1322;
    color: #fff;
    border-color: #ec4899;
    box-shadow: 0 0 0 3px rgba(236,72,153,0.25);
}

.ig-badge-on {
    background: rgba(34, 197, 94, 0.15);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
}

.ig-badge-off {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
}

.btn-ig {
    background: var(--ig-gradient);
    color: #fff;
    border: none;
    border-radius: 30px;
    padding: 10px 24px;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(220,39,67,0.35);
    transition: transform 0.2s;
}

.btn-ig:hover {
    color: #fff;
    transform: translateY(-2px);
}

.webhook-url-box {
    background: #090d16;
    border: 1px dashed #334155;
    padding: 12px 16px;
    border-radius: 10px;
    font-family: monospace;
    font-size: 0.85rem;
    color: #38bdf8;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-custom {
    color: #e2e8f0;
}

.table-custom th {
    background: rgba(255,255,255,0.03);
    color: var(--dash-muted);
    font-size: 0.8rem;
    text-transform: uppercase;
    border-bottom: 1px solid var(--dash-border);
}

.table-custom td {
    border-bottom: 1px solid var(--dash-border);
    vertical-align: middle;
    font-size: 0.88rem;
}
</style>
</head>
<body>

<?php include("includes/sidebar.php"); ?>

<div class="main-content">
    
    <!-- Top Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold m-0 d-flex align-items-center gap-2">
                <span style="background:var(--ig-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">📸 Instagram Auto-DM System</span>
            </h3>
            <p class="text-muted small m-0 mt-1">Configure automated Direct Messages & Webhooks via Meta Graph API</p>
        </div>
        <div>
            <span class="<?= $is_enabled ? 'ig-badge-on' : 'ig-badge-off' ?>">
                <i class="bi bi-circle-fill me-1" style="font-size:0.6rem;"></i>
                System Status: <?= $is_enabled ? 'AUTOMATION ACTIVE' : 'AUTOMATION PAUSED' ?>
            </span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        
        <!-- Column 1: API Configuration -->
        <div class="col-lg-7">
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_settings">

                <div class="s-card">
                    <div class="s-card-title">
                        <i class="bi bi-sliders text-pink"></i> Auto-DM Control &amp; Meta Credentials
                    </div>

                    <div class="form-check form-switch mb-4 bg-dark p-3 rounded-3 border border-secondary border-opacity-25 ms-0">
                        <input class="form-check-input ms-0 me-3" type="checkbox" name="ig_auto_dm_enabled" value="on" id="autoDmToggle" <?= $is_enabled ? 'checked' : '' ?> style="width:2.5em;height:1.3em;">
                        <label class="form-check-label fw-bold" for="autoDmToggle">
                            Enable Automatic Instagram DM System
                        </label>
                        <small class="d-block text-muted mt-1">When enabled, automated order confirmations &amp; keyword auto-replies will trigger over Instagram Graph API.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Instagram / Facebook Page Access Token</label>
                        <input type="password" name="ig_page_access_token" class="form-control"
                               value="<?= ig_setting('ig_page_access_token') ?>" placeholder="EAAG... (Meta Graph API Permanent Token)">
                        <small class="text-muted" style="font-size:0.75rem;">Generate in Meta Business Suite / Meta Developer Portal (Page Messaging Permission).</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Instagram Business Account ID</label>
                            <input type="text" name="ig_account_id" class="form-control"
                                   value="<?= ig_setting('ig_account_id') ?>" placeholder="17841400000000000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Webhook Verify Token</label>
                            <input type="text" name="ig_verify_token" class="form-control"
                                   value="<?= ig_setting('ig_verify_token', 'mukhwas_ig_secret_token') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Default Welcome / Inquiry Auto-DM</label>
                        <textarea name="ig_default_auto_reply" class="form-control" rows="3" placeholder="Hello! 👋 Thank you for messaging Raj Kathiyawadi Mukhwash. How can we help you today?"><?= ig_setting('ig_default_auto_reply', "Hello! 👋 Thank you for messaging Raj Kathiyawadi Mukhwash. How can we help you today?") ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-ig w-100 mt-2">
                        <i class="bi bi-save me-2"></i>Save Automation Settings
                    </button>
                </div>
            </form>

            <!-- Webhook Connection Card -->
            <div class="s-card">
                <div class="s-card-title">
                    <i class="bi bi-diagram-3 text-info"></i> Meta Webhook Endpoint URL
                </div>
                <p class="text-muted small">Copy this Webhook Callback URL and paste it into Meta Developers Portal &gt; Instagram &gt; Webhooks:</p>
                <div class="webhook-url-box">
                    <span id="webhookUrlText"><?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/api/instagram_webhook.php" ?></span>
                    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="navigator.clipboard.writeText(document.getElementById('webhookUrlText').innerText); alert('Webhook URL Copied!');">Copy URL</button>
                </div>
            </div>

        </div>

        <!-- Column 2: Test Sandbox & Simulator -->
        <div class="col-lg-5">
            
            <div class="s-card">
                <div class="s-card-title">
                    <i class="bi bi-send-fill text-warning"></i> Live Auto-DM Test Sandbox
                </div>
                <p class="text-muted small">Send a test Instagram DM to verify Meta Graph API connectivity.</p>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="send_test_dm">

                    <div class="mb-3">
                        <label class="form-label">Recipient Instagram User ID / IGSID</label>
                        <input type="text" name="test_recipient" class="form-control" placeholder="e.g. 1029384756102" required>
                        <small class="text-muted" style="font-size:0.75rem;">Instagram Scoped User ID of recipient.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Test DM Content</label>
                        <textarea name="test_message" class="form-control" rows="3" required>Hello from Raj Kathiyawadi Mukhwash Auto-DM system! 🌿 Your test message was delivered successfully.</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill w-100 py-2 fw-bold">
                        <i class="bi bi-send-check me-2"></i>Send Test Instagram DM
                    </button>
                </form>
            </div>

            <!-- Keyword Auto-Reply Reference -->
            <div class="s-card">
                <div class="s-card-title">
                    <i class="bi bi-lightning-charge text-success"></i> Pre-configured Auto-Reply Keywords
                </div>
                <ul class="list-unstyled small text-muted m-0">
                    <li class="mb-2"><strong class="text-white">ORDER:</strong> Sends online store link &amp; order instructions.</li>
                    <li class="mb-2"><strong class="text-white">PRICE:</strong> Auto-replies with product catalog link &amp; prices.</li>
                    <li class="mb-2"><strong class="text-white">MENU:</strong> Displays top Mukhwas categories.</li>
                    <li class="mb-0"><strong class="text-white">HELP:</strong> Shares store customer support contact details.</li>
                </ul>
            </div>

        </div>

    </div>

    <!-- Row 2: Live Activity Log -->
    <div class="s-card mt-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="s-card-title m-0 border-0 p-0">
                <i class="bi bi-journal-text text-primary"></i> Auto-DM Activity Log
            </div>
            <form method="POST" action="" onsubmit="return confirm('Clear all Auto-DM activity logs?');">
                <input type="hidden" name="action" value="clear_logs">
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                    <i class="bi bi-trash me-1"></i>Clear Logs
                </button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Recipient IGSID</th>
                        <th>Message Preview</th>
                        <th>Status</th>
                        <th>Details / Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dm_logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No Auto-DM activity logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dm_logs as $log): ?>
                            <tr>
                                <td><small class="text-muted"><?= $log['timestamp'] ?></small></td>
                                <td><code><?= htmlspecialchars($log['recipient']) ?></code></td>
                                <td><?= htmlspecialchars($log['message']) ?></td>
                                <td>
                                    <?php if ($log['status'] === 'Sent'): ?>
                                        <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-1">Sent</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-1">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= htmlspecialchars($log['details']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
