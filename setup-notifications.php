<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<h2>Access Denied</h2>";
    echo "<p>You need to be logged in as an admin to run this script.</p>";
    echo "<p><a href='login.php'>Login</a> | <a href='index.php'>Dashboard</a></p>";
    exit;
}

echo "<h2>Setting Up Notification System</h2>";
echo "<p><strong>Current User:</strong> {$_SESSION['full_name']} ({$_SESSION['role']})</p>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Read and execute the SQL file
    $sql_file = 'setup-notifications.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        $statements = explode(';', $sql_content);
        
        echo "<h3>Creating Notification Tables</h3>";
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $stmt = $pdo->prepare($statement);
                    if ($stmt->execute()) {
                        echo "<p style='color: green;'>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ Statement executed but may have warnings</p>";
                    }
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
                }
            }
        }
        
        echo "<hr>";
        echo "<h3>Verifying Tables Created</h3>";
        
        // Check if tables exist
        $tables = ['notifications', 'notification_preferences'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "<p style='color: green;'>✅ Table '$table' exists</p>";
                    
                    // Show table structure
                    $stmt = $pdo->query("DESCRIBE $table");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
                    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                    foreach ($columns as $col) {
                        echo "<tr>";
                        echo "<td>{$col['Field']}</td>";
                        echo "<td>{$col['Type']}</td>";
                        echo "<td>{$col['Null']}</td>";
                        echo "<td>{$col['Key']}</td>";
                        echo "<td>{$col['Default']}</td>";
                        echo "<td>{$col['Extra']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='color: red;'>❌ Table '$table' not found</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
            }
        }
        
        // Check notification preferences for users
        echo "<hr>";
        echo "<h3>Notification Preferences Status</h3>";
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM notification_preferences");
            $count = $stmt->fetchColumn();
            echo "<p><strong>Users with notification preferences:</strong> $count</p>";
            
            if ($count > 0) {
                $stmt = $pdo->query("SELECT np.*, u.username, u.full_name FROM notification_preferences np JOIN users u ON np.user_id = u.id LIMIT 5");
                $prefs = $stmt->fetchAll();
                
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
                echo "<tr><th>User</th><th>Email</th><th>Browser</th><th>Task Assigned</th><th>Task Updated</th><th>Task Completed</th></tr>";
                foreach ($prefs as $pref) {
                    echo "<tr>";
                    echo "<td>{$pref['full_name']} ({$pref['username']})</td>";
                    echo "<td>" . ($pref['email_notifications'] ? '✅' : '❌') . "</td>";
                    echo "<td>" . ($pref['browser_notifications'] ? '✅' : '❌') . "</td>";
                    echo "<td>" . ($pref['task_assigned'] ? '✅' : '❌') . "</td>";
                    echo "<td>" . ($pref['task_updated'] ? '✅' : '❌') . "</td>";
                    echo "<td>" . ($pref['task_completed'] ? '✅' : '❌') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error checking notification preferences: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ SQL file '$sql_file' not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre style='font-size: 10px;'>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><a href='create-task.php'>Create a Task (to test notifications)</a></li>";
echo "<li><a href='notifications.php'>View Notifications</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "</ul>";
?>
