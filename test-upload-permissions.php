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

// Test upload directory permissions
$uploadTests = [];

// Test 1: Check if uploads directory exists
$uploadTests['uploads_dir_exists'] = is_dir('uploads');
$uploadTests['uploads_dir_writable'] = is_writable('uploads');

// Test 2: Check subdirectories
$subdirs = ['documents', 'images', 'videos', 'thumbnails'];
foreach ($subdirs as $subdir) {
    $path = "uploads/$subdir";
    $uploadTests["{$subdir}_exists"] = is_dir($path);
    $uploadTests["{$subdir}_writable"] = is_writable($path);
}

// Test 3: Try to create a test file
$testFile = 'uploads/documents/test_permission_' . time() . '.txt';
$testContent = 'Test file created at ' . date('Y-m-d H:i:s');
$uploadTests['can_create_file'] = file_put_contents($testFile, $testContent) !== false;

// Test 4: Try to delete the test file
if ($uploadTests['can_create_file']) {
    $uploadTests['can_delete_file'] = unlink($testFile);
} else {
    $uploadTests['can_delete_file'] = false;
}

// Test 5: Check MultimediaManager
try {
    $pdo = getDBConnection();
    $multimediaManager = new MultimediaManager($pdo);
    $uploadTests['multimedia_manager_loaded'] = true;
} catch (Exception $e) {
    $uploadTests['multimedia_manager_loaded'] = false;
    $uploadTests['multimedia_manager_error'] = $e->getMessage();
}

