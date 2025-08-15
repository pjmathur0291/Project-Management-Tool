<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Test database connection
try {
    $pdo = getDBConnection();
    $dbStatus = "✅ Database connection successful";
} catch (Exception $e) {
    $dbStatus = "❌ Database connection failed: " . $e->getMessage();
}

// Test API endpoints
$apiTests = [];

// Test dashboard API
$dashboardResponse = file_get_contents('http://localhost/management-tool/api/dashboard.php');
$dashboardData = json_decode($dashboardResponse, true);
$apiTests['dashboard'] = $dashboardData['success'] ? "✅ Working" : "❌ Failed";

// Test projects API
$projectsResponse = file_get_contents('http://localhost/management-tool/api/projects.php');
$projectsData = json_decode($projectsResponse, true);
$apiTests['projects'] = $projectsData['success'] ? "✅ Working" : "❌ Failed";

// Test tasks API
$tasksResponse = file_get_contents('http://localhost/management-tool/api/tasks.php');
$tasksData = json_decode($tasksResponse, true);
$apiTests['tasks'] = $tasksData['success'] ? "✅ Working" : "❌ Failed";

// Test team API
$teamResponse = file_get_contents('http://localhost/management-tool/api/team.php');
$teamData = json_decode($teamResponse, true);
$apiTests['team'] = $teamData['success'] ? "✅ Working" : "❌ Failed";

// Get database stats
$stats = getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Test - Project Management Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 5px 0;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
        }
        .login-link {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .login-link:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🔧 Project Management Tool - System Test</h1>
    
    <div class="test-section">
        <h2>Database Status</h2>
        <div class="status <?php echo strpos($dbStatus, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $dbStatus; ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>API Endpoints Status</h2>
        <?php foreach ($apiTests as $api => $status): ?>
            <div class="status <?php echo strpos($status, '✅') !== false ? 'success' : 'error'; ?>">
                <strong><?php echo ucfirst($api); ?> API:</strong> <?php echo $status; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="test-section">
        <h2>Database Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                <div class="stat-label">Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_tasks']; ?></div>
                <div class="stat-label">Tasks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_team']; ?></div>
                <div class="stat-label">Team Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_tasks']; ?></div>
                <div class="stat-label">Pending Tasks</div>
            </div>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Application Access</h2>
        <div class="status info">
            If all tests above show ✅, your application should be working properly.
        </div>
        <a href="login.php" class="login-link">🚀 Go to Login Page</a>
        <br>
        <a href="index.php" class="login-link">🏠 Go to Dashboard (if logged in)</a>
    </div>
    
    <div class="test-section">
        <h2>Demo Credentials</h2>
        <div class="status info">
            <strong>Admin:</strong> admin / admin123<br>
            <strong>Manager:</strong> pjmathur157 / (check database for password)<br>
            <strong>Member:</strong> Amit / (check database for password)
        </div>
    </div>
    
    <div class="test-section">
        <h2>Troubleshooting</h2>
        <div class="status info">
            <strong>If you see ❌ errors:</strong><br>
            1. Make sure XAMPP is running (Apache + MySQL)<br>
            2. Check that the database 'project_management' exists<br>
            3. Verify all required tables are present<br>
            4. Check file permissions<br>
            5. Look at Apache error logs for more details
        </div>
    </div>
</body>
</html>
