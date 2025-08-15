<?php
require_once 'config/database.php';

class TagManager {
    
    /**
     * Get all tags
     */
    public static function getAllTags() {
        $pdo = getDBConnection();
        $stmt = $pdo->query("
            SELECT t.*, u.full_name as created_by_name 
            FROM tags t 
            LEFT JOIN users u ON t.created_by = u.id 
            ORDER BY t.name ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get tag by ID
     */
    public static function getTagById($id) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT t.*, u.full_name as created_by_name 
            FROM tags t 
            LEFT JOIN users u ON t.created_by = u.id 
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get tag by name
     */
    public static function getTagByName($name) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM tags WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch();
    }
    
    /**
     * Create a new tag
     */
    public static function createTag($data) {
        $pdo = getDBConnection();
        
        // Check if tag already exists
        if (self::getTagByName($data['name'])) {
            return ['success' => false, 'message' => 'Tag already exists'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO tags (name, color, description, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['name'],
            $data['color'] ?? '#007bff',
            $data['description'] ?? '',
            $data['created_by'] ?? ($_SESSION['user_id'] ?? null)
        ]);
        
        if ($result) {
            return ['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Tag created successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to create tag'];
    }
    
    /**
     * Update a tag
     */
    public static function updateTag($id, $data) {
        $pdo = getDBConnection();
        
        // Check if tag exists
        $existing = self::getTagById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Tag not found'];
        }
        
        // Check if new name conflicts with existing tag (excluding current tag)
        if (isset($data['name']) && $data['name'] !== $existing['name']) {
            $conflicting = self::getTagByName($data['name']);
            if ($conflicting && $conflicting['id'] != $id) {
                return ['success' => false, 'message' => 'Tag name already exists'];
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE tags 
            SET name = ?, color = ?, description = ? 
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $data['name'] ?? $existing['name'],
            $data['color'] ?? $existing['color'],
            $data['description'] ?? $existing['description'],
            $id
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Tag updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update tag'];
    }
    
    /**
     * Delete a tag
     */
    public static function deleteTag($id) {
        $pdo = getDBConnection();
        
        // Check if tag exists
        if (!self::getTagById($id)) {
            return ['success' => false, 'message' => 'Tag not found'];
        }
        
        // Start transaction to handle cascading deletes
        $pdo->beginTransaction();
        
        try {
            // Delete associations first (though CASCADE should handle this)
            $pdo->prepare("DELETE FROM task_tags WHERE tag_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM project_tags WHERE tag_id = ?")->execute([$id]);
            
            // Delete the tag
            $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            return ['success' => true, 'message' => 'Tag deleted successfully'];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to delete tag: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get tags for a specific task
     */
    public static function getTaskTags($taskId) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT t.*, tt.added_at, u.full_name as added_by_name
            FROM tags t
            JOIN task_tags tt ON t.id = tt.tag_id
            LEFT JOIN users u ON tt.added_by = u.id
            WHERE tt.task_id = ?
            ORDER BY t.name ASC
        ");
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get tags for a specific project
     */
    public static function getProjectTags($projectId) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT t.*, pt.added_at, u.full_name as added_by_name
            FROM tags t
            JOIN project_tags pt ON t.id = pt.tag_id
            LEFT JOIN users u ON pt.added_by = u.id
            WHERE pt.project_id = ?
            ORDER BY t.name ASC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add tag to task
     */
    public static function addTagToTask($taskId, $tagId, $addedBy = null) {
        $pdo = getDBConnection();
        
        // Check if association already exists
        $stmt = $pdo->prepare("SELECT id FROM task_tags WHERE task_id = ? AND tag_id = ?");
        $stmt->execute([$taskId, $tagId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Tag already assigned to this task'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO task_tags (task_id, tag_id, added_by) 
            VALUES (?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $taskId,
            $tagId,
            $addedBy ?? ($_SESSION['user_id'] ?? null)
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Tag added to task successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to add tag to task'];
    }
    
    /**
     * Remove tag from task
     */
    public static function removeTagFromTask($taskId, $tagId) {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("DELETE FROM task_tags WHERE task_id = ? AND tag_id = ?");
        $result = $stmt->execute([$taskId, $tagId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Tag removed from task successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to remove tag from task'];
    }
    
    /**
     * Add tag to project
     */
    public static function addTagToProject($projectId, $tagId, $addedBy = null) {
        $pdo = getDBConnection();
        
        // Check if association already exists
        $stmt = $pdo->prepare("SELECT id FROM project_tags WHERE project_id = ? AND tag_id = ?");
        $stmt->execute([$projectId, $tagId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Tag already assigned to this project'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO project_tags (project_id, tag_id, added_by) 
            VALUES (?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $projectId,
            $tagId,
            $addedBy ?? ($_SESSION['user_id'] ?? null)
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Tag added to project successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to add tag to project'];
    }
    
    /**
     * Remove tag from project
     */
    public static function removeTagFromProject($projectId, $tagId) {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("DELETE FROM project_tags WHERE project_id = ? AND tag_id = ?");
        $result = $stmt->execute([$projectId, $tagId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Tag removed from project successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to remove tag from project'];
    }
    
    /**
     * Update task tags (replace all tags for a task)
     */
    public static function updateTaskTags($taskId, $tagIds) {
        $pdo = getDBConnection();
        
        $pdo->beginTransaction();
        
        try {
            // Remove all existing tags for this task
            $stmt = $pdo->prepare("DELETE FROM task_tags WHERE task_id = ?");
            $stmt->execute([$taskId]);
            
            // Add new tags
            if (!empty($tagIds)) {
                $stmt = $pdo->prepare("
                    INSERT INTO task_tags (task_id, tag_id, added_by) 
                    VALUES (?, ?, ?)
                ");
                
                $userId = $_SESSION['user_id'] ?? null;
                foreach ($tagIds as $tagId) {
                    $stmt->execute([$taskId, $tagId, $userId]);
                }
            }
            
            $pdo->commit();
            return ['success' => true, 'message' => 'Task tags updated successfully'];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to update task tags: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update project tags (replace all tags for a project)
     */
    public static function updateProjectTags($projectId, $tagIds) {
        $pdo = getDBConnection();
        
        $pdo->beginTransaction();
        
        try {
            // Remove all existing tags for this project
            $stmt = $pdo->prepare("DELETE FROM project_tags WHERE project_id = ?");
            $stmt->execute([$projectId]);
            
            // Add new tags
            if (!empty($tagIds)) {
                $stmt = $pdo->prepare("
                    INSERT INTO project_tags (project_id, tag_id, added_by) 
                    VALUES (?, ?, ?)
                ");
                
                $userId = $_SESSION['user_id'] ?? null;
                foreach ($tagIds as $tagId) {
                    $stmt->execute([$projectId, $tagId, $userId]);
                }
            }
            
            $pdo->commit();
            return ['success' => true, 'message' => 'Project tags updated successfully'];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to update project tags: ' . $e->getMessage()];
        }
    }
    
    /**
     * Search tasks by tags
     */
    public static function getTasksByTags($tagIds, $additionalFilters = []) {
        $pdo = getDBConnection();
        
        $tagConditions = '';
        $params = [];
        
        if (!empty($tagIds)) {
            $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
            $tagConditions = "AND t.id IN (
                SELECT DISTINCT tt.task_id 
                FROM task_tags tt 
                WHERE tt.tag_id IN ($placeholders)
            )";
            $params = array_merge($params, $tagIds);
        }
        
        $additionalConditions = '';
        if (isset($additionalFilters['status'])) {
            $additionalConditions .= ' AND t.status = ?';
            $params[] = $additionalFilters['status'];
        }
        if (isset($additionalFilters['priority'])) {
            $additionalConditions .= ' AND t.priority = ?';
            $params[] = $additionalFilters['priority'];
        }
        if (isset($additionalFilters['project_id'])) {
            $additionalConditions .= ' AND t.project_id = ?';
            $params[] = $additionalFilters['project_id'];
        }
        if (isset($additionalFilters['assigned_to'])) {
            $additionalConditions .= ' AND t.assigned_to = ?';
            $params[] = $additionalFilters['assigned_to'];
        }
        
        $stmt = $pdo->prepare("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name, ab.full_name as assigned_by_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users ab ON t.assigned_by = ab.id
            WHERE 1=1 $tagConditions $additionalConditions
            ORDER BY t.due_date ASC, t.priority DESC, t.created_at DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get tag usage statistics
     */
    public static function getTagStatistics() {
        $pdo = getDBConnection();
        
        $stmt = $pdo->query("
            SELECT 
                t.id,
                t.name,
                t.color,
                t.description,
                (SELECT COUNT(*) FROM task_tags tt WHERE tt.tag_id = t.id) as task_count,
                (SELECT COUNT(*) FROM project_tags pt WHERE pt.tag_id = t.id) as project_count
            FROM tags t
            ORDER BY task_count DESC, project_count DESC, t.name ASC
        ");
        
        return $stmt->fetchAll();
    }
}
?>