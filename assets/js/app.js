// Main Application JavaScript
class ProjectManagementApp {
    constructor() {
        this.currentSection = 'dashboard';
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

        // Add buttons
        document.getElementById('add-project-btn')?.addEventListener('click', () => {
            this.showAddProjectModal();
        });

        document.getElementById('add-task-btn')?.addEventListener('click', () => {
            this.showAddTaskModal();
        });

        document.getElementById('add-member-btn')?.addEventListener('click', () => {
            this.showAddMemberModal();
        });

        // Logout
        document.getElementById('logout-btn')?.addEventListener('click', () => {
            this.logout();
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
            const response = await fetch('api/tasks.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderTasks(data.tasks);
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

        container.innerHTML = tasks.map(task => `
            <div class="task-item" data-task-id="${task.id}">
                <input type="checkbox" class="task-checkbox" 
                       ${task.status === 'completed' ? 'checked' : ''} 
                       onchange="app.updateTaskStatus(${task.id}, this.checked)">
                
                <div class="task-content">
                    <div class="task-title">${task.title}</div>
                    <div class="task-description">${task.description || 'No description'}</div>
                    <small><strong>Project:</strong> ${task.project_name || 'No project'}</small>
                </div>
                
                <span class="task-priority priority-${task.priority}">${this.formatPriority(task.priority)}</span>
                
                <div class="task-meta">
                    <small><strong>Assigned to:</strong> ${task.assignee_name || 'Unassigned'}</small><br>
                    <small><strong>Due:</strong> ${this.formatDate(task.due_date)}</small>
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
        `).join('');
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
                <p class="member-role">${member.role}</p>
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

    showAddProjectModal() {
        console.log('showAddProjectModal called'); // Debug log
        
        const modal = new Modal('Add New Project', this.getProjectFormHTML());
        modal.show();
        
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

    showAddTaskModal() {
        const modal = new Modal('Add New Task', this.getTaskFormHTML());
        modal.show();
        
        // Bind form submission
        document.getElementById('task-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitTaskForm();
        });
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
                    <select class="form-select" name="manager_id" required>
                        <option value="">Select Manager</option>
                        <option value="2">John Doe</option>
                        <option value="3">Jane Smith</option>
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
                        <select class="form-select" name="project_id" required>
                            <option value="">Select Project</option>
                            <option value="1">Website Redesign</option>
                            <option value="2">Mobile App Development</option>
                            <option value="3">Database Migration</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select class="form-select" name="assigned_to" required>
                            <option value="">Select Member</option>
                            <option value="3">Jane Smith</option>
                            <option value="4">Mike Wilson</option>
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
        const form = document.getElementById('task-form');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('api/tasks.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Modal.close();
                this.showMessage('Task created successfully!', 'success');
                this.loadTasks();
            } else {
                this.showMessage(data.message || 'Error creating task', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
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
            const response = await fetch(`api/tasks.php?id=${taskId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    status: completed ? 'completed' : 'pending'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Task status updated!', 'success');
                this.loadTasks();
            }
        } catch (error) {
            console.error('Error updating task status:', error);
        }
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

    editTask(taskId) {
        // Implementation for editing tasks
        console.log('Edit task:', taskId);
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
