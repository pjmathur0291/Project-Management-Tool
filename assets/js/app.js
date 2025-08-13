// Main Application JavaScript
class ProjectManagementApp {
    constructor() {
        this.currentSection = 'dashboard';
        this.currentTaskFilter = 'all'; // Store current filter state
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadDashboardStats();
        this.loadProjects();
        this.loadTasks();
        this.loadTeamMembers();
    }

    bindEvents() {
        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const section = item.dataset.section;
                this.navigateToSection(section);
            });
        });

        // Add buttons (guards in case button is hidden for role restrictions)
        const addProjectBtn = document.getElementById('add-project-btn');
        if (addProjectBtn) {
            addProjectBtn.addEventListener('click', () => {
                this.showAddProjectModal();
            });
        }

        const addTaskBtn = document.getElementById('add-task-btn');
        if (addTaskBtn) {
            addTaskBtn.addEventListener('click', () => {
                this.showAddTaskModal();
            });
        }

        const addMemberBtn = document.getElementById('add-member-btn');
        if (addMemberBtn) {
            addMemberBtn.addEventListener('click', () => {
                this.showAddMemberModal();
            });
        }

        // Logout
        document.getElementById('logout-btn')?.addEventListener('click', () => {
            this.logout();
        });

        // Task filters
        document.querySelectorAll('.task-filters button').forEach(button => {
            button.addEventListener('click', (e) => {
                const filter = e.target.getAttribute('data-filter');
                this.currentTaskFilter = filter; // Store current filter
                this.filterTasks(filter);
                
                // Update active button
                document.querySelectorAll('.task-filters button').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
            });
        });

        // Search functionality
        this.initializeSearch();
    }

    navigateToSection(section) {
        // Update navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-section="${section}"]`).classList.add('active');

        // Update content
        document.querySelectorAll('.content-section').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(section).classList.add('active');

        // Update breadcrumb
        document.getElementById('current-section').textContent = 
            section.charAt(0).toUpperCase() + section.slice(1);

        this.currentSection = section;

        // Load section-specific data
        this.loadSectionData(section);
    }

    loadSectionData(section) {
        switch (section) {
            case 'dashboard':
                this.loadDashboardStats();
                break;
            case 'projects':
                this.loadProjects();
                break;
            case 'tasks':
                this.loadTasks();
                break;
            case 'team':
                this.loadTeamMembers();
                break;
            case 'reports':
                this.loadReports();
                break;
        }
    }

    async loadDashboardStats() {
        try {
            const response = await fetch('api/dashboard.php');
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('total-projects').textContent = data.stats.total_projects;
                document.getElementById('total-tasks').textContent = data.stats.total_tasks;
                document.getElementById('total-team').textContent = data.stats.total_team;
                document.getElementById('pending-tasks').textContent = data.stats.pending_tasks;
                
                this.renderProjectProgressChart(data.stats.project_progress);
                this.renderRecentActivities(data.stats.recent_activities);
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }

    async loadProjects() {
        try {
            const response = await fetch('api/projects.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderProjects(data.projects);
            }
        } catch (error) {
            console.error('Error loading projects:', error);
        }
    }

    async loadTasks() {
        try {
            console.log('Loading tasks...');
            // Ask backend to do filtering when possible
            const filterParam = this.currentTaskFilter && this.currentTaskFilter !== 'all' ? `?filter=${this.currentTaskFilter}` : '';
            const response = await fetch(`api/tasks.php${filterParam}`);
            const data = await response.json();
            
            if (data.success) {
                console.log(`Loaded ${data.tasks.length} tasks`);
                this.renderTasks(data.tasks);
                
                // Re-apply current filter after loading tasks
                if (this.currentTaskFilter && this.currentTaskFilter !== 'all') {
                    console.log(`Re-applying filter: ${this.currentTaskFilter}`);
                    this.filterTasks(this.currentTaskFilter);
                }
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
        }
    }

    async loadTeamMembers() {
        try {
            const response = await fetch('api/team.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderTeamMembers(data.members);
            }
        } catch (error) {
            console.error('Error loading team members:', error);
        }
    }

    async loadReports() {
        try {
            const response = await fetch('api/reports.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderTimelineChart(data.timeline);
                this.renderTaskDistributionChart(data.task_distribution);
            }
        } catch (error) {
            console.error('Error loading reports:', error);
        }
    }

    renderProjects(projects) {
        const container = document.getElementById('projects-grid');
        if (!container) return;

        if (projects.length === 0) {
            container.innerHTML = '<div class="no-data">No projects found. Create your first project!</div>';
            return;
        }

        container.innerHTML = projects.map(project => `
            <div class="project-card" data-project-id="${project.id}">
                <div class="project-header">
                    <div>
                        <h3 class="project-title">${project.name}</h3>
                        <p>${project.description || 'No description'}</p>
                    </div>
                    <span class="project-status status-${project.status}">${this.formatStatus(project.status)}</span>
                </div>
                
                <div class="project-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${project.progress}%"></div>
                    </div>
                    <small>${project.progress}% Complete</small>
                </div>
                
                <div class="project-meta">
                    <div>
                        <small><strong>Manager:</strong> ${project.manager_name || 'Unassigned'}</small><br>
                        <small><strong>Priority:</strong> ${this.formatPriority(project.priority)}</small><br>
                        <small><strong>Due:</strong> ${project.end_date ? this.formatDate(project.end_date) : '<span class="no-deadline">No deadline</span>'}</small>
                    </div>
                    <div class="project-actions">
                        <button class="btn btn-secondary btn-sm" onclick="app.editProject(${project.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="app.deleteProject(${project.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    renderTasks(tasks) {
        const container = document.getElementById('tasks-container');
        if (!container) return;

        if (tasks.length === 0) {
            container.innerHTML = '<div class="no-data">No tasks found. Create your first task!</div>';
            return;
        }

        container.innerHTML = tasks.map(task => {
            // Get current user ID from PHP session (passed via data attribute)
            const currentUserId = document.body.getAttribute('data-user-id');
            const isAssignedToMe = task.assigned_to == currentUserId;
            const assignedClass = isAssignedToMe ? 'task-assigned-to-me' : '';
            
            return `
            <div class="task-item ${task.status === 'completed' ? 'task-completed' : ''} ${assignedClass}" data-task-id="${task.id}" data-task-status="${task.status}">
                <input type="checkbox" class="task-checkbox" data-task-id="${task.id}"
                       ${task.status === 'completed' ? 'checked' : ''}>
                
                <div class="task-content">
                    <div class="task-title ${task.status === 'completed' ? 'completed-text' : ''}">${task.title}</div>
                    <div class="task-description ${task.status === 'completed' ? 'completed-text' : ''}">${task.description || 'No description'}</div>
                    <small><strong>Project:</strong> ${task.project_name || 'No project'}</small>
                </div>
                
                <span class="task-priority priority-${task.priority}">${this.formatPriority(task.priority)}</span>
                
                <div class="task-meta">
                    <small><strong>Assigned to:</strong> ${task.assignee_name || 'Unassigned'}</small><br>
                    <small><strong>Due:</strong> ${this.formatDate(task.due_date)}</small>
                    ${task.status === 'completed' ? '<br><small class="completed-badge">✅ Completed</small>' : ''}
                </div>
                
                <div class="task-actions">
                    <button class="btn btn-secondary btn-sm" onclick="app.editTask(${task.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="app.deleteTask(${task.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        }).join('');
        
        // Bind checkbox events after rendering
        this.bindTaskCheckboxEvents();
    }

    bindTaskCheckboxEvents() {
        console.log('Binding task checkbox events...');
        const checkboxes = document.querySelectorAll('.task-checkbox');
        console.log(`Found ${checkboxes.length} checkboxes to bind`);
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const taskId = e.target.getAttribute('data-task-id');
                const isChecked = e.target.checked;
                console.log(`Checkbox changed for task ${taskId}, checked: ${isChecked}`);
                
                // If marking as complete, show confirmation dialog
                if (isChecked) {
                    // Uncheck the checkbox temporarily
                    e.target.checked = false;
                    
                    // Try to show confirmation dialog, fallback to direct completion if Modal is not available
                    if (typeof Modal !== 'undefined' && Modal.confirm) {
                        Modal.confirm(
                            'Mark Task as Complete?',
                            () => {
                                // User confirmed - mark as complete
                                console.log(`User confirmed marking task ${taskId} as complete`);
                                e.target.checked = true;
                                this.updateTaskStatus(taskId, true);
                            },
                            () => {
                                // User cancelled - keep unchecked
                                console.log(`User cancelled marking task ${taskId} as complete`);
                                e.target.checked = false;
                            }
                        );
                    } else {
                        // Fallback: direct completion without confirmation
                        console.log(`Modal not available, marking task ${taskId} as complete directly`);
                        e.target.checked = true;
                        this.updateTaskStatus(taskId, true);
                    }
                } else {
                    // Marking as incomplete - no confirmation needed
                    this.updateTaskStatus(taskId, false);
                }
            });
        });
    }

    renderTeamMembers(members) {
        const container = document.getElementById('team-grid');
        if (!container) return;

        if (members.length === 0) {
            container.innerHTML = '<div class="no-data">No team members found. Add your first member!</div>';
            return;
        }

        container.innerHTML = members.map(member => `
            <div class="member-card" data-member-id="${member.id}">
                <div class="member-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3 class="member-name">${member.full_name}</h3>
                <p class="member-role">${member.job_title ? member.job_title : member.role}</p>
                <p class="member-email">${member.email}</p>
                
                <div class="member-stats">
                    <div class="stat-item">
                        <div class="number">${member.projects_count || 0}</div>
                        <div class="label">Projects</div>
                    </div>
                    <div class="stat-item">
                        <div class="number">${member.tasks_count || 0}</div>
                        <div class="label">Tasks</div>
                    </div>
                </div>
                
                <div class="member-actions">
                    <button class="btn btn-secondary btn-sm" onclick="app.editMember(${member.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="app.deleteMember(${member.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `).join('');
    }

    renderProjectProgressChart(data) {
        const container = document.getElementById('project-progress-chart');
        if (!container) return;

        // Simple chart using CSS and HTML
        container.innerHTML = `
            <div class="chart-simple">
                <div class="chart-item">
                    <span class="chart-label">Active Projects</span>
                    <span class="chart-value">${data.active || 0}</span>
                </div>
                <div class="chart-item">
                    <span class="chart-label">Completed Projects</span>
                    <span class="chart-value">${data.completed || 0}</span>
                </div>
                <div class="chart-item">
                    <span class="chart-label">Pending Projects</span>
                    <span class="chart-value">${data.pending || 0}</span>
                </div>
            </div>
        `;
    }

    renderRecentActivities(activities) {
        const container = document.getElementById('recent-activities');
        if (!container) return;

        if (activities.length === 0) {
            container.innerHTML = '<p>No recent activities</p>';
            return;
        }

        container.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-${this.getActivityIcon(activity.action)}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">${activity.action}</div>
                    <div class="activity-meta">
                        by ${activity.user_name} • ${this.formatDate(activity.created_at, 'M j, g:i a')}
                    </div>
                </div>
            </div>
        `).join('');
    }

    renderTimelineChart(data) {
        const container = document.getElementById('timeline-chart');
        if (!container) return;

        container.innerHTML = `
            <div class="timeline-chart">
                <div class="timeline-item">
                    <div class="timeline-date">Q1 2024</div>
                    <div class="timeline-content">
                        <div class="timeline-project">Website Redesign</div>
                        <div class="timeline-progress">65% Complete</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-date">Q2 2024</div>
                    <div class="timeline-content">
                        <div class="timeline-project">Mobile App Development</div>
                        <div class="timeline-progress">0% Complete</div>
                    </div>
                </div>
            </div>
        `;
    }

    renderTaskDistributionChart(data) {
        const container = document.getElementById('task-distribution-chart');
        if (!container) return;

        container.innerHTML = `
            <div class="distribution-chart">
                <div class="chart-item">
                    <span class="chart-label">High Priority</span>
                    <span class="chart-value">${data.high || 0}</span>
                </div>
                <div class="chart-item">
                    <span class="chart-label">Medium Priority</span>
                    <span class="chart-value">${data.medium || 0}</span>
                </div>
                <div class="chart-item">
                    <span class="chart-label">Low Priority</span>
                    <span class="chart-value">${data.low || 0}</span>
                </div>
            </div>
        `;
    }

    getActivityIcon(action) {
        const icons = {
            'created': 'plus',
            'updated': 'edit',
            'deleted': 'trash',
            'assigned': 'user-plus',
            'completed': 'check',
            'commented': 'comment'
        };
        return icons[action.toLowerCase()] || 'info';
    }

    formatStatus(status) {
        const statuses = {
            'pending': 'Pending',
            'active': 'Active',
            'completed': 'Completed',
            'on_hold': 'On Hold',
            'in_progress': 'In Progress'
        };
        return statuses[status] || status;
    }

    formatPriority(priority) {
        const priorities = {
            'low': 'Low',
            'medium': 'Medium',
            'high': 'High'
        };
        return priorities[priority] || priority;
    }

    formatDate(date, format = 'M j, Y') {
        if (!date || date === '0000-00-00' || date === '') return 'Not set';
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    async showAddProjectModal() {
        console.log('showAddProjectModal called'); // Debug log
        
        const modal = new Modal('Add New Project', this.getProjectFormHTML());
        modal.show();
        
        // Populate manager dropdown
        await this.populateProjectFormDropdowns();
        
        // Wait a moment for the modal to be fully rendered
        setTimeout(() => {
            const form = document.getElementById('project-form');
            if (form) {
                console.log('Project form found, binding submit event'); // Debug log
                
                // Bind form submission
                form.addEventListener('submit', (e) => {
                    console.log('Form submit event triggered'); // Debug log
                    e.preventDefault();
                    this.submitProjectForm();
                });
                
                // Bind checkbox functionality for end date
                const hasEndDateCheckbox = document.getElementById('has-end-date');
                const endDateInput = document.getElementById('end-date-input');
                
                if (hasEndDateCheckbox && endDateInput) {
                    console.log('End date checkbox and input found'); // Debug log
                    hasEndDateCheckbox.addEventListener('change', function() {
                        endDateInput.disabled = !this.checked;
                        if (this.checked) {
                            endDateInput.required = true;
                            endDateInput.style.opacity = '1';
                        } else {
                            endDateInput.required = false;
                            endDateInput.value = '';
                            endDateInput.style.opacity = '0.6';
                        }
                    });
                } else {
                    console.log('End date elements not found:', { hasEndDateCheckbox, endDateInput }); // Debug log
                }
            } else {
                console.error('Project form not found after modal creation!'); // Debug log
            }
        }, 100);
    }

    async showAddTaskModal() {
        const modal = new Modal('Add New Task', this.getTaskFormHTML());
        modal.show();
        
        // Populate projects dropdown
        await this.populateTaskFormDropdowns();
        
        // Wait a moment for the modal to be fully rendered
        setTimeout(() => {
            const form = document.getElementById('task-form');
            if (form) {
                console.log('Task form found, binding submit event'); // Debug log
                
                // Bind form submission
                form.addEventListener('submit', (e) => {
                    console.log('Task form submit event triggered'); // Debug log
                    e.preventDefault();
                    this.submitTaskForm();
                });
            } else {
                console.error('Task form not found after modal creation!'); // Debug log
            }
        }, 100);
    }

    async populateTaskFormDropdowns() {
        try {
            // Load projects
            const projectsResponse = await fetch('api/projects.php');
            const projectsData = await projectsResponse.json();
            
            // Load team members
            const teamResponse = await fetch('api/team.php');
            const teamData = await teamResponse.json();
            
            // Populate projects dropdown (for add form)
            const projectSelect = document.getElementById('task-project-select');
            if (projectSelect && projectsData.success) {
                projectSelect.innerHTML = '<option value="">Select Project</option>';
                projectsData.projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.id;
                    option.textContent = project.name;
                    projectSelect.appendChild(option);
                });
            }
            
            // Populate team members dropdown (for add form)
            const assigneeSelect = document.getElementById('task-assignee-select');
            if (assigneeSelect && teamData.success) {
                assigneeSelect.innerHTML = '<option value="">Select Member</option>';
                teamData.members.forEach(member => {
                    const option = document.createElement('option');
                    option.value = member.id;
                    option.textContent = member.full_name;
                    assigneeSelect.appendChild(option);
                });
            }
            
            // Populate projects dropdown (for edit form)
            const editProjectSelect = document.getElementById('edit-task-project-select');
            if (editProjectSelect && projectsData.success) {
                editProjectSelect.innerHTML = '<option value="">Select Project</option>';
                projectsData.projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.id;
                    option.textContent = project.name;
                    editProjectSelect.appendChild(option);
                });
            }
            
            // Populate team members dropdown (for edit form)
            const editAssigneeSelect = document.getElementById('edit-task-assignee-select');
            if (editAssigneeSelect && teamData.success) {
                editAssigneeSelect.innerHTML = '<option value="">Select Member</option>';
                teamData.members.forEach(member => {
                    const option = document.createElement('option');
                    option.value = member.id;
                    option.textContent = member.full_name;
                    editAssigneeSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error populating task form dropdowns:', error);
        }
    }

    async populateProjectFormDropdowns() {
        try {
            // Load team members for manager selection
            const teamResponse = await fetch('api/team.php');
            const teamData = await teamResponse.json();
            
            // Populate manager dropdown
            const managerSelect = document.getElementById('project-manager-select');
            if (managerSelect && teamData.success) {
                managerSelect.innerHTML = '<option value="">Select Manager</option>';
                teamData.members.forEach(member => {
                    const option = document.createElement('option');
                    option.value = member.id;
                    option.textContent = member.full_name;
                    managerSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error populating project form dropdowns:', error);
        }
    }

    showAddMemberModal() {
        const modal = new Modal('Add New Team Member', this.getMemberFormHTML());
        modal.show();
        
        // Bind form submission
        document.getElementById('member-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitMemberForm();
        });
    }

    getProjectFormHTML() {
        return `
            <form id="project-form">
                <div class="form-group">
                    <label class="form-label">Project Name</label>
                    <input type="text" class="form-input" name="name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-input" name="start_date" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="has-end-date" name="has_end_date" style="margin-right: 8px;">
                        Set End Date (Optional)
                    </label>
                    <input type="date" class="form-input" name="end_date" id="end-date-input" disabled style="margin-top: 10px;">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Leave unchecked for ongoing projects without deadlines
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Project Manager</label>
                    <select class="form-select" name="manager_id" required id="project-manager-select">
                        <option value="">Select Manager</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Project</button>
                    <button type="button" class="btn btn-secondary" onclick="Modal.close()">Cancel</button>
                </div>
            </form>
        `;
    }

    getTaskFormHTML() {
        return `
            <form id="task-form">
                <div class="form-group">
                    <label class="form-label">Task Title</label>
                    <input type="text" class="form-input" name="title" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project</label>
                        <select class="form-select" name="project_id" required id="task-project-select">
                            <option value="">Select Project</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select class="form-select" name="assigned_to" required id="task-assignee-select">
                            <option value="">Select Member</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-input" name="due_date" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Estimated Hours</label>
                    <input type="number" class="form-input" name="estimated_hours" min="0" step="0.5">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Task</button>
                    <button type="button" class="btn btn-secondary" onclick="Modal.close()">Cancel</button>
                </div>
            </form>
        `;
    }

    getEditProjectFormHTML(project) {
        return `
            <form id="edit-project-form">
                <div class="form-group">
                    <label class="form-label">Project Name</label>
                    <input type="text" class="form-input" name="name" value="${project.name || ''}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="3">${project.description || ''}</textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="pending" ${project.status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="active" ${project.status === 'active' ? 'selected' : ''}>Active</option>
                            <option value="on_hold" ${project.status === 'on_hold' ? 'selected' : ''}>On Hold</option>
                            <option value="completed" ${project.status === 'completed' ? 'selected' : ''}>Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority" required>
                            <option value="low" ${project.priority === 'low' ? 'selected' : ''}>Low</option>
                            <option value="medium" ${project.priority === 'medium' ? 'selected' : ''}>Medium</option>
                            <option value="high" ${project.priority === 'high' ? 'selected' : ''}>High</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-input" name="start_date" value="${project.start_date || ''}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="edit-has-end-date" name="has_end_date" style="margin-right: 8px;">
                        Set End Date (Optional)
                    </label>
                    <input type="date" class="form-input" name="end_date" id="edit-end-date-input" 
                           value="${project.end_date && project.end_date !== '0000-00-00' ? project.end_date : ''}" 
                           style="margin-top: 10px;">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Leave unchecked for ongoing projects without deadlines
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Progress (%)</label>
                    <input type="number" class="form-input" name="progress" min="0" max="100" value="${project.progress || 0}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Project Manager</label>
                    <select class="form-select" name="manager_id" required>
                        <option value="">Select Manager</option>
                        <option value="2" ${project.manager_id == 2 ? 'selected' : ''}>John Doe</option>
                        <option value="3" ${project.manager_id == 3 ? 'selected' : ''}>Jane Smith</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Project</button>
                    <button type="button" class="btn btn-secondary" onclick="Modal.close()">Cancel</button>
                </div>
            </form>
        `;
    }

    getMemberFormHTML() {
        return `
            <form id="member-form">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-input" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-input" name="username" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" name="email" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Job Title</label>
                    <select class="form-select" name="job_title" required>
                        <option value="Account Manager">Account Manager</option>
                        <option value="Developer (Manager)">Developer (Manager)</option>
                        <option value="Graphic Designer (Manager)">Graphic Designer (Manager)</option>
                        <option value="Graphic Designer">Graphic Designer</option>
                        <option value="Social Media Executive">Social Media Executive</option>
                        <option value="Video Editor (Manager)">Video Editor (Manager)</option>
                        <option value="Video Editor">Video Editor</option>
                        <option value="UI/UX (Manager)">UI/UX (Manager)</option>
                        <option value="UI/UX">UI/UX</option>
                        <option value="Content Writer">Content Writer</option>
                        <option value="HR">HR</option>
                        <option value="Sales and Marketing (Manager)">Sales and Marketing (Manager)</option>
                        <option value="Sales and Marketing">Sales and Marketing</option>
                        <option value="SEO (Manager)">SEO (Manager)</option>
                        <option value="SEO">SEO</option>
                        <option value="Google Ads (Manager)">Google Ads (Manager)</option>
                        <option value="Google Ads">Google Ads</option>
                        <option value="Meta Ads (Manager)">Meta Ads (Manager)</option>
                        <option value="Meta Ads">Meta Ads</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="member">Member</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-input" name="password" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Member</button>
                    <button type="button" class="btn btn-secondary" onclick="Modal.close()">Cancel</button>
                </div>
            </form>
        `;
    }

    async submitProjectForm() {
        console.log('submitProjectForm called'); // Debug log
        
        const form = document.getElementById('project-form');
        if (!form) {
            console.error('Project form not found!');
            this.showMessage('Form not found', 'error');
            return;
        }
        
        const formData = new FormData(form);
        
        // Handle optional end date
        const hasEndDate = document.getElementById('has-end-date');
        if (hasEndDate && !hasEndDate.checked) {
            formData.set('end_date', ''); // Clear end date if not set
        }
        
        // Debug: Log form data
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        try {
            console.log('Sending request to api/projects.php...');
            const response = await fetch('api/projects.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('Response received:', response);
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                Modal.close();
                this.showMessage('Project created successfully!', 'success');
                this.loadProjects();
            } else {
                this.showMessage(data.message || 'Error creating project', 'error');
            }
        } catch (error) {
            console.error('Error in submitProjectForm:', error);
            this.showMessage('Error creating project: ' + error.message, 'error');
        }
    }

    async submitEditProjectForm(projectId) {
        const form = document.getElementById('edit-project-form');
        const formData = new FormData(form);
        
        // Handle optional end date
        const hasEndDate = document.getElementById('edit-has-end-date').checked;
        if (!hasEndDate) {
            formData.set('end_date', ''); // Clear end date if not set
        }
        
        try {
            const response = await fetch(`api/projects.php?id=${projectId}`, {
                method: 'PUT',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Modal.close();
                this.showMessage('Project updated successfully!', 'success');
                this.loadProjects();
            } else {
                this.showMessage(data.message || 'Error updating project', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showMessage('Error updating project', 'error');
        }
    }

    async submitTaskForm() {
        console.log('submitTaskForm called'); // Debug log
        
        const form = document.getElementById('task-form');
        if (!form) {
            console.error('Task form not found!'); // Debug log
            this.showMessage('Error: Task form not found', 'error');
            return;
        }
        
        const formData = new FormData(form);
        
        // Debug: Log form data
        console.log('Task form data:', Object.fromEntries(formData));
        
        try {
            const response = await fetch('api/tasks.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            console.log('Task API response:', data); // Debug log
            
            if (data.success) {
                Modal.close();
                this.showMessage('Task created successfully!', 'success');
                this.loadTasks();
            } else {
                this.showMessage(data.message || 'Error creating task', 'error');
            }
        } catch (error) {
            console.error('Error creating task:', error);
            this.showMessage('Error creating task', 'error');
        }
    }

    async submitMemberForm() {
        const form = document.getElementById('member-form');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('api/team.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Modal.close();
                this.showMessage('Team member added successfully!', 'success');
                this.loadTeamMembers();
            } else {
                this.showMessage(data.message || 'Error adding team member', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showMessage('Error adding team member', 'error');
        }
    }

    async updateTaskStatus(taskId, completed) {
        try {
            console.log('=== updateTaskStatus called ===');
            console.log('Task ID:', taskId);
            console.log('Completed:', completed);
            console.log('Task ID type:', typeof taskId);
            
            const formData = new FormData();
            formData.append('status', completed ? 'completed' : 'pending');
            
            console.log('Sending request to:', `api/tasks.php?id=${taskId}`);
            console.log('Form data:', Object.fromEntries(formData));
            
            const response = await fetch(`api/tasks.php?id=${taskId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(formData)
            });
            
            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Task status update response:', data);
            
            if (data.success) {
                this.showMessage('Task status updated!', 'success');
                console.log(`Task status updated successfully. Current filter: ${this.currentTaskFilter}`);
                await this.loadTasks();
                // Re-apply current filter after updating task status
                if (this.currentTaskFilter && this.currentTaskFilter !== 'all') {
                    console.log(`Re-applying filter: ${this.currentTaskFilter}`);
                    this.filterTasks(this.currentTaskFilter);
                }
            } else {
                this.showMessage(data.message || 'Error updating task status', 'error');
            }
        } catch (error) {
            console.error('Error updating task status:', error);
            this.showMessage('Error updating task status', 'error');
        }
    }

    filterTasks(filter) {
        console.log('Filtering tasks with filter:', filter);
        const taskItems = document.querySelectorAll('.task-item');
        console.log('Found task items:', taskItems.length);
        
        let visibleCount = 0;
        
        taskItems.forEach((item, index) => {
            // Check if task is completed using the data attribute (most reliable)
            const taskStatus = item.getAttribute('data-task-status');
            const isCompleted = taskStatus === 'completed';
            
            console.log(`Task ${index + 1}: status = ${taskStatus}, isCompleted = ${isCompleted}`);
            
            let shouldShow = false;
            
            switch (filter) {
                case 'all':
                    shouldShow = true;
                    break;
                case 'pending':
                    shouldShow = !isCompleted;
                    break;
                case 'completed':
                    shouldShow = isCompleted;
                    break;
            }
            
            item.style.display = shouldShow ? 'flex' : 'none';
            if (shouldShow) visibleCount++;
        });
        
        console.log(`Filter "${filter}" applied. Showing ${visibleCount} of ${taskItems.length} tasks.`);
        
        // Update the active filter button
        document.querySelectorAll('.task-filters button').forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-filter') === filter) {
                btn.classList.add('active');
            }
        });
    }

    async deleteProject(projectId) {
        if (!confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
            return;
        }
        
        try {
            const response = await fetch(`api/projects.php?id=${projectId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Project deleted successfully!', 'success');
                this.loadProjects();
            } else {
                this.showMessage(data.message || 'Error deleting project', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showMessage('Error deleting project', 'error');
        }
    }

    async deleteTask(taskId) {
        if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
            return;
        }
        
        try {
            const response = await fetch(`api/tasks.php?id=${taskId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Task deleted successfully!', 'success');
                this.loadTasks();
            } else {
                this.showMessage(data.message || 'Error deleting task', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showMessage('Error deleting task', 'error');
        }
    }

    async deleteMember(memberId) {
        if (!confirm('Are you sure you want to delete this team member? This action cannot be undone.')) {
            return;
        }
        
        try {
            const response = await fetch(`api/team.php?id=${memberId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Team member deleted successfully!', 'success');
                this.loadTeamMembers();
            } else {
                this.showMessage(data.message || 'Error deleting team member', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showMessage('Error deleting team member', 'error');
        }
    }

    async editProject(projectId) {
        try {
            const response = await fetch(`api/projects.php?id=${projectId}`);
            const data = await response.json();
            
            if (data.success) {
                const project = data.project;
                const modal = new Modal('Edit Project', this.getEditProjectFormHTML(project));
                modal.show();
                
                // Bind form submission
                document.getElementById('edit-project-form').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.submitEditProjectForm(projectId);
                });
                
                // Bind checkbox functionality for end date
                const hasEndDateCheckbox = document.getElementById('edit-has-end-date');
                const endDateInput = document.getElementById('edit-end-date-input');
                
                if (hasEndDateCheckbox && endDateInput) {
                    // Set initial state based on existing end date
                    if (project.end_date && project.end_date !== '0000-00-00') {
                        hasEndDateCheckbox.checked = true;
                        endDateInput.disabled = false;
                        endDateInput.required = true;
                        endDateInput.style.opacity = '1';
                    } else {
                        hasEndDateCheckbox.checked = false;
                        endDateInput.disabled = true;
                        endDateInput.required = false;
                        endDateInput.style.opacity = '0.6';
                    }
                    
                    hasEndDateCheckbox.addEventListener('change', function() {
                        endDateInput.disabled = !this.checked;
                        if (this.checked) {
                            endDateInput.required = true;
                            endDateInput.style.opacity = '1';
                        } else {
                            endDateInput.required = false;
                            endDateInput.value = '';
                            endDateInput.style.opacity = '0.6';
                        }
                    });
                }
            } else {
                this.showMessage('Error loading project details', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showMessage('Error loading project details', 'error');
        }
    }

    async editTask(taskId) {
        try {
            console.log('Edit task:', taskId);
            
            // Fetch task details
            const response = await fetch(`api/tasks.php?id=${taskId}`);
            const data = await response.json();
            
            if (data.success && data.task) {
                const task = data.task;
                console.log('Task data:', task);
                
                // Create and show edit modal
                const modal = new Modal('Edit Task', this.getEditTaskFormHTML(task));
                modal.show();
                
                // Populate dropdowns
                await this.populateTaskFormDropdowns();
                
                // Set form values
                setTimeout(() => {
                    const form = document.getElementById('edit-task-form');
                    if (form) {
                        // Set form values
                        form.querySelector('[name="title"]').value = task.title || '';
                        form.querySelector('[name="description"]').value = task.description || '';
                        form.querySelector('[name="project_id"]').value = task.project_id || '';
                        form.querySelector('[name="assigned_to"]').value = task.assigned_to || '';
                        form.querySelector('[name="priority"]').value = task.priority || 'medium';
                        form.querySelector('[name="due_date"]').value = task.due_date || '';
                        form.querySelector('[name="estimated_hours"]').value = task.estimated_hours || '';
                        
                        // Bind form submission
                        form.addEventListener('submit', (e) => {
                            e.preventDefault();
                            this.submitEditTaskForm(taskId);
                        });
                    }
                }, 100);
            } else {
                this.showMessage('Error loading task details', 'error');
            }
        } catch (error) {
            console.error('Error loading task details:', error);
            this.showMessage('Error loading task details', 'error');
        }
    }

    getEditTaskFormHTML(task) {
        return `
            <form id="edit-task-form">
                <div class="form-group">
                    <label class="form-label">Task Title</label>
                    <input type="text" class="form-input" name="title" value="${task.title || ''}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="3">${task.description || ''}</textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project</label>
                        <select class="form-select" name="project_id" required id="edit-task-project-select">
                            <option value="">Select Project</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select class="form-select" name="assigned_to" required id="edit-task-assignee-select">
                            <option value="">Select Member</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority" required>
                            <option value="low" ${task.priority === 'low' ? 'selected' : ''}>Low</option>
                            <option value="medium" ${task.priority === 'medium' ? 'selected' : ''}>Medium</option>
                            <option value="high" ${task.priority === 'high' ? 'selected' : ''}>High</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-input" name="due_date" value="${task.due_date || ''}" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Estimated Hours</label>
                    <input type="number" class="form-input" name="estimated_hours" min="0" step="0.5" value="${task.estimated_hours || ''}">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Task</button>
                    <button type="button" class="btn btn-secondary" onclick="Modal.close()">Cancel</button>
                </div>
            </form>
        `;
    }

    async submitEditTaskForm(taskId) {
        console.log('submitEditTaskForm called for task:', taskId);
        
        const form = document.getElementById('edit-task-form');
        if (!form) {
            console.error('Edit task form not found!');
            this.showMessage('Error: Edit task form not found', 'error');
            return;
        }
        
        const formData = new FormData(form);
        
        // Debug: Log form data
        console.log('Edit task form data:', Object.fromEntries(formData));
        
        try {
            const response = await fetch(`api/tasks.php?id=${taskId}`, {
                method: 'PUT',
                body: formData
            });
            
            const data = await response.json();
            console.log('Edit task API response:', data);
            
            if (data.success) {
                Modal.close();
                this.showMessage('Task updated successfully!', 'success');
                this.loadTasks();
            } else {
                this.showMessage(data.message || 'Error updating task', 'error');
            }
        } catch (error) {
            console.error('Error updating task:', error);
            this.showMessage('Error updating task', 'error');
        }
    }

    editMember(memberId) {
        // Implementation for editing team members
        console.log('Edit member:', memberId);
    }

    initializeSearch() {
        // Add search functionality
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search...';
        searchInput.className = 'search-input';
        searchInput.style.cssText = `
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            margin-left: 20px;
            width: 200px;
        `;
        
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            if (query.length > 2) {
                this.performSearch(query);
            }
        });
        
        // Add search to top bar
        const topBar = document.querySelector('.top-bar');
        if (topBar) {
            topBar.appendChild(searchInput);
        }
    }

    async performSearch(query) {
        try {
            const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.success) {
                this.displaySearchResults(data.results);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(results) {
        // Implementation for displaying search results
        console.log('Search results:', results);
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }

    logout() {
        if (confirm('Are you sure you want to logout?')) {
            // Clear session and redirect
            window.location.href = 'logout.php';
        }
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new ProjectManagementApp();
});
