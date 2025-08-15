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

echo "<h2>Comprehensive Status Update Debug</h2>";
echo "<p><strong>Current User:</strong> {$current_user['full_name']} ({$current_user['username']})</p>";
echo "<p><strong>User ID:</strong> {$current_user['id']}</p>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Show current task statuses
    echo "<h3>Current Task Statuses (Before Any Changes)</h3>";
    $stmt = $pdo->query("SELECT id, title, status, priority, created_at FROM tasks ORDER BY id LIMIT 10");
    $tasks = $stmt->fetchAll();
    
    if (count($tasks) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Created</th><th>Actions</th></tr>";
        foreach ($tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td><strong>{$task['status']}</strong></td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>{$task['created_at']}</td>";
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
        $action = $_POST['action'] ?? null;
        
        if ($task_id && $action) {
            echo "<h3>🔄 Processing Status Update...</h3>";
            echo "<p><strong>Task ID:</strong> {$task_id}</p>";
            echo "<p><strong>Action:</strong> {$action}</p>";
            
            // Get current task status before update
            $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            $current_status = $stmt->fetchColumn();
            echo "<p><strong>Current Status:</strong> {$current_status}</p>";
            
            switch ($action) {
                case 'complete':
                    echo "<p>Updating task {$task_id} from '{$current_status}' to 'completed'...</p>";
                    
                    $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', completed_at = NOW(), completed_by = ? WHERE id = ?");
                    $result = $stmt->execute([$current_user['id'], $task_id]);
                    
                    if ($result) {
                        echo "<p style='color: green;'>✅ Database update successful!</p>";
                        
                        // Verify the update
                        $stmt = $pdo->prepare("SELECT status, completed_at, completed_by FROM tasks WHERE id = ?");
                        $stmt->execute([$task_id]);
                        $updated_task = $stmt->fetch();
                        
                        echo "<p><strong>New Status:</strong> {$updated_task['status']}</p>";
                        echo "<p><strong>Completed At:</strong> {$updated_task['completed_at']}</p>";
                        echo "<p><strong>Completed By:</strong> {$updated_task['completed_by']}</p>";
                        
                    } else {
                        echo "<p style='color: red;'>❌ Database update failed!</p>";
                        $error_info = $stmt->errorInfo();
                        echo "<p>Error: " . print_r($error_info, true) . "</p>";
                    }
                    break;
                    
                case 'start':
                    echo "<p>Updating task {$task_id} from '{$current_status}' to 'in_progress'...</p>";
                    
                    $stmt = $pdo->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ?");
                    $result = $stmt->execute([$task_id]);
                    
                    if ($result) {
                        echo "<p style='color: green;'>✅ Database update successful!</p>";
                        
                        // Verify the update
                        $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
                        $stmt->execute([$task_id]);
                        $new_status = $stmt->fetchColumn();
                        echo "<p><strong>New Status:</strong> {$new_status}</p>";
                        
                    } else {
                        echo "<p style='color: red;'>❌ Database update failed!</p>";
                        $error_info = $stmt->errorInfo();
                        echo "<p>Error: " . print_r($error_info, true) . "</p>";
                    }
                    break;
            }
            
            echo "<hr>";
            echo "<h3>📊 Updated Task Counts</h3>";
            
            // Show updated task counts
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
                echo "<td><strong>{$status['status']}</strong></td>";
                echo "<td>{$status['count']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<hr>";
            echo "<h3>🔄 Refreshing Page in 3 seconds...</h3>";
            echo "<script>setTimeout(function(){ location.reload(); }, 3000);</script>";
        }
    }
    
    // Show final task statuses
    echo "<h3>Final Task Statuses</h3>";
    $stmt = $pdo->query("SELECT id, title, status, priority FROM tasks ORDER BY id LIMIT 10");
    $final_tasks = $stmt->fetchAll();
    
    if (count($final_tasks) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th></tr>";
        foreach ($final_tasks as $task) {
            $status_color = '';
            switch ($task['status']) {
                case 'completed': $status_color = 'background: #d4edda; color: #155724;'; break;
                case 'in_progress': $status_color = 'background: #cce5ff; color: #004085;'; break;
                case 'on_hold': $status_color = 'background: #f8d7da; color: #721c24;'; break;
                default: $status_color = 'background: #fff3cd; color: #856404;';
            }
            
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td style='{$status_color}; padding: 5px; border-radius: 3px;'><strong>{$task['status']}</strong></td>";
            echo "<td>{$task['priority']}</td>";
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
echo "<li><a href='task-management.php'>Go to Task Management System</a></li>";
echo "<li><a href='test-simple-query.php'>Test Simple Queries</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "</ul>";
?>
