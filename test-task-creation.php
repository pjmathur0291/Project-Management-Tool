<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in. Session data: " . print_r($_SESSION, true);
    exit;
}

echo "<h2>Debug Information</h2>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>User Role: " . ($_SESSION['role'] ?? 'Not set') . "</p>";

// Check if user can create tasks
$canCreateTasks = in_array($_SESSION['role'] ?? '', ['admin', 'manager']);
echo "<p>Can create tasks: " . ($canCreateTasks ? 'Yes' : 'No') . "</p>";

// Test database connection
try {
    $pdo = getDBConnection();
    echo "<p>Database connection: OK</p>";
    
    // Check if tasks table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
    if ($stmt->rowCount() > 0) {
        echo "<p>Tasks table: Exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE tasks");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Tasks table columns:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Tasks table: Does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// Test task creation function
if ($canCreateTasks) {
    echo "<h3>Testing Task Creation</h3>";
    
    $testData = [
        'title' => 'Test Task',
        'description' => 'This is a test task',
        'status' => 'pending',
        'priority' => 'medium',
        'project_id' => null,
        'assigned_to' => null,
        'assigned_by' => $_SESSION['user_id'],
        'due_date' => date('Y-m-d', strtotime('+1 week')),
        'estimated_hours' => 2.0
    ];
    
    try {
        $result = createTask($testData);
        if ($result) {
            echo "<p>Task creation test: SUCCESS</p>";
        } else {
            echo "<p>Task creation test: FAILED</p>";
        }
    } catch (Exception $e) {
        echo "<p>Task creation error: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?>
