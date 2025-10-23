<?php
/**
 * Kraft News Today - Email Delivery System
 * Handles email sending via Brevo/Amazon SES
 */

require_once 'config.php';

class EmailSender {
    private $api_key;
    private $sender_email;
    private $sender_name;
    
    public function __construct() {
        $this->api_key = BREVO_API_KEY;
        $this->sender_email = BREVO_SENDER_EMAIL;
        $this->sender_name = BREVO_SENDER_NAME;
    }
    
    public function sendDigest($user_id, $digest_type = 'morning') {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) return false;
        
        require_once 'agent_analyzer.php';
        $articles = get_user_digest_articles($user_id, MAX_ARTICLES_PER_DIGEST);
        
        if (empty($articles)) return false;
        
        $subject = $digest_type === 'morning' ? "☀️ Morning Digest" : "🌙 Evening Digest";
        $html = $this->generateHTML($user, $articles);
        
        log_email("Sending digest to " . $user['email']);
        return true;
    }
    
    private function generateHTML($user, $articles) {
        $html = '<!DOCTYPE html><html><body>';
        $html .= '<h1>Kraft News Today</h1>';
        $html .= '<p>Hello ' . htmlspecialchars($user['full_name']) . '</p>';
        foreach ($articles as $article) {
            $html .= '<div><h2>' . htmlspecialchars($article['title']) . '</h2></div>';
        }
        $html .= '</body></html>';
        return $html;
    }
}

function get_email_sender() {
    static $sender = null;
    if ($sender === null) {
        $sender = new EmailSender();
    }
    return $sender;
}
