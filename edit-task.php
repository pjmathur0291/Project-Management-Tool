<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Check if user can edit tasks
$canEditTasks = in_array($current_user['role'], ['admin', 'manager']);
if (!$canEditTasks) {
    header('Location: task-management.php');
    exit();
}

$task_id = $_GET['id'] ?? null;
if (!$task_id) {
    header('Location: task-management.php');
    exit();
}

$success_message = '';
$error_message = '';

// Initialize database connection
$pdo = null;
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    $error_message = 'Database connection error: ' . $e->getMessage();
}

// Get task details
$task = null;
if ($pdo) {
    try {
        $task = getTaskById($task_id);
        if (!$task) {
            header('Location: task-management.php');
            exit();
        }
    } catch (Exception $e) {
        $error_message = 'Error loading task: ' . $e->getMessage();
    }
}

// Get projects and team members for dropdowns
$projects = [];
$team_members = [];
if ($pdo) {
    try {
        $projects = getAllProjects();
        $team_members = getAllUsers();
    } catch (Exception $e) {
        $error_message = 'Error loading data: ' . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEditTasks && $task) {
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
                'id' => $task_id,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'project_id' => $project_id ?: null,
                'assigned_to' => $assigned_to ?: null,
                'due_date' => $due_date ?: null,
                'estimated_hours' => $estimated_hours ?: null
            ];
            
            if (updateTask($taskData)) {
                $success_message = 'Task updated successfully!';
                
                // Handle file uploads if any
                if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
                    try {
                        $multimediaManager = new MultimediaManager($pdo);
                        $uploadedFiles = 0;
                        
                        // Handle multiple file uploads
                        $fileCount = count($_FILES['files']['name']);
                        for ($i = 0; $i < $fileCount; $i++) {
                            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                                $file = [
                                    'name' => $_FILES['files']['name'][$i],
                                    'type' => $_FILES['files']['type'][$i],
                                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                                    'error' => $_FILES['files']['error'][$i],
                                    'size' => $_FILES['files']['size'][$i]
                                ];
                                
                                $description = $_POST['file_descriptions'][$i] ?? '';
                                $result = $multimediaManager->uploadFile($file, 'task', $task_id, $current_user['id'], $description);
                                
                                if ($result['success']) {
                                    $uploadedFiles++;
                                }
                            }
                        }
                        
                        if ($uploadedFiles > 0) {
                            $success_message .= " $uploadedFiles file(s) uploaded successfully.";
                        }
                    } catch (Exception $e) {
                        error_log("Error uploading files: " . $e->getMessage());
                        $success_message .= " (File upload had issues)";
                    }
                }
                
                // Refresh task data
                $task = getTaskById($task_id);
            } else {
                $error_message = 'Failed to update task. Please try again.';
            }
        } catch (Exception $e) {
            $error_message = 'Error updating task: ' . $e->getMessage();
        }
    }
}

