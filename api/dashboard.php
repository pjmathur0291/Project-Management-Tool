<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    // Get dashboard statistics
    $stats = getDashboardStats();
    
    // Get project progress breakdown
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM projects 
        GROUP BY status
    ");
    $projectProgress = $stmt->fetchAll();
    
    $projectStats = [
        'active' => 0,
        'completed' => 0,
        'pending' => 0,
        'on_hold' => 0
    ];
    
    foreach ($projectProgress as $row) {
        $projectStats[$row['status']] = (int)$row['count'];
    }
    
    // Get task distribution by priority
    $stmt = $pdo->query("
        SELECT 
            priority,
            COUNT(*) as count
        FROM tasks 
        GROUP BY priority
    ");
    $taskDistribution = $stmt->fetchAll();
    
    $taskStats = [
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    foreach ($taskDistribution as $row) {
        $taskStats[$row['priority']] = (int)$row['count'];
    }
    
    // Get recent activities
    $stmt = $pdo->query("
        SELECT 
            al.action,
            al.entity_type,
            al.created_at,
            u.full_name as user_name
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 5
    ");
    $recentActivities = $stmt->fetchAll();
    
    // Format activities for display
    $formattedActivities = [];
    foreach ($recentActivities as $activity) {
        $formattedActivities[] = [
            'action' => $activity['action'],
            'entity_type' => $activity['entity_type'],
            'user_name' => $activity['user_name'] ?? 'System',
            'created_at' => $activity['created_at'],
            'icon' => getActivityIcon($activity['action']),
            'color' => getActivityColor($activity['action'])
        ];
    }
    
    // Get timeline data
    $stmt = $pdo->query("
        SELECT 
            name,
            status,
            progress,
            start_date,
            end_date
        FROM projects 
        ORDER BY start_date ASC
    ");
    $timelineData = $stmt->fetchAll();
    
    $timeline = [];
    foreach ($timelineData as $project) {
        $quarter = getQuarterFromDate($project['start_date']);
        $timeline[] = [
            'name' => $project['name'],
            'quarter' => $quarter,
            'status' => $project['status'],
            'progress' => $project['progress'] ?? 0
        ];
    }
    
    $response = [
        'success' => true,
        'stats' => [
            'total_projects' => $stats['total_projects'],
            'total_tasks' => $stats['total_tasks'],
            'total_team' => $stats['total_team'],
            'pending_tasks' => $stats['pending_tasks'],
            'project_progress' => $projectStats,
            'recent_activities' => $formattedActivities
        ],
        'charts' => [
            'project_progress' => $projectStats,
            'task_distribution' => $taskStats,
            'timeline' => $timeline
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false, 
        'message' => 'Error loading dashboard data: ' . $e->getMessage()
    ];
}

echo json_encode($response);

// Helper functions
function getActivityIcon($action) {
    $icons = [
        'created' => 'plus',
        'updated' => 'edit',
        'deleted' => 'trash',
        'assigned' => 'user-plus',
        'completed' => 'check',
        'commented' => 'comment',
        'started' => 'play',
        'paused' => 'pause'
    ];
    
    return $icons[strtolower($action)] ?? 'info';
}

function getActivityColor($action) {
    $colors = [
        'created' => 'success',
        'updated' => 'warning',
        'deleted' => 'danger',
        'assigned' => 'info',
        'completed' => 'success',
        'commented' => 'info',
        'started' => 'success',
        'paused' => 'warning'
    ];
    
    return $colors[strtolower($action)] ?? 'info';
}

function getQuarterFromDate($date) {
    if (!$date) return 'Unknown';
    
    $month = date('n', strtotime($date));
    $year = date('Y', strtotime($date));
    
    if ($month <= 3) {
        return "Q1 $year";
    } elseif ($month <= 6) {
        return "Q2 $year";
    } elseif ($month <= 9) {
        return "Q3 $year";
    } else {
        return "Q4 $year";
    }
}
?>
