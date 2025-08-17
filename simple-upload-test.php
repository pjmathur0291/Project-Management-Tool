<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/MultimediaManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: quick-login-test.php');
    exit();
}

$current_user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'],
    'role' => $_SESSION['role']
];

// Get available tasks
$tasks = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, title, status FROM tasks ORDER BY created_at DESC LIMIT 10");
    $tasks = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .upload-area { 
            border: 2px dashed #ccc; 
            padding: 40px; 
            text-align: center; 
            margin: 20px 0; 
            cursor: pointer; 
        }
        .upload-area:hover { border-color: #007bff; }
        .progress { 
            width: 100%; 
            height: 20px; 
            background: #f0f0f0; 
            border-radius: 10px; 
            overflow: hidden; 
            margin: 10px 0; 
        }
        .progress-bar { 
            height: 100%; 
            background: #007bff; 
            width: 0%; 
            transition: width 0.3s; 
        }
        .status { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.info { background: #d1ecf1; color: #0c5460; }
        select, input[type="file"] { margin: 10px 0; padding: 10px; width: 100%; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simple Upload Test</h1>
        <p>Logged in as: <?php echo htmlspecialchars($current_user['full_name']); ?></p>
        
        <div>
            <label for="task-select">Select Task:</label>
            <select id="task-select">
                <option value="">Choose a task...</option>
                <?php foreach ($tasks as $task): ?>
                    <option value="<?php echo $task['id']; ?>">
                        <?php echo htmlspecialchars($task['title']); ?> (<?php echo $task['status']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="upload-area" onclick="document.getElementById('file-input').click()">
            <h3>Click to select files or drag and drop</h3>
            <p>Select files to upload</p>
        </div>
        
        <input type="file" id="file-input" multiple style="display: none;">
        
        <div id="upload-results"></div>
        
        <div style="margin-top: 20px;">
            <a href="background-upload-simple.php">Go to Full Upload Page</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.querySelector('.upload-area');
            const fileInput = document.getElementById('file-input');
            const taskSelect = document.getElementById('task-select');
            const resultsDiv = document.getElementById('upload-results');
            
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#007bff';
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#ccc';
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#ccc';
                const files = Array.from(e.dataTransfer.files);
                handleFiles(files);
            });
            
            fileInput.addEventListener('change', function() {
                const files = Array.from(this.files);
                handleFiles(files);
                this.value = '';
            });
            
            function handleFiles(files) {
                const selectedTask = taskSelect.value;
                if (!selectedTask) {
                    showStatus('Please select a task first', 'error');
                    return;
                }
                
                files.forEach(file => {
                    uploadFile(file, selectedTask);
                });
            }
            
            function uploadFile(file, taskId) {
                const resultDiv = document.createElement('div');
                resultDiv.className = 'status info';
                resultDiv.innerHTML = `
                    <strong>${file.name}</strong> (${formatBytes(file.size)})<br>
                    <div class="progress">
                        <div class="progress-bar"></div>
                    </div>
                    <div class="status-text">Starting upload...</div>
                `;
                resultsDiv.appendChild(resultDiv);
                
                const progressBar = resultDiv.querySelector('.progress-bar');
                const statusText = resultDiv.querySelector('.status-text');
                
                const formData = new FormData();
                formData.append('file', file);
                formData.append('entity_type', 'task');
                formData.append('entity_id', taskId);
                
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        progressBar.style.width = percent + '%';
                        statusText.textContent = `Uploading... ${Math.round(percent)}%`;
                    }
                });
                
                xhr.addEventListener('load', function() {
                    console.log('Response status:', xhr.status);
                    console.log('Response text:', xhr.responseText);
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resultDiv.className = 'status success';
                                statusText.textContent = 'Upload successful!';
                                progressBar.style.width = '100%';
                            } else {
                                resultDiv.className = 'status error';
                                statusText.textContent = 'Upload failed: ' + response.message;
                            }
                        } catch (e) {
                            resultDiv.className = 'status error';
                            statusText.textContent = 'Upload failed: Invalid response format';
                        }
                    } else {
                        resultDiv.className = 'status error';
                        statusText.textContent = 'Upload failed: HTTP ' + xhr.status;
                    }
                });
                
                xhr.addEventListener('error', function() {
                    resultDiv.className = 'status error';
                    statusText.textContent = 'Upload failed: Network error';
                });
                
                xhr.open('POST', 'api/upload.php');
                xhr.send(formData);
            }
            
            function showStatus(message, type) {
                const statusDiv = document.createElement('div');
                statusDiv.className = 'status ' + type;
                statusDiv.textContent = message;
                resultsDiv.appendChild(statusDiv);
                
                setTimeout(() => {
                    statusDiv.remove();
                }, 5000);
            }
            
            function formatBytes(bytes) {
                if (bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        });
    </script>
</body>
</html>
