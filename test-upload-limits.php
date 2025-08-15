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
    'max_input_time' => ini_get('max_input_time'),
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'max_file_uploads' => ini_get('max_file_uploads')
];

$serverLimitsBytes = [
    'upload_max_filesize' => getBytes($serverLimits['upload_max_filesize']),
    'post_max_size' => getBytes($serverLimits['post_max_size']),
    'memory_limit' => getBytes($serverLimits['memory_limit'])
];

$effectiveLimit = min($serverLimitsBytes['upload_max_filesize'], $serverLimitsBytes['post_max_size']);

// Get database settings
$dbSettings = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('max_file_size', 'allowed_file_types')");
    $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}

$dbMaxFileSize = (int)($dbSettings['max_file_size'] ?? 2147483648);
$finalLimit = min($effectiveLimit, $dbMaxFileSize);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Limits Test - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .limits-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .limits-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .limits-section h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        .limit-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .limit-item:last-child {
            border-bottom: none;
        }
        .limit-name {
            font-weight: 500;
            color: #333;
        }
        .limit-value {
            color: #007bff;
            font-weight: 500;
        }
        .limit-value.warning {
            color: #ffc107;
        }
        .limit-value.danger {
            color: #dc3545;
        }
        .limit-value.success {
            color: #28a745;
        }
        .effective-limit {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .effective-limit h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        .effective-limit .size {
            font-size: 2em;
            font-weight: bold;
            color: #2196f3;
        }
        .recommendations {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .recommendations h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        .recommendations ul {
            color: #856404;
            line-height: 1.6;
        }
        .recommendations li {
            margin-bottom: 8px;
        }
        .test-upload {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .test-upload h3 {
            color: #333;
            margin-bottom: 15px;
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
        .upload-area:hover {
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
        .upload-progress {
            margin-top: 15px;
            display: none;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #007bff;
            width: 0%;
            transition: width 0.3s ease;
        }
        .progress-text {
            text-align: center;
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }
        .upload-result {
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
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
    </style>
</head>
<body>
    <div class="limits-container">
        <div class="limits-section">
            <h1><i class="fas fa-server"></i> Server Upload Limits</h1>
            
            <div class="effective-limit">
                <h3>Effective Maximum File Size</h3>
                <div class="size"><?php echo formatBytes($finalLimit); ?></div>
                <p>This is the maximum file size you can upload based on server configuration</p>
            </div>
            
            <h2>PHP Configuration</h2>
            <?php foreach ($serverLimits as $key => $value): ?>
                <div class="limit-item">
                    <span class="limit-name"><?php echo ucwords(str_replace('_', ' ', $key)); ?></span>
                    <span class="limit-value">
                        <?php 
                        if (in_array($key, ['upload_max_filesize', 'post_max_size', 'memory_limit'])) {
                            $bytes = $serverLimitsBytes[$key];
                            $formatted = formatBytes($bytes);
                            $class = '';
                            if ($bytes < 10485760) { // Less than 10MB
                                $class = 'danger';
                            } elseif ($bytes < 104857600) { // Less than 100MB
                                $class = 'warning';
                            } else {
                                $class = 'success';
                            }
                            echo "<span class='$class'>$formatted ($value)</span>";
                        } else {
                            echo $value;
                        }
                        ?>
                    </span>
                </div>
            <?php endforeach; ?>
            
            <h2>Database Settings</h2>
            <div class="limit-item">
                <span class="limit-name">Max File Size (Database)</span>
                <span class="limit-value"><?php echo formatBytes($dbMaxFileSize); ?></span>
            </div>
            <div class="limit-item">
                <span class="limit-name">Allowed File Types</span>
                <span class="limit-value"><?php echo htmlspecialchars($dbSettings['allowed_file_types'] ?? 'Not set'); ?></span>
            </div>
        </div>
        
        <div class="recommendations">
            <h3><i class="fas fa-lightbulb"></i> Recommendations for Large File Uploads</h3>
            <ul>
                <li><strong>Current Limit:</strong> You can upload files up to <?php echo formatBytes($finalLimit); ?></li>
                <li><strong>For 10GB files:</strong> You need to increase server limits significantly</li>
                <li><strong>Server Configuration:</strong> Contact your hosting provider to increase limits</li>
                <li><strong>Alternative:</strong> Consider splitting large files or using external storage</li>
                <li><strong>Memory:</strong> Ensure sufficient server memory for large uploads</li>
                <li><strong>Timeout:</strong> Large uploads may take time - be patient</li>
            </ul>
        </div>
        
        <div class="test-upload">
            <h3><i class="fas fa-upload"></i> Test File Upload</h3>
            <p>Test uploading a file to see if the current limits work:</p>
            
            <div class="upload-area" id="test-upload-area">
                <div class="upload-prompt">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click here or drag a file to test upload</p>
                    <small>Maximum size: <?php echo formatBytes($finalLimit); ?></small>
                </div>
                <input type="file" id="test-file-input" style="display: none;">
            </div>
            
            <div class="upload-progress" id="upload-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text" id="progress-text">0%</div>
            </div>
            
            <div class="upload-result" id="upload-result"></div>
        </div>
        
        <div class="limits-section">
            <h2><i class="fas fa-cog"></i> How to Increase Limits</h2>
            <h3>For XAMPP (Local Development):</h3>
            <ol>
                <li>Edit <code>php.ini</code> file in XAMPP</li>
                <li>Set <code>upload_max_filesize = 2G</code></li>
                <li>Set <code>post_max_size = 2G</code></li>
                <li>Set <code>memory_limit = 512M</code></li>
                <li>Set <code>max_execution_time = 300</code></li>
                <li>Restart Apache server</li>
            </ol>
            
            <h3>For Production Servers:</h3>
            <ol>
                <li>Contact your hosting provider</li>
                <li>Request increase in upload limits</li>
                <li>Consider using CDN or external storage</li>
                <li>Implement chunked uploads for very large files</li>
            </ol>
        </div>
        
        <div class="limits-section">
            <h2><i class="fas fa-info-circle"></i> Current Status</h2>
            <div class="limit-item">
                <span class="limit-name">Can upload 10GB files?</span>
                <span class="limit-value <?php echo $finalLimit >= 10737418240 ? 'success' : 'danger'; ?>">
                    <?php echo $finalLimit >= 10737418240 ? 'Yes' : 'No'; ?>
                </span>
            </div>
            <div class="limit-item">
                <span class="limit-name">Can upload 1GB files?</span>
                <span class="limit-value <?php echo $finalLimit >= 1073741824 ? 'success' : 'warning'; ?>">
                    <?php echo $finalLimit >= 1073741824 ? 'Yes' : 'No'; ?>
                </span>
            </div>
            <div class="limit-item">
                <span class="limit-name">Can upload 100MB files?</span>
                <span class="limit-value <?php echo $finalLimit >= 104857600 ? 'success' : 'danger'; ?>">
                    <?php echo $finalLimit >= 104857600 ? 'Yes' : 'No'; ?>
                </span>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
            <a href="task-management.php" class="btn btn-secondary">
                <i class="fas fa-tasks"></i> Task Management
            </a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('test-upload-area');
            const fileInput = document.getElementById('test-file-input');
            const progressBar = document.getElementById('upload-progress');
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            const uploadResult = document.getElementById('upload-result');
            
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#007bff';
                uploadArea.style.background = '#f0f8ff';
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#ddd';
                uploadArea.style.background = '#fafafa';
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#ddd';
                uploadArea.style.background = '#fafafa';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    testUpload(files[0]);
                }
            });
            
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    testUpload(this.files[0]);
                }
            });
            
            function testUpload(file) {
                // Show progress
                progressBar.style.display = 'block';
                progressFill.style.width = '0%';
                progressText.textContent = '0%';
                uploadResult.style.display = 'none';
                
                // Create form data
                const formData = new FormData();
                formData.append('file', file);
                formData.append('entity_type', 'task');
                formData.append('entity_id', '1'); // Test task
                
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressFill.style.width = percentComplete + '%';
                        progressText.textContent = Math.round(percentComplete) + '%';
                    }
                });
                
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showResult('Upload successful! File uploaded to test task.', 'success');
                            } else {
                                showResult('Upload failed: ' + response.message, 'error');
                            }
                        } catch (e) {
                            showResult('Upload failed: Invalid response from server', 'error');
                        }
                    } else if (xhr.status === 413) {
                        showResult('Upload failed: File too large (HTTP 413)', 'error');
                    } else {
                        showResult('Upload failed: HTTP ' + xhr.status, 'error');
                    }
                });
                
                xhr.addEventListener('error', function() {
                    showResult('Upload failed: Network error', 'error');
                });
                
                xhr.open('POST', 'api/upload.php');
                xhr.send(formData);
            }
            
            function showResult(message, type) {
                uploadResult.textContent = message;
                uploadResult.className = 'upload-result ' + type;
                uploadResult.style.display = 'block';
            }
        });
    </script>
</body>
</html>
