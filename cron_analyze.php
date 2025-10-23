<?php
/**
 * Kraft News Today - Cron Job: AI Analysis
 * Run hourly to analyze new articles
 */

require_once 'config.php';
require_once 'agent_analyzer.php';

// Log start
$start_time = microtime(true);
log_agent("Starting AI analysis job");

try {
    $pdo = get_db_connection();
    
    // Log agent task start
    $stmt = $pdo->prepare("
        INSERT INTO agent_logs (task_type, status, message)
        VALUES ('analyze', 'started', 'AI analysis job started')
    ");
    $stmt->execute();
    $log_id = $pdo->lastInsertId();
    
    // Analyze articles
    $analyzed_count = analyze_pending_articles(20);
    
    // Calculate execution time
    $execution_time = round(microtime(true) - $start_time, 2);
    
    // Update log
    $stmt = $pdo->prepare("
        UPDATE agent_logs 
        SET status = 'completed',
            message = 'Successfully analyzed articles',
            articles_processed = ?,
            execution_time_seconds = ?
        WHERE id = ?
    ");
    $stmt->execute([$analyzed_count, $execution_time, $log_id]);
    
    log_agent("Analysis completed: $analyzed_count articles in {$execution_time}s");
    
} catch (Exception $e) {
    log_error("Analysis job failed: " . $e->getMessage());
    
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
