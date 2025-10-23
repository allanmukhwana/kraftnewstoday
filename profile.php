<?php
/**
 * Kraft News Today - User Profile Management
 * Allows users to update their profile information
 */

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('auth_login.php');
}

$user_id = $_SESSION['user_id'];
$pdo = get_db_connection();
$success = '';
$errors = [];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $timezone = sanitize_input($_POST['timezone'] ?? 'UTC');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Email is already taken';
        }
    }
    
    // Password change validation
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password';
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
    }
    
    // Update profile
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, HASH_ALGO, ['cost' => HASH_COST]);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, email = ?, timezone = ?, password_hash = ?
                    WHERE id = ?
                ");
                $stmt->execute([$full_name, $email, $timezone, $password_hash, $user_id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, email = ?, timezone = ?
                    WHERE id = ?
                ");
                $stmt->execute([$full_name, $email, $timezone, $user_id]);
            }
            
            // Update session
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            
            $success = 'Profile updated successfully!';
            log_agent("User $user_id updated profile");
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
        } catch (PDOException $e) {
            log_error("Profile update failed: " . $e->getMessage());
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

$page_title = 'Profile Settings';
include 'header.php';
?>

<style>
    .profile-container {
        padding: 2rem 0;
        min-height: calc(100vh - 200px);
    }
    
    .profile-card {
        background: white;
        border-radius: 16px;
        padding: 2.5rem;
        box-shadow: var(--shadow-md);
        max-width: 700px;
        margin: 0 auto;
    }
    
    .profile-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid var(--border-color);
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
        font-weight: 800;
        margin: 0 auto 1rem;
    }
    
    .profile-header h1 {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }
    
    .profile-header p {
        color: var(--text-light);
    }
    
    .section-divider {
        margin: 2rem 0;
        border-top: 2px solid var(--border-color);
        padding-top: 2rem;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .profile-card {
            padding: 1.5rem;
        }
    }
</style>

<div class="profile-container">
    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <?php if ($user['subscription_plan'] === 'premium'): ?>
                    <span class="premium-badge"><i class="fas fa-crown"></i> Premium Member</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="profileForm">
                <!-- Basic Information -->
                <div class="section-title">
                    <i class="fas fa-user"></i> Basic Information
                </div>
                
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="timezone" class="form-label">Timezone</label>
                    <select class="form-select" id="timezone" name="timezone">
                        <option value="UTC" <?php echo $user['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC (Default)</option>
                        <option value="America/New_York" <?php echo $user['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time (ET)</option>
                        <option value="America/Chicago" <?php echo $user['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time (CT)</option>
                        <option value="America/Denver" <?php echo $user['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time (MT)</option>
                        <option value="America/Los_Angeles" <?php echo $user['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time (PT)</option>
                        <option value="Europe/London" <?php echo $user['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London (GMT)</option>
                        <option value="Europe/Paris" <?php echo $user['timezone'] === 'Europe/Paris' ? 'selected' : ''; ?>>Paris (CET)</option>
                        <option value="Asia/Tokyo" <?php echo $user['timezone'] === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo (JST)</option>
                        <option value="Asia/Shanghai" <?php echo $user['timezone'] === 'Asia/Shanghai' ? 'selected' : ''; ?>>Shanghai (CST)</option>
                        <option value="Australia/Sydney" <?php echo $user['timezone'] === 'Australia/Sydney' ? 'selected' : ''; ?>>Sydney (AEST)</option>
                    </select>
                    <small class="text-muted">Used for scheduling your daily digests</small>
                </div>
                
                <!-- Change Password -->
                <div class="section-divider">
                    <div class="section-title">
                        <i class="fas fa-lock"></i> Change Password
                    </div>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" 
                               placeholder="Leave blank to keep current password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               placeholder="Minimum 8 characters">
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Re-enter new password">
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        showLoading();
    });
</script>

<?php include 'footer.php'; ?>
