<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>Simple Task Completion Test</h2>";

// Handle direct completion via GET parameter
if (isset($_GET['complete_task'])) {
    $taskId = $_GET['complete_task'];
    
    echo "<h3>Completing Task $taskId:</h3>";
    
    // Direct database update
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $result = $stmt->execute([$taskId]);
    
    if ($result) {
        echo "<p style='color: green; font-size: 18px;'>✅ Task $taskId marked as completed!</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ Failed to complete task $taskId</p>";
    }
}

// Get all tasks
$tasks = getAllTasks();
echo "<h3>All Tasks:</h3>";

if (count($tasks) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 14px;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Action</th>";
    echo "</tr>";
    
    foreach ($tasks as $task) {
        $statusColor = $task['status'] === 'completed' ? '#90EE90' : '#FFFFFF';
        echo "<tr style='background-color: $statusColor;'>";
        echo "<td>{$task['id']}</td>";
        echo "<td>{$task['title']}</td>";
        echo "<td><strong>{$task['status']}</strong></td>";
        echo "<td>{$task['priority']}</td>";
        echo "<td>";
        if ($task['status'] !== 'completed') {
            echo "<a href='?complete_task={$task['id']}' style='color: green; font-weight: bold;'>Complete</a>";
        } else {
            echo "<span style='color: green;'>✅ Completed</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No tasks found in database.</p>";
}

// Show completed tasks count
$completedCount = count(array_filter($tasks, function($task) {
    return $task['status'] === 'completed';
}));

echo "<h3>Summary:</h3>";
echo "<p><strong>Total tasks:</strong> " . count($tasks) . "</p>";
echo "<p><strong>Completed tasks:</strong> $completedCount</p>";
echo "<p><strong>Pending tasks:</strong> " . (count($tasks) - $completedCount) . "</p>";

echo "<hr>";
echo "<h3>Test Steps:</h3>";
echo "<ol>";
echo "<li><strong>Click 'Complete'</strong> on any task above</li>";
echo "<li><strong>Check if it turns green</strong> and shows '✅ Completed'</li>";
echo "<li><strong>Go to main app</strong> and check the 'Completed' filter</li>";
echo "</ol>";

echo "<h3>Links:</h3>";
echo "<a href='test-simple-completion.php'>Refresh This Page</a><br>";
echo "<a href='index.php'>Go to Main App</a><br>";
echo "<a href='debug-task-filtering.php'>View Debug Info</a>";
?>
