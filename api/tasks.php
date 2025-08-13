<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => 'Invalid request'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $task = getTaskById($_GET['id']);
                if ($task) {
                    $response = ['success' => true, 'task' => $task];
                } else {
                    $response = ['success' => false, 'message' => 'Task not found'];
                }
            } else if (isset($_GET['filter']) && $_GET['filter'] === 'completed') {
                $tasks = getAllCompletedTasks();
                $response = ['success' => true, 'tasks' => $tasks];
            } else if (isset($_GET['filter']) && $_GET['filter'] === 'pending') {
                $all = getAllTasks();
                $pending = array_values(array_filter($all, function($t){ return $t['status'] !== 'completed'; }));
                $response = ['success' => true, 'tasks' => $pending];
            } else {
                $tasks = getAllTasks();
                $response = ['success' => true, 'tasks' => $tasks];
            }
            break;

        case 'POST':
            // Get current user ID from session
            session_start();
            $currentUserId = $_SESSION['user_id'] ?? null;
            
            if (!$currentUserId) {
                $response = ['success' => false, 'message' => 'User not authenticated'];
                break;
            }
            
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'status' => $_POST['status'] ?? 'pending',
                'priority' => $_POST['priority'] ?? 'medium',
                'project_id' => $_POST['project_id'] ?? null,
                'assigned_to' => $_POST['assigned_to'] ?? null,
                'assigned_by' => $currentUserId, // Use current logged-in user ID
                'due_date' => $_POST['due_date'] ?? null,
                'estimated_hours' => $_POST['estimated_hours'] ?? null
            ];

            if (empty($data['title'])) {
                $response = ['success' => false, 'message' => 'Task title is required'];
                break;
            }

            // Validate that assigned_to user exists if provided
            if (!empty($data['assigned_to'])) {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $stmt->execute([$data['assigned_to']]);
                if (!$stmt->fetch()) {
                    $response = ['success' => false, 'message' => 'Assigned user does not exist'];
                    break;
                }
            }

            if (createTask($data)) {
                $response = ['success' => true, 'message' => 'Task created successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to create task'];
            }
            break;

        case 'PUT':
            // Accept both form-data and x-www-form-urlencoded/JSON
            $raw = file_get_contents("php://input");
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $putData = [];
            if (stripos($contentType, 'application/json') !== false) {
                $json = json_decode($raw, true);
                if (is_array($json)) $putData = $json;
            } else {
                // For form-data the superglobals are empty on PUT, try parse_str
                parse_str($raw, $putData);
                // If still empty and we came via fetch with FormData, fallback to $_POST (some servers populate it)
                if (empty($putData) && !empty($_POST)) {
                    $putData = $_POST;
                }
            }
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $response = ['success' => false, 'message' => 'Task ID is required'];
                break;
            }

            $data = [];
            foreach (['title','description','status','priority','project_id','assigned_to','due_date','estimated_hours','actual_hours'] as $key) {
                if (array_key_exists($key, $putData)) {
                    $data[$key] = $putData[$key];
                }
            }

            if (updateTask($id, $data)) {
                $response = ['success' => true, 'message' => 'Task updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update task'];
            }
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $response = ['success' => false, 'message' => 'Task ID is required'];
                break;
            }

            if (deleteTask($id)) {
                $response = ['success' => true, 'message' => 'Task deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete task'];
            }
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
