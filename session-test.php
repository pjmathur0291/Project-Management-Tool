<?php
session_start();

echo "<h2>Session Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
echo "<p>Session save path: " . session_save_path() . "</p>";

echo "<h3>Session Data:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>Cookies:</h3>";
echo "<pre>" . print_r($_COOKIE, true) . "</pre>";

echo "<h3>PHP Info:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Session name: " . session_name() . "</p>";

// Test setting a session value
$_SESSION['test_value'] = 'test_' . time();
echo "<p>Set test session value: " . $_SESSION['test_value'] . "</p>";

// Test if we can read it back
echo "<p>Read test session value: " . ($_SESSION['test_value'] ?? 'NOT FOUND') . "</p>";

// Check if session file exists
$sessionFile = session_save_path() . '/sess_' . session_id();
echo "<p>Session file path: " . $sessionFile . "</p>";
echo "<p>Session file exists: " . (file_exists($sessionFile) ? 'Yes' : 'No') . "</p>";

// Test database connection
try {
    require_once 'config/database.php';
    $pdo = getDBConnection();
    echo "<p>Database connection: OK</p>";
    
    // Check if users table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>Users in database: " . $result['count'] . "</p>";
    
    // Show a sample user
    $stmt = $pdo->query("SELECT id, username, role FROM users LIMIT 1");
    $user = $stmt->fetch();
    if ($user) {
        echo "<p>Sample user: ID=" . $user['id'] . ", Username=" . $user['username'] . ", Role=" . $user['role'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}
?>
