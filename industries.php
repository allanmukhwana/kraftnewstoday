<?php
/**
 * Kraft News Today - Industry Management
 * Allows users to select and manage their industry preferences
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_industries = $_POST['industries'] ?? [];
    
    if (empty($selected_industries)) {
        $errors[] = 'Please select at least one industry';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete existing industries
            $stmt = $pdo->prepare("DELETE FROM user_industries WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Insert new industries
            $stmt = $pdo->prepare("INSERT INTO user_industries (user_id, industry_code) VALUES (?, ?)");
            foreach ($selected_industries as $industry) {
                $stmt->execute([$user_id, $industry]);
            }
            
            $pdo->commit();
            $success = 'Industries updated successfully!';
            log_agent("User $user_id updated industries: " . implode(', ', $selected_industries));
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_error("Failed to update industries: " . $e->getMessage());
            $errors[] = 'Failed to update industries. Please try again.';
        }
    }
}

// Get user's current industries
$stmt = $pdo->prepare("SELECT industry_code FROM user_industries WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_industries = $stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Manage Industries';
$is_welcome = isset($_GET['welcome']);
include 'header.php';
?>

<style>
    .industries-container {
        padding: 2rem 0;
        min-height: calc(100vh - 200px);
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    
    .page-header p {
        font-size: 1.1rem;
        color: var(--text-light);
    }
    
    .industries-form {
        background: white;
        border-radius: 16px;
        padding: 2.5rem;
        box-shadow: var(--shadow-md);
        max-width: 900px;
        margin: 0 auto;
    }
    
    .industry-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .industry-checkbox {
        position: relative;
    }
    
    .industry-checkbox input[type="checkbox"] {
        display: none;
    }
    
    .industry-label {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
    }
    
    .industry-label:hover {
        border-color: var(--primary-color);
        box-shadow: var(--shadow-sm);
        transform: translateY(-2px);
    }
    
    .industry-checkbox input[type="checkbox"]:checked + .industry-label {
        border-color: var(--primary-color);
        background: rgba(10, 73, 119, 0.05);
    }
    
    .industry-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: rgba(10, 73, 119, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--primary-color);
        flex-shrink: 0;
    }
    
    .industry-checkbox input[type="checkbox"]:checked + .industry-label .industry-icon {
        background: var(--primary-color);
        color: white;
    }
    
    .industry-name {
        font-weight: 600;
        color: var(--text-dark);
        flex: 1;
    }
    
    .industry-check {
        width: 24px;
        height: 24px;
        border: 2px solid var(--border-color);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    
    .industry-checkbox input[type="checkbox"]:checked + .industry-label .industry-check {
        background: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-color), #083a5f);
        color: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .welcome-banner h2 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }
    
    .welcome-banner p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 0;
    }
    
    @media (max-width: 768px) {
        .industries-form {
            padding: 1.5rem;
        }
        
        .industry-grid {
            grid-template-columns: 1fr;
        }
        
        .page-header h1 {
            font-size: 2rem;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .form-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="industries-container">
    <div class="container">
        <?php if ($is_welcome): ?>
            <div class="welcome-banner">
                <h2><i class="fas fa-rocket"></i> Welcome to Kraft News Today!</h2>
                <p>Let's get started by selecting the industries you want to track</p>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1><i class="fas fa-industry"></i> Select Your Industries</h1>
            <p>Choose the industries you want to receive news and insights about</p>
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
        
        <div class="industries-form">
            <form method="POST" action="" id="industriesForm">
                <div class="industry-grid">
                    <?php 
                    $industry_icons = [
                        'technology' => 'fa-microchip',
                        'healthcare' => 'fa-heartbeat',
                        'finance' => 'fa-chart-line',
                        'ai_ml' => 'fa-robot',
                        'cybersecurity' => 'fa-shield-alt',
                        'blockchain' => 'fa-link',
                        'ecommerce' => 'fa-shopping-cart',
                        'marketing' => 'fa-bullhorn',
                        'real_estate' => 'fa-building',
                        'energy' => 'fa-bolt',
                        'education' => 'fa-graduation-cap',
                        'entertainment' => 'fa-film',
                        'automotive' => 'fa-car',
                        'aerospace' => 'fa-plane',
                        'biotech' => 'fa-dna'
                    ];
                    
                    foreach ($GLOBALS['INDUSTRIES'] as $code => $name): 
                        $checked = in_array($code, $user_industries) ? 'checked' : '';
                        $icon = $industry_icons[$code] ?? 'fa-industry';
                    ?>
                        <div class="industry-checkbox">
                            <input type="checkbox" id="industry_<?php echo $code; ?>" 
                                   name="industries[]" value="<?php echo $code; ?>" <?php echo $checked; ?>>
                            <label for="industry_<?php echo $code; ?>" class="industry-label">
                                <div class="industry-icon">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <span class="industry-name"><?php echo htmlspecialchars($name); ?></span>
                                <div class="industry-check">
                                    <i class="fas fa-check"></i>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Industries
                    </button>
                    <?php if (!$is_welcome): ?>
                        <a href="dashboard.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Form validation
    document.getElementById('industriesForm').addEventListener('submit', function(e) {
        const checkboxes = document.querySelectorAll('input[name="industries[]"]:checked');
        
        if (checkboxes.length === 0) {
            e.preventDefault();
            showToast('Please select at least one industry', 'danger');
            return false;
        }
        
        showLoading();
    });
    
    // Show count of selected industries
    const checkboxes = document.querySelectorAll('input[name="industries[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCount);
    });
    
    function updateCount() {
        const selected = document.querySelectorAll('input[name="industries[]"]:checked').length;
        // Could add a counter display here if desired
    }
</script>

<?php include 'footer.php'; ?>
