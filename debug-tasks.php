<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

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

echo "<h2>Task Data Debug Information</h2>";
echo "<p><strong>Current User:</strong> {$current_user['full_name']} ({$current_user['username']})</p>";
echo "<p><strong>User ID:</strong> {$current_user['id']}</p>";
echo "<p><strong>User Role:</strong> {$current_user['role']}</p>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Check if tasks table exists and has data
    echo "<h3>Tasks Table Status</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tasks table exists</p>";
        
        // Check table structure
        echo "<h4>Table Structure:</h4>";
        $stmt = $pdo->query("DESCRIBE tasks");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check total task count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
        $result = $stmt->fetch();
        echo "<p><strong>Total tasks in table:</strong> {$result['count']}</p>";
        
        if ($result['count'] > 0) {
            // Show sample tasks
            echo "<h4>Sample Tasks:</h4>";
            $stmt = $pdo->query("SELECT * FROM tasks LIMIT 5");
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Assigned By</th></tr>";
            foreach ($tasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>{$task['title']}</td>";
                echo "<td>{$task['status']}</td>";
                echo "<td>{$task['priority']}</td>";
                echo "<td>{$task['assigned_by']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Test the exact query from task-management.php
            echo "<h3>Testing Task Management Query</h3>";
            
            $where_clause = "";
            $params = [];
            $current_tab = 'all';
            
            if ($current_tab !== 'all') {
                $where_clause = "WHERE t.status = ?";
                $params[] = $current_tab;
            }
            
            // Add user role restriction
            if ($current_user['role'] !== 'admin' && $current_user['role'] !== 'manager') {
                if ($where_clause) {
                    $where_clause .= " AND t.assigned_to = ?";
                } else {
                    $where_clause = "WHERE t.assigned_to = ?";
                }
                $params[] = $current_user['id'];
            }
            
            $sql = "
                SELECT t.*, p.name as project_name, u.full_name as assignee_name, 
                       cb.full_name as completed_by_name
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assigned_to = u.id 
                LEFT JOIN users cb ON t.completed_by = cb.id 
                $where_clause
                ORDER BY 
                    CASE t.status 
                        WHEN 'pending' THEN 1 
                        WHEN 'in_progress' THEN 2 
                        WHEN 'on_hold' THEN 3 
                        WHEN 'completed' THEN 4 
                    END,
                    t.priority DESC,
                    t.due_date ASC,
                    t.created_at DESC
            ";
            
            echo "<h4>SQL Query:</h4>";
            echo "<pre>" . htmlspecialchars($sql) . "</pre>";
            
            echo "<h4>Parameters:</h4>";
            echo "<pre>" . print_r($params, true) . "</pre>";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll();
            
            echo "<p><strong>Query returned:</strong> " . count($tasks) . " tasks</p>";
            
            if (count($tasks) > 0) {
                echo "<h4>Query Results:</h4>";
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Project</th><th>Assignee</th></tr>";
                foreach ($tasks as $task) {
                    echo "<tr>";
                    echo "<td>{$task['id']}</td>";
                    echo "<td>{$task['title']}</td>";
                    echo "<td>{$task['status']}</td>";
                    echo "<td>{$task['project_name']}</td>";
                    echo "<td>{$task['assignee_name']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Test task counts query
            echo "<h3>Testing Task Counts Query</h3>";
            $count_sql = "SELECT status, COUNT(*) as count FROM tasks";
            if ($current_user['role'] !== 'admin' && $current_user['role'] !== 'manager') {
                $count_sql .= " WHERE assigned_to = ?";
                $count_stmt = $pdo->prepare($count_sql);
                $count_stmt->execute([$current_user['id']]);
            } else {
                $count_stmt = $pdo->query($count_sql);
            }
            
            $task_counts = [];
            while ($row = $count_stmt->fetch()) {
                $task_counts[$row['status']] = $row['count'];
            }
            
            echo "<h4>Task Counts:</h4>";
            echo "<pre>" . print_r($task_counts, true) . "</pre>";
            
        } else {
            echo "<p>⚠️ No tasks found in the table</p>";
            echo "<p>This might be because:</p>";
            echo "<ul>";
            echo "<li>The sample data wasn't inserted</li>";
            echo "<li>The table is empty</li>";
            echo "<li>There was an error during setup</li>";
            echo "</ul>";
        }
        
    } else {
        echo "<p>❌ Tasks table does not exist</p>";
    }
    
    // Check other related tables
    echo "<h3>Related Tables Status</h3>";
    $tables = ['projects', 'users'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count_result = $count_stmt->fetch();
            echo "<p>✅ Table '$table' exists with {$count_result['count']} records</p>";
        } else {
            echo "<p>❌ Table '$table' missing</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><a href='task-management.php'>Go to Task Management</a></li>";
echo "<li><a href='create-task.php'>Create a Test Task</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "</ul>";
?>
