<?php
/**
 * Kraft News Today - AI Analysis Orchestrator
 * Coordinates article analysis using Amazon Bedrock
 */

require_once 'config.php';
require_once 'agent_bedrock.php';

/**
 * Analyze unanalyzed articles for premium users
 */
function analyze_pending_articles($limit = 10) {
    $pdo = get_db_connection();
    $bedrock = get_bedrock_agent();
    
    // Get unanalyzed articles
    $stmt = $pdo->prepare("
        SELECT * FROM articles 
        WHERE is_analyzed = FALSE 
        ORDER BY published_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $articles = $stmt->fetchAll();
    
    $analyzed_count = 0;
    
    foreach ($articles as $article) {
        try {
            log_agent("Analyzing article: " . $article['title']);
            
            // Get industry name
            $industry_name = $GLOBALS['INDUSTRIES'][$article['industry_code']] ?? $article['industry_code'];
            
            // Perform multi-dimensional analysis
            $analyses = $bedrock->analyzeArticle(
                $article['title'],
                $article['content'],
                $industry_name
            );
            
            // Store analysis results
            $stmt = $pdo->prepare("
                INSERT INTO article_analysis 
                (article_id, dimension, analysis_text, score)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($analyses as $dimension => $analysis) {
                $analysis_text = $analysis['analysis'];
                if (!empty($analysis['key_points'])) {
                    $analysis_text .= "\n\nKey Points:\n- " . implode("\n- ", $analysis['key_points']);
                }
                
                $stmt->execute([
                    $article['id'],
                    $dimension,
                    $analysis_text,
                    $analysis['score'] ?? null
                ]);
            }
            
            // Mark article as analyzed
            $stmt = $pdo->prepare("UPDATE articles SET is_analyzed = TRUE WHERE id = ?");
            $stmt->execute([$article['id']]);
            
            $analyzed_count++;
            log_agent("Successfully analyzed article ID: " . $article['id']);
            
        } catch (Exception $e) {
            log_error("Failed to analyze article " . $article['id'] . ": " . $e->getMessage());
        }
    }
    
    return $analyzed_count;
}

/**
 * Analyze specific article by ID
 */
function analyze_article_by_id($article_id) {
    $pdo = get_db_connection();
    $bedrock = get_bedrock_agent();
    
    // Get article
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        return false;
    }
    
    try {
        // Get industry name
        $industry_name = $GLOBALS['INDUSTRIES'][$article['industry_code']] ?? $article['industry_code'];
        
        // Perform analysis
        $analyses = $bedrock->analyzeArticle(
            $article['title'],
            $article['content'],
            $industry_name
        );
        
        // Delete existing analysis
        $stmt = $pdo->prepare("DELETE FROM article_analysis WHERE article_id = ?");
        $stmt->execute([$article_id]);
        
        // Store new analysis
        $stmt = $pdo->prepare("
            INSERT INTO article_analysis 
            (article_id, dimension, analysis_text, score)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($analyses as $dimension => $analysis) {
            $analysis_text = $analysis['analysis'];
            if (!empty($analysis['key_points'])) {
                $analysis_text .= "\n\nKey Points:\n- " . implode("\n- ", $analysis['key_points']);
            }
            
            $stmt->execute([
                $article_id,
                $dimension,
                $analysis_text,
                $analysis['score'] ?? null
            ]);
        }
        
        // Mark as analyzed
        $stmt = $pdo->prepare("UPDATE articles SET is_analyzed = TRUE WHERE id = ?");
        $stmt->execute([$article_id]);
        
        return true;
        
    } catch (Exception $e) {
        log_error("Failed to analyze article $article_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Get articles for user's digest
 */
function get_user_digest_articles($user_id, $limit = 10) {
    $pdo = get_db_connection();
    
    // Get user's industries
    $stmt = $pdo->prepare("SELECT industry_code FROM user_industries WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $industries = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($industries)) {
        return [];
    }
    
    // Get recent articles for user's industries
    $placeholders = str_repeat('?,', count($industries) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM article_analysis WHERE article_id = a.id) as analysis_count
        FROM articles a
        WHERE a.industry_code IN ($placeholders)
        AND a.published_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY a.relevance_score DESC, a.published_at DESC
        LIMIT ?
    ");
    
    $params = array_merge($industries, [$limit]);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}
