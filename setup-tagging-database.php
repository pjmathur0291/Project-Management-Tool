<?php
/**
 * Tagging System Database Setup Script
 * Run this script to add tagging functionality to your existing project management tool
 */

require_once 'config/database.php';

echo "<h1>Project Management Tool - Tagging System Setup</h1>";
echo "<p>This script will add tagging functionality to your database.</p>";

try {
    $pdo = getDBConnection();
    
    echo "<h2>Setting up tagging tables...</h2>";
    
    // Create tags table
    echo "<p>Creating tags table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            color VARCHAR(7) DEFAULT '#007bff',
            description TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p style='color: green;'>✓ Tags table created successfully</p>";
    
    // Create task_tags table
    echo "<p>Creating task_tags table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS task_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            tag_id INT NOT NULL,
            added_by INT,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
            FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_task_tag (task_id, tag_id),
            INDEX idx_task_id (task_id),
            INDEX idx_tag_id (tag_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p style='color: green;'>✓ Task_tags table created successfully</p>";
    
    // Create project_tags table
    echo "<p>Creating project_tags table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS project_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            tag_id INT NOT NULL,
            added_by INT,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
            FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_project_tag (project_id, tag_id),
            INDEX idx_project_id (project_id),
            INDEX idx_tag_id (tag_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p style='color: green;'>✓ Project_tags table created successfully</p>";
    
    // Insert sample tags
    echo "<h2>Adding sample tags...</h2>";
    $sampleTags = [
        ['urgent', '#dc3545', 'Tasks that need immediate attention'],
        ['bug', '#fd7e14', 'Bug fixes and issues'],
        ['feature', '#28a745', 'New feature development'],
        ['documentation', '#6f42c1', 'Documentation related tasks'],
        ['testing', '#20c997', 'Testing and QA tasks'],
        ['design', '#e83e8c', 'UI/UX design tasks'],
        ['backend', '#6c757d', 'Backend development tasks'],
        ['frontend', '#17a2b8', 'Frontend development tasks'],
        ['maintenance', '#ffc107', 'System maintenance tasks'],
        ['research', '#fd7e14', 'Research and investigation tasks']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name, color, description, created_by) VALUES (?, ?, ?, ?)");
    $addedTags = 0;
    
    foreach ($sampleTags as $tag) {
        $result = $stmt->execute([$tag[0], $tag[1], $tag[2], 1]); // Created by admin user (id=1)
        if ($stmt->rowCount() > 0) {
            $addedTags++;
        }
    }
    
    echo "<p style='color: green;'>✓ Added {$addedTags} sample tags</p>";
    
    // Get existing counts for confirmation
    $tagCount = $pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn();
    $taskCount = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $projectCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    
    echo "<h2>Setup Summary</h2>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<p><strong>Database setup completed successfully!</strong></p>";
    echo "<ul>";
    echo "<li>Total tags available: {$tagCount}</li>";
    echo "<li>Total tasks in system: {$taskCount}</li>";
    echo "<li>Total projects in system: {$projectCount}</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>Next Steps</h2>";
    echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<ol>";
    echo "<li><strong>Access Tag Management:</strong> Go to <a href='tags-management.php'>tags-management.php</a> to manage tags</li>";
    echo "<li><strong>Create/Edit Tasks:</strong> Tags can now be added to tasks when creating or editing them</li>";
    echo "<li><strong>Filter by Tags:</strong> Use the tag filtering features to find tasks by tags</li>";
    echo "<li><strong>API Access:</strong> Use the <code>api/tags.php</code> endpoint for programmatic access</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2>Features Available</h2>";
    echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ Tag Management</h3>";
    echo "<ul>";
    echo "<li>Create, edit, and delete tags (Admin/Manager only)</li>";
    echo "<li>Color-coded tags with descriptions</li>";
    echo "<li>Tag usage statistics</li>";
    echo "</ul>";
    
    echo "<h3>✅ Task Tagging</h3>";
    echo "<ul>";
    echo "<li>Add multiple tags to tasks</li>";
    echo "<li>Visual tag display on task cards</li>";
    echo "<li>Easy tag removal from tasks</li>";
    echo "</ul>";
    
    echo "<h3>✅ Filtering & Search</h3>";
    echo "<ul>";
    echo "<li>Filter tasks by one or multiple tags</li>";
    echo "<li>Combine tag filters with status/priority filters</li>";
    echo "<li>Search tasks using tag-based queries</li>";
    echo "</ul>";
    
    echo "<h3>✅ API Integration</h3>";
    echo "<ul>";
    echo "<li>RESTful API for all tag operations</li>";
    echo "<li>Support for task and project tagging</li>";
    echo "<li>Bulk tag update operations</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p style='margin-top: 30px;'><a href='index.php' class='btn btn-primary'>Return to Dashboard</a> <a href='tags-management.php' class='btn btn-success'>Manage Tags</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagging System Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            margin: 5px; 
            text-decoration: none; 
            border-radius: 5px; 
            color: white; 
        }
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .btn:hover { opacity: 0.8; }
        code { 
            background: #f8f9fa; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-family: monospace; 
        }
    </style>
</head>
<body>
    <!-- Content is rendered above via PHP -->
</body>
</html>
