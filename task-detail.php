<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/MultimediaManager.php';
require_once 'includes/FileViewer.php';

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

$task_id = $_GET['id'] ?? null;
if (!$task_id) {
    header('Location: task-management.php');
    exit();
}

try {
    $pdo = getDBConnection();
    $multimediaManager = new MultimediaManager($pdo);
    $fileViewer = new FileViewer($multimediaManager);
    
    // Get task details
    $task = getTaskById($task_id);
    if (!$task) {
        header('Location: task-management.php');
        exit();
    }
    
    // Check if user can view this task
    $can_view = false;
    if (in_array($current_user['role'], ['admin', 'manager'])) {
        $can_view = true;
    } else {
        $can_view = ($task['assigned_to'] == $current_user['id']);
    }
    
    if (!$can_view) {
        header('Location: task-management.php');
        exit();
    }
    
    // Get multimedia files for this task
    $files = $multimediaManager->getFilesByEntity('task', $task_id);
    
    // Format files for display
    $formattedFiles = [];
    foreach ($files as $file) {
        $formattedFiles[] = [
            'id' => $file['id'],
            'filename' => $file['filename'],
            'original_filename' => $file['original_filename'],
            'file_path' => $file['file_path'],
            'file_type' => $file['file_type'],
            'file_size' => $file['file_size'],
            'formatted_size' => $multimediaManager->formatFileSize($file['file_size']),
            'mime_type' => $file['mime_type'],
            'description' => $file['description'],
            'uploaded_by' => $file['uploaded_by'],
            'uploaded_by_name' => $file['uploaded_by_name'],
            'created_at' => $file['created_at'],
            'icon' => $multimediaManager->getFileIcon($file['file_type']),
            'is_image' => $multimediaManager->isImage($file['file_type']),
            'is_video' => $multimediaManager->isVideo($file['file_type']),
            'is_document' => $multimediaManager->isDocument($file['file_type']),
            'thumbnail_path' => $file['file_type'] === 'images' ? 
                'uploads/thumbnails/thumb_' . $file['filename'] : null
        ];
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading task: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Details - <?php echo htmlspecialchars($task['title']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* UI polish for Task Details */
        .content-container { max-width: 1200px; margin: 0 auto; }
        .task-detail-container { background: #fff; border-radius: 14px; box-shadow: 0 8px 24px rgba(0,0,0,0.06); overflow: hidden; }
        .task-header { display: flex; align-items: center; justify-content: space-between; padding: 26px 28px; background: linear-gradient(135deg, #6a82fb 0%, #a777e3 100%); color: #fff; }
        .task-header h1 { margin: 0; font-size: 1.6rem; font-weight: 700; letter-spacing: .2px; }
        .task-status .status-badge { background: rgba(255,255,255,.18); color: #fff; border: 1px solid rgba(255,255,255,.3); padding: 6px 12px; border-radius: 20px; font-weight: 600; }
        .task-content { padding: 22px 24px; }
        .summary-chips { display: flex; gap: 10px; flex-wrap: wrap; margin: 8px 0 18px; }
        .chip { display: inline-flex; align-items: center; gap: 8px; background: #f5f7fb; border: 1px solid #e6e9f2; color: #374151; padding: 8px 12px; border-radius: 12px; font-size: .92rem; }
        .chip i { color: #6a82fb; }
        .task-info-grid { display: grid; grid-template-columns: 1.6fr .8fr; gap: 24px; }
        .task-description { background: #f9fafc; border: 1px solid #eef2f7; border-radius: 12px; padding: 16px; }
        .task-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .meta-item { background: #fff; border: 1px solid #eef2f7; border-radius: 10px; padding: 12px 14px; }
        .priority-badge { padding: 6px 10px; border-radius: 16px; font-weight: 700; font-size: .85rem; }
        .priority-high { background: #fee2e2; color: #991b1b; }
        .priority-medium { background: #ffedd5; color: #9a3412; }
        .priority-low { background: #dcfce7; color: #14532d; }
        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .action-buttons .btn { border-radius: 10px; }
        .multimedia-section { margin-top: 6px; }
        .multimedia-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 0 6px; }
        .gallery-section h4 { margin: 14px 0 10px; }
        /* Discussion */
        #discussion .form-input, #discussion textarea { width: 100%; border: 1px solid #e6e9f2; border-radius: 10px; padding: 10px 12px; }
        #comments-list .info-item { background: #fafbff; border: 1px solid #eef2f7; border-radius: 10px; }
        .sidebar { box-shadow: inset -1px 0 0 #eef2f7; }
        .top-bar { border-bottom: 1px solid #eef2f7; }
        @media (max-width: 992px) { .task-info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-tasks"></i> PM Tool</h2>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="task-management.php"><i class="fas fa-list-check"></i> Task Management</a>
                </li>
                <li class="nav-item active">
                    <a href="#"><i class="fas fa-eye"></i> Task Details</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <a href="task-management.php"><i class="fas fa-arrow-left"></i> Back to Tasks</a>
                    <span> / Task Details</span>
                </div>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?> (<?php echo ucfirst($current_user['role']); ?>)</span>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </header>

            <div class="content-container">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <!-- Task Details -->
                <div class="task-detail-container">
                    <div class="task-header">
                        <h1><?php echo htmlspecialchars($task['title']); ?></h1>
                        <div class="task-status">
                            <span class="status-badge status-<?php echo $task['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                            </span>
                        </div>
                    </div>

                    <div class="task-content">
                        <div class="summary-chips">
                            <span class="chip"><i class="fas fa-diagram-project"></i> <?php echo htmlspecialchars($task['project_name'] ?: 'No Project'); ?></span>
                            <span class="chip"><i class="fas fa-user"></i> <?php echo htmlspecialchars($task['assignee_name'] ?: 'Unassigned'); ?></span>
                            <span class="chip"><i class="fas fa-flag"></i> <span class="priority-badge priority-<?php echo $task['priority']; ?>"><?php echo ucfirst($task['priority']); ?></span></span>
                            <span class="chip"><i class="fas fa-calendar-day"></i> <?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No Due Date'; ?></span>
                            <span class="chip"><i class="fas fa-clock"></i> <?php echo $task['estimated_hours'] ? ($task['estimated_hours'].' hrs') : 'No Estimate'; ?></span>
                        </div>
                        <div class="task-info-grid">
                            <div class="task-main">
                                <div class="task-description">
                                    <h3>Description</h3>
                                    <p><?php echo nl2br(htmlspecialchars($task['description'] ?: 'No description provided')); ?></p>
                                </div>

                                <div class="task-meta">
                                    <div class="meta-item">
                                        <strong>Project:</strong> 
                                        <span><?php echo htmlspecialchars($task['project_name'] ?: 'No project assigned'); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Assigned to:</strong> 
                                        <span><?php echo htmlspecialchars($task['assignee_name'] ?: 'Unassigned'); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Priority:</strong> 
                                        <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Due Date:</strong> 
                                        <span><?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'Not set'; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Estimated Hours:</strong> 
                                        <span><?php echo $task['estimated_hours'] ?: 'Not set'; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Created:</strong> 
                                        <span><?php echo date('M j, Y g:i A', strtotime($task['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="task-actions">
                                <h3>Actions</h3>
                                <div class="action-buttons">
                                    <?php if ($task['status'] !== 'completed'): ?>
                                        <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                                            <form method="POST" action="task-management.php" style="display: inline;">
                                                <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-check"></i> Mark Complete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="btn btn-secondary" style="cursor: not-allowed; opacity: 0.6;" title="Only managers and admins can mark tasks as completed">
                                                <i class="fas fa-lock"></i> Complete (Restricted)
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($task['status'] === 'pending'): ?>
                                        <form method="POST" action="task-management.php" style="display: inline;">
                                            <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                                            <input type="hidden" name="action" value="start">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-play"></i> Start Task
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($current_user['role'], ['admin', 'manager'])): ?>
                                        <a href="edit-task.php?id=<?php echo $task_id; ?>" class="btn btn-secondary">
                                            <i class="fas fa-edit"></i> Edit Task
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Multimedia Section -->
                        <div class="multimedia-section">
                            <div class="multimedia-header">
                                <div class="multimedia-title">
                                    <i class="fas fa-images"></i>
                                    Files & Media
                                    <span class="multimedia-count"><?php echo count($formattedFiles); ?></span>
                                </div>
                                <button class="multimedia-toggle" onclick="toggleMultimedia()">
                                    <i class="fas fa-chevron-down"></i> Show/Hide
                                </button>
                            </div>
                            
                            <div class="multimedia-content" id="multimedia-content">
                                <style>
                                    .tab-nav { display:flex; gap:8px; margin:8px 0 12px; }
                                    .tab-btn { padding:8px 12px; border:1px solid #e6e9f2; background:#fff; border-radius:10px; cursor:pointer; }
                                    .tab-btn.active { background:#6a82fb; color:#fff; border-color:#6a82fb; }
                                    .tab-pane { display:none; }
                                    .tab-pane.active { display:block; }
                                </style>
                                <div class="tab-nav" id="detail-tabs">
                                    <button class="tab-btn active" data-tab="files">Files & Media</button>
                                    <button class="tab-btn" data-tab="discussion">Discussion</button>
                                </div>
                                <div id="tab-files" class="tab-pane active">
                                    <!-- Upload Form -->
                                    <div class="upload-section">
                                        <h4>Upload Files</h4>
                                        <?php echo $fileViewer->renderUploadForm('task', $task_id); ?>
                                    </div>
                                    <!-- File Gallery -->
                                    <div class="gallery-section">
                                        <h4>Uploaded Files</h4>
                                        <?php echo $fileViewer->renderFileGallery($formattedFiles, [
                                            'show_delete' => true,
                                            'current_user_id' => $current_user['id'],
                                            'entity_type' => 'task',
                                            'entity_id' => $task_id
                                        ]); ?>
                                    </div>
                                </div>

                                <!-- Discussion -->
                                <div id="tab-discussion" class="tab-pane">
                                <div class="gallery-section" id="discussion">
                                    <h4>Discussion</h4>
                                    <style>
                                        .comment-list { display:flex; flex-direction:column; gap:10px; }
                                        .comment-item { background:#f8fafc; border:1px solid #e6edf5; border-radius:12px; padding:10px 12px; }
                                        .comment-header { display:flex; justify-content:space-between; align-items:center; font-size:.9rem; color:#475569; }
                                        .comment-author { font-weight:600; color:#1f2937; }
                                        .comment-body { margin-top:6px; font-size:.98rem; color:#334155; white-space:pre-wrap; }
                                        #comment-input { background:#fff; border:1px solid #e6edf5; border-radius:12px; padding:12px 14px; }
                                        #mention-list .mention-item { padding:8px 10px; }
                                        #mention-list .mention-item:hover { background:#eef2ff; }
                                    </style>
                                    <div id="comments-list" class="comment-list"></div>
                                    <form id="comment-form" style="margin-top: 12px; position: relative;">
                                        <input type="hidden" name="task_id" value="<?php echo (int)$task_id; ?>">
                                        <div class="mention-container" style="position: relative;">
                                            <textarea id="comment-input" name="content" class="form-input" rows="3" placeholder="Write a message... Use @ to mention" required></textarea>
                                            <div id="mention-list" style="position:absolute; left:8px; right:auto; bottom:48px; display:none; z-index:1000; background:#fff; border:1px solid #e6e9f2; border-radius:8px; max-height:220px; overflow:auto; min-width:240px; box-shadow:0 8px 20px rgba(0,0,0,0.08)"></div>
                                        </div>
                                        <div style="margin-top: 10px; text-align: right;">
                                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                                        </div>
                                    </form>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- File Viewer Modal -->
    <?php echo $fileViewer->renderFileViewerModal(); ?>

    <!-- Upload JavaScript -->
    <?php echo $fileViewer->getUploadJavaScript('task', $task_id); ?>

    <script>
        function toggleMultimedia() {
            const content = document.getElementById('multimedia-content');
            const toggle = document.querySelector('.multimedia-toggle i');
            
            if (content.classList.contains('show')) {
                content.classList.remove('show');
                toggle.className = 'fas fa-chevron-down';
            } else {
                content.classList.add('show');
                toggle.className = 'fas fa-chevron-up';
            }
        }

        // Show multimedia section by default
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('multimedia-content').classList.add('show');
            // Tabs
            const tabs = document.getElementById('detail-tabs');
            if (tabs){
                tabs.addEventListener('click', function(e){
                    const btn = e.target.closest('.tab-btn');
                    if (!btn) return;
                    tabs.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
                    btn.classList.add('active');
                    const name = btn.dataset.tab;
                    document.getElementById('tab-files').classList.toggle('active', name==='files');
                    document.getElementById('tab-discussion').classList.toggle('active', name==='discussion');
                });
            }
        });
        
        // Discussion JS
        document.addEventListener('DOMContentLoaded', function () {
            const taskId = <?php echo (int)$task_id; ?>;
            const listEl = document.getElementById('comments-list');
            const formEl = document.getElementById('comment-form');
            if (!listEl || !formEl) return;

            function esc(html){ const div=document.createElement('div'); div.textContent=html; return div.innerHTML; }
            function renderComment(c){
                const who = esc(c.full_name || c.username || ('User #' + c.user_id));
                const when = new Date(c.created_at.replace(' ', 'T'));
                const item = document.createElement('div');
                item.className = 'info-item';
                item.innerHTML = `<div class="info-label">${who} <span style=\"font-weight: normal; color:#888;\">${when.toLocaleString()}</span></div>
                                  <div class=\"info-value\">${esc(c.content)}</div>`;
                return item;
            }
            async function loadComments(){
                listEl.innerHTML = '';
                try{
                    const res = await fetch(`api/comments.php?task_id=${taskId}`);
                    const data = await res.json();
                    if(!data.success){ listEl.innerHTML = `<div class='info-item'><div class='info-value'>${esc(data.message||'Failed to load comments')}</div></div>`; return; }
                    if(!data.comments || data.comments.length===0){ listEl.innerHTML = `<div class='info-item'><div class='info-value'>No messages yet.</div></div>`; return; }
                    data.comments.forEach(c=> listEl.appendChild(renderComment(c)));
                }catch(e){ listEl.innerHTML = `<div class='info-item'><div class='info-value'>Network error loading comments</div></div>`; }
            }
            formEl.addEventListener('submit', async function(e){
                e.preventDefault();
                const fd = new FormData(formEl);
                const btn = formEl.querySelector('button[type=\"submit\"]');
                btn.disabled = true;
                try{
                    const res = await fetch('api/comments.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    if(data.success && data.comment){
                        formEl.reset();
                        listEl.appendChild(renderComment(data.comment));
                    } else {
                        alert(data.message || 'Failed to post message');
                    }
                }catch(err){ alert('Network error posting message'); }
                finally{ btn.disabled = false; }
            });
            loadComments();
            
            // Mentions (@)
            const inputEl = document.getElementById('comment-input');
            const listBox = document.getElementById('mention-list');
            let members = [];
            let mentionActive = false;
            let mentionStart = -1;

            async function loadMembers(){
                try{
                    const res = await fetch('api/team.php');
                    const data = await res.json();
                    members = (data.members || []).map(m => ({ id:m.id, name: m.full_name || m.username || ('User #'+m.id) }));
                }catch(e){ members = []; }
            }
            function filterMembers(query){
                query = query.toLowerCase();
                return members.filter(m => m.name.toLowerCase().includes(query)).slice(0,8);
            }
            function renderMentionList(items){
                if (!items.length){ listBox.style.display='none'; listBox.innerHTML=''; return; }
                listBox.innerHTML = items.map(i=>`<div class=\"mention-item\" data-id=\"${i.id}\" data-name=\"${i.name.replace(/\"/g,'&quot;')}\" style=\"padding:8px 10px; cursor:pointer;\">@${i.name}</div>`).join('');
                listBox.style.display = 'block';
            }
            function insertMention(name){
                const text = inputEl.value;
                const before = text.substring(0, mentionStart);
                const after = text.substring(inputEl.selectionStart);
                const insert = '@' + name + ' ';
                inputEl.value = before + insert + after;
                const pos = (before + insert).length;
                inputEl.setSelectionRange(pos, pos);
                mentionActive = false; mentionStart = -1; listBox.style.display='none';
                inputEl.focus();
            }
            inputEl.addEventListener('input', function(){
                const pos = inputEl.selectionStart;
                const text = inputEl.value;
                if (!mentionActive){
                    if (text[pos-1] === '@') { mentionActive = true; mentionStart = pos-1; renderMentionList(filterMembers('')); }
                    return;
                }
                if (pos <= mentionStart){ mentionActive=false; listBox.style.display='none'; return; }
                const fragment = text.substring(mentionStart+1, pos);
                if (fragment.match(/\s/)){ mentionActive=false; listBox.style.display='none'; return; }
                renderMentionList(filterMembers(fragment));
            });
            listBox.addEventListener('click', function(e){
                const item = e.target.closest('.mention-item');
                if (!item) return;
                insertMention(item.dataset.name);
            });
            inputEl.addEventListener('keydown', function(e){ if (mentionActive && e.key==='Escape'){ mentionActive=false; listBox.style.display='none'; } });
            loadMembers();
        });
    </script>
</body>
</html>
