<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/MultimediaManager.php';

echo "<h2>Upload Debug Information</h2>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Check if multimedia_files table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'multimedia_files'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ multimedia_files table exists</p>";
    } else {
        echo "<p>❌ multimedia_files table does not exist</p>";
        echo "<p>Running multimedia database setup...</p>";
        
        // Run the multimedia setup
        $sql = file_get_contents('setup-multimedia-database.sql');
        $pdo->exec($sql);
        echo "<p>✅ Multimedia database setup completed</p>";
    }
    
    // Check if system_settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ system_settings table exists</p>";
    } else {
        echo "<p>❌ system_settings table does not exist</p>";
    }
    
    // Check if tasks table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
    $taskCount = $stmt->fetch()['count'];
    echo "<p>📋 Tasks in database: $taskCount</p>";
    
    // Check upload directory permissions
    $uploadDir = 'uploads';
    if (is_dir($uploadDir)) {
        echo "<p>✅ Upload directory exists</p>";
        if (is_writable($uploadDir)) {
            echo "<p>✅ Upload directory is writable</p>";
        } else {
            echo "<p>❌ Upload directory is not writable</p>";
        }
    } else {
        echo "<p>❌ Upload directory does not exist</p>";
    }
    
    // Check PHP upload settings
    echo "<h3>PHP Upload Settings:</h3>";
    echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
    echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
    echo "<p>max_file_uploads: " . ini_get('max_file_uploads') . "</p>";
    echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";
    
    // Test MultimediaManager
    if (class_exists('MultimediaManager')) {
        echo "<p>✅ MultimediaManager class exists</p>";
        try {
            $multimediaManager = new MultimediaManager($pdo);
            echo "<p>✅ MultimediaManager instantiated successfully</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error creating MultimediaManager: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ MultimediaManager class not found</p>";
    }
    
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ User is logged in (ID: " . $_SESSION['user_id'] . ")</p>";
    } else {
        echo "<p>❌ User is not logged in</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Test Upload Form:</h3>";
if (isset($_SESSION['user_id'])) {
    echo '<form action="test-upload-debug.php" method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="test_file" required><br><br>';
    echo '<input type="submit" value="Test Upload">';
    echo '</form>';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
        echo "<h3>Upload Test Results:</h3>";
        
        $file = $_FILES['test_file'];
        echo "<p>File name: " . $file['name'] . "</p>";
        echo "<p>File size: " . $file['size'] . " bytes</p>";
        echo "<p>File type: " . $file['type'] . "</p>";
        echo "<p>Upload error: " . $file['error'] . "</p>";
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            try {
                $multimediaManager = new MultimediaManager($pdo);
                
                // Get first task for testing
                $stmt = $pdo->query("SELECT id FROM tasks LIMIT 1");
                $task = $stmt->fetch();
                
                if ($task) {
                    $result = $multimediaManager->uploadFile(
                        $file,
                        'task',
                        $task['id'],
                        $_SESSION['user_id'],
                        'Test upload'
                    );
                    
                    if ($result['success']) {
                        echo "<p>✅ Upload successful!</p>";
                        echo "<p>File saved as: " . $result['filename'] . "</p>";
                    } else {
                        echo "<p>❌ Upload failed: " . $result['message'] . "</p>";
                    }
                } else {
                    echo "<p>❌ No tasks found in database to associate upload with</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Upload error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ File upload error: " . $file['error'] . "</p>";
        }
    }
} else {
    echo "<p>Please log in first to test uploads.</p>";
    echo '<a href="login.php">Go to Login</a>';
}
?>
