<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information
$current_user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'],
    'role' => $_SESSION['role']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-tasks"></i> PM Tool</h2>
            </div>
            <ul class="nav-menu">
                <li class="nav-item active" data-section="dashboard">
                    <a href="#dashboard"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item" data-section="projects">
                    <a href="#projects"><i class="fas fa-project-diagram"></i> Projects</a>
                </li>
                <li class="nav-item" data-section="tasks">
                    <a href="#tasks"><i class="fas fa-list-check"></i> Tasks</a>
                </li>
                <li class="nav-item" data-section="team">
                    <a href="#team"><i class="fas fa-users"></i> Team</a>
                </li>
                <li class="nav-item" data-section="reports">
                    <a href="#reports"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span id="current-section">Dashboard</span>
                </div>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?> (<?php echo ucfirst($current_user['role']); ?>)</span>
                    <button class="btn btn-secondary" id="logout-btn">Logout</button>
                </div>
            </header>

            <!-- Dynamic Content Container -->
            <div class="content-container" id="content-container">
                <!-- Dashboard Section -->
                <div id="dashboard" class="content-section active">
                    <div class="dashboard-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="total-projects">0</h3>
                                <p>Total Projects</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-list-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="total-tasks">0</h3>
                                <p>Total Tasks</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="total-team">0</h3>
                                <p>Team Members</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="pending-tasks">0</h3>
                                <p>Pending Tasks</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-charts">
                        <div class="chart-container">
                            <h3>Project Progress</h3>
                            <div id="project-progress-chart"></div>
                        </div>
                        <div class="chart-container">
                            <h3>Recent Activities</h3>
                            <div id="recent-activities"></div>
                        </div>
                    </div>
                </div>

                <!-- Projects Section -->
                <div id="projects" class="content-section">
                    <div class="section-header">
                        <h2>Project Management</h2>
                        <?php if ($current_user['role'] === 'admin'): ?>
                            <button class="btn btn-primary" id="add-project-btn">
                                <i class="fas fa-plus"></i> New Project
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="projects-grid" id="projects-grid">
                        <!-- Projects will be loaded dynamically -->
                    </div>
                </div>

                <!-- Tasks Section -->
                <div id="tasks" class="content-section">
                    <div class="section-header">
                        <h2>Task Management</h2>
                        <div class="header-actions">
                            <div class="task-filters">
                                <button class="btn btn-outline active" data-filter="all">All Tasks</button>
                                <button class="btn btn-outline" data-filter="pending">Pending</button>
                                <button class="btn btn-outline" data-filter="completed">Completed</button>
                            </div>
                            <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                                <button class="btn btn-primary" id="add-task-btn">
                                    <i class="fas fa-plus"></i> New Task
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tasks-container" id="tasks-container">
                        <!-- Tasks will be loaded dynamically -->
                    </div>
                </div>

                <!-- Team Section -->
                <div id="team" class="content-section">
                    <div class="section-header">
                        <h2>Team Management</h2>
                        <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                            <button class="btn btn-primary" id="add-member-btn">
                                <i class="fas fa-plus"></i> Add Member
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="team-grid" id="team-grid">
                        <!-- Team members will be loaded dynamically -->
                    </div>
                </div>

                <!-- Reports Section -->
                <div id="reports" class="content-section">
                    <div class="section-header">
                        <h2>Reports & Analytics</h2>
                    </div>
                    <div class="reports-container">
                        <div class="report-card">
                            <h3>Project Timeline</h3>
                            <div id="timeline-chart"></div>
                        </div>
                        <div class="report-card">
                            <h3>Task Distribution</h3>
                            <div id="task-distribution-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="modal-overlay" class="modal-overlay">
        <div class="modal" id="modal">
            <div class="modal-header">
                <h3 id="modal-title">Modal Title</h3>
                <button class="modal-close" id="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Modal content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/modals.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
