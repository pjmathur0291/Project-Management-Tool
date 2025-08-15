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

try {
    $pdo = getDBConnection();
    $multimediaManager = new MultimediaManager($pdo);
    $fileViewer = new FileViewer($multimediaManager);
    
    // Get a sample task for testing
    $stmt = $pdo->query("SELECT id, title FROM tasks LIMIT 1");
    $sampleTask = $stmt->fetch();
    
    // Get files for the sample task
    $files = [];
    if ($sampleTask) {
        $files = $multimediaManager->getFilesByEntity('task', $sampleTask['id']);
        
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
    }
    
} catch (Exception $e) {
    $error_message = 'Error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multimedia Upload Test - Project Management Tool</title>
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
                    <a href="#"><i class="fas fa-images"></i> Multimedia Test</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    <span> / Multimedia Upload Test</span>
                </div>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?> (<?php echo ucfirst($current_user['role']); ?>)</span>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </header>

            <div class="content-container">
                <div class="test-container">
                    <h1><i class="fas fa-images"></i> Multimedia Upload Test</h1>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <div class="test-sections">
                        <!-- Test Task Information -->
                        <div class="test-section">
                            <h2>Test Task</h2>
                            <?php if ($sampleTask): ?>
                                <div class="task-info">
                                    <p><strong>Task ID:</strong> <?php echo $sampleTask['id']; ?></p>
                                    <p><strong>Task Title:</strong> <?php echo htmlspecialchars($sampleTask['title']); ?></p>
                                    <p><strong>Current Files:</strong> <?php echo count($formattedFiles); ?> files uploaded</p>
                                </div>
                            <?php else: ?>
                                <p>No tasks found. Please create a task first.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Upload Test -->
                        <?php if ($sampleTask): ?>
                            <div class="test-section">
                                <h2>Upload Test</h2>
                                <p>Test uploading different types of files to the task:</p>
                                
                                <div class="upload-test">
                                    <h3>File Upload</h3>
                                    <?php echo $fileViewer->renderUploadForm('task', $sampleTask['id']); ?>
                                </div>
                            </div>

                            <!-- Gallery Test -->
                            <div class="test-section">
                                <h2>File Gallery</h2>
                                <p>View uploaded files:</p>
                                
                                <div class="gallery-test">
                                    <?php echo $fileViewer->renderFileGallery($formattedFiles, [
                                        'show_delete' => true,
                                        'current_user_id' => $current_user['id'],
                                        'entity_type' => 'task',
                                        'entity_id' => $sampleTask['id']
                                    ]); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Features Overview -->
                        <div class="test-section">
                            <h2>Multimedia Features</h2>
                            <div class="features-grid">
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-upload"></i>
                                    </div>
                                    <h3>Drag & Drop Upload</h3>
                                    <p>Upload files by dragging and dropping them into the upload area or clicking to browse.</p>
                                </div>
                                
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-image"></i>
                                    </div>
                                    <h3>Image Support</h3>
                                    <p>Upload and view images (JPG, PNG, GIF, BMP, WebP) with automatic thumbnail generation.</p>
                                </div>
                                
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-video"></i>
                                    </div>
                                    <h3>Video Support</h3>
                                    <p>Upload and play videos (MP4, AVI, MOV, WMV, FLV, WebM) directly in the browser.</p>
                                </div>
                                
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <h3>Document Support</h3>
                                    <p>Upload documents (PDF, DOC, XLS, PPT, TXT) and other file types for easy access.</p>
                                </div>
                                
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                    <h3>File Preview</h3>
                                    <p>Preview images in a modal viewer and download any file type with one click.</p>
                                </div>
                                
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-trash"></i>
                                    </div>
                                    <h3>File Management</h3>
                                    <p>Delete files you've uploaded. Admins and managers can manage all files.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Supported File Types -->
                        <div class="test-section">
                            <h2>Supported File Types</h2>
                            <div class="file-types-grid">
                                <div class="file-type-category">
                                    <h3><i class="fas fa-image"></i> Images</h3>
                                    <ul>
                                        <li>JPG / JPEG</li>
                                        <li>PNG</li>
                                        <li>GIF</li>
                                        <li>BMP</li>
                                        <li>WebP</li>
                                    </ul>
                                </div>
                                
                                <div class="file-type-category">
                                    <h3><i class="fas fa-video"></i> Videos</h3>
                                    <ul>
                                        <li>MP4</li>
                                        <li>AVI</li>
                                        <li>MOV</li>
                                        <li>WMV</li>
                                        <li>FLV</li>
                                        <li>WebM</li>
                                    </ul>
                                </div>
                                
                                <div class="file-type-category">
                                    <h3><i class="fas fa-file-alt"></i> Documents</h3>
                                    <ul>
                                        <li>PDF</li>
                                        <li>DOC / DOCX</li>
                                        <li>XLS / XLSX</li>
                                        <li>PPT / PPTX</li>
                                        <li>TXT</li>
                                        <li>ZIP / RAR</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="test-section">
                            <h2>Navigation</h2>
                            <div class="navigation-buttons">
                                <a href="task-management.php" class="btn btn-primary">
                                    <i class="fas fa-list-check"></i> Task Management
                                </a>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                                <?php if ($sampleTask): ?>
                                    <a href="task-detail.php?id=<?php echo $sampleTask['id']; ?>" class="btn btn-info">
                                        <i class="fas fa-eye"></i> View Task Details
                                    </a>
                                <?php endif; ?>
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
    <?php if ($sampleTask): ?>
        <?php echo $fileViewer->getUploadJavaScript('task', $sampleTask['id']); ?>
    <?php endif; ?>

    <style>
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .test-sections {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .test-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .test-section h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        
        .task-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .task-info p {
            margin: 5px 0;
            color: #555;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .feature-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .feature-icon {
            font-size: 2.5em;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .feature-card h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.5;
        }
        
        .file-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .file-type-category {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .file-type-category h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-type-category ul {
            list-style: none;
            padding: 0;
        }
        
        .file-type-category li {
            padding: 5px 0;
            color: #555;
            border-bottom: 1px solid #dee2e6;
        }
        
        .file-type-category li:last-child {
            border-bottom: none;
        }
        
        .navigation-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .navigation-buttons .btn {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .features-grid,
            .file-types-grid {
                grid-template-columns: 1fr;
            }
            
            .navigation-buttons {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
