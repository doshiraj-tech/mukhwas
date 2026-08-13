<?php
/**
 * Meta / Instagram Webhook API Endpoint
 * Handles incoming Meta verification challenges and automated message replies
 */

session_start();
header('Content-Type: application/json');

include_once("../includes/instagram_auto_dm.php");

// ── 1. Handle GET: Meta Webhook Verification ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $verify_token = get_setting_val('ig_verify_token', 'mukhwas_ig_secret_token');
    
    $mode      = $_GET['hub_mode'] ?? '';
    $token     = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $verify_token) {
        http_response_code(200);
        echo $challenge;
        exit();
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid verification token']);
        exit();
    }
}

// ── 2. Handle POST: Incoming Instagram Messages ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $data      = json_decode($raw_input, true);

    if (!$data || !isset($data['entry'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit();
    }

    foreach ($data['entry'] as $entry) {
        if (!isset($entry['messaging'])) continue;

        foreach ($entry['messaging'] as $messaging) {
            $sender_id = $messaging['sender']['id'] ?? '';
            $message   = $messaging['message']['text'] ?? '';

            if (empty($sender_id) || empty($message)) continue;

            // Process Keyword Auto-Replies
            $reply_text = processKeywordAutoReply($message);

            if ($reply_text) {
                sendInstagramDM($sender_id, $reply_text);
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'EVENT_RECEIVED']);
    exit();
}

/**
 * Match incoming message against configured auto-reply keywords
 */
function processKeywordAutoReply($userMsg) {
    $cleanMsg = strtoupper(trim($userMsg));

    // Custom Keyword Rules (Loaded from Session/Admin configuration)
    $keywords = $_SESSION['settings']['ig_auto_keywords'] ?? [
        'ORDER' => "📦 To order our premium mouth fresheners online, visit: https://rajkathiyawadimukhwash.com/shop.php or reply with the product name!",
        'PRICE' => "💰 Explore our complete catalog and price list here: https://rajkathiyawadimukhwash.com/shop.php",
        'HELP'  => "👋 Hello! Our team is here to assist you. Leave your query or call us directly at +91 8140265904.",
        'MENU'  => "🍃 Our top categories:\n1. Kathiyawadi Special Mukhwas\n2. Sweet Paan Mukhwas\n3. Digestives & Churna\nVisit: https://rajkathiyawadimukhwash.com"
    ];

    foreach ($keywords as $kw => $reply) {
        if (strpos($cleanMsg, strtoupper($kw)) !== false) {
            return $reply;
        }
    }

    // Default welcome reply if enabled
    $default_reply = get_setting_val('ig_default_auto_reply', '');
    if (!empty($default_reply)) {
        return $default_reply;
    }

    return "Hello! 👋 Thank you for messaging Raj Kathiyawadi Mukhwash. How can we help you today? Visit our website at https://rajkathiyawadimukhwash.com to view all products!";
}
