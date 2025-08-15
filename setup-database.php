<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<h2>Access Denied</h2>";
    echo "<p>Only administrators can set up the database.</p>";
    echo "<p><a href='login.php'>Login as Admin</a></p>";
    exit();
}

echo "<h2>Database Setup for Task Management System</h2>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Read and execute the SQL setup file
    $sql_file = 'setup-task-tables.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        echo "<h3>Executing SQL statements...</h3>";
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !str_starts_with($statement, '--')) {
                try {
                    $pdo->exec($statement);
                    echo "<p>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
        
        echo "<h3>Database setup completed!</h3>";
        
        // Verify the tables were created
        $tables = ['tasks', 'task_history', 'task_comments'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p>✅ Table '$table' exists</p>";
            } else {
                echo "<p>❌ Table '$table' missing</p>";
            }
        }
        
        // Check task count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
        $result = $stmt->fetch();
        echo "<p>📋 Total tasks in system: " . $result['count'] . "</p>";
        
        echo "<h3>Next Steps:</h3>";
        echo "<ul>";
        echo "<li><a href='task-management.php'>Go to Task Management System</a></li>";
        echo "<li><a href='create-task.php'>Create Your First Task</a></li>";
        echo "<li><a href='index.php'>Return to Dashboard</a></li>";
        echo "</ul>";
        
    } else {
        echo "<p>❌ SQL setup file not found: $sql_file</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
