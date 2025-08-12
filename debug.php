<?php
echo "<h1>Project Management Tool - Debug Page</h1>";

// Test database connection
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✓ Database configuration loaded</p>";
    
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test basic queries
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Users table accessible: $userCount users</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
    $projectCount = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Projects table accessible: $projectCount projects</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Test Project Creation API</h2>";
echo "<form method='POST' action='debug.php'>";
echo "<input type='hidden' name='test' value='create_project'>";
echo "<p><strong>Project Name:</strong> <input type='text' name='name' value='Test Project' required></p>";
echo "<p><strong>Description:</strong> <textarea name='description'>Test Description</textarea></p>";
echo "<p><strong>Status:</strong> <select name='status'><option value='pending'>Pending</option><option value='active'>Active</option></select></p>";
echo "<p><strong>Priority:</strong> <select name='priority'><option value='low'>Low</option><option value='medium'>Medium</option><option value='high'>High</option></select></p>";
echo "<p><strong>Start Date:</strong> <input type='date' name='start_date' value='" . date('Y-m-d') . "' required></p>";
echo "<p><strong>End Date:</strong> <input type='date' name='end_date' value=''></p>";
echo "<p><strong>Manager ID:</strong> <input type='number' name='manager_id' value='2' required></p>";
echo "<p><input type='submit' value='Test Create Project' style='background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'></p>";
echo "</form>";

// Handle test project creation
if (isset($_POST['test']) && $_POST['test'] === 'create_project') {
    echo "<hr>";
    echo "<h3>Testing Project Creation...</h3>";
    
    try {
        require_once 'includes/functions.php';
        
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'status' => $_POST['status'],
            'priority' => $_POST['priority'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'] ?: null,
            'manager_id' => $_POST['manager_id']
        ];
        
        echo "<p><strong>Data being sent:</strong></p>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        
        $projectId = createProject($data);
        
        if ($projectId) {
            echo "<p style='color: green;'>✓ Project created successfully! ID: $projectId</p>";
            
            // Verify the project was created
            $project = getProjectById($projectId);
            echo "<p><strong>Created project details:</strong></p>";
            echo "<pre>" . print_r($project, true) . "</pre>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create project</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h2>Current Projects</h2>";
try {
    $projects = getAllProjects();
    if ($projects) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Status</th><th>Priority</th><th>Start Date</th><th>End Date</th><th>Progress</th></tr>";
        foreach ($projects as $project) {
            echo "<tr>";
            echo "<td>{$project['id']}</td>";
            echo "<td>{$project['name']}</td>";
            echo "<td>{$project['status']}</td>";
            echo "<td>{$project['priority']}</td>";
            echo "<td>{$project['start_date']}</td>";
            echo "<td>{$project['end_date'] ?: 'No deadline'}</td>";
            echo "<td>{$project['progress']}%</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No projects found.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error loading projects: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>JavaScript Test</h2>";
echo "<p>Open browser console and check for any JavaScript errors.</p>";
echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Main App</a></p>";
echo "<p><a href='login.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
?>
