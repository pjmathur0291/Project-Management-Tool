<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/MultimediaManager.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => 'Invalid request'];

try {
    $pdo = getDBConnection();
    $multimediaManager = new MultimediaManager($pdo);

    switch ($method) {
        case 'POST':
            // Handle file upload
            if (!isset($_SESSION['user_id'])) {
                $response = ['success' => false, 'message' => 'User not authenticated'];
                break;
            }

            $entityType = $_POST['entity_type'] ?? '';
            $entityId = (int)($_POST['entity_id'] ?? 0);
            $description = $_POST['description'] ?? '';

            if (empty($entityType) || empty($entityId)) {
                $response = ['success' => false, 'message' => 'Entity type and ID are required'];
                break;
            }

            // Validate entity exists
            $entityExists = false;
            $taskAssigneeId = null; // used for permission checks on tasks
            switch ($entityType) {
                case 'task':
                    $stmt = $pdo->prepare("SELECT id, assigned_to FROM tasks WHERE id = ?");
                    $stmt->execute([$entityId]);
                    if ($row = $stmt->fetch()) {
                        $entityExists = true;
                        $taskAssigneeId = $row['assigned_to'];
                    }
                    break;
                case 'project':
                    $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
                    $stmt->execute([$entityId]);
                    $entityExists = $stmt->fetch() !== false;
                    break;
                case 'comment':
                    $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ?");
                    $stmt->execute([$entityId]);
                    $entityExists = $stmt->fetch() !== false;
                    break;
            }

            if (!$entityExists) {
                $response = ['success' => false, 'message' => 'Entity not found'];
                break;
            }

            // Authorization: allow admins/managers to upload to any task; members only to their assigned tasks
            $currentUserId = $_SESSION['user_id'];
            $currentUserRole = $_SESSION['role'] ?? 'member';
            if ($entityType === 'task') {
                $isPrivileged = in_array($currentUserRole, ['admin', 'manager'], true);
                $isAssignee = ($taskAssigneeId !== null && (int)$taskAssigneeId === (int)$currentUserId);
                if (!$isPrivileged && !$isAssignee) {
                    $response = ['success' => false, 'message' => "You don't have permission to upload to this task."];
                    break;
                }
            } else {
                // For non-task entities, restrict to admins/managers for now
                if (!in_array($currentUserRole, ['admin', 'manager'], true)) {
                    $response = ['success' => false, 'message' => 'Only admins or managers can upload to this entity type'];
                    break;
                }
            }

            // Check if file was uploaded
            if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
                $response = ['success' => false, 'message' => 'No file uploaded'];
                break;
            }

            // Upload file
            $uploadResult = $multimediaManager->uploadFile(
                $_FILES['file'],
                $entityType,
                $entityId,
                $_SESSION['user_id'],
                $description
            );

            $response = $uploadResult;
            break;

        case 'GET':
            // Get files for an entity
            $entityType = $_GET['entity_type'] ?? '';
            $entityId = (int)($_GET['entity_id'] ?? 0);

            if (empty($entityType) || empty($entityId)) {
                $response = ['success' => false, 'message' => 'Entity type and ID are required'];
                break;
            }

            $files = $multimediaManager->getFilesByEntity($entityType, $entityId);
            
            // Format file data for response
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

            $response = [
                'success' => true,
                'files' => $formattedFiles
            ];
            break;

        case 'DELETE':
            // Delete file
            if (!isset($_SESSION['user_id'])) {
                $response = ['success' => false, 'message' => 'User not authenticated'];
                break;
            }

            $fileId = (int)($_GET['id'] ?? 0);
            if (empty($fileId)) {
                $response = ['success' => false, 'message' => 'File ID is required'];
                break;
            }

            $deleteResult = $multimediaManager->deleteFile($fileId, $_SESSION['user_id']);
            $response = $deleteResult;
            break;

        default:
            $response = ['success' => false, 'message' => 'Method not allowed'];
            break;
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
}

echo json_encode($response);
?>
