<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    try {
        logActivity($_SESSION['user_id'], 'logged_out', 'user', $_SESSION['user_id']);
    } catch (Exception $e) {
        // Continue with logout even if logging fails
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>
