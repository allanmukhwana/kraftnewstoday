<?php
/**
 * Kraft News Today - Admin Testing Panel
 * Allows manual triggering of agent tasks for testing
 */

require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    redirect('auth_login.php');
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM admin_users WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$is_admin = $stmt->fetchColumn() > 0;

if (!$is_admin) {
    die('Access denied. Admin privileges required.');
}

// Handle actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'scrape':
            require_once 'agent_scraper.php';
            $scraper = get_news_scraper();
            $count = $scraper->scrapeAllIndustries();
            $message = "Scraped $count articles successfully!";
            break;
            
        case 'analyze':
            require_once 'agent_analyzer.php';
            $count = analyze_pending_articles(10);
            $message = "Analyzed $count articles successfully!";
            break;
            
        case 'digest':
            require_once 'email_sender.php';
            $sender = get_email_sender();
            $sender->sendDigest($_SESSION['user_id'], 'morning');
            $message = "Test digest sent to your email!";
            break;
    }
}

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) FROM articles");
$total_articles = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_analyzed = TRUE");
$analyzed_articles = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE subscription_plan = 'premium'");
$premium_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT * FROM agent_logs ORDER BY created_at DESC LIMIT 10");
$recent_logs = $stmt->fetchAll();

$page_title = 'Admin Panel';
include 'header.php';
?>

<style>
    .admin-container {
        padding: 2rem 0;
    }
    
    .admin-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
    }
    
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-box {
        background: linear-gradient(135deg, var(--primary-color), #083a5f);
        color: white;
        padding: 1.5rem;
        border-radius: 12px;
        text-align: center;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }
    
    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .log-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .log-table th,
    .log-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    
    .log-table th {
        background: var(--primary-color);
        color: white;
        font-weight: 600;
    }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-completed {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .status-failed {
        background: rgba(237, 28, 36, 0.1);
        color: var(--secondary-color);
    }
</style>

<div class="admin-container">
    <div class="container">
        <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-tools"></i> Admin Testing Panel
        </h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <h2 style="margin-bottom: 1.5rem;">System Statistics</h2>
            <div class="stat-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_articles; ?></div>
                    <div>Total Articles</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $analyzed_articles; ?></div>
                    <div>Analyzed Articles</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $premium_users; ?></div>
                    <div>Premium Users</div>
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <h2 style="margin-bottom: 1.5rem;">Manual Actions</h2>
            <div class="action-grid">
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="scrape">
                    <button type="submit" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-globe"></i><br>Trigger Scraping
                    </button>
                </form>
                
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="analyze">
                    <button type="submit" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-brain"></i><br>Trigger Analysis
                    </button>
                </form>
                
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="digest">
                    <button type="submit" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-envelope"></i><br>Send Test Digest
                    </button>
                </form>
            </div>
        </div>
        
        <div class="admin-card">
            <h2 style="margin-bottom: 1.5rem;">Recent Agent Logs</h2>
            <div style="overflow-x: auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Task Type</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Processed</th>
                            <th>Time</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['task_type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $log['status']; ?>">
                                        <?php echo htmlspecialchars($log['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['message']); ?></td>
                                <td><?php echo $log['articles_processed']; ?></td>
                                <td><?php echo $log['execution_time_seconds']; ?>s</td>
                                <td><?php echo date('M j, H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
