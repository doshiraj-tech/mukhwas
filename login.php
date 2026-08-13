<?php
include("../config/db.php");

// Auto-login via "Remember Me" cookie if not already logged in
if (!isset($_SESSION['admin']) && !empty($_COOKIE['admin_remember'])) {
    $cookie_data = explode(':', $_COOKIE['admin_remember']);
    if (count($cookie_data) === 2) {
        $selector = $cookie_data[0];
        $token    = $cookie_data[1];

        $sel_esc = mysqli_real_escape_string($conn, $selector);
        $token_res = mysqli_query($conn, "SELECT * FROM admin_remember_tokens WHERE selector='$sel_esc' AND expires_at > NOW()");

        if ($token_res && mysqli_num_rows($token_res) > 0) {
            $tok_row = mysqli_fetch_assoc($token_res);

            if (hash_equals($tok_row['token_hash'], hash('sha256', $token))) {
                $uname_esc = mysqli_real_escape_string($conn, $tok_row['username']);
                $admin_res = mysqli_query($conn, "SELECT * FROM admin WHERE username='$uname_esc'");

                if ($admin_res && mysqli_num_rows($admin_res) > 0) {
                    $admin_row = mysqli_fetch_assoc($admin_res);

                    session_regenerate_id(true);
                    $_SESSION['admin']               = $admin_row['username'];
                    $_SESSION['admin_role']          = $admin_row['role'] ?? 'subadmin';
                    $_SESSION['admin_agent']         = md5($_SERVER['HTTP_USER_AGENT']);
                    $_SESSION['admin_last_activity'] = time();

                    // Rotate Token for security
                    $new_token = bin2hex(random_bytes(32));
                    $new_hash  = hash('sha256', $new_token);
                    $exp_ts   = time() + (86400 * 30);
                    $exp_date = date('Y-m-d H:i:s', $exp_ts);

                    mysqli_query($conn, "UPDATE admin_remember_tokens SET token_hash='$new_hash', expires_at='$exp_date' WHERE selector='$sel_esc'");

                    setcookie('admin_remember', $selector . ':' . $new_token, [
                        'expires'  => $exp_ts,
                        'path'     => '/',
                        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);

                    log_admin_login_attempt($conn, $admin_row['username'], 'SUCCESS');

                    header("Location: dashboard.php");
                    exit();
                }
            }
        }
    }
}

// If already logged in, go to dashboard
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$info  = "";

// Show message based on redirect reason
if (isset($_GET['reason'])) {
    if ($_GET['reason'] === 'timeout') {
        $info = "Your session expired due to inactivity. Please log in again.";
    } elseif ($_GET['reason'] === 'security') {
        $info = "Session was terminated for security reasons. Please log in again.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed. Please try again.";
    } else {
        $raw_username = $_POST['username'] ?? '';
        // Rate limiting: max 5 attempts per 10 minutes per session
        if (is_rate_limited('admin_login', 5, 600)) {
            log_admin_login_attempt($conn, $raw_username, 'BLOCKED');
            $error = "Too many failed login attempts. Please wait 10 minutes and try again.";
        } else {
            $username = mysqli_real_escape_string($conn, $raw_username);
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = "Please fill in all fields.";
            } else {
                $query = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username'");
                if (mysqli_num_rows($query) > 0) {
                    $row = mysqli_fetch_assoc($query);

                    $password_ok = false;

                    if (password_verify($password, $row['password'])) {
                        // Modern hashed password — valid
                        $password_ok = true;
                    } elseif ($password === $row['password']) {
                        // Legacy plain-text password — valid, but auto-upgrade it now
                        $password_ok = true;
                        $rehashed = password_hash($password, PASSWORD_DEFAULT);
                        mysqli_query($conn, "UPDATE admin SET password='" . mysqli_real_escape_string($conn, $rehashed) . "' WHERE username='" . mysqli_real_escape_string($conn, $row['username']) . "'");
                    }

                    if ($password_ok) {
                        // Clear rate-limit counter on success
                        reset_rate_limit('admin_login');

                        // Prevent session fixation
                        session_regenerate_id(true);

                        $_SESSION['admin']               = $row['username'];
                        $_SESSION['admin_role']          = $row['role'] ?? 'subadmin';
                        $_SESSION['admin_agent']         = md5($_SERVER['HTTP_USER_AGENT']);
                        $_SESSION['admin_last_activity'] = time();

                        // Audit Log
                        log_admin_login_attempt($conn, $row['username'], 'SUCCESS');

                        // Remember Me token generation
                        if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1') {
                            $selector = bin2hex(random_bytes(16));
                            $token    = bin2hex(random_bytes(32));
                            $hash     = hash('sha256', $token);
                            $exp_ts   = time() + (86400 * 30);
                            $exp_date = date('Y-m-d H:i:s', $exp_ts);
                            $u_esc    = mysqli_real_escape_string($conn, $row['username']);

                            mysqli_query($conn, "INSERT INTO admin_remember_tokens (username, selector, token_hash, expires_at) VALUES ('$u_esc', '$selector', '$hash', '$exp_date')");

                            setcookie('admin_remember', $selector . ':' . $token, [
                                'expires'  => $exp_ts,
                                'path'     => '/',
                                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);
                        }

                        header("Location: dashboard.php");
                        exit();
                    } else {
                        log_admin_login_attempt($conn, $raw_username, 'FAILED');
                        $error = "Invalid username or password.";
                    }
                } else {
                    log_admin_login_attempt($conn, $raw_username, 'FAILED');
                    $error = "Invalid username or password.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Raj Kathiyawadi Mukhwash</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">

    <style>
        /* ===== Dynamic Admin Login Background ===== */
        .admin-login-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #0a0e1a 0%, #0d1b2a 25%, #1b2838 50%, #162a4a 75%, #0a0e1a 100%);
            background-size: 400% 400%;
            animation: adminGradientShift 15s ease infinite;
        }

        @keyframes adminGradientShift {
            0%   { background-position: 0% 50%; }
            25%  { background-position: 100% 0%; }
            50%  { background-position: 100% 100%; }
            75%  { background-position: 0% 100%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating Orbs */
        .admin-login-bg .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            will-change: transform;
        }
        .admin-login-bg .orb-1 {
            width: 450px; height: 450px;
            background: radial-gradient(circle, #00d4ff 0%, transparent 70%);
            top: -10%; left: -8%;
            animation: orbFloat1 18s ease-in-out infinite;
        }
        .admin-login-bg .orb-2 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, #4a6cf7 0%, transparent 70%);
            top: 60%; right: -5%;
            animation: orbFloat2 22s ease-in-out infinite;
        }
        .admin-login-bg .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, #00d4ff 0%, transparent 70%);
            bottom: -8%; left: 30%;
            animation: orbFloat3 20s ease-in-out infinite;
        }
        .admin-login-bg .orb-4 {
            width: 200px; height: 200px;
            background: radial-gradient(circle, #7b61ff 0%, transparent 70%);
            top: 25%; right: 25%;
            animation: orbFloat4 16s ease-in-out infinite;
        }
        .admin-login-bg .orb-5 {
            width: 180px; height: 180px;
            background: radial-gradient(circle, #00d4ff 0%, transparent 70%);
            top: 10%; right: 10%;
            opacity: 0.2;
            animation: orbFloat5 25s ease-in-out infinite;
        }

        @keyframes orbFloat1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%      { transform: translate(120px, 80px) scale(1.1); }
            66%      { transform: translate(-60px, 140px) scale(0.95); }
        }
        @keyframes orbFloat2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%      { transform: translate(-100px, -70px) scale(1.15); }
            66%      { transform: translate(80px, -120px) scale(0.9); }
        }
        @keyframes orbFloat3 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%      { transform: translate(90px, -60px) scale(1.05); }
            66%      { transform: translate(-110px, -30px) scale(1.1); }
        }
        @keyframes orbFloat4 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50%      { transform: translate(-70px, 90px) scale(1.2); }
        }
        @keyframes orbFloat5 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            40%      { transform: translate(60px, 50px) scale(1.15); }
            80%      { transform: translate(-40px, -60px) scale(0.9); }
        }

        /* Mesh Grid Overlay */
        .admin-login-bg .mesh-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 212, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 212, 255, 0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: meshDrift 30s linear infinite;
        }
        @keyframes meshDrift {
            0%   { transform: translate(0, 0); }
            100% { transform: translate(60px, 60px); }
        }

        /* Particle Canvas */
        #particleCanvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        /* Vignette overlay */
        .admin-login-bg .vignette {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, transparent 40%, rgba(10, 14, 26, 0.6) 100%);
            pointer-events: none;
        }

        /* ===== Glassmorphism Auth Card Override ===== */
        .admin-login-page .auth-wrapper {
            position: relative;
            z-index: 1;
            background: transparent !important;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .admin-login-page .auth-card {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 44px 40px;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: cardAppear 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(30px) scale(0.96);
            width: 440px;
        }

        @keyframes cardAppear {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Card shimmer line at top */
        .admin-login-page .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 20%; right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.7), transparent);
            border-radius: 2px;
        }

        /* Override text colors for glass card */
        .admin-login-page .auth-card h4,
        .admin-login-page .auth-card .fw-bold,
        .admin-login-page .auth-card .fw-semibold {
            color: #ffffff !important;
        }

        .admin-login-page .auth-card .brand-font,
        .admin-login-page .auth-card .text-primary {
            color: #00d4ff !important;
        }

        .admin-login-page .auth-card .form-label {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .admin-login-page .auth-card .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }
        .admin-login-page .auth-card .form-control::placeholder {
            color: rgba(255, 255, 255, 0.35);
        }
        .admin-login-page .auth-card .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(0, 212, 255, 0.6);
            box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.15);
            color: #ffffff;
        }

        /* Password toggle button */
        .admin-login-page .auth-card #togglePassword {
            background: rgba(255, 255, 255, 0.08) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-left: none !important;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
        }
        .admin-login-page .auth-card #togglePassword:hover {
            background: rgba(255, 255, 255, 0.15) !important;
        }
        .admin-login-page .auth-card #togglePassword i {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        /* Login Button */
        .admin-login-page .auth-card .btn-outline-primary {
            background: linear-gradient(135deg, #00d4ff, #4a6cf7) !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .admin-login-page .auth-card .btn-outline-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.4);
        }
        .admin-login-page .auth-card .btn-outline-primary:active {
            transform: translateY(0);
        }

        /* Back link */
        .admin-login-page .auth-card .text-muted {
            color: rgba(255, 255, 255, 0.5) !important;
            transition: color 0.3s ease;
        }
        .admin-login-page .auth-card .text-muted:hover {
            color: rgba(0, 212, 255, 0.9) !important;
        }

        /* Alert overrides */
        .admin-login-page .auth-card .alert-warning {
            background: rgba(255, 193, 7, 0.15);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ffd54f;
            backdrop-filter: blur(8px);
        }
        .admin-login-page .auth-card .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff8a80;
            backdrop-filter: blur(8px);
        }

        /* Logo image glow */
        .admin-login-page .auth-card .logo img {
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
            border: 2px solid rgba(0, 212, 255, 0.4) !important;
        }

        /* Security badge */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
            color: rgba(255, 255, 255, 0.3);
            font-size: 11px;
            letter-spacing: 0.5px;
            font-family: 'Outfit', sans-serif;
        }
        .security-badge i {
            font-size: 12px;
            color: rgba(0, 212, 255, 0.5);
        }

        /* Mouse-move glow effect */
        .cursor-glow {
            position: fixed;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        .cursor-glow.active {
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .admin-login-page .auth-card {
                width: 100%;
                padding: 32px 24px;
            }
            .admin-login-bg .orb { filter: blur(60px); }
        }
    </style>
</head>
<body class="fade-in admin-login-page">

<!-- Dynamic Animated Background -->
<div class="admin-login-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>
    <div class="orb orb-5"></div>
    <div class="mesh-grid"></div>
    <canvas id="particleCanvas"></canvas>
    <div class="vignette"></div>
</div>

<!-- Cursor Glow Effect -->
<div class="cursor-glow" id="cursorGlow"></div>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="logo text-center mb-4">
            <a href="../index.php" class="text-decoration-none d-flex flex-column align-items-center">
                <img src="../assets/images/logo.jpg" alt="Raj Kathiyawadi Mukhwash Logo" style="height: 80px; width: auto; border-radius: 8px;" class="mb-2 shadow border">
                <span class="fs-4 brand-font text-primary" style="font-family: var(--font-heading); font-weight: 800;">Raj Kathiyawadi</span>
            </a>
        </div>
        
        <h4 class="text-center mb-4 fw-bold">Admin Portal Login</h4>

        <?php if(!empty($info)) { ?>
            <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-clock-history me-2"></i>
                <div><?php echo htmlspecialchars($info); ?></div>
            </div>
        <?php } ?>
        <?php if(!empty($error)) { ?>
            <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?php echo $error; ?></div>
            </div>
        <?php } ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="mb-4">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Admin username" required autocomplete="off">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="adminPassword" class="form-control" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div id="capsLockWarning" class="alert alert-warning py-1 px-2 mt-2 d-none mb-0" style="font-size: 0.8rem; background: rgba(255, 193, 7, 0.15); border-color: rgba(255, 193, 7, 0.3); color: #ffd54f;">
                    <i class="bi bi-capslock-fill me-1"></i><strong>Caps Lock</strong> is ON!
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check mb-0">
                    <input class="form-check-input bg-transparent border-secondary" type="checkbox" name="remember_me" id="rememberMe" value="1">
                    <label class="form-check-label small" for="rememberMe" style="color: rgba(255, 255, 255, 0.8);">
                        Remember me (30 days)
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-outline-primary w-100 py-3 rounded-pill fw-semibold mb-3">
                <i class="bi bi-shield-lock-fill me-2"></i>Login to Dashboard
            </button>
            
            <div class="text-center">
                <a href="../user/login.php" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i>Back to Customer Login
                </a>
            </div>
        </form>

        <div class="security-badge">
            <i class="bi bi-lock-fill"></i>
            <span>256-BIT SSL ENCRYPTED</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== Password Toggle =====
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('adminPassword');
    
    if(togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }

    // ===== Caps Lock Detection =====
    if (passwordInput) {
        const capsWarning = document.getElementById('capsLockWarning');
        function checkCapsLock(e) {
            if (e.getModifierState && capsWarning) {
                if (e.getModifierState('CapsLock')) {
                    capsWarning.classList.remove('d-none');
                } else {
                    capsWarning.classList.add('d-none');
                }
            }
        }
        passwordInput.addEventListener('keyup', checkCapsLock);
        passwordInput.addEventListener('keydown', checkCapsLock);
    }

    // ===== Particle Canvas Animation =====
    const canvas = document.getElementById('particleCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let particles = [];
        const PARTICLE_COUNT = 60;

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        class Particle {
            constructor() {
                this.reset();
            }
            reset() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2.5 + 0.5;
                this.speedX = (Math.random() - 0.5) * 0.4;
                this.speedY = (Math.random() - 0.5) * 0.4;
                this.opacity = Math.random() * 0.5 + 0.1;
                this.fadeDir = Math.random() > 0.5 ? 1 : -1;
                // Mix of cyan and blue tones
                const isCyan = Math.random() > 0.5;
                this.r = isCyan ? 0 : 74;
                this.g = isCyan ? 212 : 108;
                this.b = isCyan ? 255 : 247;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                this.opacity += this.fadeDir * 0.003;
                if (this.opacity >= 0.6) this.fadeDir = -1;
                if (this.opacity <= 0.05) this.fadeDir = 1;
                if (this.x < -10 || this.x > canvas.width + 10 ||
                    this.y < -10 || this.y > canvas.height + 10) {
                    this.reset();
                }
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(${this.r}, ${this.g}, ${this.b}, ${this.opacity})`;
                ctx.fill();
            }
        }

        // Initialize particles
        for (let i = 0; i < PARTICLE_COUNT; i++) {
            particles.push(new Particle());
        }

        // Draw connection lines between nearby particles
        function drawConnections() {
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 120) {
                        const lineOpacity = (1 - dist / 120) * 0.12;
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.strokeStyle = `rgba(0, 212, 255, ${lineOpacity})`;
                        ctx.lineWidth = 0.5;
                        ctx.stroke();
                    }
                }
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            drawConnections();
            requestAnimationFrame(animate);
        }
        animate();
    }

    // ===== Cursor Glow Effect =====
    const cursorGlow = document.getElementById('cursorGlow');
    if (cursorGlow) {
        let glowActive = false;
        document.addEventListener('mousemove', function(e) {
            if (!glowActive) {
                cursorGlow.classList.add('active');
                glowActive = true;
            }
            cursorGlow.style.left = e.clientX + 'px';
            cursorGlow.style.top = e.clientY + 'px';
        });
        document.addEventListener('mouseleave', function() {
            cursorGlow.classList.remove('active');
            glowActive = false;
        });
    }

    // ===== Input Focus Animation =====
    document.querySelectorAll('.auth-card .form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.mb-4')?.classList.add('input-focused');
        });
        input.addEventListener('blur', function() {
            this.closest('.mb-4')?.classList.remove('input-focused');
        });
    });
});
</script>
</body>
</html>
