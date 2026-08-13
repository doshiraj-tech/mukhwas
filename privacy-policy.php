<?php
$base_path = "./";
include($base_path . "config/db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Privacy Policy of Raj Kathiyawadi Mukhwash - learn how we collect, use, and protect your personal information.">
    <title>Privacy Policy - Raj Kathiyawadi Mukhwash</title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1; --primary-light: #818cf8; --bg: #030712;
            --surface: #0f172a; --surface2: #1e293b; --border: rgba(255,255,255,0.08);
            --text: #f1f5f9; --text-muted: #94a3b8; --green: #34d399;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); line-height: 1.8; min-height: 100vh; }

        .top-nav {
            background: rgba(3,7,18,0.95); border-bottom: 1px solid var(--border);
            padding: 14px 24px; display: flex; align-items: center; gap: 16px;
            position: sticky; top: 0; z-index: 100; backdrop-filter: blur(12px);
        }
        .top-nav a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s; }
        .top-nav a:hover { color: var(--primary-light); }
        .nav-brand { font-family: 'Outfit', sans-serif; font-weight: 700; color: var(--text); font-size: 1rem; margin-left: auto; }

        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            border-bottom: 1px solid var(--border); padding: 60px 24px 48px;
            text-align: center; position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(99,102,241,0.2) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3);
            border-radius: 999px; padding: 6px 18px; font-size: 0.8rem;
            color: var(--primary-light); font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.05em; margin-bottom: 20px;
        }
        .hero h1 {
            font-family: 'Outfit', sans-serif; font-size: clamp(2rem, 5vw, 3rem); font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #818cf8 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; margin-bottom: 14px;
        }
        .hero p { color: var(--text-muted); font-size: 1.05rem; max-width: 560px; margin: 0 auto 20px; }
        .updated-badge {
            display: inline-block; background: rgba(52,211,153,0.12);
            border: 1px solid rgba(52,211,153,0.25); color: var(--green);
            border-radius: 8px; padding: 4px 14px; font-size: 0.82rem; font-weight: 600;
        }

        .page-wrap { max-width: 900px; margin: 0 auto; padding: 48px 24px 80px; }

        .section {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 32px 36px; margin-bottom: 24px; transition: border-color 0.2s;
        }
        .section:hover { border-color: rgba(99,102,241,0.25); }
        .section-header {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border);
        }
        .section-icon {
            width: 42px; height: 42px; border-radius: 10px;
            background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.25);
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;
        }
        .section h2 { font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--text); }
        p { color: var(--text-muted); margin-bottom: 14px; }
        p:last-child { margin-bottom: 0; }
        ul { padding-left: 22px; color: var(--text-muted); margin-bottom: 14px; }
        li { margin-bottom: 8px; }
        strong { color: var(--text); font-weight: 600; }
        code { background: var(--surface2); color: var(--primary-light); padding: 1px 6px; border-radius: 4px; font-size: 0.9em; }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 0.9rem; }
        .data-table th { background: var(--surface2); color: var(--primary-light); font-weight: 600; padding: 10px 14px; text-align: left; border: 1px solid var(--border); }
        .data-table td { padding: 10px 14px; border: 1px solid var(--border); color: var(--text-muted); vertical-align: top; }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }

        .highlight { background: rgba(99,102,241,0.07); border: 1px solid rgba(99,102,241,0.2); border-radius: 10px; padding: 16px 20px; margin-top: 4px; }
        .highlight-warn { background: rgba(245,158,11,0.07); border-color: rgba(245,158,11,0.2); }
        .highlight p { margin: 0; }

        .contact-card {
            background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(244,114,182,0.08));
            border: 1px solid rgba(99,102,241,0.25); border-radius: 14px;
            padding: 28px 32px; text-align: center;
        }
        .contact-card h3 { font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 700; margin-bottom: 12px; }
        .contact-card a { color: var(--primary-light); text-decoration: none; font-weight: 600; }
        .contact-card a:hover { text-decoration: underline; }
        .contact-card p { margin-bottom: 0; }

        @media (max-width: 640px) {
            .section { padding: 22px 18px; }
            .data-table { font-size: 0.8rem; }
            .data-table th, .data-table td { padding: 8px 10px; }
        }
    </style>
</head>
<body>

<nav class="top-nav">
    <a href="index.php">&larr; Back to Store</a>
    <a href="user/login.php">Login</a>
    <a href="user/register.php">Register</a>
    <span class="nav-brand">&#127807; Raj Kathiyawadi Mukhwash</span>
</nav>

<div class="hero">
    <div class="hero-badge">&#128274; Legal &amp; Privacy</div>
    <h1>Privacy Policy</h1>
    <p>We value your trust. This policy explains exactly what information we collect, why we collect it, and how we protect it.</p>
    <span class="updated-badge">&#9989; Last Updated: August 2026</span>
