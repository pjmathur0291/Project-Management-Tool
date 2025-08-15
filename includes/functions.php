<?php
// Use __DIR__ to get the correct path regardless of where this file is called from
require_once __DIR__ . '/../config/database.php';

// Get dashboard statistics
function getDashboardStats() {
    $pdo = getDBConnection();
    
    $stats = [];
    
    // Total projects
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
    $stats['total_projects'] = $stmt->fetchColumn();
    
    // Total tasks
    $stmt = $pdo->query("SELECT COUNT(*) FROM tasks");
    $stats['total_tasks'] = $stmt->fetchColumn();
    
    // Total team members
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_team'] = $stmt->fetchColumn();
    
    // Pending tasks
    $stmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'");
    $stats['pending_tasks'] = $stmt->fetchColumn();
    
    return $stats;
}

// Get all projects with manager information
function getAllProjects() {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("
        SELECT p.*, u.full_name as manager_name 
        FROM projects p 
        LEFT JOIN users u ON p.manager_id = u.id 
        ORDER BY p.created_at DESC
    ");
    
    return $stmt->fetchAll();
}

// Get project by ID
function getProjectById($id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as manager_name 
        FROM projects p 
        LEFT JOIN users u ON p.manager_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    
    return $stmt->fetch();
}

// Get all tasks with project and assignee information
function getAllTasks() {
    $pdo = getDBConnection();
    
    // Check if user is admin or manager (can see all tasks)
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? null;
    
    // If no session or user info, show all tasks (for API calls)
    if (!$userId || !$userRole) {
        $stmt = $pdo->query("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name 
            FROM tasks t 
            LEFT JOIN projects p ON t.project_id = p.id 
            LEFT JOIN users u ON t.assigned_to = u.id 
            ORDER BY t.created_at DESC
        ");
    } else if ($userRole === 'admin' || $userRole === 'manager') {
        // Admin and managers see all tasks
        $stmt = $pdo->query("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name 
            FROM tasks t 
            LEFT JOIN projects p ON t.project_id = p.id 
            LEFT JOIN users u ON t.assigned_to = u.id 
            ORDER BY t.created_at DESC
        ");
    } else {
        // Regular members only see tasks assigned to them
        $stmt = $pdo->prepare("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name 
            FROM tasks t 
            LEFT JOIN projects p ON t.project_id = p.id 
            LEFT JOIN users u ON t.assigned_to = u.id 
            WHERE t.assigned_to = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$userId]);
    }
    
    return $stmt->fetchAll();
}

// Get task by ID
function getTaskById($id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT t.*, p.name as project_name, u.full_name as assignee_name 
        FROM tasks t 
        LEFT JOIN projects p ON t.project_id = p.id 
        LEFT JOIN users u ON t.assigned_to = u.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    
    return $stmt->fetch();
}

// Get all users
function getAllUsers() {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("
        SELECT u.*, 
               COUNT(DISTINCT p.id) as projects_count,
               COUNT(DISTINCT t.id) as tasks_count
        FROM users u 
        LEFT JOIN project_members pm ON u.id = pm.user_id 
        LEFT JOIN projects p ON pm.project_id = p.id 
        LEFT JOIN tasks t ON u.id = t.assigned_to 
        GROUP BY u.id 
        ORDER BY u.full_name
    ");
    
    return $stmt->fetchAll();
}

// Get user by ID
function getUserById($id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    return $stmt->fetch();
}

// Create new project
function createProject($data) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO projects (name, description, status, priority, start_date, end_date, manager_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $data['name'],
        $data['description'],
        $data['status'],
        $data['priority'],
        $data['start_date'],
        $data['end_date'],
        $data['manager_id']
    ]);
    
    if ($result) {
        $projectId = $pdo->lastInsertId();
        
        // Add manager as project member
        $stmt = $pdo->prepare("
            INSERT INTO project_members (project_id, user_id, role) 
            VALUES (?, ?, 'manager')
        ");
        $stmt->execute([$projectId, $data['manager_id']]);
        
        return $projectId;
    }
    
    return false;
}

// Update project
function updateProject($id, $data) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        UPDATE projects 
        SET name = ?, description = ?, status = ?, priority = ?, 
            start_date = ?, end_date = ?, progress = ?, manager_id = ? 
        WHERE id = ?
    ");
    
    return $stmt->execute([
        $data['name'],
        $data['description'],
        $data['status'],
        $data['priority'],
        $data['start_date'],
        $data['end_date'],
        $data['progress'],
        $data['manager_id'],
        $id
    ]);
}

// Delete project
function deleteProject($id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    return $stmt->execute([$id]);
}

// Create new task
function createTask($data) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO tasks (title, description, status, priority, project_id, assigned_to, assigned_by, due_date, estimated_hours) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $data['title'],
        $data['description'],
        $data['status'],
        $data['priority'],
        $data['project_id'],
        $data['assigned_to'],
        $data['assigned_by'],
        $data['due_date'],
        $data['estimated_hours']
    ]);
}

