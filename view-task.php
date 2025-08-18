<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/MultimediaManager.php';
require_once 'includes/FileViewer.php';

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

$task_id = $_GET['id'] ?? null;
$error_message = '';
$task = null;

if (!$task_id) {
    $error_message = 'No task ID provided.';
} else {
    try {
        $pdo = getDBConnection();
        
        // Get task details with project and user information
        $sql = "SELECT t.*, p.name as project_name, u.full_name as assignee_name, c.full_name as completed_by_name 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assigned_to = u.id 
                LEFT JOIN users c ON t.completed_by = c.id 
                WHERE t.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            $error_message = 'Task not found.';
        } else {
            // Check if user can view this task
            $can_view = false;
            if (in_array($current_user['role'], ['admin', 'manager'])) {
                $can_view = true;
            } else {
                $can_view = ($task['assigned_to'] == $current_user['id']);
            }
            
            if (!$can_view) {
                $error_message = 'You do not have permission to view this task.';
                $task = null;
            } else {
                // Load multimedia files for this task so assignees can see manager uploads
                try {
                    $multimediaManager = new MultimediaManager($pdo);
                    $fileViewer = new FileViewer($multimediaManager);
                    $files = $multimediaManager->getFilesByEntity('task', $task_id);
                    
                    $formattedFiles = [];
                    foreach ($files as $file) {
                        $formattedFiles[] = [
                            'id' => $file['id'],
                            'filename' => $file['filename'],
                            'original_filename' => $file['original_filename'],
                            'file_path' => $file['file_path'],
                            'file_type' => $file['file_type'],
                            'file_size' => $file['file_size'],
                            'formatted_size' => $multimediaManager->formatFileSize($file['file_size']),
                            'mime_type' => $file['mime_type'],
                            'description' => $file['description'],
                            'uploaded_by' => $file['uploaded_by'],
                            'uploaded_by_name' => $file['uploaded_by_name'],
                            'created_at' => $file['created_at'],
                            'icon' => $multimediaManager->getFileIcon($file['file_type']),
                            'is_image' => $multimediaManager->isImage($file['file_type']),
                            'is_video' => $multimediaManager->isVideo($file['file_type']),
                            'is_document' => $multimediaManager->isDocument($file['file_type'])
                        ];
                    }
                } catch (Exception $e) {
                    // If multimedia loading fails, continue without blocking task view
                    $formattedFiles = [];
                }
            }
        }
    } catch (Exception $e) {
        $error_message = 'Error loading task: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Details - Project Management Tool</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .page-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        
        .page-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .back-button {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #5a6268;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin: 20px 0;
            text-align: center;
        }
        
        .task-detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .task-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            position: relative;
        }
        
        .task-title {
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .task-status {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .task-priority {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.9);
            color: #333;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .priority-high { color: #dc3545; }
        .priority-medium { color: #fd7e14; }
        .priority-low { color: #28a745; }
        
        .task-content {
            padding: 30px;
        }
        
        .task-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
            font-weight: 600;
        }
        
        .task-description {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            font-size: 1.1rem;
            line-height: 1.7;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #e9ecef;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .task-actions {
            background: #f8f9fa;
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; }
        
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; }
        
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        .btn:disabled, .btn[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #d1ecf1; color: #0c5460; }
        .status-on_hold { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d4edda; color: #155724; }
        
        .user-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        
        .user-info h3 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        
        .user-info p {
            margin: 5px 0;
            color: #424242;
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
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .task-header { padding: 20px; }
            .task-content { padding: 20px; }
            .info-grid { grid-template-columns: 1fr; }
            .task-actions { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Previous Page
        </a>
        
        <div class="page-header">
            <h1><i class="fas fa-tasks"></i> Task Details</h1>
            <p>View comprehensive information about your task</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                <br><br>
                <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            </div>
        <?php elseif ($task): ?>
            <div class="user-info">
                <h3><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($current_user['full_name']); ?></h3>
                <p><span class="role-badge <?php echo $current_user['role']; ?>"><?php echo ucfirst($current_user['role']); ?></span></p>
                <?php if (!in_array($current_user['role'], ['admin', 'manager'])): ?>
                    <p><i class="fas fa-info-circle"></i> <strong>Note:</strong> You can view all task details, but only managers and admins can mark tasks as completed.</p>
                <?php endif; ?>
            </div>

            <div class="task-detail-card">
                <div class="task-header">
                    <h1 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h1>
                    <span class="task-status status-<?php echo $task['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                    </span>
                    <div class="task-priority priority-<?php echo $task['priority']; ?>">
                        <i class="fas fa-flag"></i> <?php echo ucfirst($task['priority']); ?> Priority
                    </div>
                </div>
                
                <div class="task-content">
                    <?php if ($task['description']): ?>
                        <div class="task-section">
                            <h2 class="section-title">Task Description</h2>
                            <div class="task-description">
                                <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="task-section">
                        <h2 class="section-title">Task Information</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Project</div>
                                <div class="info-value">
                                    <?php echo $task['project_name'] ? htmlspecialchars($task['project_name']) : 'No Project Assigned'; ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Assigned To</div>
                                <div class="info-value">
                                    <?php echo $task['assignee_name'] ? htmlspecialchars($task['assignee_name']) : 'Unassigned'; ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Priority Level</div>
                                <div class="info-value">
                                    <span class="status-badge status-<?php echo $task['priority']; ?>">
                                        <?php echo ucfirst($task['priority']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="status-badge status-<?php echo $task['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($task['due_date']): ?>
                                <div class="info-item">
                                    <div class="info-label">Due Date</div>
                                    <div class="info-value">
                                        <i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($task['due_date'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($task['estimated_hours']): ?>
                                <div class="info-item">
                                    <div class="info-label">Estimated Hours</div>
                                    <div class="info-value">
                                        <i class="fas fa-clock"></i> <?php echo $task['estimated_hours']; ?> hours
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <div class="info-label">Created On</div>
                                <div class="info-value">
                                    <i class="fas fa-calendar-plus"></i> <?php echo date('F j, Y \a\t g:i A', strtotime($task['created_at'])); ?>
                                </div>
                            </div>
                            
                            <?php if ($task['completed_at']): ?>
                                <div class="info-item">
                                    <div class="info-label">Completed On</div>
                                    <div class="info-value">
                                        <i class="fas fa-check-circle"></i> <?php echo date('F j, Y \a\t g:i A', strtotime($task['completed_at'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($task['completed_by_name']): ?>
                                <div class="info-item">
                                    <div class="info-label">Completed By</div>
                                    <div class="info-value">
                                        <i class="fas fa-user-check"></i> <?php echo htmlspecialchars($task['completed_by_name']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Files & Media visible to assignee and managers -->
                    <div class="task-section">
                        <h2 class="section-title">Files & Media</h2>
                        <?php if (!empty($formattedFiles)): ?>
                            <div class="info-grid" style="grid-template-columns: 1fr;">
                                <div>
                                    <?php echo $fileViewer->renderFileGallery($formattedFiles, [
                                        'show_delete' => in_array($current_user['role'], ['admin','manager']) || ($task['assigned_to'] == $current_user['id']),
                                        'current_user_id' => $current_user['id'],
                                        'entity_type' => 'task',
                                        'entity_id' => $task_id
                                    ]); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="info-item">
                                <div class="info-label">Files</div>
                                <div class="info-value">No files uploaded yet.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="task-actions">
                    <?php 
                    // Check if user can modify this task
                    $can_modify = false;
                    if (in_array($current_user['role'], ['admin', 'manager'])) {
                        $can_modify = true;
                    } else {
                        $can_modify = ($task['assigned_to'] == $current_user['id']);
                    }
                    ?>
                    
                    <?php if ($can_modify): ?>
                        <?php if ($task['status'] === 'pending'): ?>
                            <form method="POST" action="simple-task-management.php" style="display: inline;">
                                <input type="hidden" name="action" value="start">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-play"></i> Start Task
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($task['status'] === 'pending' || $task['status'] === 'in_progress'): ?>
                            <form method="POST" action="simple-task-management.php" style="display: inline;">
                                <input type="hidden" name="action" value="hold">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-pause"></i> Put On Hold
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($task['status'] !== 'completed'): ?>
                            <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                                <form method="POST" action="simple-task-management.php" style="display: inline;">
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Mark this task as completed?')">
                                        <i class="fas fa-check"></i> Mark Complete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="btn btn-secondary" style="cursor: not-allowed; opacity: 0.6;" title="Only managers and admins can mark tasks as completed">
                                    <i class="fas fa-lock"></i> Complete (Restricted)
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                        <a href="edit-task.php?id=<?php echo $task['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Task
                        </a>
                    <?php endif; ?>
                    
                    <a href="simple-task-management.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Back to Task List
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
