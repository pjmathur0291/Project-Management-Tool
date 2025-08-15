<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Check if user can create tasks
$canCreateTasks = in_array($current_user['role'], ['admin', 'manager']);
if (!$canCreateTasks) {
    $error_message = 'You do not have permission to create tasks. Only admins and managers can create tasks.';
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canCreateTasks) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $project_id = $_POST['project_id'] ?? null;
    $assigned_to = $_POST['assigned_to'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = $_POST['due_date'] ?? null;
    $estimated_hours = $_POST['estimated_hours'] ?? null;
    
    // Validation
    if (empty($title)) {
        $error_message = 'Task title is required.';
    } else {
        try {
            $taskData = [
                'title' => $title,
                'description' => $description,
                'status' => 'pending',
                'priority' => $priority,
                'project_id' => $project_id ?: null,
                'assigned_to' => $assigned_to ?: null,
                'assigned_by' => $current_user['id'],
                'due_date' => $due_date ?: null,
                'estimated_hours' => $estimated_hours ?: null
            ];
            
            if (createTask($taskData)) {
                $success_message = 'Task created successfully!';
                // Clear form data
                $_POST = array();
            } else {
                $error_message = 'Failed to create task. Please try again.';
            }
        } catch (Exception $e) {
            $error_message = 'Error creating task: ' . $e->getMessage();
        }
    }
}

// Get projects for dropdown
$projects = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY name");
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Error loading projects: ' . $e->getMessage();
}

// Get team members for dropdown
$team_members = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, full_name, job_title, role FROM users ORDER BY job_title, full_name");
    $team_members = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Error loading team members: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Task - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .create-task-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .form-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .form-header p {
            color: #666;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .btn-back {
            background: #6c757d;
            margin-right: 10px;
        }
        .btn-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="create-task-container">
        <div class="form-header">
            <h1><i class="fas fa-plus-circle"></i> Create New Task (Simple Version)</h1>
            <p>Add a new task to your project management system</p>
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

        <?php if ($canCreateTasks): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Task Title *</label>
                    <input type="text" class="form-input" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-input" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project</label>
                        <select class="form-input" name="project_id">
                            <option value="">Select Project (Optional)</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>" <?php echo ($_POST['project_id'] ?? '') == $project['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Assign To</label>
                        <select class="form-input" name="assigned_to">
                            <option value="">Select Team Member (Optional)</option>
                            <?php foreach ($team_members as $member): ?>
                                <option value="<?php echo $member['id']; ?>" <?php echo ($_POST['assigned_to'] ?? '') == $member['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($member['full_name']); ?> - <?php echo htmlspecialchars($member['job_title'] ?? 'No Title'); ?> (<?php echo ucfirst($member['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-input" name="priority">
                            <option value="low" <?php echo ($_POST['priority'] ?? '') == 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo ($_POST['priority'] ?? '') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo ($_POST['priority'] ?? '') == 'high' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-input" name="due_date" value="<?php echo $_POST['due_date'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Estimated Hours</label>
                    <input type="number" class="form-input" name="estimated_hours" min="0" step="0.5" value="<?php echo $_POST['estimated_hours'] ?? ''; ?>" placeholder="e.g., 8.5">
                </div>
                
                <div class="form-actions">
                    <a href="index.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Create Task
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <div class="form-actions">
                <a href="index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
