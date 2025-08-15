<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h2>Not Logged In</h2>";
    echo "<p>You need to <a href='login.php'>log in</a> first.</p>";
    exit;
}

$current_user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'],
    'role' => $_SESSION['role']
];

echo "<h2>Testing Task Status Updates</h2>";
echo "<p><strong>Current User:</strong> {$current_user['full_name']} ({$current_user['username']})</p>";
echo "<p><strong>User ID:</strong> {$current_user['id']}</p>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Show current task statuses
    echo "<h3>Current Task Statuses</h3>";
    $stmt = $pdo->query("SELECT id, title, status, priority FROM tasks ORDER BY id LIMIT 5");
    $tasks = $stmt->fetchAll();
    
    if (count($tasks) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Actions</th></tr>";
        foreach ($tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>";
            if ($task['status'] !== 'completed') {
                echo "<form method='POST' style='display: inline;'>";
                echo "<input type='hidden' name='task_id' value='{$task['id']}'>";
                echo "<input type='hidden' name='action' value='complete'>";
                echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;'>Complete</button>";
                echo "</form> ";
            }
            if ($task['status'] === 'pending') {
                echo "<form method='POST' style='display: inline;'>";
                echo "<input type='hidden' name='task_id' value='{$task['id']}'>";
                echo "<input type='hidden' name='action' value='start'>";
                echo "<button type='submit' style='background: #17a2b8; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;'>Start</button>";
                echo "</form>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Handle status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $task_id = $_POST['task_id'] ?? null;
        
        if ($task_id) {
            echo "<h3>Updating Task Status...</h3>";
            
            switch ($_POST['action']) {
                case 'complete':
                    $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', completed_at = NOW(), completed_by = ? WHERE id = ?");
                    if ($stmt->execute([$current_user['id'], $task_id])) {
                        echo "<p style='color: green;'>✅ Task ID {$task_id} marked as completed!</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Failed to mark task as completed.</p>";
                    }
                    break;
                    
                case 'start':
                    $stmt = $pdo->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ?");
                    if ($stmt->execute([$task_id])) {
                        echo "<p style='color: green;'>✅ Task ID {$task_id} started!</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Failed to start task.</p>";
                    }
                    break;
            }
            
            // Refresh the page to show updated data
            echo "<script>setTimeout(function(){ location.reload(); }, 1000);</script>";
        }
    }
    
    // Show updated task counts
    echo "<h3>Updated Task Counts by Status</h3>";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status ORDER BY 
        CASE status 
            WHEN 'pending' THEN 1 
            WHEN 'in_progress' THEN 2 
            WHEN 'on_hold' THEN 3 
            WHEN 'completed' THEN 4 
        END");
    
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
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><a href='task-management.php'>Go to Task Management System</a></li>";
echo "<li><a href='test-simple-query.php'>Test Simple Queries</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "</ul>";
?>
