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
    'username' => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'],
    'role' => $_SESSION['role']
];

// Handle task actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $task_id = $_POST['task_id'] ?? null;
    
    if ($task_id) {
        try {
            $pdo = getDBConnection();
            
            // Check if user can modify this task (assigned to them or admin/manager)
            $can_modify = false;
            if (in_array($current_user['role'], ['admin', 'manager'])) {
                $can_modify = true;
            } else {
                $stmt = $pdo->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
                $stmt->execute([$task_id]);
                $assigned_to = $stmt->fetchColumn();
                $can_modify = ($assigned_to == $current_user['id']);
            }
            
            if (!$can_modify) {
                $error_message = 'You can only modify tasks assigned to you.';
            } else {
                switch ($_POST['action']) {
                    case 'complete':
                        $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', completed_at = NOW(), completed_by = ? WHERE id = ?");
                        if ($stmt->execute([$current_user['id'], $task_id])) {
                            // Redirect to refresh the page and show success message
                            header("Location: task-management.php?tab=" . ($_GET['tab'] ?? 'all') . "&success=Task marked as completed!");
                            exit();
                        } else {
                            $error_message = 'Failed to mark task as completed.';
                        }
                        break;
                        
                    case 'start':
                        $stmt = $pdo->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ?");
                        if ($stmt->execute([$task_id])) {
                            header("Location: task-management.php?tab=" . ($_GET['tab'] ?? 'all') . "&success=Task started!");
                            exit();
                        } else {
                            $error_message = 'Failed to start task.';
                        }
                        break;
                        
                    case 'hold':
                        $stmt = $pdo->prepare("UPDATE tasks SET status = 'on_hold' WHERE id = ?");
                        if ($stmt->execute([$task_id])) {
                            header("Location: task-management.php?tab=" . ($_GET['tab'] ?? 'all') . "&success=Task put on hold!");
                            exit();
                        } else {
                            $error_message = 'Failed to put task on hold.';
                        }
                        break;
                        
                    case 'delete':
                        if (in_array($current_user['role'], ['admin', 'manager'])) {
                            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                            if ($stmt->execute([$task_id])) {
                                header("Location: task-management.php?tab=" . ($_GET['tab'] ?? 'all') . "&success=Task deleted successfully!");
                                exit();
                            } else {
                                $error_message = 'Failed to delete task.';
                            }
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error updating task: ' . $e->getMessage();
        }
    }
}

// Get current tab and mine flag
$current_tab = $_GET['tab'] ?? 'all';
$mine = isset($_GET['mine']) && $_GET['mine'] == '1';

// Get tasks based on current tab and user role
try {
    $pdo = getDBConnection();
    
    // Build query based on user role and mine flag
    if (in_array($current_user['role'], ['admin', 'manager']) && !$mine) {
        // Admins and managers see all tasks unless mine=1 is set
        if ($current_tab === 'all') {
            $sql = "SELECT t.*, p.name as project_name, u.full_name as assignee_name, c.full_name as completed_by_name 
                    FROM tasks t 
                    LEFT JOIN projects p ON t.project_id = p.id 
                    LEFT JOIN users u ON t.assigned_to = u.id 
                    LEFT JOIN users c ON t.completed_by = c.id 
                    ORDER BY 
                        CASE t.status 
                            WHEN 'pending' THEN 1 
                            WHEN 'in_progress' THEN 2 
                            WHEN 'on_hold' THEN 3 
                            WHEN 'completed' THEN 4 
                        END,
                        t.priority DESC,
                        t.due_date ASC,
                        t.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        } else {
            $sql = "SELECT t.*, p.name as project_name, u.full_name as assignee_name, c.full_name as completed_by_name 
                    FROM tasks t 
                    LEFT JOIN projects p ON t.project_id = p.id 
                    LEFT JOIN users u ON t.assigned_to = u.id 
                    LEFT JOIN users c ON t.completed_by = c.id 
                    WHERE t.status = ? 
                    ORDER BY 
                        t.priority DESC,
                        t.due_date ASC,
                        t.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$current_tab]);
        }
    } else {
        // Regular users or mine=1: only see tasks assigned to current user
        if ($current_tab === 'all') {
            $sql = "SELECT t.*, p.name as project_name, u.full_name as assignee_name, c.full_name as completed_by_name 
                    FROM tasks t 
                    LEFT JOIN projects p ON t.project_id = p.id 
                    LEFT JOIN users u ON t.assigned_to = u.id 
                    LEFT JOIN users c ON t.completed_by = c.id 
                    WHERE t.assigned_to = ? 
                    ORDER BY 
                        CASE t.status 
                            WHEN 'pending' THEN 1 
                            WHEN 'in_progress' THEN 2 
                            WHEN 'on_hold' THEN 3 
                            WHEN 'completed' THEN 4 
                        END,
                        t.priority DESC,
                        t.due_date ASC,
                        t.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$current_user['id']]);
        } else {
            $sql = "SELECT t.*, p.name as project_name, u.full_name as assignee_name, c.full_name as completed_by_name 
                    FROM tasks t 
                    LEFT JOIN projects p ON t.project_id = p.id 
                    LEFT JOIN users u ON t.assigned_to = u.id 
                    LEFT JOIN users c ON t.completed_by = c.id 
                    WHERE t.status = ? AND t.assigned_to = ? 
                    ORDER BY 
                        t.priority DESC,
                        t.due_date ASC,
                        t.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$current_tab, $current_user['id']]);
        }
    }
    
    $tasks = $stmt->fetchAll();
    
    // Get task counts for each status (filtered by user role)
    if (in_array($current_user['role'], ['admin', 'manager']) && !$mine) {
        $count_sql = "SELECT status, COUNT(*) as count FROM tasks GROUP BY status";
        $count_stmt = $pdo->query($count_sql);
    } else {
        $count_sql = "SELECT status, COUNT(*) as count FROM tasks WHERE assigned_to = ? GROUP BY status";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute([$current_user['id']]);
    }
    
    $task_counts = [];
    while ($row = $count_stmt->fetch()) {
        $task_counts[$row['status']] = $row['count'];
    }
    
    // Debug output
    echo "<!-- Debug: Found " . count($tasks) . " tasks for tab: $current_tab -->";
    echo "<!-- Debug: Task counts: " . print_r($task_counts, true) . " -->";
    
} catch (Exception $e) {
    $error_message = 'Error loading tasks: ' . $e->getMessage();
    $tasks = [];
    $task_counts = [];
}

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .task-management-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .page-actions {
            text-align: center;
            margin-bottom: 30px;
        }
        .tab-navigation {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background: white;
            border-radius: 12px;
            padding: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .tab-button {
            padding: 12px 24px;
            border: none;
            background: transparent;
            color: #666;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            font-weight: 500;
        }
        .tab-button.active {
            background: #007bff;
            color: white;
        }
        .tab-button:hover:not(.active) {
            background: #f8f9fa;
            color: #333;
        }
        .tab-count {
            background: #e9ecef;
            color: #666;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 8px;
        }
        .tab-button.active .tab-count {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .task-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #007bff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        .task-card.completed {
            border-left-color: #28a745;
            opacity: 0.8;
        }
        .task-card.high-priority {
            border-left-color: #dc3545;
        }
        .task-card.medium-priority {
            border-left-color: #ffc107;
        }
        .task-card.low-priority {
            border-left-color: #6c757d;
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
            flex: 1;
            margin-right: 15px;
        }
        .task-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-on_hold { background: #f8d7da; color: #721c24; }
        .task-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .task-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        .meta-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 4px;
            font-size: 0.8rem;
        }
        .meta-value {
            color: #333;
        }
        .task-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .no-tasks {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            grid-column: 1 / -1;
        }
        .no-tasks i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        .priority-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .priority-high { background: #f8d7da; color: #721c24; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-low { background: #e2e3e5; color: #383d41; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        
        .user-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .user-info p {
            margin: 5px 0;
            color: #666;
        }
        
        .role-badge {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .role-badge.admin { background: #dc3545; }
        .role-badge.manager { background: #fd7e14; }
        .role-badge.member { background: #28a745; }
    </style>
</head>
<body>
    <div class="task-management-container">
        <div class="page-header">
            <h1><i class="fas fa-tasks"></i> Task Management System</h1>
            <p>Organize and track all your tasks efficiently</p>
        </div>

        <div class="user-info">
            <h3><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($current_user['full_name']); ?></h3>
            <p><span class="role-badge <?php echo $current_user['role']; ?>"><?php echo ucfirst($current_user['role']); ?></span></p>
            <?php if (in_array($current_user['role'], ['admin', 'manager']) && !$mine): ?>
                <p><i class="fas fa-eye"></i> You can see <strong>all tasks</strong> in the system</p>
            <?php else: ?>
                <p><i class="fas fa-user-check"></i> You can only see <strong>tasks assigned to you</strong></p>
                <?php if (!in_array($current_user['role'], ['admin', 'manager'])): ?>
                    <p><i class="fas fa-info-circle"></i> <strong>Note:</strong> You can view and start/hold your tasks, but only managers and admins can mark them as completed.</p>
                <?php endif; ?>
            <?php endif; ?>
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

        <div class="page-actions">
            <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                <a href="create-task.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Task
                </a>
            <?php endif; ?>
            <a href="background-upload-simple.php" class="btn btn-info">
                <i class="fas fa-cloud-upload-alt"></i> Background Upload
            </a>
            <a href="large-file-upload.php" class="btn btn-success">
                <i class="fas fa-file-upload"></i> Large File Upload
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="tab-navigation">
            <a href="?tab=all<?php echo $mine ? '&mine=1' : ''; ?>" class="tab-button <?php echo $current_tab === 'all' ? 'active' : ''; ?>">
                All Tasks
                <span class="tab-count"><?php echo array_sum($task_counts); ?></span>
            </a>
            <a href="?tab=pending<?php echo $mine ? '&mine=1' : ''; ?>" class="tab-button <?php echo $current_tab === 'pending' ? 'active' : ''; ?>">
                Pending
                <span class="tab-count"><?php echo $task_counts['pending'] ?? 0; ?></span>
            </a>
            <a href="?tab=in_progress<?php echo $mine ? '&mine=1' : ''; ?>" class="tab-button <?php echo $current_tab === 'in_progress' ? 'active' : ''; ?>">
                In Progress
                <span class="tab-count"><?php echo $task_counts['in_progress'] ?? 0; ?></span>
            </a>
            <a href="?tab=on_hold<?php echo $mine ? '&mine=1' : ''; ?>" class="tab-button <?php echo $current_tab === 'on_hold' ? 'active' : ''; ?>">
                On Hold
                <span class="tab-count"><?php echo $task_counts['on_hold'] ?? 0; ?></span>
            </a>
            <a href="?tab=completed<?php echo $mine ? '&mine=1' : ''; ?>" class="tab-button <?php echo $current_tab === 'completed' ? 'active' : ''; ?>">
                Completed
                <span class="tab-count"><?php echo $task_counts['completed'] ?? 0; ?></span>
            </a>
        </div>



        <?php if (empty($tasks)): ?>
            <div class="no-tasks">
                <i class="fas fa-clipboard-list"></i>
                <h3>No tasks found</h3>
                <p>There are no tasks in the "<?php echo ucfirst(str_replace('_', ' ', $current_tab)); ?>" category.</p>
                

                
                <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                    <a href="create-task.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Your First Task
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="tasks-grid">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?> <?php echo $task['priority']; ?>-priority" data-status="<?php echo $task['status']; ?>">
                        <div class="task-header">
                            <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                            <span class="task-status status-<?php echo $task['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                            </span>
                        </div>
                        
                        <?php if ($task['description']): ?>
                            <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
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
                                    <span class="priority-badge priority-<?php echo $task['priority']; ?>">
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
                            <?php if ($task['completed_at']): ?>
                                <div class="meta-item">
                                    <div class="meta-label">Completed</div>
                                    <div class="meta-value"><?php echo date('M j, Y', strtotime($task['completed_at'])); ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($task['completed_by_name']): ?>
                                <div class="meta-item">
                                    <div class="meta-label">Completed By</div>
                                    <div class="meta-value"><?php echo htmlspecialchars($task['completed_by_name']); ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <div class="meta-label">Created</div>
                                <div class="meta-value"><?php echo date('M j, Y', strtotime($task['created_at'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="task-actions">
                            <?php 
                            // Can modify = can start, hold, or complete tasks
                            $can_modify = false;
                            if (in_array($current_user['role'], ['admin', 'manager'])) {
                                $can_modify = true;
                            } else {
                                $can_modify = ($task['assigned_to'] == $current_user['id']);
                            }
                            
                            // Can view = can see task details (always true for assigned tasks or admins/managers)
                            $can_view = true;
                            ?>
                            
                            <?php if ($can_modify): ?>
                                <?php if ($task['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="start">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" class="btn btn-info btn-sm">
                                            <i class="fas fa-play"></i> Start
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($task['status'] === 'pending' || $task['status'] === 'in_progress'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="hold">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="fas fa-pause"></i> Hold
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($task['status'] !== 'completed'): ?>
                                    <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark this task as completed?')">
                                                <i class="fas fa-check"></i> Complete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="btn btn-secondary btn-sm" style="cursor: not-allowed; opacity: 0.6;" title="Only managers and admins can mark tasks as completed">
                                            <i class="fas fa-lock"></i> Complete (Restricted)
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if (!$can_modify): ?>
                                <span class="btn btn-info btn-sm" style="cursor: default; opacity: 0.8;" title="You can view this task but only managers/admins can modify it">
                                    <i class="fas fa-eye"></i> View Only
                                </span>
                            <?php endif; ?>
                            
                            <a href="task-detail.php?id=<?php echo $task['id']; ?>" class="btn btn-info btn-sm" title="View detailed task information with multimedia files">
                                <i class="fas fa-search"></i> View Details
                            </a>
                            
                            <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                                <a href="edit-task.php?id=<?php echo $task['id']; ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this task?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
