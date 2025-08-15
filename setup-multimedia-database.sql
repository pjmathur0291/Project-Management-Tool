-- Add multimedia file support to the project management system

-- Create multimedia_files table
CREATE TABLE IF NOT EXISTS multimedia_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    entity_type ENUM('task', 'project', 'comment') NOT NULL,
    entity_id INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_file_type (file_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create uploads directory structure
-- Note: This will be handled by PHP code

-- Add file upload settings to a configuration table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default file upload settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('max_file_size', '10485760', 'Maximum file size in bytes (10MB)'),
('allowed_file_types', 'jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,wmv,flv,webm,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar', 'Comma-separated list of allowed file extensions'),
('upload_directory', 'uploads', 'Directory for storing uploaded files'),
('image_max_width', '1920', 'Maximum width for uploaded images'),
('image_max_height', '1080', 'Maximum height for uploaded images'),
('video_max_duration', '300', 'Maximum video duration in seconds (5 minutes)'),
('enable_thumbnails', '1', 'Enable thumbnail generation for images (1=yes, 0=no)'),
('thumbnail_size', '150', 'Thumbnail size in pixels');

-- Add multimedia count columns to existing tables for quick access
ALTER TABLE tasks ADD COLUMN multimedia_count INT DEFAULT 0;
ALTER TABLE projects ADD COLUMN multimedia_count INT DEFAULT 0;
ALTER TABLE comments ADD COLUMN multimedia_count INT DEFAULT 0;

-- Create triggers to update multimedia counts
DELIMITER //

CREATE TRIGGER update_task_multimedia_count_insert
AFTER INSERT ON multimedia_files
FOR EACH ROW
BEGIN
    IF NEW.entity_type = 'task' THEN
        UPDATE tasks SET multimedia_count = (
            SELECT COUNT(*) FROM multimedia_files 
            WHERE entity_type = 'task' AND entity_id = NEW.entity_id
        ) WHERE id = NEW.entity_id;
    END IF;
END//

CREATE TRIGGER update_task_multimedia_count_delete
AFTER DELETE ON multimedia_files
FOR EACH ROW
BEGIN
    IF OLD.entity_type = 'task' THEN
        UPDATE tasks SET multimedia_count = (
            SELECT COUNT(*) FROM multimedia_files 
            WHERE entity_type = 'task' AND entity_id = OLD.entity_id
        ) WHERE id = OLD.entity_id;
    END IF;
END//

CREATE TRIGGER update_project_multimedia_count_insert
AFTER INSERT ON multimedia_files
FOR EACH ROW
BEGIN
    IF NEW.entity_type = 'project' THEN
        UPDATE projects SET multimedia_count = (
            SELECT COUNT(*) FROM multimedia_files 
            WHERE entity_type = 'project' AND entity_id = NEW.entity_id
        ) WHERE id = NEW.entity_id;
    END IF;
END//

CREATE TRIGGER update_project_multimedia_count_delete
AFTER DELETE ON multimedia_files
FOR EACH ROW
BEGIN
    IF OLD.entity_type = 'project' THEN
        UPDATE projects SET multimedia_count = (
            SELECT COUNT(*) FROM multimedia_files 
            WHERE entity_type = 'project' AND entity_id = OLD.entity_id
        ) WHERE id = OLD.entity_id;
    END IF;
END//

CREATE TRIGGER update_comment_multimedia_count_insert
AFTER INSERT ON multimedia_files
FOR EACH ROW
BEGIN
    IF NEW.entity_type = 'comment' THEN
        UPDATE comments SET multimedia_count = (
            SELECT COUNT(*) FROM multimedia_files 
            WHERE entity_type = 'comment' AND entity_id = NEW.entity_id
        ) WHERE id = NEW.entity_id;
    END IF;
END//

CREATE TRIGGER update_comment_multimedia_count_delete
AFTER DELETE ON multimedia_files
FOR EACH ROW
BEGIN
    IF OLD.entity_type = 'comment' THEN
        UPDATE comments SET multimedia_count = (
            SELECT COUNT(*) FROM multimedia_files 
            WHERE entity_type = 'comment' AND entity_id = OLD.entity_id
        ) WHERE id = OLD.entity_id;
    END IF;
END//

DELIMITER ;
