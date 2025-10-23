<?php
/**
 * Kraft News Today - Logout Handler
 * Destroys user session and redirects to home page
 */

require_once 'config.php';

// Log the logout
if (isset($_SESSION['user_email'])) {
    log_agent("User logged out: " . $_SESSION['user_email']);
}

// Destroy session
session_unset();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to home page
redirect('index.php');
