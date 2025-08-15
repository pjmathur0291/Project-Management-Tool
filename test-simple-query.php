<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h2>Not Logged In</h2>";
    echo "<p>You need to <a href='login.php'>log in</a> first.</p>";
    exit;
}

echo "<h2>Testing Simple SELECT Query</h2>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Test 1: Simple SELECT all tasks
    echo "<h3>Test 1: SELECT * FROM tasks</h3>";
    $stmt = $pdo->query("SELECT * FROM tasks");
    $all_tasks = $stmt->fetchAll();
    echo "<p>Found " . count($all_tasks) . " tasks</p>";
    
    if (count($all_tasks) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Created</th></tr>";
        foreach ($all_tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>{$task['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: SELECT with WHERE clause
    echo "<h3>Test 2: SELECT * FROM tasks WHERE status = 'pending'</h3>";
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = ?");
    $stmt->execute(['pending']);
    $pending_tasks = $stmt->fetchAll();
    echo "<p>Found " . count($pending_tasks) . " pending tasks</p>";
    
    // Test 3: Task counts by status
    echo "<h3>Test 3: SELECT status, COUNT(*) FROM tasks GROUP BY status</h3>";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
    $status_counts = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    foreach ($status_counts as $status) {
        echo "<tr>";
        echo "<td>{$status['status']}</td>";
        echo "<td>{$status['count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 4: ORDER BY query
    echo "<h3>Test 4: SELECT with ORDER BY</h3>";
    $sql = "SELECT * FROM tasks ORDER BY 
            CASE status 
                WHEN 'pending' THEN 1 
                WHEN 'in_progress' THEN 2 
                WHEN 'on_hold' THEN 3 
                WHEN 'completed' THEN 4 
            END,
            priority DESC,
            created_at DESC";
    
    $stmt = $pdo->query($sql);
    $ordered_tasks = $stmt->fetchAll();
    echo "<p>Found " . count($ordered_tasks) . " tasks with ordering</p>";
    
    if (count($ordered_tasks) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Created</th></tr>";
        foreach ($ordered_tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>{$task['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><a href='task-management.php'>Go to Task Management</a></li>";
echo "<li><a href='insert-sample-tasks.php'>Insert Sample Tasks</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "</ul>";
?>
