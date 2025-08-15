<?php
class MultimediaManager {
    private $pdo;
    private $uploadDir;
    private $maxFileSize;
    private $allowedFileTypes;
    private $imageMaxWidth;
    private $imageMaxHeight;
    private $videoMaxDuration;
    private $enableThumbnails;
    private $thumbnailSize;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadSettings();
    }

    private function loadSettings() {
        $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $this->uploadDir = $settings['upload_directory'] ?? 'uploads';
        
        // Get server limits
        $uploadMaxFilesize = $this->getBytes(ini_get('upload_max_filesize'));
        $postMaxSize = $this->getBytes(ini_get('post_max_size'));
        $memoryLimit = $this->getBytes(ini_get('memory_limit'));
        
        // Use the smallest of server limits or database setting
        $serverLimit = min($uploadMaxFilesize, $postMaxSize);
        $dbLimit = (int)($settings['max_file_size'] ?? 10737418240); // 10GB default
        
        $this->maxFileSize = min($serverLimit, $dbLimit);
        
        $this->allowedFileTypes = explode(',', $settings['allowed_file_types'] ?? 'jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,wmv,flv,webm,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar');
        $this->imageMaxWidth = (int)($settings['image_max_width'] ?? 1920);
        $this->imageMaxHeight = (int)($settings['image_max_height'] ?? 1080);
        $this->videoMaxDuration = (int)($settings['video_max_duration'] ?? 300);
        $this->enableThumbnails = (bool)($settings['enable_thumbnails'] ?? true);
        $this->thumbnailSize = (int)($settings['thumbnail_size'] ?? 150);
    }
    
    private function getBytes($val) {
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

    public function uploadFile($file, $entityType, $entityId, $uploadedBy, $description = '') {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            
            // Determine upload directory based on file type
            $fileType = $this->getFileType($extension);
            $uploadPath = $this->uploadDir . '/' . $fileType . '/' . $filename;
            
            // Create directory if it doesn't exist
            $dir = dirname($uploadPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                return ['success' => false, 'message' => 'Failed to move uploaded file'];
            }

            // Generate thumbnail for images
            $thumbnailPath = null;
            if ($fileType === 'images' && $this->enableThumbnails) {
                $thumbnailPath = $this->generateThumbnail($uploadPath, $filename);
            }

            // Resize image if needed
            if ($fileType === 'images') {
                $this->resizeImage($uploadPath);
            }

            // Get file info
            $fileSize = filesize($uploadPath);
            $mimeType = mime_content_type($uploadPath);

            // Save to database
            $stmt = $this->pdo->prepare("
                INSERT INTO multimedia_files (
                    filename, original_filename, file_path, file_type, file_size, 
                    mime_type, uploaded_by, entity_type, entity_id, description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $filename,
                $file['name'],
                $uploadPath,
                $fileType,
                $fileSize,
                $mimeType,
                $uploadedBy,
                $entityType,
                $entityId,
                $description
            ]);

            $fileId = $this->pdo->lastInsertId();

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_id' => $fileId,
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_path' => $uploadPath,
                'thumbnail_path' => $thumbnailPath,
                'file_size' => $fileSize,
                'file_type' => $fileType
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Upload error: ' . $e->getMessage()];
        }
    }

    private function validateFile($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'No file uploaded or invalid upload'];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedFileTypes)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }

        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
        }

        return ['success' => true];
    }

    private function getFileType($extension) {
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $videoTypes = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
        
        if (in_array($extension, $imageTypes)) {
            return 'images';
        } elseif (in_array($extension, $videoTypes)) {
            return 'videos';
        } else {
            return 'documents';
        }
    }

    private function generateThumbnail($imagePath, $filename) {
        try {
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return null;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];

            // Calculate thumbnail dimensions
            $ratio = min($this->thumbnailSize / $width, $this->thumbnailSize / $height);
            $thumbWidth = round($width * $ratio);
            $thumbHeight = round($height * $ratio);

            // Create thumbnail
            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            
            // Load source image
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($imagePath);
                    // Preserve transparency
                    imagealphablending($thumb, false);
                    imagesavealpha($thumb, true);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($imagePath);
                    break;
                default:
                    return null;
            }

            // Resize
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

            // Save thumbnail
            $thumbnailPath = $this->uploadDir . '/thumbnails/thumb_' . $filename;
            $dir = dirname($thumbnailPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($thumb, $thumbnailPath, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($thumb, $thumbnailPath, 8);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($thumb, $thumbnailPath);
                    break;
            }

            imagedestroy($thumb);
            imagedestroy($source);

            return $thumbnailPath;

        } catch (Exception $e) {
            error_log("Thumbnail generation error: " . $e->getMessage());
            return null;
        }
    }

    private function resizeImage($imagePath) {
        try {
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return false;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];

            // Check if resizing is needed
            if ($width <= $this->imageMaxWidth && $height <= $this->imageMaxHeight) {
                return true;
            }

            // Calculate new dimensions
            $ratio = min($this->imageMaxWidth / $width, $this->imageMaxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);

            // Create new image
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Load source image
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($imagePath);
                    // Preserve transparency
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($imagePath);
                    break;
                default:
                    return false;
            }

            // Resize
            imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Save resized image
            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($newImage, $imagePath, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($newImage, $imagePath, 8);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($newImage, $imagePath);
                    break;
            }

            imagedestroy($newImage);
            imagedestroy($source);

            return true;

        } catch (Exception $e) {
            error_log("Image resize error: " . $e->getMessage());
            return false;
        }
    }

    public function getFilesByEntity($entityType, $entityId) {
        $stmt = $this->pdo->prepare("
            SELECT mf.*, u.full_name as uploaded_by_name
            FROM multimedia_files mf
            LEFT JOIN users u ON mf.uploaded_by = u.id
            WHERE mf.entity_type = ? AND mf.entity_id = ?
            ORDER BY mf.created_at DESC
        ");
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll();
    }

    public function deleteFile($fileId, $userId) {
        try {
            // Get file info
            $stmt = $this->pdo->prepare("
                SELECT * FROM multimedia_files 
                WHERE id = ? AND uploaded_by = ?
            ");
            $stmt->execute([$fileId, $userId]);
            $file = $stmt->fetch();

            if (!$file) {
                return ['success' => false, 'message' => 'File not found or access denied'];
            }

            // Delete physical file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Delete thumbnail if exists
            $thumbnailPath = $this->uploadDir . '/thumbnails/thumb_' . $file['filename'];
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }

            // Delete from database
            $stmt = $this->pdo->prepare("DELETE FROM multimedia_files WHERE id = ?");
            $stmt->execute([$fileId]);

            return ['success' => true, 'message' => 'File deleted successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Delete error: ' . $e->getMessage()];
        }
    }

    public function getFileInfo($fileId) {
        $stmt = $this->pdo->prepare("
            SELECT mf.*, u.full_name as uploaded_by_name
            FROM multimedia_files mf
            LEFT JOIN users u ON mf.uploaded_by = u.id
            WHERE mf.id = ?
        ");
        $stmt->execute([$fileId]);
        return $stmt->fetch();
    }

    public function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function getFileIcon($fileType) {
        $icons = [
            'images' => 'fas fa-image',
            'videos' => 'fas fa-video',
            'documents' => 'fas fa-file-alt'
        ];
        return $icons[$fileType] ?? 'fas fa-file';
    }

    public function isImage($fileType) {
        return $fileType === 'images';
    }

    public function isVideo($fileType) {
        return $fileType === 'videos';
    }

    public function isDocument($fileType) {
        return $fileType === 'documents';
    }
}
?>
