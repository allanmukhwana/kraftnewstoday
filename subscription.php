<?php
/**
 * Kraft News Today - Subscription Management
 * Allows premium users to manage their subscription
 */

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('auth_login.php');
}

$user_id = $_SESSION['user_id'];
$pdo = get_db_connection();

// Get user and subscription data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT * FROM subscriptions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$subscription = $stmt->fetch();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = 'cancelled'
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        
        $stmt = $pdo->prepare("
            UPDATE subscriptions 
            SET cancel_at_period_end = TRUE
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$user_id]);
        
        $_SESSION['subscription_status'] = 'cancelled';
        $success = 'Subscription will be cancelled at the end of the billing period.';
        
    } catch (PDOException $e) {
        log_error("Subscription cancellation failed: " . $e->getMessage());
        $error = 'Failed to cancel subscription. Please try again.';
    }
}

$page_title = 'Manage Subscription';
include 'header.php';
?>

<style>
    .subscription-container {
        padding: 2rem 0;
        min-height: calc(100vh - 200px);
    }
    
    .subscription-card {
        background: white;
        border-radius: 16px;
        padding: 2.5rem;
        box-shadow: var(--shadow-md);
        max-width: 700px;
        margin: 0 auto;
    }
    
    .subscription-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid var(--border-color);
    }
    
    .plan-badge {
        display: inline-block;
        padding: 0.5rem 1.5rem;
        border-radius: 30px;
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    
    .plan-badge.premium {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: var(--text-dark);
    }
    
    .plan-badge.free {
        background: rgba(108, 117, 125, 0.1);
        color: var(--text-light);
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 1rem 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .info-label {
        font-weight: 600;
        color: var(--text-light);
    }
    
    .info-value {
        font-weight: 700;
        color: var(--text-dark);
    }
</style>

<div class="subscription-container">
    <div class="container">
        <div class="subscription-card">
            <div class="subscription-header">
                <h1 style="font-size: 2rem; font-weight: 800; color: var(--primary-color); margin-bottom: 1rem;">
                    <i class="fas fa-credit-card"></i> Subscription Management
                </h1>
                
                <?php if ($user['subscription_plan'] === 'premium'): ?>
                    <span class="plan-badge premium">
                        <i class="fas fa-crown"></i> Premium Plan
                    </span>
                <?php else: ?>
                    <span class="plan-badge free">
                        <i class="fas fa-user"></i> Free Plan
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($user['subscription_plan'] === 'premium' && $subscription): ?>
                <div class="info-row">
                    <span class="info-label">Plan</span>
                    <span class="info-value">Premium</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Amount</span>
                    <span class="info-value">$<?php echo number_format($subscription['amount'], 2); ?>/month</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <?php 
                        $status = ucfirst($subscription['status']);
                        if ($subscription['cancel_at_period_end']) {
                            $status .= ' (Cancelling)';
                        }
                        echo htmlspecialchars($status);
                        ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Current Period</span>
                    <span class="info-value">
                        <?php echo date('M j, Y', strtotime($subscription['current_period_start'])); ?> - 
                        <?php echo date('M j, Y', strtotime($subscription['current_period_end'])); ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Next Billing Date</span>
                    <span class="info-value">
                        <?php echo date('M j, Y', strtotime($subscription['current_period_end'])); ?>
                    </span>
                </div>
                
                <?php if (!$subscription['cancel_at_period_end']): ?>
                    <div style="margin-top: 2rem;">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel your subscription?');">
                            <button type="submit" name="cancel" class="btn btn-outline-danger w-100">
                                <i class="fas fa-times-circle"></i> Cancel Subscription
                            </button>
                        </form>
                        <p class="text-muted text-center mt-2" style="font-size: 0.9rem;">
                            You'll retain access until the end of your billing period
                        </p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-info-circle"></i> Your subscription will end on 
                        <?php echo date('M j, Y', strtotime($subscription['current_period_end'])); ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> You're currently on the free plan.
                </div>
                
                <div class="text-center mt-4">
                    <a href="payment.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-crown"></i> Upgrade to Premium
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="text-muted">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
