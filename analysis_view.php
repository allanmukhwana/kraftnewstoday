<?php
/**
 * Kraft News Today - Article Analysis View
 * Displays AI-powered analysis for premium users
 */

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('auth_login.php');
}

// Check if premium
if ($_SESSION['subscription_plan'] !== 'premium') {
    redirect('payment.php');
}

$article_id = intval($_GET['id'] ?? 0);
$pdo = get_db_connection();

// Get article
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$article_id]);
$article = $stmt->fetch();

if (!$article) {
    redirect('dashboard.php');
}

// Get analysis
$stmt = $pdo->prepare("
    SELECT * FROM article_analysis 
    WHERE article_id = ? 
    ORDER BY dimension
");
$stmt->execute([$article_id]);
$analyses = $stmt->fetchAll();

$page_title = 'AI Analysis';
include 'header.php';
?>

<style>
    .analysis-container {
        padding: 2rem 0;
        min-height: calc(100vh - 200px);
    }
    
    .article-header {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
    }
    
    .article-title {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    
    .article-meta {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    
    .analysis-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-md);
        border-left: 4px solid var(--primary-color);
    }
    
    .analysis-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .analysis-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .analysis-score {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary-color);
    }
    
    .analysis-text {
        color: var(--text-dark);
        line-height: 1.8;
        white-space: pre-line;
    }
    
    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .article-title {
            font-size: 1.5rem;
        }
    }
</style>

<div class="analysis-container">
    <div class="container">
        <div class="article-header">
            <div class="article-meta">
                <span class="badge" style="background: rgba(10, 73, 119, 0.1); color: var(--primary-color);">
                    <i class="fas fa-tag"></i>
                    <?php echo htmlspecialchars($GLOBALS['INDUSTRIES'][$article['industry_code']] ?? $article['industry_code']); ?>
                </span>
                <span class="badge" style="background: rgba(108, 117, 125, 0.1); color: var(--text-light);">
                    <i class="fas fa-globe"></i>
                    <?php echo htmlspecialchars($article['source']); ?>
                </span>
                <span class="badge" style="background: rgba(108, 117, 125, 0.1); color: var(--text-light);">
                    <i class="fas fa-clock"></i>
                    <?php echo date('M j, Y', strtotime($article['published_at'])); ?>
                </span>
            </div>
            
            <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
            
            <?php if (!empty($article['description'])): ?>
                <p style="color: var(--text-light); font-size: 1.1rem; margin-bottom: 1.5rem;">
                    <?php echo htmlspecialchars($article['description']); ?>
                </p>
            <?php endif; ?>
            
            <a href="<?php echo htmlspecialchars($article['url']); ?>" target="_blank" class="btn btn-primary">
                <i class="fas fa-external-link-alt"></i> Read Full Article
            </a>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem;">
            <i class="fas fa-brain"></i> AI-Powered Analysis
        </h2>
        
        <?php if (empty($analyses)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Analysis is being generated. Please check back soon.
            </div>
        <?php else: ?>
            <?php foreach ($analyses as $analysis): ?>
                <?php 
                $dimension_name = $GLOBALS['ANALYSIS_DIMENSIONS'][$analysis['dimension']] ?? $analysis['dimension'];
                $icons = [
                    'accuracy' => 'fa-check-circle',
                    'impact' => 'fa-chart-line',
                    'trends' => 'fa-trending-up',
                    'strategic' => 'fa-chess',
                    'technical' => 'fa-cogs',
                    'competitive' => 'fa-users',
                    'bias' => 'fa-balance-scale'
                ];
                $icon = $icons[$analysis['dimension']] ?? 'fa-star';
                ?>
                <div class="analysis-card">
                    <div class="analysis-header">
                        <div class="analysis-title">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <?php echo htmlspecialchars($dimension_name); ?>
                        </div>
                        <?php if ($analysis['score']): ?>
                            <div class="analysis-score">
                                <?php echo number_format($analysis['score'] * 100, 0); ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="analysis-text">
                        <?php echo nl2br(htmlspecialchars($analysis['analysis_text'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
