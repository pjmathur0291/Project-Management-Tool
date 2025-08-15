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
if (!$task_id) {
    header('Location: task-management.php');
    exit();
}

try {
    $pdo = getDBConnection();
    $multimediaManager = new MultimediaManager($pdo);
    $fileViewer = new FileViewer($multimediaManager);
    
    // Get task details
    $task = getTaskById($task_id);
    if (!$task) {
        header('Location: task-management.php');
        exit();
    }
    
    // Check if user can view this task
    $can_view = false;
    if (in_array($current_user['role'], ['admin', 'manager'])) {
        $can_view = true;
    } else {
        $can_view = ($task['assigned_to'] == $current_user['id']);
    }
    
    if (!$can_view) {
        header('Location: task-management.php');
        exit();
    }
    
    // Get multimedia files for this task
    $files = $multimediaManager->getFilesByEntity('task', $task_id);
    
    // Format files for display
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
            'is_document' => $multimediaManager->isDocument($file['file_type']),
            'thumbnail_path' => $file['file_type'] === 'images' ? 
                'uploads/thumbnails/thumb_' . $file['filename'] : null
        ];
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading task: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Details - <?php echo htmlspecialchars($task['title']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-tasks"></i> PM Tool</h2>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="task-management.php"><i class="fas fa-list-check"></i> Task Management</a>
                </li>
                <li class="nav-item active">
                    <a href="#"><i class="fas fa-eye"></i> Task Details</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <a href="task-management.php"><i class="fas fa-arrow-left"></i> Back to Tasks</a>
                    <span> / Task Details</span>
                </div>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?> (<?php echo ucfirst($current_user['role']); ?>)</span>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </header>

            <div class="content-container">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <!-- Task Details -->
                <div class="task-detail-container">
                    <div class="task-header">
                        <h1><?php echo htmlspecialchars($task['title']); ?></h1>
                        <div class="task-status">
                            <span class="status-badge status-<?php echo $task['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                            </span>
                        </div>
                    </div>

                    <div class="task-content">
                        <div class="task-info-grid">
                            <div class="task-main">
                                <div class="task-description">
                                    <h3>Description</h3>
                                    <p><?php echo nl2br(htmlspecialchars($task['description'] ?: 'No description provided')); ?></p>
                                </div>

                                <div class="task-meta">
                                    <div class="meta-item">
                                        <strong>Project:</strong> 
                                        <span><?php echo htmlspecialchars($task['project_name'] ?: 'No project assigned'); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Assigned to:</strong> 
                                        <span><?php echo htmlspecialchars($task['assignee_name'] ?: 'Unassigned'); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Priority:</strong> 
                                        <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Due Date:</strong> 
                                        <span><?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'Not set'; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Estimated Hours:</strong> 
                                        <span><?php echo $task['estimated_hours'] ?: 'Not set'; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Created:</strong> 
                                        <span><?php echo date('M j, Y g:i A', strtotime($task['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="task-actions">
                                <h3>Actions</h3>
                                <div class="action-buttons">
                                    <?php if ($task['status'] !== 'completed'): ?>
                                        <form method="POST" action="task-management.php" style="display: inline;">
                                            <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check"></i> Mark Complete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($task['status'] === 'pending'): ?>
                                        <form method="POST" action="task-management.php" style="display: inline;">
                                            <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                                            <input type="hidden" name="action" value="start">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-play"></i> Start Task
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                                        <a href="edit-task.php?id=<?php echo $task_id; ?>" class="btn btn-secondary">
                                            <i class="fas fa-edit"></i> Edit Task
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Multimedia Section -->
                        <div class="multimedia-section">
                            <div class="multimedia-header">
                                <div class="multimedia-title">
                                    <i class="fas fa-images"></i>
                                    Files & Media
                                    <span class="multimedia-count"><?php echo count($formattedFiles); ?></span>
                                </div>
                                <button class="multimedia-toggle" onclick="toggleMultimedia()">
                                    <i class="fas fa-chevron-down"></i> Show/Hide
                                </button>
                            </div>
                            
                            <div class="multimedia-content" id="multimedia-content">
                                <!-- Upload Form -->
                                <div class="upload-section">
                                    <h4>Upload Files</h4>
                                    <?php echo $fileViewer->renderUploadForm('task', $task_id); ?>
                                </div>

                                <!-- File Gallery -->
                                <div class="gallery-section">
                                    <h4>Uploaded Files</h4>
                                    <?php echo $fileViewer->renderFileGallery($formattedFiles, [
                                        'show_delete' => true,
                                        'current_user_id' => $current_user['id'],
                                        'entity_type' => 'task',
                                        'entity_id' => $task_id
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- File Viewer Modal -->
    <?php echo $fileViewer->renderFileViewerModal(); ?>

    <!-- Upload JavaScript -->
    <?php echo $fileViewer->getUploadJavaScript('task', $task_id); ?>

    <script>
        function toggleMultimedia() {
            const content = document.getElementById('multimedia-content');
            const toggle = document.querySelector('.multimedia-toggle i');
            
            if (content.classList.contains('show')) {
                content.classList.remove('show');
                toggle.className = 'fas fa-chevron-down';
            } else {
                content.classList.add('show');
                toggle.className = 'fas fa-chevron-up';
            }
        }

        // Show multimedia section by default
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('multimedia-content').classList.add('show');
        });
    </script>
</body>
</html>
