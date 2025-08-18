<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_user = [
    'id' => $_SESSION['user_id'],
    'role' => $_SESSION['role']
];

// Handle task status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $task_id = $_POST['task_id'] ?? null;
    
    if ($_POST['action'] === 'complete' && $task_id) {
        try {
            $pdo = getDBConnection();
            // Only admins/managers can complete tasks via this endpoint
            if (!in_array($current_user['role'], ['admin', 'manager'])) {
                throw new Exception('Permission denied');
            }
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', completed_at = NOW(), completed_by = ? WHERE id = ?");
            if ($stmt->execute([$current_user['id'], $task_id])) {
                $success_message = 'Task marked as completed!';
            } else {
                $error_message = 'Failed to update task status.';
            }
        } catch (Exception $e) {
            $error_message = 'Error updating task: ' . $e->getMessage();
        }
    }
}

// Get tasks based on user role
try {
    $pdo = getDBConnection();
    
    if ($current_user['role'] === 'admin' || $current_user['role'] === 'manager') {
        // Admin and managers see all tasks
        $stmt = $pdo->query("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name 
            FROM tasks t 
            LEFT JOIN projects p ON t.project_id = p.id 
            LEFT JOIN users u ON t.assigned_to = u.id 
            ORDER BY t.created_at DESC
        ");
    } else {
        // Regular members only see tasks assigned to them
        $stmt = $pdo->prepare("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name 
            FROM tasks t 
            LEFT JOIN projects p ON t.project_id = p.id 
            LEFT JOIN users u ON t.assigned_to = u.id 
            WHERE t.assigned_to = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$current_user['id']]);
    }
    
    $tasks = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Error loading tasks: ' . $e->getMessage();
    $tasks = [];
}

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks List - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .tasks-list-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .page-title h1 {
            color: #333;
            margin: 0;
        }
        .page-actions {
            display: flex;
            gap: 15px;
        }
        .task-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e9ecef;
            background: white;
            color: #666;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .filter-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .filter-btn:hover {
            border-color: #007bff;
            color: #007bff;
        }
        .filter-btn.active:hover {
            background: #0056b3;
            border-color: #0056b3;
            color: white;
        }
        .task-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #007bff;
        }
        .task-item.completed {
            border-left-color: #28a745;
            opacity: 0.8;
        }
        .task-item.high-priority {
            border-left-color: #dc3545;
        }
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .task-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .task-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-on_hold { background: #f8d7da; color: #721c24; }
        .task-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .meta-item {
            font-size: 0.9rem;
        }
        .meta-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        .meta-value {
            color: #333;
        }
        .task-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .no-tasks {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .no-tasks i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="tasks-list-container">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-list-check"></i> Task Management</h1>
                <p>View and manage all tasks in your system</p>
            </div>
            <div class="page-actions">
                <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                    <a href="create-task.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Task
                    </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="task-filters">
            <button class="filter-btn active" onclick="filterTasks('all')">All Tasks</button>
            <button class="filter-btn" onclick="filterTasks('pending')">Pending</button>
            <button class="filter-btn" onclick="filterTasks('in_progress')">In Progress</button>
            <button class="filter-btn" onclick="filterTasks('completed')">Completed</button>
        </div>

        <?php if (empty($tasks)): ?>
            <div class="no-tasks">
                <i class="fas fa-clipboard-list"></i>
                <h3>No tasks found</h3>
                <p>There are currently no tasks in the system.</p>
                <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                    <a href="create-task.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Your First Task
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <div class="task-item <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?> <?php echo $task['priority'] === 'high' ? 'high-priority' : ''; ?>" data-status="<?php echo $task['status']; ?>">
                    <div class="task-header">
                        <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                        <span class="task-status status-<?php echo $task['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                        </span>
                    </div>
                    
                    <?php if ($task['description']): ?>
                        <p style="color: #666; margin-bottom: 15px;"><?php echo htmlspecialchars($task['description']); ?></p>
                    <?php endif; ?>
                    
                    <div class="task-meta">
                        <div class="meta-item">
                            <div class="meta-label">Project</div>
                            <div class="meta-value"><?php echo $task['project_name'] ? htmlspecialchars($task['project_name']) : 'No Project'; ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Assigned To</div>
                            <div class="meta-value"><?php echo $task['assignee_name'] ? htmlspecialchars($task['assignee_name']) : 'Unassigned'; ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Priority</div>
                            <div class="meta-value">
                                <span class="priority-<?php echo $task['priority']; ?>">
                                    <?php echo ucfirst($task['priority']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Due Date</div>
                            <div class="meta-value"><?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No Deadline'; ?></div>
                        </div>
                        <?php if ($task['estimated_hours']): ?>
                            <div class="meta-item">
                                <div class="meta-label">Estimated Hours</div>
                                <div class="meta-value"><?php echo $task['estimated_hours']; ?> hours</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="task-actions">
                        <?php if ($task['status'] !== 'completed'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="complete">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark this task as completed?')">
                                    <i class="fas fa-check"></i> Complete
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                            <a href="edit-task.php?id=<?php echo $task['id']; ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn btn-danger btn-sm" onclick="deleteTask(<?php echo $task['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function filterTasks(status) {
            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show/hide tasks based on status
            const tasks = document.querySelectorAll('.task-item');
            tasks.forEach(task => {
                if (status === 'all' || task.dataset.status === status) {
                    task.style.display = 'block';
                } else {
                    task.style.display = 'none';
                }
            });
        }
        
        function deleteTask(taskId) {
            if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                // Redirect to delete task page
                window.location.href = `delete-task.php?id=${taskId}`;
            }
        }
    </script>
</body>
</html>
