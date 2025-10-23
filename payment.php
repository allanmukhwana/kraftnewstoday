<?php
/**
 * Kraft News Today - Stripe Payment Integration
 * Handles premium subscription payments
 */

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('auth_login.php');
}

$user_id = $_SESSION['user_id'];
$pdo = get_db_connection();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Redirect if already premium
if ($user['subscription_plan'] === 'premium' && $user['subscription_status'] === 'active') {
    redirect('subscription.php');
}

$page_title = 'Upgrade to Premium';
include 'header.php';
?>

<style>
    .payment-container {
        padding: 2rem 0;
        min-height: calc(100vh - 200px);
    }
    
    .payment-card {
        background: white;
        border-radius: 16px;
        padding: 2.5rem;
        box-shadow: var(--shadow-md);
        max-width: 600px;
        margin: 0 auto;
    }
    
    .payment-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .payment-header h1 {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    
    .price-display {
        text-align: center;
        padding: 2rem;
        background: linear-gradient(135deg, var(--primary-color), #083a5f);
        border-radius: 12px;
        color: white;
        margin-bottom: 2rem;
    }
    
    .price-amount {
        font-size: 4rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }
    
    .price-amount span {
        font-size: 2rem;
    }
    
    .price-period {
        font-size: 1.25rem;
        opacity: 0.9;
    }
    
    .features-list {
        list-style: none;
        padding: 0;
        margin: 2rem 0;
    }
    
    .features-list li {
        padding: 0.75rem 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--text-dark);
    }
    
    .features-list li i {
        color: #28a745;
        font-size: 1.25rem;
    }
    
    #card-element {
        border: 2px solid var(--border-color);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    #card-errors {
        color: var(--secondary-color);
        margin-top: 0.5rem;
        font-size: 0.9rem;
    }
    
    .secure-badge {
        text-align: center;
        color: var(--text-light);
        font-size: 0.9rem;
        margin-top: 1rem;
    }
    
    .secure-badge i {
        color: #28a745;
    }
    
    @media (max-width: 768px) {
        .payment-card {
            padding: 1.5rem;
        }
        
        .price-amount {
            font-size: 3rem;
        }
    }
</style>

<div class="payment-container">
    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <h1><i class="fas fa-crown"></i> Upgrade to Premium</h1>
                <p>Unlock AI-powered insights and advanced features</p>
            </div>
            
            <div class="price-display">
                <div class="price-amount">
                    $<?php echo number_format(PREMIUM_PRICE, 2); ?><span>/mo</span>
                </div>
                <div class="price-period">Billed monthly • Cancel anytime</div>
            </div>
            
            <ul class="features-list">
                <li><i class="fas fa-check-circle"></i> AI-powered article analysis</li>
                <li><i class="fas fa-check-circle"></i> 7-dimensional insights</li>
                <li><i class="fas fa-check-circle"></i> Unlimited industry tracking</li>
                <li><i class="fas fa-check-circle"></i> Trend detection & predictions</li>
                <li><i class="fas fa-check-circle"></i> Strategic recommendations</li>
                <li><i class="fas fa-check-circle"></i> Competitive intelligence</li>
                <li><i class="fas fa-check-circle"></i> Priority email support</li>
            </ul>
            
            <form id="payment-form">
                <div class="mb-3">
                    <label for="card-element" class="form-label">
                        <i class="fas fa-credit-card"></i> Card Information
                    </label>
                    <div id="card-element">
                        <!-- Stripe Card Element will be inserted here -->
                    </div>
                    <div id="card-errors" role="alert"></div>
                </div>
                
                <button type="submit" class="btn btn-secondary w-100 py-3" id="submit-button">
                    <i class="fas fa-lock"></i> Subscribe Now - $<?php echo number_format(PREMIUM_PRICE, 2); ?>/month
                </button>
                
                <div class="secure-badge">
                    <i class="fas fa-shield-alt"></i> Secure payment powered by Stripe
                </div>
            </form>
            
            <div class="text-center mt-3">
                <a href="dashboard.php" class="text-muted">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
    // Initialize Stripe
    const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
    const elements = stripe.elements();
    
    // Create card element
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#1a1a1a',
                fontFamily: "'League Spartan', sans-serif",
                '::placeholder': {
                    color: '#6c757d'
                }
            },
            invalid: {
                color: '#ed1c24'
            }
        }
    });
    
    cardElement.mount('#card-element');
    
    // Handle real-time validation errors
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Handle form submission
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        showLoading();
        
        try {
            // Create payment method
            const {paymentMethod, error} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    email: '<?php echo $user['email']; ?>',
                    name: '<?php echo $user['full_name']; ?>'
                }
            });
            
            if (error) {
                throw new Error(error.message);
            }
            
            // Send payment method to server
            const response = await fetch('payment_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    payment_method_id: paymentMethod.id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Subscription activated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000);
            } else {
                throw new Error(result.message || 'Payment failed');
            }
            
        } catch (error) {
            hideLoading();
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-lock"></i> Subscribe Now - $<?php echo number_format(PREMIUM_PRICE, 2); ?>/month';
            
            const displayError = document.getElementById('card-errors');
            displayError.textContent = error.message;
            showToast(error.message, 'danger');
        }
    });
</script>

<?php include 'footer.php'; ?>
