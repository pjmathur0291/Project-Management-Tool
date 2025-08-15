<?php
session_start();
require_once '../config/database.php';
require_once '../includes/TagManager.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost($input);
        break;
    case 'PUT':
        handlePut($input);
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet() {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            $tags = TagManager::getAllTags();
            echo json_encode(['success' => true, 'data' => $tags]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tag ID required']);
                return;
            }
            
            $tag = TagManager::getTagById($id);
            if ($tag) {
                echo json_encode(['success' => true, 'data' => $tag]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Tag not found']);
            }
            break;
            
        case 'task':
            $taskId = $_GET['task_id'] ?? null;
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                return;
            }
            
            $tags = TagManager::getTaskTags($taskId);
            echo json_encode(['success' => true, 'data' => $tags]);
            break;
            
        case 'project':
            $projectId = $_GET['project_id'] ?? null;
            if (!$projectId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID required']);
                return;
            }
            
            $tags = TagManager::getProjectTags($projectId);
            echo json_encode(['success' => true, 'data' => $tags]);
            break;
            
        case 'search':
            $tagIds = isset($_GET['tag_ids']) ? explode(',', $_GET['tag_ids']) : [];
            $filters = [];
            
            if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
            if (isset($_GET['priority'])) $filters['priority'] = $_GET['priority'];
            if (isset($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
            if (isset($_GET['assigned_to'])) $filters['assigned_to'] = $_GET['assigned_to'];
            
            $tasks = TagManager::getTasksByTags($tagIds, $filters);
            echo json_encode(['success' => true, 'data' => $tasks]);
            break;
            
        case 'statistics':
            $stats = TagManager::getTagStatistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePost($input) {
    $action = $input['action'] ?? 'create';
    
    switch ($action) {
        case 'create':
            // Check if user has permission to create tags (admin/manager)
            if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
                return;
            }
            
            $requiredFields = ['name'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                    return;
                }
            }
            
            $data = [
                'name' => trim($input['name']),
                'color' => $input['color'] ?? '#007bff',
                'description' => $input['description'] ?? '',
                'created_by' => $_SESSION['user_id']
            ];
            
            $result = TagManager::createTag($data);
            echo json_encode($result);
            break;
            
        case 'add_to_task':
            $taskId = $input['task_id'] ?? null;
            $tagId = $input['tag_id'] ?? null;
            
            if (!$taskId || !$tagId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID and Tag ID required']);
                return;
            }
            
            $result = TagManager::addTagToTask($taskId, $tagId, $_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'add_to_project':
            $projectId = $input['project_id'] ?? null;
            $tagId = $input['tag_id'] ?? null;
            
            if (!$projectId || !$tagId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID and Tag ID required']);
                return;
            }
            
            $result = TagManager::addTagToProject($projectId, $tagId, $_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'update_task_tags':
            $taskId = $input['task_id'] ?? null;
            $tagIds = $input['tag_ids'] ?? [];
            
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                return;
            }
            
            $result = TagManager::updateTaskTags($taskId, $tagIds);
            echo json_encode($result);
            break;
            
        case 'update_project_tags':
            $projectId = $input['project_id'] ?? null;
            $tagIds = $input['tag_ids'] ?? [];
            
            if (!$projectId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID required']);
                return;
            }
            
            $result = TagManager::updateProjectTags($projectId, $tagIds);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePut($input) {
    // Check if user has permission to update tags (admin/manager)
    if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    $id = $input['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tag ID required']);
        return;
    }
    
    $data = [
        'name' => isset($input['name']) ? trim($input['name']) : null,
        'color' => $input['color'] ?? null,
        'description' => $input['description'] ?? null
    ];
    
    // Remove null values
    $data = array_filter($data, function($value) {
        return $value !== null;
    });
    
    $result = TagManager::updateTag($id, $data);
    echo json_encode($result);
}

function handleDelete() {
    // Check if user has permission to delete tags (admin/manager)
    if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    $action = $_GET['action'] ?? 'delete';
    
    switch ($action) {
        case 'delete':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tag ID required']);
                return;
            }
            
            $result = TagManager::deleteTag($id);
            echo json_encode($result);
            break;
            
        case 'remove_from_task':
            $taskId = $_GET['task_id'] ?? null;
            $tagId = $_GET['tag_id'] ?? null;
            
            if (!$taskId || !$tagId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID and Tag ID required']);
                return;
            }
            
            $result = TagManager::removeTagFromTask($taskId, $tagId);
            echo json_encode($result);
            break;
            
        case 'remove_from_project':
            $projectId = $_GET['project_id'] ?? null;
            $tagId = $_GET['tag_id'] ?? null;
            
            if (!$projectId || !$tagId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID and Tag ID required']);
                return;
            }
            
            $result = TagManager::removeTagFromProject($projectId, $tagId);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>