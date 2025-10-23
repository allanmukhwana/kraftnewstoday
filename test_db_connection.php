<?php
/**
 * Test Database Connection
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Testing Database Connection ===\n\n";

// Load config
require_once 'config.php';

echo "Database Configuration:\n";
echo "  Host: " . DB_HOST . "\n";
echo "  Database: " . DB_NAME . "\n";
echo "  User: " . DB_USER . "\n";
echo "  Password: " . (empty(DB_PASS) ? '(empty)' : '***hidden***') . "\n";
echo "  Charset: " . DB_CHARSET . "\n\n";

echo "Attempting connection...\n";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✓ Connection successful!\n\n";
    
    // Test query
    echo "Testing query...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM articles");
    $result = $stmt->fetch();
    echo "✓ Articles table exists. Total articles: {$result['count']}\n\n";
    
    // Check user_industries table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_industries");
    $result = $stmt->fetch();
    echo "✓ User industries table exists. Total entries: {$result['count']}\n\n";
    
    if ($result['count'] == 0) {
        echo "⚠ WARNING: No industries are being tracked!\n";
        echo "   The scraper won't scrape anything because user_industries table is empty.\n";
        echo "   You need to add industries for users to track.\n\n";
    }
    
    // Show tracked industries
    $stmt = $pdo->query("SELECT DISTINCT industry_code FROM user_industries");
    $industries = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($industries) > 0) {
        echo "Currently tracked industries:\n";
        foreach ($industries as $code) {
            echo "  - $code\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n\n";
    
    echo "Common issues:\n";
    echo "1. MySQL server not running\n";
    echo "2. Wrong database credentials\n";
    echo "3. Database doesn't exist\n";
    echo "4. User doesn't have permissions\n";
}

echo "\n=== Test Complete ===\n";
