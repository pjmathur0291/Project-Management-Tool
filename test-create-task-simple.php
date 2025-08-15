<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Create Task Test</h2>";

// Check session
if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ No user session. <a href='login.php'>Login here</a></p>";
    exit;
}

echo "<p>✅ User logged in: {$_SESSION['user_id']} - {$_SESSION['full_name']} - {$_SESSION['role']}</p>";

// Check database
try {
    require_once 'config/database.php';
    $pdo = getDBConnection();
    echo "<p>✅ Database connected</p>";
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Check functions
try {
    require_once 'includes/functions.php';
    echo "<p>✅ Functions loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ Functions error: " . $e->getMessage() . "</p>";
    exit;
}

// Check if createTask function exists
if (function_exists('createTask')) {
    echo "<p>✅ createTask function exists</p>";
} else {
    echo "<p>❌ createTask function not found</p>";
}

// Check if tasks table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tasks table exists</p>";
    } else {
        echo "<p>❌ Tasks table not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking tasks table: " . $e->getMessage() . "</p>";
}

// Check if notifications table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Notifications table exists</p>";
    } else {
        echo "<p>⚠️ Notifications table not found (this is OK for now)</p>";
    }
} catch (Exception $e) {
    echo "<p>⚠️ Error checking notifications table: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='create-task.php'>Try Create Task Page</a></p>";
echo "<p><a href='index.php'>Back to Dashboard</a></p>";
?>
