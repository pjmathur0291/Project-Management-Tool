-- Add completion tracking columns to existing tasks table
ALTER TABLE tasks 
ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
ADD COLUMN completed_by INT(11) NULL DEFAULT NULL AFTER completed_at;

-- Add foreign key constraint for completed_by
ALTER TABLE tasks 
ADD CONSTRAINT fk_tasks_completed_by 
FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL;

-- Update any existing completed tasks to have completion data
UPDATE tasks 
SET completed_at = updated_at, completed_by = assigned_by 
WHERE status = 'completed' AND completed_at IS NULL;
