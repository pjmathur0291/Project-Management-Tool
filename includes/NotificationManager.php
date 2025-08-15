<?php

class NotificationManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send a notification to a user
     */
    public function sendNotification($user_id, $title, $message, $type = 'task_assigned', $related_task_id = null) {
        try {
            // Check if user has notifications enabled for this type
            if (!$this->isNotificationEnabled($user_id, $type)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_task_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$user_id, $title, $message, $type, $related_task_id]);
            
            if ($result) {
                // Send browser notification if enabled
                if ($this->isBrowserNotificationEnabled($user_id)) {
                    $this->sendBrowserNotification($user_id, $title, $message);
                }
                
                // Send email notification if enabled
                if ($this->isEmailNotificationEnabled($user_id)) {
                    $this->sendEmailNotification($user_id, $title, $message);
                }
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send task assignment notification
     */
    public function sendTaskAssignmentNotification($task_id, $assigned_to_user_id, $assigned_by_user_id) {
        try {
            // Get task details
            $stmt = $this->pdo->prepare("
                SELECT t.title, t.description, p.name as project_name, u.full_name as assigned_by_name
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assigned_by = u.id
                WHERE t.id = ?
            ");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();
            
            if ($task) {
                $title = "New Task Assigned";
                $message = "You have been assigned a new task: '{$task['title']}'";
                
                if ($task['project_name']) {
                    $message .= " for project: {$task['project_name']}";
                }
                
                if ($task['assigned_by_name']) {
                    $message .= " by {$task['assigned_by_name']}";
                }
                
                if ($task['description']) {
                    $message .= "\n\nDescription: " . substr($task['description'], 0, 100);
                    if (strlen($task['description']) > 100) {
                        $message .= "...";
                    }
                }
                
                return $this->sendNotification(
                    $assigned_to_user_id, 
                    $title, 
                    $message, 
                    'task_assigned', 
                    $task_id
                );
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending task assignment notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send task update notification
     */
    public function sendTaskUpdateNotification($task_id, $user_id, $update_type) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.title, u.full_name as updated_by_name
                FROM tasks t 
                LEFT JOIN users u ON t.updated_by = u.id
                WHERE t.id = ?
            ");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();
            
            if ($task) {
                $title = "Task Updated";
                $message = "Task '{$task['title']}' has been updated";
                
                if ($task['updated_by_name']) {
                    $message .= " by {$task['updated_by_name']}";
                }
                
                $message .= "\n\nUpdate: " . ucfirst(str_replace('_', ' ', $update_type));
                
                return $this->sendNotification(
                    $user_id, 
                    $title, 
                    $message, 
                    'task_updated', 
                    $task_id
                );
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending task update notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send task completion notification
     */
    public function sendTaskCompletionNotification($task_id, $completed_by_user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.title, u.full_name as completed_by_name
                FROM tasks t 
                LEFT JOIN users u ON t.completed_by = u.id
                WHERE t.id = ?
            ");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();
            
            if ($task) {
                $title = "Task Completed";
                $message = "Task '{$task['title']}' has been marked as completed";
                
                if ($task['completed_by_name']) {
                    $message .= " by {$task['completed_by_name']}";
                }
                
                return $this->sendNotification(
                    $completed_by_user_id, 
                    $title, 
                    $message, 
                    'task_completed', 
                    $task_id
                );
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending task completion notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if notifications are enabled for a user and type
     */
    private function isNotificationEnabled($user_id, $type) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT task_assigned, task_updated, task_completed 
                FROM notification_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $prefs = $stmt->fetch();
            
            if ($prefs) {
                switch ($type) {
                    case 'task_assigned':
                        return (bool)$prefs['task_assigned'];
                    case 'task_updated':
                        return (bool)$prefs['task_updated'];
                    case 'task_completed':
                        return (bool)$prefs['task_completed'];
                    default:
                        return true;
                }
            }
            
            return true; // Default to enabled if no preferences found
        } catch (Exception $e) {
            error_log("Error checking notification preferences: " . $e->getMessage());
            return true; // Default to enabled on error
        }
    }
    
    /**
     * Check if browser notifications are enabled for a user
     */
    private function isBrowserNotificationEnabled($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT browser_notifications 
                FROM notification_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $pref = $stmt->fetchColumn();
            
            return $pref ? (bool)$pref : true;
        } catch (Exception $e) {
            error_log("Error checking browser notification preferences: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Check if email notifications are enabled for a user
     */
    private function isEmailNotificationEnabled($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT email_notifications 
                FROM notification_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $pref = $stmt->fetchColumn();
            
            return $pref ? (bool)$pref : true;
        } catch (Exception $e) {
            error_log("Error checking email notification preferences: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Send browser notification (placeholder for future implementation)
     */
    private function sendBrowserNotification($user_id, $title, $message) {
        // This would integrate with a real-time notification system
        // For now, we'll just log it
        error_log("Browser notification for user $user_id: $title - $message");
    }
    
    /**
     * Send email notification (placeholder for future implementation)
     */
    private function sendEmailNotification($user_id, $title, $message) {
        // This would integrate with an email service
        // For now, we'll just log it
        error_log("Email notification for user $user_id: $title - $message");
    }
    
    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications($user_id, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting unread notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllNotificationsAsRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ?
            ");
            return $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notification count for a user
     */
    public function getNotificationCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error getting notification count: " . $e->getMessage());
            return 0;
        }
    }
}
?>
