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
            } else {
                $tasks = getAllTasks();
                $response = ['success' => true, 'tasks' => $tasks];
            }
            break;

        case 'POST':
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'status' => $_POST['status'] ?? 'pending',
                'priority' => $_POST['priority'] ?? 'medium',
                'project_id' => $_POST['project_id'] ?? null,
                'assigned_to' => $_POST['assigned_to'] ?? null,
                'assigned_by' => 2, // Default to current user ID
                'due_date' => $_POST['due_date'] ?? null,
                'estimated_hours' => $_POST['estimated_hours'] ?? null
            ];

            if (empty($data['title'])) {
                $response = ['success' => false, 'message' => 'Task title is required'];
                break;
            }

            if (createTask($data)) {
                $response = ['success' => true, 'message' => 'Task created successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to create task'];
            }
            break;

        case 'PUT':
            parse_str(file_get_contents("php://input"), $putData);
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $response = ['success' => false, 'message' => 'Task ID is required'];
                break;
            }

            $data = [
                'title' => $putData['title'] ?? '',
                'description' => $putData['description'] ?? '',
                'status' => $putData['status'] ?? 'pending',
                'priority' => $putData['priority'] ?? 'medium',
                'project_id' => $putData['project_id'] ?? null,
                'assigned_to' => $putData['assigned_to'] ?? null,
                'due_date' => $putData['due_date'] ?? null,
                'estimated_hours' => $putData['estimated_hours'] ?? null,
                'actual_hours' => $putData['actual_hours'] ?? null
            ];

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
