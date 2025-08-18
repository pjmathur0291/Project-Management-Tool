<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => 'Invalid request'];

try {
    $pdo = getDBConnection();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $currentUserId = (int)$_SESSION['user_id'];
    $currentUserRole = $_SESSION['role'] ?? 'member';

    // Helper to check view/post permissions on a task
    $canAccessTask = function(int $taskId) use ($pdo, $currentUserId, $currentUserRole): array {
        $stmt = $pdo->prepare('SELECT id, assigned_to, assigned_by FROM tasks WHERE id = ?');
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        if (!$task) {
            return [false, 'Task not found'];
        }
        $isPrivileged = in_array($currentUserRole, ['admin', 'manager'], true);
        $isAssignee = ((int)$task['assigned_to'] === $currentUserId);
        $isAssigner = ((int)$task['assigned_by'] === $currentUserId);
        if ($isPrivileged || $isAssignee || $isAssigner) {
            return [true, $task];
        }
        return [false, "You don't have permission to access this task."];
    };

    switch ($method) {
        case 'GET':
            $taskId = (int)($_GET['task_id'] ?? 0);
            if (!$taskId) {
                $response = ['success' => false, 'message' => 'task_id is required'];
                break;
            }

            [$ok, $info] = $canAccessTask($taskId);
            if (!$ok) { $response = ['success' => false, 'message' => $info]; break; }

            $stmt = $pdo->prepare('
                SELECT c.id, c.content as content, c.created_at,
                       u.id as user_id, u.full_name, u.username
                FROM comments c
                JOIN users u ON u.id = c.user_id
                WHERE c.task_id = ?
                ORDER BY c.created_at ASC, c.id ASC
            ');
            $stmt->execute([$taskId]);
            $comments = $stmt->fetchAll();
            $response = ['success' => true, 'comments' => $comments];
            break;

        case 'POST':
            $taskId = (int)($_POST['task_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');

            if (!$taskId || $content === '') {
                $response = ['success' => false, 'message' => 'task_id and content are required'];
                break;
            }

            if (mb_strlen($content) > 5000) {
                $response = ['success' => false, 'message' => 'Comment too long'];
                break;
            }

            [$ok, $info] = $canAccessTask($taskId);
            if (!$ok) { $response = ['success' => false, 'message' => $info]; break; }

            $stmt = $pdo->prepare('INSERT INTO comments (content, user_id, task_id) VALUES (?, ?, ?)');
            $stmt->execute([$content, $currentUserId, $taskId]);

            $id = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('
                SELECT c.id, c.content as content, c.created_at,
                       u.id as user_id, u.full_name, u.username
                FROM comments c
                JOIN users u ON u.id = c.user_id
                WHERE c.id = ?
            ');
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            $response = ['success' => true, 'comment' => $row];
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