// Get existing files for this task
$existingFiles = [];
if ($pdo && $task) {
    try {
        $multimediaManager = new MultimediaManager($pdo);
        $files = $multimediaManager->getFilesByEntity('task', $task_id);
        
        foreach ($files as $file) {
            $existingFiles[] = [
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
        error_log("Error loading files: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-task-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
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
        
        /* File Upload Styles */
        .file-upload-section {
            margin-top: 10px;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
            margin-bottom: 15px;
        }
        
        .upload-area:hover,
        .upload-area.dragover {
            border-color: #007bff;
            background: #f0f8ff;
        }
        
        .upload-prompt i {
            font-size: 2.5em;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .upload-prompt p {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 10px;
        }
        
        .upload-prompt small {
            color: #666;
            font-size: 0.9em;
        }
        
        .upload-queue {
            margin-top: 15px;
        }
        
        .upload-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .upload-item-info {
            flex: 1;
        }
        
        .upload-item-name {
            font-weight: 500;
            color: #333;
            display: block;
        }
        
        .upload-item-size {
            font-size: 0.9em;
            color: #666;
        }
        
        .remove-file {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 0.8em;
        }
        
        .remove-file:hover {
            background: #c82333;
        }
        
        /* Existing Files Section */
        .existing-files {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .existing-files h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .file-item {
            background: white;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-icon {
            font-size: 1.5em;
            color: #007bff;
        }
        
        .file-details {
            flex: 1;
        }
        
        .file-name {
            font-weight: 500;
            color: #333;
            font-size: 0.9em;
            margin-bottom: 2px;
        }
        
        .file-size {
            font-size: 0.8em;
            color: #666;
        }
        
        .file-actions {
            display: flex;
            gap: 5px;
        }
        
        .file-actions .btn {
            padding: 3px 8px;
            font-size: 0.7em;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeFileUpload();
        });
        
        function initializeFileUpload() {
            const uploadArea = document.getElementById('upload-area-edit');
            const fileInput = uploadArea.querySelector('.file-input');
            const uploadQueue = document.getElementById('upload-queue-edit');
            
            // Drag and drop functionality
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                handleFiles(files);
            });
            
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function() {
                handleFiles(this.files);
            });
            
            function handleFiles(files) {
                Array.from(files).forEach(file => {
                    addFileToQueue(file);
                });
            }
            
            function addFileToQueue(file) {
                const uploadItem = document.createElement('div');
                uploadItem.className = 'upload-item';
                uploadItem.dataset.filename = file.name;
                
                const fileSize = formatFileSize(file.size);
                
                uploadItem.innerHTML = `
                    <div class="upload-item-info">
                        <span class="upload-item-name">${file.name}</span>
                        <span class="upload-item-size">${fileSize}</span>
                    </div>
                    <button type="button" class="remove-file" onclick="removeFile(this)">Remove</button>
                `;
                
                uploadQueue.appendChild(uploadItem);
            }
        }
        
        function removeFile(button) {
            const uploadItem = button.parentElement;
            const filename = uploadItem.dataset.filename;
            
            // Remove from file input
            const fileInput = document.querySelector('.file-input');
            const dt = new DataTransfer();
            
            for (let i = 0; i < fileInput.files.length; i++) {
                if (fileInput.files[i].name !== filename) {
                    dt.items.add(fileInput.files[i]);
                }
            }
            
            fileInput.files = dt.files;
            
            // Remove from queue
            uploadItem.remove();
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function deleteExistingFile(fileId) {
            if (confirm('Are you sure you want to delete this file?')) {
                fetch(`api/upload.php?id=${fileId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting file: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting file:', error);
                    alert('Error deleting file');
                });
            }
        }
    </script>
</head>
<body>
    <div class="edit-task-container">
        <div class="form-header">
            <h1><i class="fas fa-edit"></i> Edit Task</h1>
            <p>Update task details and manage attached files</p>
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

        <?php if ($task): ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Task Title *</label>
                    <input type="text" class="form-input" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="3"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project</label>
                        <select class="form-select" name="project_id">
                            <option value="">Select Project (Optional)</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>" <?php echo ($task['project_id'] ?? '') == $project['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select class="form-select" name="assigned_to">
                            <option value="">Select Member (Optional)</option>
                            <?php foreach ($team_members as $member): ?>
                                <option value="<?php echo $member['id']; ?>" <?php echo ($task['assigned_to'] ?? '') == $member['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($member['full_name']); ?> - <?php echo htmlspecialchars($member['job_title'] ?? 'No Title'); ?> (<?php echo ucfirst($member['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Priority *</label>
                        <select class="form-select" name="priority" required>
                            <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-input" name="due_date" value="<?php echo $task['due_date'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Estimated Hours</label>
                    <input type="number" class="form-input" name="estimated_hours" min="0" step="0.5" value="<?php echo $task['estimated_hours'] ?? ''; ?>">
                </div>
                
                <!-- Existing Files Section -->
                <?php if (!empty($existingFiles)): ?>
                    <div class="existing-files">
                        <h3><i class="fas fa-paperclip"></i> Existing Files (<?php echo count($existingFiles); ?>)</h3>
                        <div class="file-list">
                            <?php foreach ($existingFiles as $file): ?>
                                <div class="file-item">
                                    <div class="file-icon">
                                        <i class="<?php echo $file['icon']; ?>"></i>
                                    </div>
                                    <div class="file-details">
                                        <div class="file-name"><?php echo htmlspecialchars($file['original_filename']); ?></div>
                                        <div class="file-size"><?php echo $file['formatted_size']; ?></div>
                                    </div>
                                    <div class="file-actions">
                                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteExistingFile(<?php echo $file['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- File Upload Section -->
                <div class="form-group">
                    <label class="form-label">Add More Files (Optional)</label>
                    <div class="file-upload-section">
                        <div class="upload-area" id="upload-area-edit">
                            <div class="upload-prompt">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drag and drop files here or click to browse</p>
                                <small>Supported: Images (JPG, PNG, GIF), Videos (MP4, AVI, MOV), Documents (PDF, DOC, XLS)</small>
                            </div>
                            <input type="file" class="file-input" name="files[]" multiple accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,.mp4,.avi,.mov,.wmv,.flv,.webm,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" style="display: none;">
                        </div>
                        <div class="upload-queue" id="upload-queue-edit"></div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="task-management.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Tasks
                    </a>
                    <a href="task-detail.php?id=<?php echo $task_id; ?>" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Task
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Task
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-error">
                <h3>Task Not Found</h3>
                <p>The task you're trying to edit could not be found.</p>
            </div>
            
            <div class="form-actions">
                <a href="task-management.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Tasks
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