// Get a sample task for testing
$sampleTask = null;
try {
    $stmt = $pdo->query("SELECT id, title FROM tasks LIMIT 1");
    $sampleTask = $stmt->fetch();
} catch (Exception $e) {
    $uploadTests['sample_task_error'] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Permissions Test - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        .test-result {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .test-result.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .test-result.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .test-result.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .test-label {
            font-weight: 600;
        }
        .test-status {
            font-weight: bold;
        }
        .test-status.success { color: #28a745; }
        .test-status.error { color: #dc3545; }
        .test-status.warning { color: #ffc107; }
        .upload-test-area {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            background: #f8f9fa;
        }
        .upload-test-area:hover {
            border-color: #0056b3;
            background: #e3f2fd;
        }
        .summary-box {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .summary-box h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
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
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-section">
            <h1><i class="fas fa-cog"></i> Upload Permissions Test</h1>
            <p>This page tests if your upload directories have the correct permissions for file uploads.</p>
        </div>
        
        <div class="test-section">
            <h2><i class="fas fa-check-circle"></i> Test Results Summary</h2>
            
            <?php
            $totalTests = count($uploadTests);
            $passedTests = 0;
            $failedTests = 0;
            $warningTests = 0;
            
            foreach ($uploadTests as $test => $result) {
                if ($result === true) {
                    $passedTests++;
                } elseif ($result === false) {
                    $failedTests++;
                } else {
                    $warningTests++;
                }
            }
            ?>
            
            <div class="summary-box">
                <h3><i class="fas fa-chart-bar"></i> Test Summary</h3>
                <div class="summary-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $totalTests; ?></div>
                        <div class="stat-label">Total Tests</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: #28a745;"><?php echo $passedTests; ?></div>
                        <div class="stat-label">Passed</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: #dc3545;"><?php echo $failedTests; ?></div>
                        <div class="stat-label">Failed</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: #ffc107;"><?php echo $warningTests; ?></div>
                        <div class="stat-label">Warnings</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2><i class="fas fa-folder"></i> Directory Permissions</h2>
            
            <?php foreach ($uploadTests as $test => $result): ?>
                <?php
                $testClass = $result === true ? 'success' : ($result === false ? 'error' : 'warning');
                $statusText = $result === true ? 'PASS' : ($result === false ? 'FAIL' : 'WARNING');
                $statusClass = $result === true ? 'success' : ($result === false ? 'error' : 'warning');
                ?>
                <div class="test-result <?php echo $testClass; ?>">
                    <div class="test-label"><?php echo ucwords(str_replace('_', ' ', $test)); ?></div>
                    <div class="test-status <?php echo $statusClass; ?>">
                        <?php echo $statusText; ?>
                        <?php if ($result !== true && $result !== false): ?>
                            <br><small><?php echo $result; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($sampleTask): ?>
            <div class="test-section">
                <h2><i class="fas fa-upload"></i> Test Upload</h2>
                <p>Test uploading a small file to verify everything is working:</p>
                
                <div class="upload-test-area" id="upload-test-area">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3em; color: #007bff; margin-bottom: 15px;"></i>
                    <h3>Drag & Drop a Small Test File Here</h3>
                    <p>or click to browse files</p>
                    <small>Choose a small file (less than 1MB) for testing</small>
                    <input type="file" id="test-file-input" style="display: none;">
                </div>
                
                <div id="test-result" style="display: none;"></div>
            </div>
        <?php else: ?>
            <div class="test-section">
                <div class="test-result warning">
                    <div class="test-label">No Tasks Available</div>
                    <div class="test-status warning">Cannot test upload without a task</div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="test-section">
            <h2><i class="fas fa-tools"></i> Troubleshooting</h2>
            
            <?php if ($failedTests > 0): ?>
                <div class="test-result error">
                    <div class="test-label">Issues Found</div>
                    <div class="test-status error">Some tests failed</div>
                </div>
                
                <div style="margin-top: 15px;">
                    <h4>How to Fix:</h4>
                    <ol>
                        <li><strong>Run the permission fix script:</strong><br>
                            <code>./fix-upload-permissions.sh</code></li>
                        <li><strong>Restart XAMPP:</strong><br>
                            <code>./restart-xampp-for-10gb.sh</code></li>
                        <li><strong>Check server logs:</strong><br>
                            <code>tail -f /Applications/XAMPP/xamppfiles/logs/error_log</code></li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="test-result success">
                    <div class="test-label">All Tests Passed</div>
                    <div class="test-status success">Upload system is ready!</div>
                </div>
                
                <div style="margin-top: 15px;">
                    <h4>Next Steps:</h4>
                    <ul>
                        <li><a href="background-upload-simple.php">Try Background Upload</a></li>
                        <li><a href="large-file-upload.php">Test Large File Upload</a></li>
                        <li><a href="task-management.php">Go to Task Management</a></li>
                    </ul>
                </div>
            <?php endif; ?>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('upload-test-area');
            const fileInput = document.getElementById('test-file-input');
            const testResult = document.getElementById('test-result');
            
            if (!uploadArea) return;
            
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#28a745';
                uploadArea.style.background = '#d4edda';
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#007bff';
                uploadArea.style.background = '#f8f9fa';
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#007bff';
                uploadArea.style.background = '#f8f9fa';
                
                const files = Array.from(e.dataTransfer.files);
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
                // Check file size (limit to 1MB for testing)
                if (file.size > 1024 * 1024) {
                    showResult('File too large for testing. Please choose a file smaller than 1MB.', 'error');
                    return;
                }
                
                const formData = new FormData();
                formData.append('file', file);
                formData.append('entity_type', 'task');
                formData.append('entity_id', '<?php echo $sampleTask ? $sampleTask['id'] : 1; ?>');
                
                showResult('Uploading test file...', 'info');
                
                const xhr = new XMLHttpRequest();
                
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showResult('✅ Test upload successful! Upload system is working correctly.', 'success');
                            } else {
                                showResult('❌ Test upload failed: ' + response.message, 'error');
                            }
                        } catch (e) {
                            showResult('❌ Test upload failed: Invalid response from server', 'error');
                        }
                    } else {
                        showResult('❌ Test upload failed: HTTP ' + xhr.status, 'error');
                    }
                });
                
                xhr.addEventListener('error', function() {
                    showResult('❌ Test upload failed: Network error', 'error');
                });
                
                xhr.open('POST', 'api/upload.php');
                xhr.send(formData);
            }
            
            function showResult(message, type) {
                testResult.innerHTML = `
                    <div class="test-result ${type}">
                        <div class="test-label">Test Upload Result</div>
                        <div class="test-status ${type}">${message}</div>
                    </div>
                `;
                testResult.style.display = 'block';
            }
        });
    </script>
</body>
</html>
