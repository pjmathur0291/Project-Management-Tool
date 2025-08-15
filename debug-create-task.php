<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug: Create Task Page</h2>";

// Check session
echo "<h3>Session Check:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in: {$_SESSION['user_id']} - {$_SESSION['full_name']} - {$_SESSION['role']}<br>";
} else {
    echo "❌ No user session found<br>";
    echo "<a href='login.php'>Login here</a><br>";
    exit;
}

// Check database connection
echo "<h3>Database Connection Check:</h3>";
try {
    require_once 'config/database.php';
    $pdo = getDBConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Check if NotificationManager exists
echo "<h3>NotificationManager Check:</h3>";
$notification_file = 'includes/NotificationManager.php';
if (file_exists($notification_file)) {
    echo "✅ NotificationManager.php file exists<br>";
    try {
        require_once $notification_file;
        $notificationManager = new NotificationManager($pdo);
        echo "✅ NotificationManager class instantiated successfully<br>";
    } catch (Exception $e) {
        echo "❌ Error creating NotificationManager: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "❌ NotificationManager.php file not found<br>";
}

// Check if notifications table exists
echo "<h3>Database Tables Check:</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Notifications table exists<br>";
    } else {
        echo "❌ Notifications table does not exist<br>";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'notification_preferences'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Notification preferences table exists<br>";
    } else {
        echo "❌ Notification preferences table does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
}

// Check if functions.php exists and works
echo "<h3>Functions Check:</h3>";
if (file_exists('includes/functions.php')) {
    echo "✅ functions.php file exists<br>";
    try {
        require_once 'includes/functions.php';
        echo "✅ functions.php loaded successfully<br>";
    } catch (Exception $e) {
        echo "❌ Error loading functions.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ functions.php file not found<br>";
}

// Check if createTask function exists
echo "<h3>createTask Function Check:</h3>";
if (function_exists('createTask')) {
    echo "✅ createTask function exists<br>";
} else {
    echo "❌ createTask function not found<br>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='setup-notifications.php'>Setup Notifications Tables</a></li>";
echo "<li><a href='create-task.php'>Try Create Task Again</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "</ul>";
?>
