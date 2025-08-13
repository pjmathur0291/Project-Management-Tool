<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>Manual Task Completion Test</h2>";

// Get current tasks
$tasks = getAllTasks();
echo "<h3>Current Tasks:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>ID</th><th>Title</th><th>Status</th><th>Action</th>";
echo "</tr>";

foreach ($tasks as $task) {
    $statusColor = $task['status'] === 'completed' ? '#90EE90' : '#FFFFFF';
    echo "<tr style='background-color: $statusColor;'>";
    echo "<td>{$task['id']}</td>";
    echo "<td>{$task['title']}</td>";
    echo "<td><strong>{$task['status']}</strong></td>";
    echo "<td>";
    if ($task['status'] !== 'completed') {
        echo "<a href='?mark_complete={$task['id']}' style='color: green;'>Mark Complete</a>";
    } else {
        echo "<a href='?mark_pending={$task['id']}' style='color: orange;'>Mark Pending</a>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// Handle marking task as complete
if (isset($_GET['mark_complete'])) {
    $taskId = $_GET['mark_complete'];
    
    echo "<h3>Marking Task $taskId as Complete:</h3>";
    
    // Test direct database update
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $result = $stmt->execute([$taskId]);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Task marked as completed in database!</p>";
        
        // Verify the update
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $updatedTask = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($updatedTask) {
            echo "<p><strong>Updated Task:</strong></p>";
            echo "<ul>";
            echo "<li>ID: {$updatedTask['id']}</li>";
            echo "<li>Title: {$updatedTask['title']}</li>";
            echo "<li>Status: <strong style='color: green;'>{$updatedTask['status']}</strong></li>";
            echo "<li>Updated At: {$updatedTask['updated_at']}</li>";
            echo "</ul>";
        }
        
        // Test the getAllTasks function
        echo "<h4>Testing getAllTasks() after update:</h4>";
        $allTasks = getAllTasks();
        $completedTasks = array_filter($allTasks, function($task) {
            return $task['status'] === 'completed';
        });
        
        echo "<p><strong>Completed tasks found:</strong> " . count($completedTasks) . "</p>";
        foreach ($completedTasks as $task) {
            echo "<p>✅ {$task['title']} (ID: {$task['id']})</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Failed to mark task as completed.</p>";
    }
}

// Handle marking task as pending
if (isset($_GET['mark_pending'])) {
    $taskId = $_GET['mark_pending'];
    
    echo "<h3>Marking Task $taskId as Pending:</h3>";
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $result = $stmt->execute([$taskId]);
    
    if ($result) {
        echo "<p style='color: orange;'>🔄 Task marked as pending!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to mark task as pending.</p>";
    }
}

echo "<hr>";
echo "<h3>Test Actions:</h3>";
echo "<a href='test-manual-completion.php'>Refresh Page</a><br>";
echo "<a href='debug-task-filtering.php'>View Debug Info</a><br>";
echo "<a href='index.php'>Go to Main App</a>";

echo "<h3>What This Test Does:</h3>";
echo "<ol>";
echo "<li><strong>Shows all current tasks</strong> with their status</li>";
echo "<li><strong>Allows manual completion</strong> of any task</li>";
echo "<li><strong>Tests database update</strong> directly</li>";
echo "<li><strong>Verifies getAllTasks()</strong> returns completed tasks</li>";
echo "<li><strong>Tests the complete flow</strong> from database to function</li>";
echo "</ol>";
?>
