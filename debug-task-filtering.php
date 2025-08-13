<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>Debug: Task Filtering Issue</h2>";

// Get all tasks from database
$tasks = getAllTasks();
echo "<h3>All Tasks in Database (Raw Data):</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Created</th><th>Updated</th>";
echo "</tr>";

foreach ($tasks as $task) {
    $statusColor = $task['status'] === 'completed' ? '#90EE90' : '#FFFFFF';
    echo "<tr style='background-color: $statusColor;'>";
    echo "<td>{$task['id']}</td>";
    echo "<td>{$task['title']}</td>";
    echo "<td><strong>{$task['status']}</strong></td>";
    echo "<td>{$task['priority']}</td>";
    echo "<td>{$task['created_at']}</td>";
    echo "<td>{$task['updated_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check specific completed tasks
echo "<h3>Completed Tasks Query:</h3>";
$pdo = getDBConnection();

// Direct SQL query for completed tasks
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'");
$stmt->execute();
$completedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "<p><strong>Total completed tasks in database:</strong> $completedCount</p>";

if ($completedCount > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'completed' ORDER BY updated_at DESC");
    $stmt->execute();
    $completedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #90EE90;'>";
    echo "<th>ID</th><th>Title</th><th>Status</th><th>Completed At</th>";
    echo "</tr>";
    
    foreach ($completedTasks as $task) {
        echo "<tr>";
        echo "<td>{$task['id']}</td>";
        echo "<td>{$task['title']}</td>";
        echo "<td><strong>{$task['status']}</strong></td>";
        echo "<td>{$task['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test the getAllTasks function specifically
echo "<h3>Testing getAllTasks() Function:</h3>";
$allTasks = getAllTasks();
$completedFromFunction = array_filter($allTasks, function($task) {
    return $task['status'] === 'completed';
});

echo "<p><strong>Completed tasks from getAllTasks():</strong> " . count($completedFromFunction) . "</p>";

if (count($completedFromFunction) > 0) {
    echo "<ul>";
    foreach ($completedFromFunction as $task) {
        echo "<li>ID: {$task['id']} - {$task['title']} - Status: {$task['status']}</li>";
    }
    echo "</ul>";
}

// Test API endpoint directly
echo "<h3>Testing API Endpoint:</h3>";
$url = "http://localhost/management-tool/api/tasks.php";
echo "API URL: $url<br>";

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
        echo "<p><strong>Completed tasks from API:</strong> $apiCompletedCount</p>";
        
        if ($apiCompletedCount > 0) {
            echo "<ul>";
            foreach ($data['tasks'] as $task) {
                if ($task['status'] === 'completed') {
                    echo "<li>ID: {$task['id']} - {$task['title']} - Status: {$task['status']}</li>";
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

// Check if there are any tasks at all
echo "<h3>Database Summary:</h3>";
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
$statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Status</th><th>Count</th>";
echo "</tr>";

foreach ($statusCounts as $status) {
    echo "<tr>";
    echo "<td>{$status['status']}</td>";
    echo "<td>{$status['count']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Debug Actions:</h3>";
echo "<a href='debug-task-filtering.php'>Refresh Debug Info</a><br>";
echo "<a href='index.php'>Go to Main App</a>";

echo "<h3>Possible Issues:</h3>";
echo "<ol>";
echo "<li><strong>No completed tasks in database:</strong> Check if tasks are actually being updated</li>";
echo "<li><strong>API not returning completed tasks:</strong> Check API response</li>";
echo "<li><strong>Frontend filtering issue:</strong> Check JavaScript console for errors</li>";
echo "<li><strong>Database connection issue:</strong> Check if updates are being saved</li>";
echo "</ol>";
?>
