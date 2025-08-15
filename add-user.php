<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $job_title = trim($_POST['job_title'] ?? '');
    $role = $_POST['role'] ?? 'member';
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($job_title) || empty($password)) {
        $error_message = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error_message = 'Username already exists.';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = 'Email already exists.';
                } else {
                    // Hash password and insert user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password, full_name, job_title, role) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([$username, $email, $hashed_password, $full_name, $job_title, $role])) {
                        $success_message = 'User added successfully!';
                        // Clear form data
                        $_POST = array();
                    } else {
                        $error_message = 'Failed to add user. Please try again.';
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error adding user: ' . $e->getMessage();
        }
    }
}

// Get existing job titles for reference
$existing_job_titles = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT DISTINCT job_title FROM users WHERE job_title IS NOT NULL ORDER BY job_title");
    $existing_job_titles = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Ignore errors for this
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .add-user-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .form-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .form-header p {
            color: #666;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .btn-back {
            background: #6c757d;
            margin-right: 10px;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        .existing-titles {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .existing-titles h4 {
            margin-top: 0;
            color: #333;
        }
        .title-tag {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin: 2px;
        }
    </style>
</head>
<body>
    <div class="add-user-container">
        <div class="form-header">
            <h1><i class="fas fa-user-plus"></i> Add New User</h1>
            <p>Create a new user account with proper job title and role</p>
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

        <?php if (!empty($existing_job_titles)): ?>
            <div class="existing-titles">
                <h4><i class="fas fa-info-circle"></i> Existing Job Titles in System:</h4>
                <p>You can use these existing titles or create a new one:</p>
                <?php foreach ($existing_job_titles as $title): ?>
                    <span class="title-tag"><?php echo htmlspecialchars($title); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" class="form-input" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-input" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Job Title *</label>
                    <input type="text" class="form-input" name="job_title" value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>" 
                           placeholder="e.g., Developer, Graphic Designer" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select class="form-input" name="role" required>
                        <option value="member" <?php echo ($_POST['role'] ?? '') == 'member' ? 'selected' : ''; ?>>Member</option>
                        <option value="manager" <?php echo ($_POST['role'] ?? '') == 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="admin" <?php echo ($_POST['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" class="form-input" name="password" required>
                    <small style="color: #666;">Minimum 6 characters</small>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
            </div>
        </form>
    </div>
</body>
</html>