</div>

<div class="page-wrap">

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#128203;</div>
            <h2>1. Introduction &amp; Scope</h2>
        </div>
        <p>This Privacy Policy applies to <strong>Raj Kathiyawadi Mukhwash</strong> ("we", "us", "our") and covers all personal data collected when you:</p>
        <ul>
            <li>Create an account or log in to our website</li>
            <li>Place an order for products</li>
            <li>Use the GPS location feature on checkout or profile pages</li>
            <li>Contact us via the contact form</li>
            <li>Write a product review</li>
            <li>Use the wishlist or reward points features</li>
        </ul>
        <p>By registering, logging in, or placing an order, you agree to the terms described in this policy.</p>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#128202;</div>
            <h2>2. Personal Data We Collect</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Data Category</th><th>Specific Fields</th><th>Why We Collect It</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Account Identity</strong></td>
                    <td>Full Name, Email Address</td>
                    <td>To create your account and send order confirmations</td>
                </tr>
                <tr>
                    <td><strong>Contact Information</strong></td>
                    <td>Mobile Number (10 digits)</td>
                    <td>For order communication and delivery coordination</td>
                </tr>
                <tr>
                    <td><strong>Authentication</strong></td>
                    <td>Password (stored as bcrypt hash &mdash; never in plain text)</td>
                    <td>To verify your identity on login</td>
                </tr>
                <tr>
                    <td><strong>Delivery Address</strong></td>
                    <td>Full Name, Address Line, City, Pincode</td>
                    <td>To ship your orders correctly</td>
                </tr>
                <tr>
                    <td><strong>GPS Location</strong></td>
                    <td>Latitude &amp; Longitude (optional, browser-prompted)</td>
                    <td>To auto-fill delivery address on checkout/profile. Only when you click "Use Current Location". You may decline at any time.</td>
                </tr>
                <tr>
                    <td><strong>Order Details</strong></td>
                    <td>Products, quantities, prices, total, payment method, status</td>
                    <td>To process, fulfil, and track orders; for accounting</td>
                </tr>
                <tr>
                    <td><strong>Product Reviews</strong></td>
                    <td>Star rating, review text (linked to your account)</td>
                    <td>To display verified reviews on product pages</td>
                </tr>
                <tr>
                    <td><strong>Wishlist</strong></td>
                    <td>Product IDs saved to your wishlist</td>
                    <td>To remember products you have saved for later</td>
                </tr>
                <tr>
                    <td><strong>Reward Points</strong></td>
                    <td>Points balance and transaction history</td>
                    <td>To operate the loyalty rewards programme</td>
                </tr>
                <tr>
                    <td><strong>Contact Messages</strong></td>
                    <td>Name, Email, Mobile, Subject, Message</td>
                    <td>To respond to your enquiries from the Contact page</td>
                </tr>
                <tr>
                    <td><strong>Technical Data</strong></td>
                    <td>IP address, browser user-agent, login timestamp</td>
                    <td>For security, fraud prevention, and audit logging</td>
                </tr>
            </tbody>
        </table>
        <div class="highlight">
            <p>&#128274; <strong>We never collect:</strong> payment card numbers, bank details, Aadhaar/PAN, or biometric data. Payments are handled externally (Cash on Delivery or third-party gateways).</p>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#9881;</div>
            <h2>3. How We Use Your Data</h2>
        </div>
        <ul>
            <li><strong>Account management:</strong> Creating, authenticating, and securing your account.</li>
            <li><strong>Order fulfilment:</strong> Processing orders, arranging delivery, and issuing invoices.</li>
            <li><strong>Customer support:</strong> Responding to queries via WhatsApp, email, or phone.</li>
            <li><strong>Site improvement:</strong> Understanding usage patterns to improve features.</li>
            <li><strong>Security:</strong> Detecting and preventing fraudulent logins and abuse.</li>
            <li><strong>Loyalty rewards:</strong> Tracking and crediting points on eligible orders.</li>
            <li><strong>Legal compliance:</strong> Meeting applicable Indian legal and tax obligations.</li>
        </ul>
        <div class="highlight highlight-warn">
            <p>&#9888; <strong>We do not</strong> sell, rent, or trade your personal data to any third party for marketing purposes.</p>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#128737;</div>
            <h2>4. Data Storage &amp; Security</h2>
        </div>
        <ul>
            <li>All data is stored in a <strong>MySQL database</strong> on a secure server.</li>
            <li>Passwords are hashed with <strong>PHP bcrypt</strong> (PASSWORD_DEFAULT). We cannot recover your password &mdash; only reset it.</li>
            <li>Admin actions are logged with IP address and timestamp.</li>
            <li>HTTP Security Headers are set: Content Security Policy, X-Frame-Options, X-Content-Type-Options, Referrer-Policy.</li>
            <li>Session tokens are bound to your browser user-agent and expire after inactivity.</li>
            <li><strong>CSRF tokens</strong> protect every form on the site.</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#128205;</div>
            <h2>5. GPS &amp; Location Data</h2>
        </div>
        <p>On the <strong>Checkout</strong> and <strong>My Profile</strong> pages, we offer an optional "Use Current Location" button via your browser's <code>navigator.geolocation</code> API.</p>
        <ul>
            <li><strong>Your browser always prompts for permission first</strong> &mdash; we cannot access GPS without your explicit consent.</li>
            <li>Coordinates are sent to <strong>OpenStreetMap Nominatim</strong> (a privacy-respecting geocoding service) to produce a readable address.</li>
            <li>Latitude and longitude may be stored with your order to assist delivery.</li>
            <li>You may <strong>decline location access</strong> at any time; all fields can be filled manually.</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#128279;</div>
            <h2>6. Third-Party Services</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Service</th><th>Purpose</th><th>Data Sent</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>OpenStreetMap Nominatim</strong></td><td>GPS to address conversion</td><td>Latitude &amp; Longitude (on "Use Current Location" click only)</td></tr>
                <tr><td><strong>Google Maps</strong></td><td>Map on Contact page</td><td>Your IP address (standard browser request)</td></tr>
                <tr><td><strong>Google Fonts</strong></td><td>Website typography</td><td>Your IP address (standard CDN request)</td></tr>
                <tr><td><strong>Bootstrap / jsDelivr CDN</strong></td><td>UI components &amp; icons</td><td>Your IP address (standard CDN request)</td></tr>
                <tr><td><strong>WhatsApp</strong></td><td>Order enquiries (optional)</td><td>Whatever you type in your message</td></tr>
                <tr><td><strong>Meta / Instagram</strong></td><td>Order DM confirmations (if enabled in admin)</td><td>Instagram user ID and order ID (only if opted in)</td></tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#128197;</div>
            <h2>7. Data Retention</h2>
        </div>
        <ul>
            <li><strong>Account data:</strong> Retained while your account is active. Deletable on request.</li>
            <li><strong>Order records:</strong> Retained for 7+ years (Indian GST compliance).</li>
            <li><strong>Reviews:</strong> Retained until account deletion or specific removal request.</li>
            <li><strong>Admin login logs:</strong> Up to 90 days.</li>
            <li><strong>Session data:</strong> Cleared on logout or after inactivity.</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#9989;</div>
            <h2>8. Your Rights</h2>
        </div>
        <ul>
            <li><strong>Access:</strong> Request a copy of all data we hold about you.</li>
            <li><strong>Rectification:</strong> Ask us to correct inaccurate information.</li>
            <li><strong>Erasure:</strong> Request account deletion (subject to legal retention).</li>
            <li><strong>Withdraw Consent:</strong> Withdraw consent for optional data (e.g. GPS) at any time.</li>
            <li><strong>Object:</strong> Object to any specific use of your data.</li>
        </ul>
        <p>We will respond within <strong>30 days</strong> of your request.</p>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#127850;</div>
            <h2>9. Cookies &amp; Sessions</h2>
        </div>
        <p>We use only <strong>essential session cookies</strong> &mdash; no advertising or tracking cookies:</p>
        <ul>
            <li><strong>PHPSESSID</strong> &mdash; keeps you logged in. Strictly necessary, cannot be disabled during use.</li>
            <li>Set with <code>HttpOnly</code>, <code>SameSite=Lax</code>, and <code>Secure</code> (on HTTPS) flags.</li>
            <li>We do <strong>not</strong> use Google Analytics, Facebook Pixel, or any third-party tracking.</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#128100;</div>
            <h2>10. Children's Privacy</h2>
        </div>
        <p>Our services are not directed at children under <strong>13</strong>. If you believe a child has provided personal data, contact us and we will delete it promptly.</p>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-icon">&#128221;</div>
            <h2>11. Changes to This Policy</h2>
        </div>
        <p>We may update this policy periodically. The "Last Updated" date above will always reflect the latest version. Continued use after an update constitutes acceptance of the revised terms.</p>
    </div>

    <div class="contact-card">
        <h3>&#128236; Questions or Requests?</h3>
        <p style="color:var(--text-muted);margin-bottom:14px;">To exercise your data rights or ask questions about this policy, contact us:</p>
        <p>
            <strong>Raj Kathiyawadi Mukhwash</strong><br>
            &#128231; <a href="mailto:info@rajkathiyawadimukhwash.com">info@rajkathiyawadimukhwash.com</a><br>
            &#128241; <a href="https://wa.me/918140265904" target="_blank" rel="noopener noreferrer">+91 8140265904 (WhatsApp)</a><br>
            &#128205; Rajkot, Gujarat, India
        </p>
    </div>

</div>
</body>
</html>
