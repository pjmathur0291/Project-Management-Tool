<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/MultimediaManager.php';

echo "<h1>Upload System Diagnostic</h1>";

// Check authentication
echo "<h2>1. Authentication Check</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in: " . $_SESSION['username'] . " (ID: " . $_SESSION['user_id'] . ")<br>";
} else {
    echo "❌ User not logged in<br>";
    echo "<a href='quick-login-test.php'>Login here</a><br>";
    exit();
}

// Check database connection
echo "<h2>2. Database Check</h2>";
try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful<br>";
    
    // Check tables
    $tables = ['multimedia_files', 'system_settings', 'tasks', 'users'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
    
    // Check for tasks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
    $taskCount = $stmt->fetch()['count'];
    echo "📋 Tasks available: $taskCount<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Check file system
echo "<h2>3. File System Check</h2>";
$uploadDirs = ['uploads', 'uploads/images', 'uploads/documents', 'uploads/videos', 'uploads/thumbnails'];
foreach ($uploadDirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Directory '$dir' exists and is writable<br>";
        } else {
            echo "⚠️ Directory '$dir' exists but is not writable<br>";
        }
    } else {
        echo "❌ Directory '$dir' does not exist<br>";
    }
}

// Check PHP settings
echo "<h2>4. PHP Settings</h2>";
$settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'file_uploads' => ini_get('file_uploads')
];

foreach ($settings as $setting => $value) {
    echo "$setting: $value<br>";
}

// Test MultimediaManager
echo "<h2>5. MultimediaManager Test</h2>";
try {
    $multimediaManager = new MultimediaManager($pdo);
    echo "✅ MultimediaManager instantiated successfully<br>";
} catch (Exception $e) {
    echo "❌ MultimediaManager error: " . $e->getMessage() . "<br>";
}

// Test API endpoint
echo "<h2>6. API Endpoint Test</h2>";
$apiUrl = 'http://localhost/management-tool/api/upload.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ API endpoint accessible (HTTP 200)<br>";
} else {
    echo "⚠️ API endpoint returned HTTP $httpCode<br>";
}

// Check for common issues
echo "<h2>7. Common Issues Check</h2>";

// Check if .htaccess might be blocking uploads
if (file_exists('.htaccess')) {
    echo "⚠️ .htaccess file exists - might affect uploads<br>";
} else {
    echo "✅ No .htaccess file found<br>";
}

// Check for PHP errors
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "📝 Error log location: $errorLog<br>";
} else {
    echo "📝 Error logging: " . (ini_get('log_errors') ? 'enabled' : 'disabled') . "<br>";
}

echo "<h2>8. Quick Fixes</h2>";
echo "<p>If uploads are still not working, try these steps:</p>";
echo "<ol>";
echo "<li><a href='simple-upload-test.php'>Test the simple upload page</a></li>";
echo "<li><a href='test-upload-debug.php'>Run the upload debug test</a></li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Try uploading a small file first (less than 1MB)</li>";
echo "<li>Make sure you select a task before uploading</li>";
echo "</ol>";

echo "<h2>9. Test Links</h2>";
echo "<a href='simple-upload-test.php'>Simple Upload Test</a><br>";
echo "<a href='background-upload-simple.php'>Full Background Upload</a><br>";
echo "<a href='test-upload-debug.php'>Upload Debug Test</a><br>";
echo "<a href='logout.php'>Logout</a><br>";
?>
