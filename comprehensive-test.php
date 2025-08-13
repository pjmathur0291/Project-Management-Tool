<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>Comprehensive Task Completion Test</h2>";

// Step 1: Check current database state
echo "<h3>Step 1: Current Database State</h3>";
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tasks");
$totalTasks = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as completed FROM tasks WHERE status = 'completed'");
$completedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];

echo "<p><strong>Total tasks in database:</strong> $totalTasks</p>";
echo "<p><strong>Completed tasks in database:</strong> $completedTasks</p>";

// Step 2: Show all tasks
echo "<h3>Step 2: All Tasks in Database</h3>";
$tasks = getAllTasks();
if (count($tasks) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
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

// Step 3: Test API endpoint
echo "<h3>Step 3: Testing API Endpoint</h3>";
$url = "http://localhost/management-tool/api/tasks.php";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['tasks'])) {
        $apiCompletedCount = count(array_filter($data['tasks'], function($task) {
            return $task['status'] === 'completed';
        }));
        echo "<p><strong>API Response:</strong> " . count($data['tasks']) . " total tasks, $apiCompletedCount completed</p>";
        
        if ($apiCompletedCount > 0) {
            echo "<p><strong>Completed tasks from API:</strong></p><ul>";
            foreach ($data['tasks'] as $task) {
                if ($task['status'] === 'completed') {
                    echo "<li>ID: {$task['id']} - {$task['title']}</li>";
                }
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>API response error: " . htmlspecialchars($response) . "</p>";
    }
} else {
    echo "<p style='color: red;'>Failed to fetch from API</p>";
}

// Step 4: Handle task completion
if (isset($_GET['complete_task'])) {
    $taskId = $_GET['complete_task'];
    
    echo "<h3>Step 4: Completing Task $taskId</h3>";
    
    // Test direct database update
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $result = $stmt->execute([$taskId]);
    
    if ($result) {
        echo "<p style='color: green; font-size: 18px;'>✅ Task $taskId marked as completed in database!</p>";
        
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
        
        // Test getAllTasks function after update
        echo "<h4>Testing getAllTasks() after update:</h4>";
        $allTasks = getAllTasks();
        $completedFromFunction = array_filter($allTasks, function($task) {
            return $task['status'] === 'completed';
        });
        
        echo "<p><strong>Completed tasks from getAllTasks():</strong> " . count($completedFromFunction) . "</p>";
        foreach ($completedFromFunction as $task) {
            echo "<p>✅ {$task['title']} (ID: {$task['id']})</p>";
        }
        
        // Test API again
        echo "<h4>Testing API after update:</h4>";
        $response = file_get_contents($url, false, $context);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['tasks'])) {
                $apiCompletedCount = count(array_filter($data['tasks'], function($task) {
                    return $task['status'] === 'completed';
                }));
                echo "<p><strong>API now shows:</strong> $apiCompletedCount completed tasks</p>";
            }
        }
        
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ Failed to mark task as completed.</p>";
    }
}

// Step 5: Test JavaScript simulation
echo "<h3>Step 5: JavaScript Test</h3>";
echo "<div id='js-test'>";
echo "<p>Testing JavaScript functionality...</p>";
echo "<button onclick='testJavaScript()'>Test JavaScript</button>";
echo "<div id='js-result'></div>";
echo "</div>";

echo "<hr>";
echo "<h3>Test Actions:</h3>";
echo "<a href='comprehensive-test.php'>Refresh This Page</a><br>";
echo "<a href='index.php'>Go to Main App</a><br>";
echo "<a href='debug-task-filtering.php'>View Debug Info</a>";

echo "<script>";
echo "function testJavaScript() {";
echo "  const resultDiv = document.getElementById('js-result');";
echo "  resultDiv.innerHTML = '<p>Testing JavaScript...</p>';";
echo "  ";
echo "  // Test if we can find task elements";
echo "  const taskItems = document.querySelectorAll('.task-item');";
echo "  resultDiv.innerHTML += '<p>Found ' + taskItems.length + ' task items on this page</p>';";
echo "  ";
echo "  // Test if we can find completed tasks";
echo "  const completedTasks = document.querySelectorAll('[data-task-status=\"completed\"]');";
echo "  resultDiv.innerHTML += '<p>Found ' + completedTasks.length + ' completed tasks</p>';";
echo "  ";
echo "  // Test filter buttons";
echo "  const filterButtons = document.querySelectorAll('.task-filters button');";
echo "  resultDiv.innerHTML += '<p>Found ' + filterButtons.length + ' filter buttons</p>';";
echo "}";
echo "</script>";
?>
