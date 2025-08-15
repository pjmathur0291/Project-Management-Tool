<?php
session_start();
require_once 'config/database.php';
require_once 'includes/NotificationManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'],
    'role' => $_SESSION['role']
];

$success_message = '';
$error_message = '';

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo = getDBConnection();
        $notificationManager = new NotificationManager($pdo);
        
        switch ($_POST['action']) {
            case 'mark_read':
                $notification_id = $_POST['notification_id'] ?? null;
                if ($notification_id) {
                    if ($notificationManager->markNotificationAsRead($notification_id, $current_user['id'])) {
                        $success_message = 'Notification marked as read.';
                    } else {
                        $error_message = 'Failed to mark notification as read.';
                    }
                }
                break;
                
            case 'mark_all_read':
                if ($notificationManager->markAllNotificationsAsRead($current_user['id'])) {
                    $success_message = 'All notifications marked as read.';
                } else {
                    $error_message = 'Failed to mark all notifications as read.';
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = 'Error processing notification action: ' . $e->getMessage();
    }
}

// Get notifications
try {
    $pdo = getDBConnection();
    $notificationManager = new NotificationManager($pdo);
    
    $notifications = $notificationManager->getUnreadNotifications($current_user['id'], 50);
    $notification_count = $notificationManager->getNotificationCount($current_user['id']);
    
} catch (Exception $e) {
    $error_message = 'Error loading notifications: ' . $e->getMessage();
    $notifications = [];
    $notification_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notifications-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .page-actions {
            text-align: center;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .notification-stats {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        .notification-stats h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .notifications-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .notification-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .notification-title {
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .notification-time {
            color: #666;
            font-size: 0.9rem;
            white-space: nowrap;
            margin-left: 15px;
        }
        .notification-message {
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        .notification-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .notification-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: inline-block;
        }
        .type-task_assigned { background: #e3f2fd; color: #1976d2; }
        .type-task_updated { background: #fff3e0; color: #f57c00; }
        .type-task_completed { background: #e8f5e8; color: #388e3c; }
        .type-general { background: #f3e5f5; color: #7b1fa2; }
        .no-notifications {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .no-notifications i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        .notification-link {
            color: #007bff;
            text-decoration: none;
        }
        .notification-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="notifications-container">
        <div class="page-header">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <p>Stay updated with your task assignments and updates</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="simple-task-management.php<?php echo ($current_user['role'] === 'admin' || $current_user['role'] === 'manager') ? '' : '?mine=1'; ?>" class="btn btn-primary">
                <i class="fas fa-tasks"></i> View Tasks
            </a>
        </div>

        <div class="notification-stats">
            <h3>Notification Summary</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $notification_count; ?></div>
                    <div class="stat-label">Unread Notifications</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($notifications); ?></div>
                    <div class="stat-label">Total Notifications</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $current_user['role']; ?></div>
                    <div class="stat-label">Your Role</div>
                </div>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <h3>No unread notifications</h3>
                <p>You're all caught up! Check back later for new updates.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <div class="notification-item" style="background: #f8f9fa; padding: 15px 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong>Quick Actions</strong>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <i class="fas fa-check-double"></i> Mark All as Read
                            </button>
                        </form>
                    </div>
                </div>
                
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item">
                        <span class="notification-type type-<?php echo $notification['type']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                        </span>
                        
                        <div class="notification-header">
                            <h4 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h4>
                            <span class="notification-time">
                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="notification-message">
                            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                        </div>
                        
                        <div class="notification-actions">
                            <?php if ($notification['related_task_id']): ?>
                                <a href="simple-task-management.php?tab=all<?php echo ($current_user['role'] === 'admin' || $current_user['role'] === 'manager') ? '' : '&mine=1'; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Task
                                </a>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-check"></i> Mark as Read
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh page after actions to show updated data
        <?php if ($success_message): ?>
        setTimeout(function() {
            location.reload();
        }, 1500);
        <?php endif; ?>
    </script>
</body>
</html>
