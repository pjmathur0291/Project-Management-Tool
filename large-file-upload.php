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

// Get a sample task for testing
$sampleTask = null;
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, title FROM tasks LIMIT 1");
    $sampleTask = $stmt->fetch();
} catch (Exception $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Large File Upload (10GB) - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .large-upload-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .upload-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .upload-section h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        .server-info {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .server-info h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        .limit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .limit-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .limit-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #2196f3;
        }
        .upload-area-large {
            border: 3px dashed #007bff;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        .upload-area-large:hover {
            border-color: #0056b3;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .upload-area-large.dragover {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }
        .upload-prompt-large i {
            font-size: 4em;
            color: #007bff;
            margin-bottom: 20px;
        }
        .upload-prompt-large h3 {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 15px;
        }
        .upload-prompt-large p {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 10px;
        }
        .upload-prompt-large small {
            color: #888;
            font-size: 0.9em;
        }
        .progress-container {
            margin: 20px 0;
            display: none;
        }
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .progress-bar-large {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress-fill-large {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            width: 0%;
            transition: width 0.3s ease;
            position: relative;
        }
        .progress-fill-large::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .upload-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .stat-value {
            font-size: 1.3em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .upload-result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        .upload-result.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .upload-result.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .upload-result.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .file-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            display: none;
        }
        .file-info h4 {
            color: #333;
            margin-bottom: 10px;
        }
        .file-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        .file-detail {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .file-detail:last-child {
            border-bottom: none;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .warning-box h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        .warning-box ul {
            color: #856404;
            line-height: 1.6;
        }
        .warning-box li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="large-upload-container">
        <div class="upload-section">
            <h1><i class="fas fa-cloud-upload-alt"></i> Large File Upload (10GB Support)</h1>
            
            <div class="server-info">
                <h3><i class="fas fa-server"></i> Server Configuration</h3>
                <p>Your server is configured to handle files up to <strong><?php echo formatBytes($effectiveLimit); ?></strong></p>
                
                <div class="limit-grid">
                    <div class="limit-item">
                        <div class="limit-value"><?php echo $serverLimits['upload_max_filesize']; ?></div>
                        <div>Upload Max Filesize</div>
                    </div>
                    <div class="limit-item">
                        <div class="limit-value"><?php echo $serverLimits['post_max_size']; ?></div>
                        <div>Post Max Size</div>
                    </div>
                    <div class="limit-item">
                        <div class="limit-value"><?php echo $serverLimits['memory_limit']; ?></div>
                        <div>Memory Limit</div>
                    </div>
                    <div class="limit-item">
                        <div class="limit-value"><?php echo $serverLimits['max_execution_time']; ?>s</div>
                        <div>Execution Time</div>
                    </div>
                </div>
            </div>
            
            <div class="warning-box">
                <h3><i class="fas fa-exclamation-triangle"></i> Important Notes for Large File Uploads</h3>
                <ul>
                    <li><strong>Upload Time:</strong> 10GB files may take 10-30 minutes to upload</li>
                    <li><strong>Stable Connection:</strong> Ensure your internet connection is stable</li>
                    <li><strong>Don't Close Browser:</strong> Keep the browser open during upload</li>
                    <li><strong>Disk Space:</strong> Ensure server has >10GB free space</li>
                    <li><strong>Progress Tracking:</strong> Monitor upload progress below</li>
                    <li><strong>Backup:</strong> Consider backing up large files before upload</li>
                </ul>
            </div>
        </div>
        
        <?php if ($sampleTask): ?>
            <div class="upload-section">
                <h2><i class="fas fa-upload"></i> Upload Large File</h2>
                <p>Upload your large file (up to 10GB) to task: <strong><?php echo htmlspecialchars($sampleTask['title']); ?></strong></p>
                
                <div class="upload-area-large" id="large-upload-area">
                    <div class="upload-prompt-large">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3>Drag & Drop Your Large File Here</h3>
                        <p>or click to browse files</p>
                        <small>Maximum size: <?php echo formatBytes($effectiveLimit); ?> | Supported: All file types</small>
                    </div>
                    <input type="file" id="large-file-input" style="display: none;">
                </div>
                
                <div class="file-info" id="file-info">
                    <h4><i class="fas fa-file"></i> File Information</h4>
                    <div class="file-details" id="file-details"></div>
                </div>
                
                <div class="progress-container" id="progress-container">
                    <div class="progress-header">
                        <span><i class="fas fa-clock"></i> Upload Progress</span>
                        <span id="progress-percentage">0%</span>
                    </div>
                    <div class="progress-bar-large">
                        <div class="progress-fill-large" id="progress-fill-large"></div>
                    </div>
                    
                    <div class="upload-stats">
                        <div class="stat-item">
                            <div class="stat-value" id="uploaded-size">0 B</div>
                            <div class="stat-label">Uploaded</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="total-size">0 B</div>
                            <div class="stat-label">Total Size</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="upload-speed">0 KB/s</div>
                            <div class="stat-label">Speed</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="time-remaining">--:--</div>
                            <div class="stat-label">Time Remaining</div>
                        </div>
                    </div>
                </div>
                
                <div class="upload-result" id="upload-result"></div>
            </div>
        <?php else: ?>
            <div class="upload-section">
                <div class="alert alert-warning">
                    <h3>No Tasks Available</h3>
                    <p>Please create a task first before uploading large files.</p>
                    <a href="create-task.php" class="btn btn-primary">Create Task</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="upload-section">
            <h2><i class="fas fa-info-circle"></i> Upload Tips</h2>
            <div class="limit-grid">
                <div class="limit-item">
                    <i class="fas fa-wifi" style="font-size: 2em; color: #007bff; margin-bottom: 10px;"></i>
                    <h4>Stable Connection</h4>
                    <p>Use a wired connection if possible for large uploads</p>
                </div>
                <div class="limit-item">
                    <i class="fas fa-clock" style="font-size: 2em; color: #007bff; margin-bottom: 10px;"></i>
                    <h4>Be Patient</h4>
                    <p>Large files take time - don't interrupt the upload</p>
                </div>
                <div class="limit-item">
                    <i class="fas fa-hdd" style="font-size: 2em; color: #007bff; margin-bottom: 10px;"></i>
                    <h4>Check Space</h4>
                    <p>Ensure sufficient disk space on server</p>
                </div>
                <div class="limit-item">
                    <i class="fas fa-shield-alt" style="font-size: 2em; color: #007bff; margin-bottom: 10px;"></i>
                    <h4>Backup First</h4>
                    <p>Keep a backup of important large files</p>
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
            <a href="task-management.php" class="btn btn-info">
                <i class="fas fa-tasks"></i> Task Management
            </a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('large-upload-area');
            const fileInput = document.getElementById('large-file-input');
            const fileInfo = document.getElementById('file-info');
            const fileDetails = document.getElementById('file-details');
            const progressContainer = document.getElementById('progress-container');
            const progressFill = document.getElementById('progress-fill-large');
            const progressPercentage = document.getElementById('progress-percentage');
            const uploadedSize = document.getElementById('uploaded-size');
            const totalSize = document.getElementById('total-size');
            const uploadSpeed = document.getElementById('upload-speed');
            const timeRemaining = document.getElementById('time-remaining');
            const uploadResult = document.getElementById('upload-result');
            
            let uploadStartTime = 0;
            let lastUploadedBytes = 0;
            let speedUpdateInterval;
            
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
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFile(files[0]);
                }
            });
            
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    handleFile(this.files[0]);
                }
            });
            
            function handleFile(file) {
                // Show file info
                showFileInfo(file);
                
                // Check file size
                const maxSize = <?php echo $effectiveLimit; ?>;
                if (file.size > maxSize) {
                    showResult(`File too large! Maximum size is ${formatBytes(maxSize)}`, 'error');
                    return;
                }
                
                // Start upload
                uploadFile(file);
            }
            
            function showFileInfo(file) {
                fileDetails.innerHTML = `
                    <div class="file-detail">
                        <span>Name:</span>
                        <span>${file.name}</span>
                    </div>
                    <div class="file-detail">
                        <span>Size:</span>
                        <span>${formatBytes(file.size)}</span>
                    </div>
                    <div class="file-detail">
                        <span>Type:</span>
                        <span>${file.type || 'Unknown'}</span>
                    </div>
                    <div class="file-detail">
                        <span>Last Modified:</span>
                        <span>${new Date(file.lastModified).toLocaleString()}</span>
                    </div>
                `;
                fileInfo.style.display = 'block';
            }
            
            function uploadFile(file) {
                // Show progress
                progressContainer.style.display = 'block';
                progressFill.style.width = '0%';
                progressPercentage.textContent = '0%';
                uploadResult.style.display = 'none';
                
                // Initialize stats
                uploadStartTime = Date.now();
                lastUploadedBytes = 0;
                totalSize.textContent = formatBytes(file.size);
                uploadedSize.textContent = '0 B';
                uploadSpeed.textContent = '0 KB/s';
                timeRemaining.textContent = '--:--';
                
                // Start speed monitoring
                speedUpdateInterval = setInterval(updateSpeed, 1000);
                
                // Create form data
                const formData = new FormData();
                formData.append('file', file);
                formData.append('entity_type', 'task');
                formData.append('entity_id', '<?php echo $sampleTask ? $sampleTask['id'] : 1; ?>');
                
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressFill.style.width = percentComplete + '%';
                        progressPercentage.textContent = Math.round(percentComplete) + '%';
                        uploadedSize.textContent = formatBytes(e.loaded);
                        lastUploadedBytes = e.loaded;
                    }
                });
                
                xhr.addEventListener('load', function() {
                    clearInterval(speedUpdateInterval);
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showResult('✅ Upload successful! Large file uploaded successfully.', 'success');
                            } else {
                                showResult('❌ Upload failed: ' + response.message, 'error');
                            }
                        } catch (e) {
                            showResult('❌ Upload failed: Invalid response from server', 'error');
                        }
                    } else if (xhr.status === 413) {
                        showResult('❌ Upload failed: File too large (HTTP 413)', 'error');
                    } else {
                        showResult('❌ Upload failed: HTTP ' + xhr.status, 'error');
                    }
                });
                
                xhr.addEventListener('error', function() {
                    clearInterval(speedUpdateInterval);
                    showResult('❌ Upload failed: Network error', 'error');
                });
                
                xhr.addEventListener('abort', function() {
                    clearInterval(speedUpdateInterval);
                    showResult('⚠️ Upload cancelled by user', 'warning');
                });
                
                xhr.open('POST', 'api/upload.php');
                xhr.send(formData);
            }
            
            function updateSpeed() {
                const now = Date.now();
                const timeDiff = (now - uploadStartTime) / 1000; // seconds
                const bytesDiff = lastUploadedBytes;
                
                if (timeDiff > 0 && bytesDiff > 0) {
                    const speed = bytesDiff / timeDiff;
                    uploadSpeed.textContent = formatBytes(speed) + '/s';
                    
                    // Calculate time remaining
                    const totalBytes = <?php echo $effectiveLimit; ?>;
                    const remainingBytes = totalBytes - lastUploadedBytes;
                    if (speed > 0) {
                        const remainingTime = remainingBytes / speed;
                        timeRemaining.textContent = formatTime(remainingTime);
                    }
                }
            }
            
            function showResult(message, type) {
                uploadResult.textContent = message;
                uploadResult.className = 'upload-result ' + type;
                uploadResult.style.display = 'block';
            }
            
            function formatBytes(bytes) {
                if (bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            function formatTime(seconds) {
                if (seconds < 60) {
                    return Math.round(seconds) + 's';
                } else if (seconds < 3600) {
                    const minutes = Math.floor(seconds / 60);
                    const secs = Math.round(seconds % 60);
                    return minutes + 'm ' + secs + 's';
                } else {
                    const hours = Math.floor(seconds / 3600);
                    const minutes = Math.floor((seconds % 3600) / 60);
                    return hours + 'h ' + minutes + 'm';
                }
            }
        });
    </script>
</body>
</html>
