<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Multimedia Manager Test</h1>";

try {
    echo "<p>✅ Database connection successful</p>";
    
    // Test if MultimediaManager class exists
    if (class_exists('MultimediaManager')) {
        echo "<p>✅ MultimediaManager class exists</p>";
    } else {
        echo "<p>❌ MultimediaManager class not found</p>";
        
        // Try to include it
        echo "<p>Attempting to include MultimediaManager.php...</p>";
        require_once 'includes/MultimediaManager.php';
        
        if (class_exists('MultimediaManager')) {
            echo "<p>✅ MultimediaManager class loaded successfully</p>";
        } else {
            echo "<p>❌ Failed to load MultimediaManager class</p>";
        }
    }
    
    // Test if FileViewer class exists
    if (class_exists('FileViewer')) {
        echo "<p>✅ FileViewer class exists</p>";
    } else {
        echo "<p>❌ FileViewer class not found</p>";
        
        // Try to include it
        echo "<p>Attempting to include FileViewer.php...</p>";
        require_once 'includes/FileViewer.php';
        
        if (class_exists('FileViewer')) {
            echo "<p>✅ FileViewer class loaded successfully</p>";
        } else {
            echo "<p>❌ Failed to load FileViewer class</p>";
        }
    }
    
    // Test database tables
    $pdo = getDBConnection();
    
    $tables = ['multimedia_files', 'system_settings'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' not found</p>";
        }
    }
    
    // Test uploads directory
    if (is_dir('uploads')) {
        echo "<p>✅ Uploads directory exists</p>";
        
        $subdirs = ['images', 'videos', 'documents', 'thumbnails'];
        foreach ($subdirs as $subdir) {
            if (is_dir("uploads/$subdir")) {
                echo "<p>✅ Subdirectory 'uploads/$subdir' exists</p>";
            } else {
                echo "<p>❌ Subdirectory 'uploads/$subdir' not found</p>";
            }
        }
    } else {
        echo "<p>❌ Uploads directory not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='test-app.php'>Back to System Test</a></p>";
?>
