<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<h2>Access Denied</h2>";
    echo "<p>You need to be logged in as an admin to run this script.</p>";
    echo "<p><a href='login.php'>Login</a> | <a href='index.php'>Dashboard</a></p>";
    exit;
}

echo "<h2>Job Title Cleanup - Separating Titles from Roles</h2>";
echo "<p><strong>Current User:</strong> {$_SESSION['full_name']} ({$_SESSION['role']})</p>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Get current users with their job titles and roles
    echo "<h3>Current Users (Before Cleanup)</h3>";
    $stmt = $pdo->query("SELECT id, username, full_name, job_title, role FROM users ORDER BY job_title, role");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>No users found in the database.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Job Title</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$user['job_title']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h3>Starting Cleanup Process</h3>";
    
    // Process each user to clean up job titles and set proper roles
    $updated_count = 0;
    $errors = [];
    
    foreach ($users as $user) {
        $job_title = $user['job_title'];
        $current_role = $user['role'];
        $new_job_title = $job_title;
        $new_role = $current_role;
        
        // Check if job title contains "(Manager)"
        if (strpos($job_title, '(Manager)') !== false) {
            // Remove "(Manager)" from job title
            $new_job_title = str_replace('(Manager)', '', $job_title);
            $new_job_title = trim($new_job_title);
            
            // Set role to 'manager' if it's not already
            if ($current_role !== 'manager') {
                $new_role = 'manager';
            }
            
            echo "<p>Processing: <strong>{$user['full_name']}</strong></p>";
            echo "<p>  - Old job title: '{$job_title}'</p>";
            echo "<p>  - New job title: '{$new_job_title}'</p>";
            echo "<p>  - Old role: '{$current_role}'</p>";
            echo "<p>  - New role: '{$new_role}'</p>";
            
            // Update the user
            try {
                $stmt = $pdo->prepare("UPDATE users SET job_title = ?, role = ? WHERE id = ?");
                if ($stmt->execute([$new_job_title, $new_role, $user['id']])) {
                    echo "<p style='color: green;'>  ✅ Updated successfully</p>";
                    $updated_count++;
                } else {
                    echo "<p style='color: red;'>  ❌ Failed to update</p>";
                    $errors[] = "Failed to update user {$user['full_name']}";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>  ❌ Error: " . $e->getMessage() . "</p>";
                $errors[] = "Error updating user {$user['full_name']}: " . $e->getMessage();
            }
        } else {
            echo "<p>No changes needed for: <strong>{$user['full_name']}</strong> (job title: '{$job_title}')</p>";
        }
        
        echo "<br>";
    }
    
    echo "<hr>";
    echo "<h3>Cleanup Results</h3>";
    echo "<p><strong>Users Updated:</strong> {$updated_count}</p>";
    
    if (!empty($errors)) {
        echo "<p><strong>Errors:</strong></p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li style='color: red;'>{$error}</li>";
        }
        echo "</ul>";
    }
    
    // Show updated users
    echo "<h3>Updated Users (After Cleanup)</h3>";
    $stmt = $pdo->query("SELECT id, username, full_name, job_title, role FROM users ORDER BY job_title, role");
    $updated_users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Job Title</th><th>Role</th></tr>";
    foreach ($updated_users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['job_title']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show summary of job titles and roles
    echo "<h3>Job Title and Role Summary</h3>";
    $stmt = $pdo->query("SELECT job_title, role, COUNT(*) as count FROM users GROUP BY job_title, role ORDER BY job_title, role");
    $summary = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
    echo "<tr><th>Job Title</th><th>Role</th><th>Count</th></tr>";
    foreach ($summary as $row) {
        echo "<tr>";
        echo "<td>{$row['job_title']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td>{$row['count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre style='font-size: 10px;'>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "<li><a href='simple-task-management.php'>View Task Management</a></li>";
echo "<li><a href='create-task.php'>Create New Task</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>What This Script Did:</h3>";
echo "<ul>";
echo "<li><strong>Separated Job Titles from Roles:</strong> Removed '(Manager)' from job titles</li>";
echo "<li><strong>Set Proper Roles:</strong> Users with '(Manager)' in job title now have role='manager'</li>";
echo "<li><strong>Clean Data Structure:</strong> Job titles are now clean (e.g., 'Developer', 'Graphic Designer')</li>";
echo "<li><strong>Role Management:</strong> Roles are now separate (admin, manager, member)</li>";
echo "</ul>";
?>