// Update task
function updateTask($id, $data) {
    $pdo = getDBConnection();
    
    // Fetch existing task so we can preserve fields not provided
    $existing = getTaskById($id);
    if (!$existing) return false;

    $title = $data['title'] ?? $existing['title'];
    $description = array_key_exists('description', $data) ? $data['description'] : $existing['description'];
    $status = $data['status'] ?? $existing['status'];
    $priority = $data['priority'] ?? $existing['priority'];
    $projectId = array_key_exists('project_id', $data) ? $data['project_id'] : $existing['project_id'];
    $assignedTo = array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $existing['assigned_to'];
    $dueDate = array_key_exists('due_date', $data) ? $data['due_date'] : $existing['due_date'];
    $estimatedHours = array_key_exists('estimated_hours', $data) ? $data['estimated_hours'] : $existing['estimated_hours'];
    $actualHours = array_key_exists('actual_hours', $data) ? $data['actual_hours'] : $existing['actual_hours'];

    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET title = ?, description = ?, status = ?, priority = ?, 
            project_id = ?, assigned_to = ?, due_date = ?, 
            estimated_hours = ?, actual_hours = ? 
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $title,
        $description,
        $status,
        $priority,
        $projectId,
        $assignedTo,
        $dueDate,
        $estimatedHours,
        $actualHours,
        $id
    ]);

    if ($result) {
        // If status transitioned to completed, upsert into completed_tasks
        if ($status === 'completed') {
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $stmt2 = $pdo->prepare("INSERT INTO completed_tasks (task_id, completed_by) VALUES (?, ?) ON DUPLICATE KEY UPDATE completed_by = VALUES(completed_by), completed_at = CURRENT_TIMESTAMP");
            $stmt2->execute([$id, $userId]);
        } else {
            // If moved out of completed, remove from completed_tasks
            $stmt3 = $pdo->prepare("DELETE FROM completed_tasks WHERE task_id = ?");
            $stmt3->execute([$id]);
        }
    }

    return $result;
}

// Get all completed tasks joined with task info
function getAllCompletedTasks() {
    $pdo = getDBConnection();
    
    // Check if user is admin or manager (can see all completed tasks)
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? null;
    
    if ($userRole === 'admin' || $userRole === 'manager') {
        // Admin and managers see all completed tasks
        $stmt = $pdo->query("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name, ct.completed_at, cb.full_name as completed_by_name
            FROM completed_tasks ct
            JOIN tasks t ON ct.task_id = t.id
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users cb ON ct.completed_by = cb.id
            ORDER BY ct.completed_at DESC
        ");
    } else {
        // Regular members only see their completed tasks
        $stmt = $pdo->prepare("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name, ct.completed_at, cb.full_name as completed_by_name
            FROM completed_tasks ct
            JOIN tasks t ON ct.task_id = t.id
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users cb ON ct.completed_by = cb.id
            WHERE t.assigned_to = ?
            ORDER BY ct.completed_at DESC
        ");
        $stmt->execute([$userId]);
    }
    
    return $stmt->fetchAll();
}

// Delete task
function deleteTask($id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    return $stmt->execute([$id]);
}

// Create new user
function createUser($data) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, role, job_title) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $data['username'],
        $data['email'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        $data['full_name'],
        $data['role'],
        $data['job_title'] ?? null
    ]);
}

// Update user
function updateUser($id, $data) {
    $pdo = getDBConnection();
    
    $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, job_title = ?";
    $params = [$data['username'], $data['email'], $data['full_name'], $data['role'], $data['job_title'] ?? null];
    
    if (!empty($data['password'])) {
        $sql .= ", password = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

// Delete user
function deleteUser($id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

// Get project progress
function getProjectProgress($projectId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks
        FROM tasks 
        WHERE project_id = ?
    ");
    $stmt->execute([$projectId]);
    
    $result = $stmt->fetch();
    
    if ($result['total_tasks'] > 0) {
        $result['progress_percentage'] = round(($result['completed_tasks'] / $result['total_tasks']) * 100);
    } else {
        $result['progress_percentage'] = 0;
    }
    
    return $result;
}

// Update project progress
function updateProjectProgress($projectId) {
    $progress = getProjectProgress($projectId);
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE projects SET progress = ? WHERE id = ?");
    return $stmt->execute([$progress['progress_percentage'], $projectId]);
}

// Get recent activities
function getRecentActivities($limit = 10) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name as user_name
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}

// Log activity
function logActivity($userId, $action, $entityType, $entityId, $details = null) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, entity_type, entity_id, details) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $userId,
        $action,
        $entityType,
        $entityId,
        $details ? json_encode($details) : null
    ]);
}

