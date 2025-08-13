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
                $member = getUserById($_GET['id']);
                if ($member) {
                    $response = ['success' => true, 'member' => $member];
                } else {
                    $response = ['success' => false, 'message' => 'Team member not found'];
                }
            } else {
                $members = getAllUsers();
                $response = ['success' => true, 'members' => $members];
            }
            break;

        case 'POST':
            $data = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'role' => $_POST['role'] ?? 'member',
                'job_title' => $_POST['job_title'] ?? null
            ];

            if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
                $response = ['success' => false, 'message' => 'All fields are required'];
                break;
            }

            if (createUser($data)) {
                $response = ['success' => true, 'message' => 'Team member added successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add team member'];
            }
            break;

        case 'PUT':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $response = ['success' => false, 'message' => 'Member ID is required'];
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
                'username' => $putData['username'] ?? '',
                'email' => $putData['email'] ?? '',
                'full_name' => $putData['full_name'] ?? '',
                'role' => $putData['role'] ?? 'member',
                'job_title' => $putData['job_title'] ?? null
            ];

            // Only update password if provided
            if (!empty($putData['password'])) {
                $data['password'] = $putData['password'];
            }

            if (updateUser($id, $data)) {
                $response = ['success' => true, 'message' => 'Team member updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update team member'];
            }
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $response = ['success' => false, 'message' => 'Member ID is required'];
                break;
            }

            if (deleteUser($id)) {
                $response = ['success' => true, 'message' => 'Team member deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete team member'];
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
