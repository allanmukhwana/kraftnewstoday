<?php
/**
 * Kraft News Today - Cron Job: Email Digests
 * Run twice daily (7 AM and 7 PM) to send digests
 */

require_once 'config.php';
require_once 'email_sender.php';

// Determine digest type based on hour
$current_hour = date('G');
$digest_type = ($current_hour >= 6 && $current_hour < 12) ? 'morning' : 'evening';

// Log start
$start_time = microtime(true);
log_agent("Starting $digest_type digest job");

try {
    $pdo = get_db_connection();
    
    // Log agent task start
    $stmt = $pdo->prepare("
        INSERT INTO agent_logs (task_type, status, message)
        VALUES ('email', 'started', ?)
    ");
    $stmt->execute(["$digest_type digest job started"]);
    $log_id = $pdo->lastInsertId();
    
    // Get active users
    $stmt = $pdo->query("
        SELECT id FROM users 
        WHERE is_active = TRUE 
        AND subscription_status = 'active'
    ");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Send digests
    $sender = get_email_sender();
    $sent_count = 0;
    
    foreach ($users as $user_id) {
        if ($sender->sendDigest($user_id, $digest_type)) {
            $sent_count++;
        }
    }
    
    // Calculate execution time
    $execution_time = round(microtime(true) - $start_time, 2);
    
    // Update log
    $stmt = $pdo->prepare("
        UPDATE agent_logs 
        SET status = 'completed',
            message = 'Successfully sent digests',
            users_affected = ?,
            execution_time_seconds = ?
        WHERE id = ?
    ");
    $stmt->execute([$sent_count, $execution_time, $log_id]);
    
    log_agent("Digest completed: $sent_count emails in {$execution_time}s");
    
} catch (Exception $e) {
    log_error("Digest job failed: " . $e->getMessage());
    
    if (isset($log_id)) {
        $stmt = $pdo->prepare("
            UPDATE agent_logs 
            SET status = 'failed',
                error_details = ?
            WHERE id = ?
        ");
        $stmt->execute([$e->getMessage(), $log_id]);
    }
}