// Format date for display
function formatDate($date, $format = 'M j, Y') {
    if (!$date) return 'Not set';
    return date($format, strtotime($date));
}

// Format priority for display
function formatPriority($priority) {
    $priorities = [
        'low' => '<span class="priority-low">Low</span>',
        'medium' => '<span class="priority-medium">Medium</span>',
        'high' => '<span class="priority-high">High</span>'
    ];
    
    return $priorities[$priority] ?? $priority;
}

// Format status for display
function formatStatus($status) {
    $statuses = [
        'pending' => '<span class="status-pending">Pending</span>',
        'active' => '<span class="status-active">Active</span>',
        'completed' => '<span class="status-completed">Completed</span>',
        'on_hold' => '<span class="status-pending">On Hold</span>',
        'in_progress' => '<span class="status-active">In Progress</span>'
    ];
    
    return $statuses[$status] ?? $status;
}

// Get project members
function getProjectMembers($projectId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT u.*, pm.role as project_role
        FROM project_members pm
        JOIN users u ON pm.user_id = u.id
        WHERE pm.project_id = ?
        ORDER BY pm.role, u.full_name
    ");
    $stmt->execute([$projectId]);
    
    return $stmt->fetchAll();
}

// Add project member
function addProjectMember($projectId, $userId, $role) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO project_members (project_id, user_id, role) 
        VALUES (?, ?, ?)
    ");
    
    return $stmt->execute([$projectId, $userId, $role]);
}

// Remove project member
function removeProjectMember($projectId, $userId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        DELETE FROM project_members 
        WHERE project_id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$projectId, $userId]);
}

// Search projects
function searchProjects($query) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as manager_name 
        FROM projects p 
        LEFT JOIN users u ON p.manager_id = u.id 
        WHERE p.name LIKE ? OR p.description LIKE ?
        ORDER BY p.created_at DESC
    ");
    
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $searchTerm]);
    
    return $stmt->fetchAll();
}

// Search tasks
function searchTasks($query) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT t.*, p.name as project_name, u.full_name as assignee_name 
        FROM tasks t 
        LEFT JOIN projects p ON t.project_id = p.id 
        LEFT JOIN users u ON t.assigned_to = u.id 
        WHERE t.title LIKE ? OR t.description LIKE ?
        ORDER BY t.created_at DESC
    ");
    
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $searchTerm]);
    
    return $stmt->fetchAll();
}

// Get tasks by project
function getTasksByProject($projectId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT t.*, u.full_name as assignee_name 
        FROM tasks t 
        LEFT JOIN users u ON t.assigned_to = u.id 
        WHERE t.project_id = ?
        ORDER BY t.due_date ASC
    ");
    $stmt->execute([$projectId]);
    
    return $stmt->fetchAll();
}

// Get tasks by user
function getTasksByUser($userId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT t.*, p.name as project_name 
        FROM tasks t 
        LEFT JOIN projects p ON t.project_id = p.id 
        WHERE t.assigned_to = ?
        ORDER BY t.due_date ASC
    ");
    $stmt->execute([$userId]);
    
    return $stmt->fetchAll();
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

// Check if user has permission
function hasPermission($userId, $permission, $entityId = null) {
    $user = getUserById($userId);
    
    if (!$user) return false;
    
    // Admin has all permissions
    if ($user['role'] === 'admin') return true;
    
    // Manager permissions
    if ($user['role'] === 'manager') {
        switch ($permission) {
            case 'manage_projects':
                return true;
            case 'manage_tasks':
                return true;
            case 'manage_team':
                return true;
            case 'view_reports':
                return true;
        }
    }
    
    // Member permissions
    if ($user['role'] === 'member') {
        switch ($permission) {
            case 'view_projects':
                return true;
            case 'manage_own_tasks':
                return true;
            case 'view_team':
                return true;
        }
    }
    
    return false;
}
?>
