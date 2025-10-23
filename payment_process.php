<?php
/**
 * Kraft News Today - Payment Processing Backend
 * Handles Stripe subscription creation
 */

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Not authenticated'], 401);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$payment_method_id = $input['payment_method_id'] ?? '';

if (empty($payment_method_id)) {
    json_response(['success' => false, 'message' => 'Payment method is required'], 400);
}

$user_id = $_SESSION['user_id'];
$pdo = get_db_connection();

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        json_response(['success' => false, 'message' => 'User not found'], 404);
    }
    
    // Check if Stripe keys are configured
    if (empty(STRIPE_SECRET_KEY) || empty(STRIPE_PRICE_ID)) {
        log_error("Stripe not configured properly");
        json_response(['success' => false, 'message' => 'Payment system not configured. Please contact support.'], 500);
    }
    
    // Initialize Stripe (requires Stripe PHP library)
    // For demo purposes, we'll simulate the subscription creation
    // In production, you would use: \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Simulate Stripe customer and subscription creation
    $stripe_customer_id = 'cus_' . bin2hex(random_bytes(12));
    $stripe_subscription_id = 'sub_' . bin2hex(random_bytes(12));
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update user subscription
    $stmt = $pdo->prepare("
        UPDATE users 
        SET subscription_plan = 'premium',
            subscription_status = 'active',
            stripe_customer_id = ?,
            stripe_subscription_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$stripe_customer_id, $stripe_subscription_id, $user_id]);
    
    // Create subscription record
    $current_period_start = date('Y-m-d H:i:s');
    $current_period_end = date('Y-m-d H:i:s', strtotime('+1 month'));
    
    $stmt = $pdo->prepare("
        INSERT INTO subscriptions 
        (user_id, stripe_subscription_id, stripe_customer_id, plan_name, amount, 
         currency, status, current_period_start, current_period_end)
        VALUES (?, ?, ?, 'premium', ?, 'USD', 'active', ?, ?)
    ");
    $stmt->execute([
        $user_id, 
        $stripe_subscription_id, 
        $stripe_customer_id, 
        PREMIUM_PRICE,
        $current_period_start,
        $current_period_end
    ]);
    
    $pdo->commit();
    
    // Update session
    $_SESSION['subscription_plan'] = 'premium';
    
    log_agent("User $user_id subscribed to premium plan");
    
    json_response([
        'success' => true,
        'message' => 'Subscription activated successfully'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error("Payment processing failed: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Payment processing failed'], 500);
}

/* 
 * PRODUCTION IMPLEMENTATION WITH STRIPE PHP SDK:
 * 
 * require_once 'vendor/autoload.php';
 * \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
 * 
 * try {
 *     // Create or retrieve customer
 *     $customer = \Stripe\Customer::create([
 *         'email' => $user['email'],
 *         'name' => $user['full_name'],
 *         'payment_method' => $payment_method_id,
 *         'invoice_settings' => [
 *             'default_payment_method' => $payment_method_id
 *         ]
 *     ]);
 *     
 *     // Create subscription
 *     $subscription = \Stripe\Subscription::create([
 *         'customer' => $customer->id,
 *         'items' => [['price' => STRIPE_PRICE_ID]],
 *         'expand' => ['latest_invoice.payment_intent']
 *     ]);
 *     
 *     // Update database with real IDs
 *     // ... database update code ...
 *     
 * } catch (\Stripe\Exception\CardException $e) {
 *     json_response(['success' => false, 'message' => $e->getError()->message], 400);
 * }
 */
