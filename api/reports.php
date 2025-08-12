<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    $pdo = getDBConnection();
    
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
    
    // Get project status distribution
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM projects 
        GROUP BY status
    ");
    $projectStatus = $stmt->fetchAll();
    
    $projectStats = [
        'active' => 0,
        'completed' => 0,
        'pending' => 0,
        'on_hold' => 0
    ];
    
    foreach ($projectStatus as $row) {
        $projectStats[$row['status']] = (int)$row['count'];
    }
    
    $response = [
        'success' => true,
        'timeline' => $timeline,
        'task_distribution' => $taskStats,
        'project_status' => $projectStats
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false, 
        'message' => 'Error loading reports: ' . $e->getMessage()
    ];
}

echo json_encode($response);

// Helper function to get quarter from date
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
