<?php
session_start();
require_once 'config/database.php';

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

echo "<h2>Insert Sample Tasks</h2>";
echo "<p><strong>Current User:</strong> {$current_user['full_name']} ({$current_user['username']})</p>";
echo "<p><strong>User ID:</strong> {$current_user['id']}</p>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection established</p>";
    
    // Check if tasks table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ Tasks table does not exist. Please run setup-database.php first.</p>";
        exit;
    }
    
    // Check if there are already tasks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
    $result = $stmt->fetch();
    $existing_count = $result['count'];
    
    if ($existing_count > 0) {
        echo "<p>⚠️ There are already {$existing_count} tasks in the table.</p>";
        echo "<p>Do you want to add more sample tasks?</p>";
        echo "<form method='POST'>";
        echo "<input type='submit' name='add_more' value='Add More Sample Tasks' class='btn btn-primary'>";
        echo "</form>";
    }
    
    // Insert sample tasks
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $existing_count == 0) {
        echo "<h3>Inserting Sample Tasks...</h3>";
        
        $sample_tasks = [
            [
                'title' => 'Complete Project Documentation',
                'description' => 'Write comprehensive documentation for the current project including user guides and technical specifications.',
                'status' => 'pending',
                'priority' => 'high',
                'due_date' => date('Y-m-d', strtotime('+7 days')),
                'estimated_hours' => 8.0
            ],
            [
                'title' => 'Review Code Changes',
                'description' => 'Review and approve recent code changes submitted by the development team.',
                'status' => 'in_progress',
                'priority' => 'medium',
                'due_date' => date('Y-m-d', strtotime('+3 days')),
                'estimated_hours' => 4.0
            ],
            [
                'title' => 'Update User Interface',
                'description' => 'Implement the new UI design changes based on user feedback and design team recommendations.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => date('Y-m-d', strtotime('+14 days')),
                'estimated_hours' => 12.0
            ],
            [
                'title' => 'Database Optimization',
                'description' => 'Analyze and optimize database queries for better performance and reduced load times.',
                'status' => 'on_hold',
                'priority' => 'low',
                'due_date' => date('Y-m-d', strtotime('+21 days')),
                'estimated_hours' => 6.0
            ],
            [
                'title' => 'Security Audit',
                'description' => 'Conduct a comprehensive security audit of the application to identify and fix vulnerabilities.',
                'status' => 'completed',
                'priority' => 'high',
                'due_date' => date('Y-m-d', strtotime('-2 days')),
                'estimated_hours' => 10.0
            ],
            [
                'title' => 'Team Training Session',
                'description' => 'Organize and conduct training session for team members on new development tools and practices.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => date('Y-m-d', strtotime('+10 days')),
                'estimated_hours' => 3.0
            ]
        ];
        
        $inserted_count = 0;
        foreach ($sample_tasks as $task_data) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO tasks (title, description, status, priority, assigned_by, due_date, estimated_hours, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([
                    $task_data['title'],
                    $task_data['description'],
                    $task_data['status'],
                    $task_data['priority'],
                    $current_user['id'],
                    $task_data['due_date'],
                    $task_data['estimated_hours']
                ])) {
                    $inserted_count++;
                    echo "<p>✅ Inserted: {$task_data['title']}</p>";
                } else {
                    echo "<p>❌ Failed to insert: {$task_data['title']}</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Error inserting {$task_data['title']}: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h3>Sample Tasks Insertion Complete!</h3>";
        echo "<p>Successfully inserted {$inserted_count} sample tasks.</p>";
        
        // Show current task count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
        $result = $stmt->fetch();
        echo "<p><strong>Total tasks now in system:</strong> {$result['count']}</p>";
        
        // Show tasks by status
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
        $status_counts = $stmt->fetchAll();
        
        echo "<h4>Tasks by Status:</h4>";
        echo "<ul>";
        foreach ($status_counts as $status) {
            echo "<li><strong>" . ucfirst(str_replace('_', ' ', $status['status'])) . ":</strong> {$status['count']}</li>";
        }
        echo "</ul>";
        
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li><a href='task-management.php'>Go to Task Management System</a></li>";
echo "<li><a href='debug-tasks.php'>Debug Task Data</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "</ul>";
?>
