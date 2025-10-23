<?php
/**
 * Kraft News Today - User Dashboard
 * Main dashboard showing personalized news feed
 */

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('auth_login.php');
}

$user_id = $_SESSION['user_id'];
$is_premium = $_SESSION['subscription_plan'] === 'premium';

// Get user's industries
$pdo = get_db_connection();
$stmt = $pdo->prepare("
    SELECT industry_code 
    FROM user_industries 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$user_industries = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get recent articles for user's industries
$articles = [];
if (!empty($user_industries)) {
    $placeholders = str_repeat('?,', count($user_industries) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM article_analysis WHERE article_id = a.id) as analysis_count
        FROM articles a
        WHERE a.industry_code IN ($placeholders)
        ORDER BY a.published_at DESC
        LIMIT 20
    ");
    $stmt->execute($user_industries);
    $articles = $stmt->fetchAll();
}

$page_title = 'Dashboard';
include 'header.php';
?>

<style>
    .dashboard-container {
        padding: 2rem 0;
        min-height: calc(100vh - 200px);
    }
    
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-color), #083a5f);
        color: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
    }
    
    .welcome-banner h1 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }
    
    .welcome-banner p {
        opacity: 0.9;
        margin-bottom: 0;
    }
    
    .stats-row {
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 2px solid var(--border-color);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        font-size: 1.5rem;
    }
    
    .stat-icon.primary {
        background: rgba(10, 73, 119, 0.1);
        color: var(--primary-color);
    }
    
    .stat-icon.secondary {
        background: rgba(237, 28, 36, 0.1);
        color: var(--secondary-color);
    }
    
    .stat-icon.success {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        color: var(--text-light);
        font-size: 0.9rem;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .section-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }
    
    .article-card {
        background: white;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 2px solid var(--border-color);
        transition: all 0.3s ease;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .article-card:hover {
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }
    
    .article-image {
        width: 100%;
        height: 250px;
        object-fit: cover;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    
    .article-content {
        padding: 1.5rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .article-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .article-meta {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    
    .article-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .article-badge.industry {
        background: rgba(10, 73, 119, 0.1);
        color: var(--primary-color);
    }
    
    .article-badge.source {
        background: rgba(108, 117, 125, 0.1);
        color: var(--text-light);
    }
    
    .article-badge.analyzed {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .article-badge.premium {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: var(--text-dark);
    }
    
    .article-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }
    
    .article-title a {
        color: inherit;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .article-title a:hover {
        color: var(--primary-color);
    }
    
    .article-description {
        color: var(--text-light);
        line-height: 1.6;
        margin-bottom: 1rem;
        flex: 1;
    }
    
    .article-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
    }
    
    .empty-state i {
        font-size: 4rem;
        color: var(--text-light);
        margin-bottom: 1.5rem;
    }
    
    .empty-state h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1rem;
    }
    
    .empty-state p {
        color: var(--text-light);
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .welcome-banner h1 {
            font-size: 1.5rem;
        }
        
        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .article-image {
            height: 200px;
        }
        
        .article-actions {
            flex-direction: column;
        }
        
        .article-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="dashboard-container">
    <div class="container">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1><i class="fas fa-wave-square"></i> Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p>
                <?php if ($is_premium): ?>
                    <i class="fas fa-crown"></i> Premium Member - Enjoying AI-powered insights
                <?php else: ?>
                    <i class="fas fa-info-circle"></i> Free Plan - <a href="payment.php" style="color: #ffd700; text-decoration: underline;">Upgrade to Premium</a> for AI analysis
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Stats Row -->
        <div class="row stats-row g-3">
            <div class="col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-value"><?php echo count($articles); ?></div>
                    <div class="stat-label">Articles Today</div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon secondary">
                        <i class="fas fa-industry"></i>
                    </div>
                    <div class="stat-value"><?php echo count($user_industries); ?></div>
                    <div class="stat-label">Industries Tracked</div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value">
                        <?php 
                        $analyzed_count = 0;
                        foreach ($articles as $article) {
                            if ($article['analysis_count'] > 0) $analyzed_count++;
                        }
                        echo $analyzed_count;
                        ?>
                    </div>
                    <div class="stat-label">AI Analyzed</div>
                </div>
            </div>
        </div>
        
        <?php if (empty($user_industries)): ?>
            <!-- Empty State - No Industries -->
            <div class="empty-state">
                <i class="fas fa-industry"></i>
                <h3>No Industries Selected</h3>
                <p>Start by selecting the industries you want to track</p>
                <a href="industries.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus-circle"></i> Add Industries
                </a>
            </div>
        <?php elseif (empty($articles)): ?>
            <!-- Empty State - No Articles -->
            <div class="empty-state">
                <i class="fas fa-hourglass-half"></i>
                <h3>No Articles Yet</h3>
                <p>Our AI agent is discovering news for your selected industries. Check back soon!</p>
                <a href="industries.php" class="btn btn-outline-primary">
                    <i class="fas fa-cog"></i> Manage Industries
                </a>
            </div>
        <?php else: ?>
            <!-- Articles Feed -->
            <div class="section-header">
                <h2><i class="fas fa-newspaper"></i> Your News Feed</h2>
                <div>
                    <a href="industries.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-cog"></i> Manage Industries
                    </a>
                </div>
            </div>
            
            <div class="row g-4">
            <?php foreach ($articles as $article): ?>
                <div class="col-lg-6">
                <div class="article-card">
                    <?php 
                    $article_image = $article['og_image'] ?? $article['featured_image'] ?? $article['image_url'] ?? 'https://placehold.co/1200x630/0a4977/ffffff?text=Kraft+News';
                    $article_description = $article['og_description'] ?? $article['description'] ?? 'No description available';
                    $article_title = $article['og_title'] ?? $article['title'];
                    ?>
                    <img src="<?php echo htmlspecialchars($article_image); ?>" 
                         alt="<?php echo htmlspecialchars($article_title); ?>" 
                         class="article-image"
                         onerror="this.src='https://placehold.co/1200x630/0a4977/ffffff?text=Kraft+News'">
                    
                    <div class="article-content">
                    <div class="article-meta">
                        <span class="article-badge industry">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($GLOBALS['INDUSTRIES'][$article['industry_code']] ?? $article['industry_code']); ?>
                        </span>
                        <span class="article-badge source">
                            <i class="fas fa-globe"></i>
                            <?php echo htmlspecialchars($article['source']); ?>
                        </span>
                        <span class="article-badge source">
                            <i class="fas fa-clock"></i>
                            <?php echo date('M j, Y', strtotime($article['published_at'])); ?>
                        </span>
                        <?php if ($article['analysis_count'] > 0): ?>
                            <span class="article-badge analyzed">
                                <i class="fas fa-brain"></i> AI Analyzed
                            </span>
                        <?php endif; ?>
                        <?php if (!$is_premium && $article['analysis_count'] > 0): ?>
                            <span class="article-badge premium">
                                <i class="fas fa-lock"></i> Premium
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="article-title">
                        <a href="<?php echo htmlspecialchars($article['url']); ?>" target="_blank">
                            <?php echo htmlspecialchars($article_title); ?>
                        </a>
                    </h3>
                    
                    <p class="article-description">
                        <?php 
                        $desc_text = strip_tags($article_description);
                        echo htmlspecialchars(strlen($desc_text) > 150 ? substr($desc_text, 0, 150) . '...' : $desc_text); 
                        ?>
                    </p>
                    
                    <div class="article-actions">
                        <a href="<?php echo htmlspecialchars($article['url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i> Read Article
                        </a>
                        <?php if ($is_premium && $article['analysis_count'] > 0): ?>
                            <a href="analysis_view.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-chart-line"></i> View AI Analysis
                            </a>
                        <?php elseif (!$is_premium && $article['analysis_count'] > 0): ?>
                            <a href="payment.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-crown"></i> Upgrade to View Analysis
                            </a>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
