<?php
/**
 * Kraft News Today - User Registration Page
 * Handles new user registration with email verification
 */

require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$page_title = 'Sign Up';
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $timezone = sanitize_input($_POST['timezone'] ?? 'UTC');
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered';
            }
        } catch (PDOException $e) {
            log_error("Registration check failed: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
        }
    }
    
    // Create user account
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
            $verification_token = bin2hex(random_bytes(32));
            
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password_hash, full_name, timezone, verification_token, email_verified) 
                VALUES (?, ?, ?, ?, ?, FALSE)
            ");
            
            $stmt->execute([$email, $password_hash, $full_name, $timezone, $verification_token]);
            $user_id = $pdo->lastInsertId();
            
            // Log the user in immediately (skip email verification for demo)
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['subscription_plan'] = 'free';
            
            log_agent("New user registered: $email");
            
            // Redirect to industry selection
            redirect('industries.php?welcome=1');
            
        } catch (PDOException $e) {
            log_error("Registration failed: " . $e->getMessage());
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

include 'header.php';
?>

<style>
    .auth-container {
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 0;
    }
    
    .auth-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow-lg);
        padding: 2.5rem;
        width: 100%;
        max-width: 500px;
    }
    
    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .auth-header h1 {
        color: var(--primary-color);
        font-weight: 800;
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .auth-header p {
        color: var(--text-light);
        font-size: 1rem;
    }
    
    .form-label {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
        border: 2px solid var(--border-color);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(10, 73, 119, 0.15);
    }
    
    .input-group-text {
        background: white;
        border: 2px solid var(--border-color);
        border-right: none;
        color: var(--text-light);
    }
    
    .input-group .form-control {
        border-left: none;
    }
    
    .input-group:focus-within .input-group-text {
        border-color: var(--primary-color);
    }
    
    .password-strength {
        height: 4px;
        background: var(--border-color);
        border-radius: 2px;
        margin-top: 0.5rem;
        overflow: hidden;
    }
    
    .password-strength-bar {
        height: 100%;
        width: 0;
        transition: all 0.3s ease;
    }
    
    .password-strength-bar.weak {
        width: 33%;
        background: var(--secondary-color);
    }
    
    .password-strength-bar.medium {
        width: 66%;
        background: #ffa500;
    }
    
    .password-strength-bar.strong {
        width: 100%;
        background: #28a745;
    }
    
    .auth-divider {
        text-align: center;
        margin: 1.5rem 0;
        position: relative;
    }
    
    .auth-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: var(--border-color);
    }
    
    .auth-divider span {
        background: white;
        padding: 0 1rem;
        position: relative;
        color: var(--text-light);
        font-size: 0.9rem;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 1.5rem;
        color: var(--text-light);
    }
    
    .auth-footer a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
    }
    
    .auth-footer a:hover {
        text-decoration: underline;
    }
    
    @media (max-width: 576px) {
        .auth-card {
            padding: 1.5rem;
        }
        
        .auth-header h1 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="auth-card">
                    <div class="auth-header">
                        <h1><i class="fas fa-user-plus"></i> Create Account</h1>
                        <p>Join Kraft News Today and get AI-powered insights</p>
                    </div>
                    
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
                    
                    <form method="POST" action="" id="registerForm">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                                   required placeholder="John Doe">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   required placeholder="john@example.com">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required placeholder="Minimum 8 characters">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <small class="text-muted" id="strengthText"></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required placeholder="Re-enter your password">
                        </div>
                        
                        <div class="mb-4">
                            <label for="timezone" class="form-label">
                                <i class="fas fa-clock"></i> Timezone
                            </label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="UTC">UTC (Default)</option>
                                <option value="America/New_York">Eastern Time (ET)</option>
                                <option value="America/Chicago">Central Time (CT)</option>
                                <option value="America/Denver">Mountain Time (MT)</option>
                                <option value="America/Los_Angeles">Pacific Time (PT)</option>
                                <option value="Europe/London">London (GMT)</option>
                                <option value="Europe/Paris">Paris (CET)</option>
                                <option value="Asia/Tokyo">Tokyo (JST)</option>
                                <option value="Asia/Shanghai">Shanghai (CST)</option>
                                <option value="Australia/Sydney">Sydney (AEST)</option>
                            </select>
                            <small class="text-muted">Used for scheduling your daily digests</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-3">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </form>
                    
                    <div class="auth-footer">
                        Already have an account? <a href="auth_login.php">Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        strengthBar.className = 'password-strength-bar';
        
        if (strength === 0) {
            strengthBar.style.width = '0';
            strengthText.textContent = '';
        } else if (strength <= 2) {
            strengthBar.classList.add('weak');
            strengthText.textContent = 'Weak password';
            strengthText.style.color = 'var(--secondary-color)';
        } else if (strength === 3) {
            strengthBar.classList.add('medium');
            strengthText.textContent = 'Medium password';
            strengthText.style.color = '#ffa500';
        } else {
            strengthBar.classList.add('strong');
            strengthText.textContent = 'Strong password';
            strengthText.style.color = '#28a745';
        }
    });
    
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    
    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            showToast('Passwords do not match', 'danger');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            showToast('Password must be at least 8 characters long', 'danger');
            return false;
        }
        
        showLoading();
    });
</script>

<?php include 'footer.php'; ?>
