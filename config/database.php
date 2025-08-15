<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'project_management');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database and create tables
function initializeDatabase() {
    try {
        // First connect without database to create it if it doesn't exist
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Create tables
        createTables($pdo);
        
        // Insert sample data
        insertSampleData($pdo);
        
        return true;
    } catch (PDOException $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}

// Create all necessary tables
function createTables($pdo) {
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'manager', 'member') DEFAULT 'member',
            job_title VARCHAR(100) NULL,
            avatar VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Ensure job_title exists for previously created databases
    try { $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS job_title VARCHAR(100) NULL"); } catch (Exception $e) { /* ignore for older MySQL not supporting IF NOT EXISTS */ }
    
    // Projects table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            status ENUM('pending', 'active', 'completed', 'on_hold') DEFAULT 'pending',
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            start_date DATE,
            end_date DATE NULL,
            progress INT DEFAULT 0,
            manager_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Tasks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            status ENUM('pending', 'in_progress', 'completed', 'on_hold') DEFAULT 'pending',
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            project_id INT,
            assigned_to INT,
            assigned_by INT,
            due_date DATE,
            estimated_hours DECIMAL(5,2),
            actual_hours DECIMAL(5,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Completed tasks table (history of completions)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS completed_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL UNIQUE,
            completed_by INT NULL,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Project members table (many-to-many relationship)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS project_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('manager', 'developer', 'designer', 'tester', 'viewer') DEFAULT 'developer',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_project_user (project_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Comments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            content TEXT NOT NULL,
            user_id INT NOT NULL,
            project_id INT,
            task_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Activity log table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            entity_type ENUM('project', 'task', 'user', 'comment') NOT NULL,
            entity_id INT,
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Tags table - stores all available tags
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            color VARCHAR(7) DEFAULT '#007bff',
            description TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Task Tags table - many-to-many relationship between tasks and tags
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS task_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            tag_id INT NOT NULL,
            added_by INT,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
            FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_task_tag (task_id, tag_id),
            INDEX idx_task_id (task_id),
            INDEX idx_tag_id (tag_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Project Tags table - many-to-many relationship between projects and tags
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS project_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            tag_id INT NOT NULL,
            added_by INT,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
            FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_project_tag (project_id, tag_id),
            INDEX idx_project_id (project_id),
            INDEX idx_tag_id (tag_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

// Insert sample data for demonstration
function insertSampleData($pdo) {
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() > 0) {
        return; // Data already exists
    }
    
    // Insert sample users
    $users = [
        ['admin', 'admin@pmtool.com', password_hash('admin123', PASSWORD_DEFAULT), 'Admin User', 'admin'],
        ['john_doe', 'john@pmtool.com', password_hash('password123', PASSWORD_DEFAULT), 'John Doe', 'manager'],
        ['jane_smith', 'jane@pmtool.com', password_hash('password123', PASSWORD_DEFAULT), 'Jane Smith', 'member'],
        ['mike_wilson', 'mike@pmtool.com', password_hash('password123', PASSWORD_DEFAULT), 'Mike Wilson', 'member']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $stmt->execute($user);
    }
    
    // Insert sample projects
    $projects = [
        ['Website Redesign', 'Complete redesign of company website with modern UI/UX', 'active', 'high', '2024-01-01', '2024-06-30', 65, 2],
        ['Mobile App Development', 'Develop iOS and Android apps for customer service', 'pending', 'medium', '2024-03-01', '2024-12-31', 0, 2],
        ['Database Migration', 'Migrate legacy database to new cloud infrastructure', 'active', 'low', '2024-02-15', '2024-05-15', 40, 2]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO projects (name, description, status, priority, start_date, end_date, progress, manager_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($projects as $project) {
        $stmt->execute($project);
    }
    
    // Insert sample tasks
    $tasks = [
        ['Design Homepage', 'Create wireframes and mockups for homepage', 'in_progress', 'high', 1, 3, 2, '2024-04-15', 8, 6],
        ['Implement User Authentication', 'Build login/register system', 'completed', 'high', 1, 4, 2, '2024-03-20', 12, 10],
        ['API Development', 'Develop REST API endpoints', 'pending', 'medium', 2, 4, 2, '2024-05-01', 20, 0],
        ['Database Schema Design', 'Design new database structure', 'in_progress', 'medium', 3, 2, 2, '2024-04-01', 16, 8]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, status, priority, project_id, assigned_to, assigned_by, due_date, estimated_hours, actual_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($tasks as $task) {
        $stmt->execute($task);
    }
    
    // Insert project members
    $members = [
        [1, 2, 'manager'],
        [1, 3, 'developer'],
        [1, 4, 'designer'],
        [2, 2, 'manager'],
        [2, 3, 'developer'],
        [3, 2, 'manager'],
        [3, 4, 'developer']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
    foreach ($members as $member) {
        $stmt->execute($member);
    }
    
    // Insert sample tags
    $tags = [
        ['urgent', '#dc3545', 'Tasks that need immediate attention', 1],
        ['bug', '#fd7e14', 'Bug fixes and issues', 1],
        ['feature', '#28a745', 'New feature development', 1],
        ['documentation', '#6f42c1', 'Documentation related tasks', 1],
        ['testing', '#20c997', 'Testing and QA tasks', 1],
        ['design', '#e83e8c', 'UI/UX design tasks', 1],
        ['backend', '#6c757d', 'Backend development tasks', 1],
        ['frontend', '#17a2b8', 'Frontend development tasks', 1],
        ['maintenance', '#ffc107', 'System maintenance tasks', 1],
        ['research', '#fd7e14', 'Research and investigation tasks', 1]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name, color, description, created_by) VALUES (?, ?, ?, ?)");
    foreach ($tags as $tag) {
        $stmt->execute($tag);
    }
}

// Initialize database on first run
if (!function_exists('isDatabaseInitialized')) {
    initializeDatabase();
}
?>
