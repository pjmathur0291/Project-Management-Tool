<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => 'Invalid request'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $project = getProjectById($_GET['id']);
                if ($project) {
                    $response = ['success' => true, 'project' => $project];
                } else {
                    $response = ['success' => false, 'message' => 'Project not found'];
                }
            } else {
                $projects = getAllProjects();
                $response = ['success' => true, 'projects' => $projects];
            }
            break;

        case 'POST':
            // Debug logging
            error_log("POST request received to projects API");
            error_log("POST data: " . print_r($_POST, true));
            
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'status' => $_POST['status'] ?? 'pending',
                'priority' => $_POST['priority'] ?? 'medium',
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => (!empty($_POST['end_date']) ? $_POST['end_date'] : null),
                'manager_id' => $_POST['manager_id'] ?? null
            ];
            
            error_log("Processed data: " . print_r($data, true));

            if (empty($data['name'])) {
                error_log("Project name is empty");
                $response = ['success' => false, 'message' => 'Project name is required'];
                break;
            }

            error_log("Calling createProject function...");
            $projectId = createProject($data);
            error_log("createProject result: " . ($projectId ? $projectId : 'false'));
            
            if ($projectId) {
                $response = ['success' => true, 'message' => 'Project created successfully', 'project_id' => $projectId];
            } else {
                $response = ['success' => false, 'message' => 'Failed to create project'];
            }
            break;

        case 'PUT':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $response = ['success' => false, 'message' => 'Project ID is required'];
                break;
            }

            // Handle both form data and JSON input
            $input = file_get_contents("php://input");
            $putData = [];
            
            if (strpos($input, '=') !== false) {
                // Form data
                parse_str($input, $putData);
            } else {
                // JSON data
                $putData = json_decode($input, true) ?? [];
            }

            $data = [
                'name' => $putData['name'] ?? '',
                'description' => $putData['description'] ?? '',
                'status' => $putData['status'] ?? 'pending',
                'priority' => $putData['priority'] ?? 'medium',
                'start_date' => $putData['start_date'] ?? null,
                'end_date' => $putData['end_date'] ?? null,
                'progress' => $putData['progress'] ?? 0,
                'manager_id' => $putData['manager_id'] ?? null
            ];

            if (updateProject($id, $data)) {
                $response = ['success' => true, 'message' => 'Project updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update project'];
            }
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $response = ['success' => false, 'message' => 'Project ID is required'];
                break;
            }

            if (deleteProject($id)) {
                $response = ['success' => true, 'message' => 'Project deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete project'];
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
