<?php
include("../config/db.php");
require_once('auth_guard.php');

$admin_username = $_SESSION['admin'];
$success = '';
$error = '';

// Fetch current admin info
$admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE username='$admin_username'");
$admin_data = mysqli_fetch_assoc($admin_q);
$current_photo = $admin_data['profile_photo'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed. Please try again.";
    } else {
        $new_username = mysqli_real_escape_string($conn, trim($_POST['username']));
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($new_username)) {
            $error = "Username cannot be empty.";
        } elseif (empty($current_password)) {
            $error = "Current password is required to save changes.";
        } else {
            // Verify current password first
            // Supporting both plain text and hashed passwords
            if ($current_password == $admin_data['password'] || password_verify($current_password, $admin_data['password'])) {
                
                $update_query = "UPDATE admin SET username='$new_username'";
                
                // If they want to change the password
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $error = "New password must be at least 6 characters.";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "New passwords do not match.";
                    } else {
                        $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
                        $plain_pw_esc = mysqli_real_escape_string($conn, $new_password);
                        $update_query .= ", password='$hashed_pw', plain_password='$plain_pw_esc'";
                    }
                }
                
                if (empty($error)) {
                    // Handle photo upload
                    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                        $upload_dir   = '../assets/images/admin/';
                        $upload_error = '';
                        $new_filename = secure_upload_image(
                            $_FILES['profile_photo'],
                            realpath($upload_dir),
                            $upload_error,
                            'admin'
                        );

                        if ($new_filename !== false) {
                            $new_filename_esc = mysqli_real_escape_string($conn, $new_filename);
                            $update_query    .= ", profile_photo='$new_filename_esc'";
                            $current_photo    = $new_filename;
                        } else {
                            $error = $upload_error;
                        }
                    }
                }
                
                if (empty($error)) {
                    $update_query .= " WHERE username='$admin_username'";
                    
                    if (mysqli_query($conn, $update_query)) {
                        $_SESSION['admin'] = $new_username;
                        $admin_username = $new_username;
                        // Re-fetch admin data to have updated info
                        $admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE username='$admin_username'");
                        $admin_data = mysqli_fetch_assoc($admin_q);
                        $success = "Profile updated successfully!";
                    } else {
                        $error = "Error updating profile.";
                    }
                }
            } else {
                $error = "Incorrect current password.";
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
<title>Edit Profile - Raj Kathiyawadi Mukhwash</title>
<link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body{ background:#f5f7fa; }
.main-content{ margin-left:255px; padding:30px; }
.card{ border:none; border-radius:15px; box-shadow:0 3px 10px rgba(0,0,0,0.1); }
</style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Admin Profile</h2>
    </div>

    <div class="card" style="max-width: 600px;">
        <div class="card-body p-4">
            <?php if($success) { echo "<div class='alert alert-success'>" . htmlspecialchars($success) . "</div>"; } ?>
            <?php if($error) { echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>"; } ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="mb-4 text-center">
                    <?php if (!empty($current_photo) && file_exists('../assets/images/admin/'.$current_photo)): ?>
                        <img src="../assets/images/admin/<?= htmlspecialchars($current_photo) ?>" alt="Profile Photo" style="width:100px; height:100px; border-radius:50%; object-fit:cover; margin-bottom:15px; border:2px solid #e2e8f0;">
                    <?php else: ?>
                        <div style="width:100px; height:100px; border-radius:50%; background:linear-gradient(135deg, #0066ff, #7c3aed); display:flex; align-items:center; justify-content:center; color:white; font-size:40px; margin:0 auto 15px;">👤</div>
                    <?php endif; ?>
                    <input type="file" name="profile_photo" class="form-control form-control-sm" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Username</label>
                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($admin_username); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">New Password</label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Leave blank to keep current password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('new_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Minimum 6 characters. Enter only if you wish to change your password.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter new password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirm_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <hr class="my-4">
                <div class="mb-4">
                    <label class="form-label fw-bold text-danger">Current Password *</label>
                    <div class="input-group">
                        <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter current password to authorize changes" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('current_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text text-muted">Required to authorize any profile changes.</div>
                </div>
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

</body>
</html>
