<?php
// Basic error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if possible
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Database Fix Script</h2>";
echo "<p>This script will add the missing columns to your tasks table.</p>";

// Check if database config exists
if (!file_exists('config/database.php')) {
    echo "<p style='color: red;'>❌ Database config file not found!</p>";
    echo "<p>Make sure you're running this from the correct directory.</p>";
    exit;
}

// Include database config
try {
    require_once 'config/database.php';
    echo "<p>✅ Database config loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading database config: " . $e->getMessage() . "</p>";
    exit;
}

// Check if user is logged in (optional check)
$is_admin = false;
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $is_admin = ($_SESSION['role'] === 'admin');
    echo "<p><strong>User:</strong> {$_SESSION['full_name'] ?? 'Unknown'} ({$_SESSION['role']})</p>";
} else {
    echo "<p><strong>Note:</strong> Not logged in - proceeding anyway</p>";
}

try {
    // Get database connection
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Check current table structure
    echo "<h3>Current Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "<p style='color: red;'>❌ No columns found in tasks table!</p>";
        exit;
    }
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
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
    
    echo "<h3>Column Status</h3>";
    echo "<p><strong>completed_at:</strong> " . ($needs_completed_at ? '❌ Missing' : '✅ Exists') . "</p>";
    echo "<p><strong>completed_by:</strong> " . ($needs_completed_by ? '❌ Missing' : '✅ Exists') . "</p>";
    
    if ($needs_completed_at || $needs_completed_by) {
        echo "<h3>Adding Missing Columns</h3>";
        
        if ($needs_completed_at) {
            echo "<p>Adding <code>completed_at</code> column...</p>";
            try {
                $sql = "ALTER TABLE tasks ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ <code>completed_at</code> column added successfully!</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to add <code>completed_at</code> column</p>";
                    $error_info = $stmt->errorInfo();
                    echo "<p>Error: " . print_r($error_info, true) . "</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error adding <code>completed_at</code> column: " . $e->getMessage() . "</p>";
            }
        }
        
        if ($needs_completed_by) {
            echo "<p>Adding <code>completed_by</code> column...</p>";
            try {
                $sql = "ALTER TABLE tasks ADD COLUMN completed_by INT(11) NULL DEFAULT NULL AFTER completed_at";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ <code>completed_by</code> column added successfully!</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to add <code>completed_by</code> column</p>";
                    $error_info = $stmt->errorInfo();
                    echo "<p>Error: " . print_r($error_info, true) . "</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error adding <code>completed_by</code> column: " . $e->getMessage() . "</p>";
            }
        }
        
        // Try to add foreign key constraint (optional)
        if ($needs_completed_by) {
            echo "<p>Adding foreign key constraint for <code>completed_by</code>...</p>";
            try {
                $sql = "ALTER TABLE tasks ADD CONSTRAINT fk_tasks_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ Foreign key constraint added successfully!</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ Foreign key constraint may already exist or couldn't be added</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Foreign key constraint: " . $e->getMessage() . "</p>";
            }
        }
        
        // Update any existing completed tasks
        echo "<p>Updating existing completed tasks...</p>";
        try {
            $sql = "UPDATE tasks SET completed_at = updated_at, completed_by = assigned_by WHERE status = 'completed' AND (completed_at IS NULL OR completed_by IS NULL)";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute();
            $affected_rows = $stmt->rowCount();
            
            if ($result) {
                echo "<p style='color: green;'>✅ Updated {$affected_rows} existing completed tasks</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ No existing completed tasks to update</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Error updating existing tasks: " . $e->getMessage() . "</p>";
        }
        
        echo "<hr>";
        echo "<h3>Updated Table Structure</h3>";
        
        // Show updated structure
        try {
            $stmt = $pdo->query("DESCRIBE tasks");
            $updated_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
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
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error showing updated structure: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: green;'>✅ All required columns already exist!</p>";
    }
    
    // Show sample data
    echo "<hr>";
    echo "<h3>Sample Task Data</h3>";
    try {
        $stmt = $pdo->query("SELECT id, title, status, completed_at, completed_by FROM tasks ORDER BY id LIMIT 5");
        $sample_tasks = $stmt->fetchAll();
        
        if (count($sample_tasks) > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
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
        } else {
            echo "<p>No tasks found in the database.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error showing sample data: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre style='font-size: 10px;'>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><a href='debug-status-update.php'>Test Status Updates Again</a></li>";
echo "<li><a href='simple-task-management.php'>Go to Simple Task Management</a></li>";
echo "<li><a href='task-management.php'>Go to Main Task Management</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>Debug Information</h3>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Files in current directory:</strong></p>";
echo "<ul>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "<li>{$file}</li>";
    }
}
echo "</ul>";
?>
