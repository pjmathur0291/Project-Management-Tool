<?php
echo "<h1>Project Management Tool - Test Page</h1>";

try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✓ Database configuration loaded successfully</p>";
    
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection established successfully</p>";
    
    // Test basic queries
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $userCount = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Users table accessible: $userCount users found</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as project_count FROM projects");
    $projectCount = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Projects table accessible: $projectCount projects found</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as task_count FROM tasks");
    $taskCount = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Tasks table accessible: $taskCount tasks found</p>";
    
    echo "<hr>";
    echo "<h2>Sample Data:</h2>";
    
    // Show sample users
    echo "<h3>Users:</h3>";
    $stmt = $pdo->query("SELECT username, full_name, role FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li><strong>{$user['username']}</strong> - {$user['full_name']} ({$user['role']})</li>";
    }
    echo "</ul>";
    
    // Show sample projects
    echo "<h3>Projects:</h3>";
    $stmt = $pdo->query("SELECT name, status, progress FROM projects LIMIT 5");
    $projects = $stmt->fetchAll();
    echo "<ul>";
    foreach ($projects as $project) {
        echo "<li><strong>{$project['name']}</strong> - {$project['status']} ({$project['progress']}% complete)</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<h2>Next Steps:</h2>";
    echo "<p><a href='login.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Main Application</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your XAMPP configuration and ensure MySQL is running.</p>";
}
?>
