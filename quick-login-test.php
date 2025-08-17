<?php
session_start();
require_once 'config/database.php';

echo "<h2>Quick Login Test</h2>";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ Already logged in as: " . $_SESSION['username'] . "</p>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Role: " . $_SESSION['role'] . "</p>";
    echo '<a href="background-upload-simple.php">Go to Upload Page</a><br>';
    echo '<a href="test-upload-debug.php">Go to Upload Debug</a><br>';
    echo '<a href="logout.php">Logout</a>';
    exit();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            echo "<p>✅ Login successful!</p>";
            echo "<p>Welcome: " . $user['full_name'] . "</p>";
            echo '<a href="background-upload-simple.php">Go to Upload Page</a><br>';
            echo '<a href="test-upload-debug.php">Go to Upload Debug</a>';
        } else {
            echo "<p>❌ Invalid username or password</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Login error: " . $e->getMessage() . "</p>";
    }
}

// Show login form
echo '<form method="post">';
echo '<p>Username: <input type="text" name="username" value="testuser" required></p>';
echo '<p>Password: <input type="password" name="password" value="testpass123" required></p>';
echo '<input type="submit" value="Login">';
echo '</form>';
?>
