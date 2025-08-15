<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/TagManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userRole = $_SESSION['role'] ?? 'member';
$canManageTags = in_array($userRole, ['admin', 'manager']);

// Get all tags
$tags = TagManager::getAllTags();
$tagStats = TagManager::getTagStatistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tag Management - Project Management Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .tag-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        .tag-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            color: white;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .tag-preview {
            font-size: 1.1rem;
            padding: 6px 12px;
            border-radius: 15px;
            color: white;
            display: inline-block;
            margin-bottom: 10px;
        }
        .color-picker-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        .color-option {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .color-option:hover,
        .color-option.selected {
            border-color: #007bff;
            border-width: 3px;
        }
        .stats-badge {
            background: #e9ecef;
            color: #495057;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tag Management</h1>
                    <?php if ($canManageTags): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTagModal">
                        <i class="bi bi-plus-circle"></i> Create Tag
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Tags</h5>
                                <h2><?php echo count($tags); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Tags in Use</h5>
                                <h2><?php echo count(array_filter($tagStats, function($tag) { return $tag['task_count'] > 0 || $tag['project_count'] > 0; })); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Tagged Tasks</h5>
                                <h2><?php echo array_sum(array_column($tagStats, 'task_count')); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Tagged Projects</h5>
                                <h2><?php echo array_sum(array_column($tagStats, 'project_count')); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tags List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Tags</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tags)): ?>
                            <p class="text-muted text-center py-4">No tags have been created yet.</p>
                        <?php else: ?>
                            <div id="tagsList">
                                <?php foreach ($tagStats as $tag): ?>
                                <div class="tag-item" data-tag-id="<?php echo $tag['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="tag-badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>">
                                                    <?php echo htmlspecialchars($tag['name']); ?>
                                                </span>
                                                <span class="stats-badge">
                                                    <i class="bi bi-check-square"></i> <?php echo $tag['task_count']; ?> tasks
                                                </span>
                                                <span class="stats-badge">
                                                    <i class="bi bi-folder"></i> <?php echo $tag['project_count']; ?> projects
                                                </span>
                                            </div>
                                            <?php if (!empty($tag['description'])): ?>
                                            <p class="text-muted mb-1"><?php echo htmlspecialchars($tag['description']); ?></p>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                Created: <?php echo date('M j, Y', strtotime($tag['created_at'])); ?>
                                            </small>
                                        </div>
                                        <?php if ($canManageTags): ?>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary edit-tag-btn" 
                                                    data-tag-id="<?php echo $tag['id']; ?>"
                                                    data-tag-name="<?php echo htmlspecialchars($tag['name']); ?>"
                                                    data-tag-color="<?php echo htmlspecialchars($tag['color']); ?>"
                                                    data-tag-description="<?php echo htmlspecialchars($tag['description']); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-tag-btn" 
                                                    data-tag-id="<?php echo $tag['id']; ?>"
                                                    data-tag-name="<?php echo htmlspecialchars($tag['name']); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php if ($canManageTags): ?>
    <!-- Create Tag Modal -->
    <div class="modal fade" id="createTagModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createTagForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tagName" class="form-label">Tag Name *</label>
                            <input type="text" class="form-control" id="tagName" name="name" required maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label for="tagDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="tagDescription" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Color</label>
                            <div class="tag-preview" id="tagPreview" style="background-color: #007bff;">Preview Tag</div>
                            <input type="hidden" id="tagColor" name="color" value="#007bff">
                            <div class="color-picker-container">
                                <div class="color-option selected" data-color="#007bff" style="background-color: #007bff;"></div>
                                <div class="color-option" data-color="#dc3545" style="background-color: #dc3545;"></div>
                                <div class="color-option" data-color="#28a745" style="background-color: #28a745;"></div>
                                <div class="color-option" data-color="#ffc107" style="background-color: #ffc107;"></div>
                                <div class="color-option" data-color="#17a2b8" style="background-color: #17a2b8;"></div>
                                <div class="color-option" data-color="#6f42c1" style="background-color: #6f42c1;"></div>
                                <div class="color-option" data-color="#fd7e14" style="background-color: #fd7e14;"></div>
                                <div class="color-option" data-color="#20c997" style="background-color: #20c997;"></div>
                                <div class="color-option" data-color="#e83e8c" style="background-color: #e83e8c;"></div>
                                <div class="color-option" data-color="#6c757d" style="background-color: #6c757d;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Tag</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Tag Modal -->
    <div class="modal fade" id="editTagModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editTagForm">
                    <input type="hidden" id="editTagId" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editTagName" class="form-label">Tag Name *</label>
                            <input type="text" class="form-control" id="editTagName" name="name" required maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label for="editTagDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editTagDescription" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Color</label>
                            <div class="tag-preview" id="editTagPreview" style="background-color: #007bff;">Preview Tag</div>
                            <input type="hidden" id="editTagColor" name="color" value="#007bff">
                            <div class="color-picker-container" id="editColorPicker">
                                <div class="color-option" data-color="#007bff" style="background-color: #007bff;"></div>
                                <div class="color-option" data-color="#dc3545" style="background-color: #dc3545;"></div>
                                <div class="color-option" data-color="#28a745" style="background-color: #28a745;"></div>
                                <div class="color-option" data-color="#ffc107" style="background-color: #ffc107;"></div>
                                <div class="color-option" data-color="#17a2b8" style="background-color: #17a2b8;"></div>
                                <div class="color-option" data-color="#6f42c1" style="background-color: #6f42c1;"></div>
                                <div class="color-option" data-color="#fd7e14" style="background-color: #fd7e14;"></div>
                                <div class="color-option" data-color="#20c997" style="background-color: #20c997;"></div>
                                <div class="color-option" data-color="#e83e8c" style="background-color: #e83e8c;"></div>
                                <div class="color-option" data-color="#6c757d" style="background-color: #6c757d;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Tag</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        // Tag management functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Color picker functionality
            function setupColorPicker(containerSelector, previewSelector, hiddenInputSelector) {
                const container = document.querySelector(containerSelector);
                const preview = document.querySelector(previewSelector);
                const hiddenInput = document.querySelector(hiddenInputSelector);
                
                container.addEventListener('click', function(e) {
                    if (e.target.classList.contains('color-option')) {
                        // Remove selected class from all options
                        container.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
                        // Add selected class to clicked option
                        e.target.classList.add('selected');
                        // Update preview and hidden input
                        const color = e.target.dataset.color;
                        preview.style.backgroundColor = color;
                        hiddenInput.value = color;
                    }
                });
            }
            
            // Setup color pickers
            setupColorPicker('.color-picker-container', '#tagPreview', '#tagColor');
            setupColorPicker('#editColorPicker', '#editTagPreview', '#editTagColor');
            
            // Update preview text when name changes
            document.getElementById('tagName').addEventListener('input', function() {
                document.getElementById('tagPreview').textContent = this.value || 'Preview Tag';
            });
            
            document.getElementById('editTagName').addEventListener('input', function() {
                document.getElementById('editTagPreview').textContent = this.value || 'Preview Tag';
            });
            
            // Create tag form submission
            document.getElementById('createTagForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    action: 'create',
                    name: formData.get('name'),
                    description: formData.get('description'),
                    color: formData.get('color')
                };
                
                fetch('api/tags.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('Tag created successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while creating the tag.');
                });
            });
            
            // Edit tag buttons
            document.querySelectorAll('.edit-tag-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tagId = this.dataset.tagId;
                    const tagName = this.dataset.tagName;
                    const tagColor = this.dataset.tagColor;
                    const tagDescription = this.dataset.tagDescription;
                    
                    document.getElementById('editTagId').value = tagId;
                    document.getElementById('editTagName').value = tagName;
                    document.getElementById('editTagDescription').value = tagDescription;
                    document.getElementById('editTagColor').value = tagColor;
                    document.getElementById('editTagPreview').style.backgroundColor = tagColor;
                    document.getElementById('editTagPreview').textContent = tagName;
                    
                    // Update color picker selection
                    document.querySelectorAll('#editColorPicker .color-option').forEach(opt => {
                        opt.classList.toggle('selected', opt.dataset.color === tagColor);
                    });
                    
                    new bootstrap.Modal(document.getElementById('editTagModal')).show();
                });
            });
            
            // Edit tag form submission
            document.getElementById('editTagForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    id: formData.get('id'),
                    name: formData.get('name'),
                    description: formData.get('description'),
                    color: formData.get('color')
                };
                
                fetch('api/tags.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('Tag updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the tag.');
                });
            });
            
            // Delete tag buttons
            document.querySelectorAll('.delete-tag-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tagId = this.dataset.tagId;
                    const tagName = this.dataset.tagName;
                    
                    if (confirm(`Are you sure you want to delete the tag "${tagName}"? This will also remove it from all tasks and projects.`)) {
                        fetch(`api/tags.php?action=delete&id=${tagId}`, {
                            method: 'DELETE'
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                alert('Tag deleted successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + result.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the tag.');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
