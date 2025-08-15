<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/MultimediaManager.php';

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

// Get server limits
function getBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

$serverLimits = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time')
];

$serverLimitsBytes = [
    'upload_max_filesize' => getBytes($serverLimits['upload_max_filesize']),
    'post_max_size' => getBytes($serverLimits['post_max_size']),
    'memory_limit' => getBytes($serverLimits['memory_limit'])
];

$effectiveLimit = min($serverLimitsBytes['upload_max_filesize'], $serverLimitsBytes['post_max_size']);

// Get available tasks for upload
$tasks = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, title, status FROM tasks ORDER BY created_at DESC LIMIT 10");
    $tasks = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}

// Get user's upload history
$uploadHistory = [];
try {
    $stmt = $pdo->prepare("
        SELECT mf.*, t.title as task_title 
        FROM multimedia_files mf 
        LEFT JOIN tasks t ON mf.entity_id = t.id 
        WHERE mf.uploaded_by = ? AND mf.entity_type = 'task'
        ORDER BY mf.uploaded_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$current_user['id']]);
    $uploadHistory = $stmt->fetchAll();
} catch (Exception $e) {
    // Ignore history errors
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Background File Upload - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .background-upload-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .upload-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .upload-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .upload-section h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .upload-area-background {
            border: 3px dashed #007bff;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 20px 0;
            position: relative;
        }
        .upload-area-background:hover {
            border-color: #0056b3;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .upload-area-background.dragover {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }
        .upload-prompt-background i {
            font-size: 3em;
            color: #007bff;
            margin-bottom: 15px;
        }
        .upload-prompt-background h3 {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 10px;
        }
        .upload-prompt-background p {
            font-size: 1em;
            color: #666;
            margin-bottom: 8px;
        }
        .upload-prompt-background small {
            color: #888;
            font-size: 0.85em;
        }
        .task-selector {
            margin: 20px 0;
        }
        .task-selector label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .task-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 1em;
            background: white;
        }
        .task-selector select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        .upload-queue {
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        .upload-item-background {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .upload-item-icon {
            font-size: 1.5em;
            color: #007bff;
        }
        .upload-item-info {
            flex: 1;
        }
        .upload-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .upload-item-size {
            font-size: 0.9em;
            color: #666;
        }
        .upload-item-progress {
            flex: 1;
            margin: 0 15px;
        }
        .progress-bar-background {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill-background {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            width: 0%;
            transition: width 0.3s ease;
        }
        .upload-item-status {
            font-size: 0.9em;
            font-weight: 600;
            min-width: 80px;
            text-align: center;
        }
        .status-queued { color: #6c757d; }
        .status-uploading { color: #007bff; }
        .status-completed { color: #28a745; }
        .status-error { color: #dc3545; }
        .upload-item-actions {
            display: flex;
            gap: 5px;
        }
        .btn-small {
            padding: 4px 8px;
            font-size: 0.8em;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-cancel {
            background: #dc3545;
            color: white;
        }
        .btn-cancel:hover {
            background: #c82333;
        }
        .btn-retry {
            background: #ffc107;
            color: #212529;
        }
        .btn-retry:hover {
            background: #e0a800;
        }
        .upload-stats {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #2196f3;
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .upload-history {
            margin-top: 20px;
        }
        .history-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .history-icon {
            font-size: 1.2em;
            color: #28a745;
        }
        .history-info {
            flex: 1;
        }
        .history-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        .history-details {
            font-size: 0.85em;
            color: #666;
        }
        .history-date {
            font-size: 0.8em;
            color: #888;
        }
        .notification-area {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }
        .notification {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-left: 4px solid #007bff;
            animation: slideIn 0.3s ease;
        }
        .notification.success {
            border-left-color: #28a745;
        }
        .notification.error {
            border-left-color: #dc3545;
        }
        .notification.warning {
            border-left-color: #ffc107;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .notification-title {
            font-weight: 600;
            color: #333;
        }
        .notification-close {
            background: none;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
            color: #666;
        }
        .notification-message {
            color: #666;
            font-size: 0.9em;
        }
        .server-info-compact {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .server-info-compact h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1em;
        }
        .server-limits {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            font-size: 0.9em;
        }
        .server-limit {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .server-limit-value {
            font-weight: 600;
            color: #007bff;
        }
        .work-while-uploading {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .work-while-uploading h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        .work-while-uploading ul {
            color: #856404;
            line-height: 1.6;
        }
        .work-while-uploading li {
            margin-bottom: 8px;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .quick-action {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        .quick-action:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-decoration: none;
            color: #333;
        }
        .quick-action i {
            font-size: 2em;
            color: #007bff;
            margin-bottom: 10px;
        }
        .quick-action h4 {
            margin-bottom: 8px;
        }
        .quick-action p {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="background-upload-container">
        <div class="upload-section">
            <h1><i class="fas fa-cloud-upload-alt"></i> Background File Upload</h1>
            <p>Upload large files in the background while you continue working on other tasks.</p>
            
            <div class="server-info-compact">
                <h4><i class="fas fa-server"></i> Server Configuration</h4>
                <div class="server-limits">
                    <div class="server-limit">
                        <span>Max File Size:</span>
                        <span class="server-limit-value"><?php echo formatBytes($effectiveLimit); ?></span>
                    </div>
                    <div class="server-limit">
                        <span>Upload Timeout:</span>
                        <span class="server-limit-value"><?php echo $serverLimits['max_execution_time']; ?>s</span>
                    </div>
                    <div class="server-limit">
                        <span>Memory Limit:</span>
                        <span class="server-limit-value"><?php echo $serverLimits['memory_limit']; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="work-while-uploading">
                <h3><i class="fas fa-lightbulb"></i> Work While Uploading</h3>
                <ul>
                    <li><strong>Start upload</strong> and continue with other tasks</li>
                    <li><strong>Monitor progress</strong> in real-time notifications</li>
                    <li><strong>Switch tabs</strong> - upload continues in background</li>
                    <li><strong>Close upload page</strong> - files still upload</li>
                    <li><strong>Get notified</strong> when uploads complete</li>
                </ul>
            </div>
        </div>
        
        <div class="upload-grid">
            <!-- Upload Section -->
            <div class="upload-section">
                <h2><i class="fas fa-upload"></i> Start Background Upload</h2>
                
                <div class="task-selector">
                    <label for="task-select">Select Task to Upload To:</label>
                    <select id="task-select">
                        <option value="">Choose a task...</option>
                        <?php foreach ($tasks as $task): ?>
                            <option value="<?php echo $task['id']; ?>">
                                <?php echo htmlspecialchars($task['title']); ?> (<?php echo $task['status']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="upload-area-background" id="background-upload-area">
                    <div class="upload-prompt-background">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3>Drag & Drop Files Here</h3>
                        <p>or click to browse files</p>
                        <small>Files will upload in background while you work</small>
                    </div>
                    <input type="file" id="background-file-input" multiple style="display: none;">
                </div>
                
                <div class="upload-stats">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value" id="total-files">0</div>
                            <div class="stat-label">Total Files</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="uploading-files">0</div>
                            <div class="stat-label">Uploading</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="completed-files">0</div>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="total-size">0 B</div>
                            <div class="stat-label">Total Size</div>
                        </div>
                    </div>
                </div>
                
                <div class="upload-queue" id="upload-queue"></div>
            </div>
            
            <!-- Work Section -->
            <div class="upload-section">
                <h2><i class="fas fa-tasks"></i> Continue Working</h2>
                
                <div class="quick-actions">
                    <a href="task-management.php" class="quick-action">
                        <i class="fas fa-list"></i>
                        <h4>Task Management</h4>
                        <p>View and manage all tasks</p>
                    </a>
                    <a href="create-task.php" class="quick-action">
                        <i class="fas fa-plus"></i>
                        <h4>Create Task</h4>
                        <p>Add new tasks to the system</p>
                    </a>
                    <a href="index.php" class="quick-action">
                        <i class="fas fa-chart-bar"></i>
                        <h4>Dashboard</h4>
                        <p>View project overview</p>
                    </a>
                    <a href="large-file-upload.php" class="quick-action">
                        <i class="fas fa-file-upload"></i>
                        <h4>Large File Upload</h4>
                        <p>Direct upload with progress</p>
                    </a>
                </div>
                
                <div class="upload-history">
                    <h3><i class="fas fa-history"></i> Recent Uploads</h3>
                    <?php if (empty($uploadHistory)): ?>
                        <p style="color: #666; font-style: italic;">No recent uploads found.</p>
                    <?php else: ?>
                        <?php foreach ($uploadHistory as $file): ?>
                            <div class="history-item">
                                <div class="history-icon">
                                    <i class="fas fa-file"></i>
                                </div>
                                <div class="history-info">
                                    <div class="history-name"><?php echo htmlspecialchars($file['original_filename']); ?></div>
                                    <div class="history-details">
                                        <?php echo formatBytes($file['file_size']); ?> • 
                                        <?php echo htmlspecialchars($file['task_title'] ?? 'Unknown Task'); ?>
                                    </div>
                                </div>
                                <div class="history-date">
                                    <?php echo date('M j, Y g:i A', strtotime($file['uploaded_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
            <a href="test-upload-limits.php" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Test Upload Limits
            </a>
        </div>
    </div>
    
    <!-- Notification Area -->
    <div class="notification-area" id="notification-area"></div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('background-upload-area');
            const fileInput = document.getElementById('background-file-input');
            const taskSelect = document.getElementById('task-select');
            const uploadQueue = document.getElementById('upload-queue');
            const notificationArea = document.getElementById('notification-area');
            
            // Upload tracking
            let uploads = [];
            let uploadCounter = 0;
            
            // Stats elements
            const totalFilesEl = document.getElementById('total-files');
            const uploadingFilesEl = document.getElementById('uploading-files');
            const completedFilesEl = document.getElementById('completed-files');
            const totalSizeEl = document.getElementById('total-size');
            
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
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
                
                const files = Array.from(e.dataTransfer.files);
                handleFiles(files);
            });
            
            fileInput.addEventListener('change', function() {
                const files = Array.from(this.files);
                handleFiles(files);
                this.value = ''; // Reset input
            });
            
            function handleFiles(files) {
                const selectedTask = taskSelect.value;
                if (!selectedTask) {
                    showNotification('Please select a task first', 'warning');
                    return;
                }
                
                files.forEach(file => {
                    addToUploadQueue(file, selectedTask);
                });
                
                updateStats();
            }
            
            function addToUploadQueue(file, taskId) {
                const uploadId = ++uploadCounter;
                const upload = {
                    id: uploadId,
                    file: file,
                    taskId: taskId,
                    status: 'queued',
                    progress: 0,
                    startTime: null,
                    xhr: null
                };
                
                uploads.push(upload);
                renderUploadItem(upload);
                
                // Start upload after a short delay
                setTimeout(() => {
                    startUpload(upload);
                }, 1000);
            }
            
            function renderUploadItem(upload) {
                const uploadItem = document.createElement('div');
                uploadItem.className = 'upload-item-background';
                uploadItem.id = `upload-${upload.id}`;
                
                uploadItem.innerHTML = `
                    <div class="upload-item-icon">
                        <i class="fas fa-file"></i>
                    </div>
                    <div class="upload-item-info">
                        <div class="upload-item-name">${upload.file.name}</div>
                        <div class="upload-item-size">${formatBytes(upload.file.size)}</div>
                    </div>
                    <div class="upload-item-progress">
                        <div class="progress-bar-background">
                            <div class="progress-fill-background" style="width: ${upload.progress}%"></div>
                        </div>
                    </div>
                    <div class="upload-item-status status-${upload.status}">
                        ${getStatusText(upload.status)}
                    </div>
                    <div class="upload-item-actions">
                        ${upload.status === 'queued' || upload.status === 'uploading' ? 
                            `<button class="btn-small btn-cancel" onclick="cancelUpload(${upload.id})">
                                <i class="fas fa-times"></i>
                            </button>` : ''
                        }
                        ${upload.status === 'error' ? 
                            `<button class="btn-small btn-retry" onclick="retryUpload(${upload.id})">
                                <i class="fas fa-redo"></i>
                            </button>` : ''
                        }
                    </div>
                `;
                
                uploadQueue.appendChild(uploadItem);
            }
            
            function updateUploadItem(upload) {
                const uploadItem = document.getElementById(`upload-${upload.id}`);
                if (!uploadItem) return;
                
                const progressFill = uploadItem.querySelector('.progress-fill-background');
                const status = uploadItem.querySelector('.upload-item-status');
                const actions = uploadItem.querySelector('.upload-item-actions');
                
                progressFill.style.width = upload.progress + '%';
                status.className = `upload-item-status status-${upload.status}`;
                status.textContent = getStatusText(upload.status);
                
                // Update actions
                actions.innerHTML = '';
                if (upload.status === 'queued' || upload.status === 'uploading') {
                    actions.innerHTML = `
                        <button class="btn-small btn-cancel" onclick="cancelUpload(${upload.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                } else if (upload.status === 'error') {
                    actions.innerHTML = `
                        <button class="btn-small btn-retry" onclick="retryUpload(${upload.id})">
                            <i class="fas fa-redo"></i>
                        </button>
                    `;
                }
            }
            
            function getStatusText(status) {
                switch(status) {
                    case 'queued': return 'Queued';
                    case 'uploading': return 'Uploading';
                    case 'completed': return 'Completed';
                    case 'error': return 'Error';
                    case 'cancelled': return 'Cancelled';
                    default: return 'Unknown';
                }
            }
            
            function startUpload(upload) {
                upload.status = 'uploading';
                upload.startTime = Date.now();
                updateUploadItem(upload);
                
                const formData = new FormData();
                formData.append('file', upload.file);
                formData.append('entity_type', 'task');
                formData.append('entity_id', upload.taskId);
                
                const xhr = new XMLHttpRequest();
                upload.xhr = xhr;
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        upload.progress = (e.loaded / e.total) * 100;
                        updateUploadItem(upload);
                    }
                });
                
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                upload.status = 'completed';
                                upload.progress = 100;
                                showNotification(`✅ ${upload.file.name} uploaded successfully!`, 'success');
                            } else {
                                upload.status = 'error';
                                showNotification(`❌ ${upload.file.name} upload failed: ${response.message}`, 'error');
                            }
                        } catch (e) {
                            upload.status = 'error';
                            showNotification(`❌ ${upload.file.name} upload failed: Invalid response`, 'error');
                        }
                    } else {
                        upload.status = 'error';
                        showNotification(`❌ ${upload.file.name} upload failed: HTTP ${xhr.status}`, 'error');
                    }
                    
                    updateUploadItem(upload);
                    updateStats();
                });
                
                xhr.addEventListener('error', function() {
                    upload.status = 'error';
                    updateUploadItem(upload);
                    updateStats();
                    showNotification(`❌ ${upload.file.name} upload failed: Network error`, 'error');
                });
                
                xhr.addEventListener('abort', function() {
                    upload.status = 'cancelled';
                    updateUploadItem(upload);
                    updateStats();
                    showNotification(`⚠️ ${upload.file.name} upload cancelled`, 'warning');
                });
                
                xhr.open('POST', 'api/upload.php');
                xhr.send(formData);
            }
            
            function updateStats() {
                const totalFiles = uploads.length;
                const uploadingFiles = uploads.filter(u => u.status === 'uploading').length;
                const completedFiles = uploads.filter(u => u.status === 'completed').length;
                const totalSize = uploads.reduce((sum, u) => sum + u.file.size, 0);
                
                totalFilesEl.textContent = totalFiles;
                uploadingFilesEl.textContent = uploadingFiles;
                completedFilesEl.textContent = completedFiles;
                totalSizeEl.textContent = formatBytes(totalSize);
            }
            
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                
                notification.innerHTML = `
                    <div class="notification-header">
                        <div class="notification-title">
                            ${type === 'success' ? '✅ Success' : 
                              type === 'error' ? '❌ Error' : 
                              type === 'warning' ? '⚠️ Warning' : 'ℹ️ Info'}
                        </div>
                        <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
                    </div>
                    <div class="notification-message">${message}</div>
                `;
                
                notificationArea.appendChild(notification);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 5000);
            }
            
            function formatBytes(bytes) {
                if (bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            // Global functions for buttons
            window.cancelUpload = function(uploadId) {
                const upload = uploads.find(u => u.id === uploadId);
                if (upload && upload.xhr) {
                    upload.xhr.abort();
                }
            };
            
            window.retryUpload = function(uploadId) {
                const upload = uploads.find(u => u.id === uploadId);
                if (upload) {
                    upload.status = 'queued';
                    upload.progress = 0;
                    upload.xhr = null;
                    updateUploadItem(upload);
                    setTimeout(() => {
                        startUpload(upload);
                    }, 1000);
                }
            };
            
            // Check for completed uploads periodically
            setInterval(() => {
                const completedUploads = uploads.filter(u => u.status === 'completed');
                if (completedUploads.length > 0) {
                    // You could add additional logic here, like refreshing the page
                    // or updating a global upload counter
                }
            }, 5000);
        });
    </script>
</body>
</html>
