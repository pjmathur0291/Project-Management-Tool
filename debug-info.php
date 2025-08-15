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

echo "<h2>Debug Information for Task Creation</h2>";
echo "<p><strong>Current User:</strong> {$current_user['full_name']} ({$current_user['username']})</p>";
echo "<p><strong>User ID:</strong> {$current_user['id']}</p>";
echo "<p><strong>User Role:</strong> {$current_user['role']}</p>";

// Check permissions
$canCreateTasks = in_array($current_user['role'], ['admin', 'manager']);
$canCreateProjects = $current_user['role'] === 'admin';
$canManageTeam = in_array($current_user['role'], ['admin', 'manager']);

echo "<h3>Permissions</h3>";
echo "<ul>";
echo "<li>Create Tasks: " . ($canCreateTasks ? '✅ YES' : '❌ NO') . "</li>";
echo "<li>Create Projects: " . ($canCreateProjects ? '✅ YES' : '❌ NO') . "</li>";
echo "<li>Manage Team: " . ($canManageTeam ? '✅ YES' : '❌ NO') . "</li>";
echo "</ul>";

if (!$canCreateTasks) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h4>⚠️ Why You Can't Create Tasks</h4>";
    echo "<p>Your current role is '<strong>{$current_user['role']}</strong>'. Only users with 'admin' or 'manager' roles can create tasks.</p>";
    echo "<p>To create tasks, you need to:</p>";
    echo "<ol>";
    echo "<li>Contact an administrator</li>";
    echo "<li>Ask them to change your role to 'manager' or 'admin'</li>";
    echo "<li>Or ask them to create tasks for you</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h4>✅ You Can Create Tasks!</h4>";
    echo "<p>Your role '<strong>{$current_user['role']}</strong>' has permission to create tasks.</p>";
    echo "<p>If you're still having issues, check the browser console for JavaScript errors.</p>";
    echo "</div>";
}

// Test database connection and tables
echo "<h3>Database Status</h3>";
try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection: OK</p>";
    
    // Check tables
    $tables = ['users', 'projects', 'tasks', 'project_members'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table': Exists</p>";
        } else {
            echo "<p>❌ Table '$table': Missing</p>";
        }
    }
    
    // Check user count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>👥 Total users: {$result['count']}</p>";
    
    // Check project count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM projects");
    $result = $stmt->fetch();
    echo "<p>📋 Total projects: {$result['count']}</p>";
    
    // Check task count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
    $result = $stmt->fetch();
    echo "<p>✅ Total tasks: {$result['count']}</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test task creation if user has permission
if ($canCreateTasks) {
    echo "<h3>Test Task Creation</h3>";
    echo "<p>Testing if the backend can create tasks...</p>";
    
    $testData = [
        'title' => 'Test Task - ' . date('Y-m-d H:i:s'),
        'description' => 'This is a test task to verify the system works',
        'status' => 'pending',
        'priority' => 'medium',
        'project_id' => null,
        'assigned_to' => null,
        'assigned_by' => $current_user['id'],
        'due_date' => date('Y-m-d', strtotime('+1 week')),
        'estimated_hours' => 1.0
    ];
    
    try {
        $result = createTask($testData);
        if ($result) {
            echo "<p>✅ Backend task creation: SUCCESS</p>";
            echo "<p>The issue is likely in the frontend (JavaScript/Modal).</p>";
        } else {
            echo "<p>❌ Backend task creation: FAILED</p>";
            echo "<p>The issue is in the backend PHP code.</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Backend task creation error: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><a href='index.php'>Go back to main application</a></li>";
echo "<li><a href='test-modal.html'>Test modal functionality</a></li>";
echo "<li><a href='troubleshooting.md'>View troubleshooting guide</a></li>";
echo "</ul>";

echo "<h3>Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?>
