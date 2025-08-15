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

echo "<h2>Adding Completion Tracking Columns</h2>";
echo "<p><strong>Current User:</strong> {$_SESSION['full_name']} ({$_SESSION['role']})</p>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Check current table structure
    echo "<h3>Current Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
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
    
    // Check if columns already exist
    $existing_columns = array_column($columns, 'Field');
    $needs_completed_at = !in_array('completed_at', $existing_columns);
    $needs_completed_by = !in_array('completed_by', $existing_columns);
    
    if ($needs_completed_at || $needs_completed_by) {
        echo "<h3>Adding Missing Columns</h3>";
        
        if ($needs_completed_at) {
            echo "<p>Adding <code>completed_at</code> column...</p>";
            $stmt = $pdo->prepare("ALTER TABLE tasks ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ <code>completed_at</code> column added successfully!</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to add <code>completed_at</code> column</p>";
            }
        }
        
        if ($needs_completed_by) {
            echo "<p>Adding <code>completed_by</code> column...</p>";
            $stmt = $pdo->prepare("ALTER TABLE tasks ADD COLUMN completed_by INT(11) NULL DEFAULT NULL AFTER completed_at");
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ <code>completed_by</code> column added successfully!</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to add <code>completed_by</code> column</p>";
            }
        }
        
        // Try to add foreign key constraint
        echo "<p>Adding foreign key constraint for <code>completed_by</code>...</p>";
        try {
            $stmt = $pdo->prepare("ALTER TABLE tasks ADD CONSTRAINT fk_tasks_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL");
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Foreign key constraint added successfully!</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Foreign key constraint may already exist or couldn't be added</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Foreign key constraint: " . $e->getMessage() . "</p>";
        }
        
        // Update any existing completed tasks
        echo "<p>Updating existing completed tasks...</p>";
        $stmt = $pdo->prepare("UPDATE tasks SET completed_at = updated_at, completed_by = assigned_by WHERE status = 'completed' AND completed_at IS NULL");
        $result = $stmt->execute();
        $affected_rows = $stmt->rowCount();
        
        if ($result) {
            echo "<p style='color: green;'>✅ Updated {$affected_rows} existing completed tasks</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No existing completed tasks to update</p>";
        }
        
        echo "<hr>";
        echo "<h3>Updated Table Structure</h3>";
        
        // Show updated structure
        $stmt = $pdo->query("DESCRIBE tasks");
        $updated_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($updated_columns as $col) {
            $highlight = '';
            if (in_array($col['Field'], ['completed_at', 'completed_by'])) {
                $highlight = 'background: #d4edda; color: #155724;';
            }
            echo "<tr style='{$highlight}'>";
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
        echo "<p style='color: green;'>✅ All required columns already exist!</p>";
    }
    
    // Show sample data
    echo "<hr>";
    echo "<h3>Sample Task Data</h3>";
    $stmt = $pdo->query("SELECT id, title, status, completed_at, completed_by FROM tasks ORDER BY id LIMIT 5");
    $sample_tasks = $stmt->fetchAll();
    
    if (count($sample_tasks) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Completed At</th><th>Completed By</th></tr>";
        foreach ($sample_tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td><strong>{$task['status']}</strong></td>";
            echo "<td>{$task['completed_at'] ?: 'Not completed'}</td>";
            echo "<td>{$task['completed_by'] ?: 'Not completed'}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><a href='debug-status-update.php'>Test Status Updates Again</a></li>";
echo "<li><a href='simple-task-management.php'>Go to Simple Task Management</a></li>";
echo "<li><a href='task-management.php'>Go to Main Task Management</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "</ul>";
?>
