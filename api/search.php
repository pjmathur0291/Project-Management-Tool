<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    $query = $_GET['q'] ?? '';
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'message' => 'Search query is required']);
        exit;
    }
    
    $pdo = getDBConnection();
    $searchTerm = '%' . $query . '%';
    
    // Search projects
    $stmt = $pdo->prepare("
        SELECT 
            'project' as type,
            id,
            name as title,
            description,
            status,
            priority
        FROM projects 
        WHERE name LIKE ? OR description LIKE ?
        LIMIT 5
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $projects = $stmt->fetchAll();
    
    // Search tasks
    $stmt = $pdo->prepare("
        SELECT 
            'task' as type,
            id,
            title,
            description,
            status,
            priority
        FROM tasks 
        WHERE title LIKE ? OR description LIKE ?
        LIMIT 5
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $tasks = $stmt->fetchAll();
    
    // Search team members
    $stmt = $pdo->prepare("
        SELECT 
            'member' as type,
            id,
            full_name as title,
            email as description,
            role as status,
            username as priority
        FROM users 
        WHERE full_name LIKE ? OR username LIKE ? OR email LIKE ?
        LIMIT 5
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $members = $stmt->fetchAll();
    
    $results = [
        'projects' => $projects,
        'tasks' => $tasks,
        'members' => $members
    ];
    
    $response = [
        'success' => true,
        'query' => $query,
        'results' => $results,
        'total' => count($projects) + count($tasks) + count($members)
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false, 
        'message' => 'Search error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>
