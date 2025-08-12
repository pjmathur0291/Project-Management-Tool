// Dashboard functionality for Project Management Tool
class Dashboard {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.initializeCharts();
        this.bindEvents();
        this.startAutoRefresh();
    }

    initializeCharts() {
        // Initialize project progress chart
        this.initProjectProgressChart();
        
        // Initialize task distribution chart
        this.initTaskDistributionChart();
        
        // Initialize timeline chart
        this.initTimelineChart();
        
        // Initialize recent activities
        this.initRecentActivities();
    }

    initProjectProgressChart() {
        const container = document.getElementById('project-progress-chart');
        if (!container) return;

        // Create simple chart using CSS
        container.innerHTML = `
            <div class="chart-simple">
                <div class="chart-item">
                    <div class="chart-bar">
                        <div class="chart-fill" style="width: 65%"></div>
                    </div>
                    <div class="chart-label">Active Projects</div>
                    <div class="chart-value">2</div>
                </div>
                <div class="chart-item">
                    <div class="chart-bar">
                        <div class="chart-fill" style="width: 0%"></div>
                    </div>
                    <div class="chart-label">Completed Projects</div>
                    <div class="chart-value">0</div>
                </div>
                <div class="chart-item">
                    <div class="chart-bar">
                        <div class="chart-fill" style="width: 100%"></div>
                    </div>
                    <div class="chart-label">Pending Projects</div>
                    <div class="chart-value">1</div>
                </div>
            </div>
        `;

        // Add CSS for chart bars
        this.addChartStyles();
    }

    initTaskDistributionChart() {
        const container = document.getElementById('task-distribution-chart');
        if (!container) return;

        container.innerHTML = `
            <div class="distribution-chart">
                <div class="chart-item">
                    <div class="chart-circle">
                        <svg viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#e9ecef" stroke-width="8"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#dc3545" stroke-width="8" 
                                    stroke-dasharray="251.2" stroke-dashoffset="188.4" transform="rotate(-90 50 50)"/>
                        </svg>
                        <div class="chart-center">1</div>
                    </div>
                    <div class="chart-label">High Priority</div>
                </div>
                <div class="chart-item">
                    <div class="chart-circle">
                        <svg viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#e9ecef" stroke-width="8"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#ffc107" stroke-width="8" 
                                    stroke-dasharray="251.2" stroke-dashoffset="125.6" transform="rotate(-90 50 50)"/>
                        </svg>
                        <div class="chart-center">2</div>
                    </div>
                    <div class="chart-label">Medium Priority</div>
                </div>
                <div class="chart-item">
                    <div class="chart-circle">
                        <svg viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#e9ecef" stroke-width="8"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#17a2b8" stroke-width="8" 
                                    stroke-dasharray="251.2" stroke-dashoffset="0" transform="rotate(-90 50 50)"/>
                        </svg>
                        <div class="chart-center">1</div>
                    </div>
                    <div class="chart-label">Low Priority</div>
                </div>
            </div>
        `;
    }

    initTimelineChart() {
        const container = document.getElementById('timeline-chart');
        if (!container) return;

        container.innerHTML = `
            <div class="timeline-chart">
                <div class="timeline-item">
                    <div class="timeline-marker active"></div>
                    <div class="timeline-content">
                        <div class="timeline-date">Q1 2024</div>
                        <div class="timeline-project">Website Redesign</div>
                        <div class="timeline-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 65%"></div>
                            </div>
                            <span>65% Complete</span>
                        </div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker pending"></div>
                    <div class="timeline-content">
                        <div class="timeline-date">Q2 2024</div>
                        <div class="timeline-project">Mobile App Development</div>
                        <div class="timeline-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 0%"></div>
                            </div>
                            <span>0% Complete</span>
                        </div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker active"></div>
                    <div class="timeline-content">
                        <div class="timeline-date">Q2 2024</div>
                        <div class="timeline-project">Database Migration</div>
                        <div class="timeline-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 40%"></div>
                            </div>
                            <span>40% Complete</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    initRecentActivities() {
        const container = document.getElementById('recent-activities');
        if (!container) return;

        // Sample activities - in real app, this would come from API
        const activities = [
            {
                action: 'Project created',
                user: 'John Doe',
                time: '2 hours ago',
                icon: 'plus',
                color: 'success'
            },
            {
                action: 'Task completed',
                user: 'Jane Smith',
                time: '4 hours ago',
                icon: 'check',
                color: 'success'
            },
            {
                action: 'Comment added',
                user: 'Mike Wilson',
                time: '6 hours ago',
                icon: 'comment',
                color: 'info'
            },
            {
                action: 'Project updated',
                user: 'John Doe',
                time: '1 day ago',
                icon: 'edit',
                color: 'warning'
            }
        ];

        container.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon ${activity.color}">
                    <i class="fas fa-${activity.icon}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">${activity.action}</div>
                    <div class="activity-meta">
                        by ${activity.user} • ${activity.time}
                    </div>
                </div>
            </div>
        `).join('');
    }

    addChartStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .chart-simple {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
            
            .chart-item {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            
            .chart-bar {
                width: 100px;
                height: 20px;
                background: #e9ecef;
                border-radius: 10px;
                overflow: hidden;
            }
            
            .chart-fill {
                height: 100%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                transition: width 0.5s ease;
            }
            
            .chart-label {
                flex: 1;
                font-weight: 500;
                color: #333;
            }
            
            .chart-value {
                font-size: 1.2rem;
                font-weight: 600;
                color: #667eea;
                min-width: 30px;
                text-align: center;
            }
            
            .distribution-chart {
                display: flex;
                justify-content: space-around;
                align-items: center;
                margin-top: 20px;
            }
            
            .chart-circle {
                position: relative;
                width: 80px;
                height: 80px;
            }
            
            .chart-circle svg {
                width: 100%;
                height: 100%;
            }
            
            .chart-center {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-weight: 600;
                color: #333;
            }
            
            .timeline-chart {
                position: relative;
                padding-left: 30px;
            }
            
            .timeline-item {
                position: relative;
                margin-bottom: 30px;
            }
            
            .timeline-marker {
                position: absolute;
                left: -35px;
                top: 0;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                border: 3px solid #fff;
                box-shadow: 0 0 0 3px #e9ecef;
            }
            
            .timeline-marker.active {
                background: #28a745;
            }
            
            .timeline-marker.pending {
                background: #ffc107;
            }
            
            .timeline-marker.completed {
                background: #17a2b8;
            }
            
            .timeline-content {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                border-left: 4px solid #667eea;
            }
            
            .timeline-date {
                font-weight: 600;
                color: #667eea;
                margin-bottom: 10px;
            }
            
            .timeline-project {
                font-weight: 500;
                color: #333;
                margin-bottom: 15px;
            }
            
            .timeline-progress {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .timeline-progress .progress-bar {
                flex: 1;
                height: 8px;
                background: #e9ecef;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .timeline-progress .progress-fill {
                height: 100%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                transition: width 0.5s ease;
            }
            
            .timeline-progress span {
                font-size: 0.9rem;
                color: #666;
                min-width: 80px;
            }
            
            .activity-item {
                display: flex;
                align-items: flex-start;
                gap: 15px;
                padding: 15px 0;
                border-bottom: 1px solid #eee;
            }
            
            .activity-item:last-child {
                border-bottom: none;
            }
            
            .activity-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 1rem;
                flex-shrink: 0;
            }
            
            .activity-icon.success {
                background: #28a745;
            }
            
            .activity-icon.info {
                background: #17a2b8;
            }
            
            .activity-icon.warning {
                background: #ffc107;
            }
            
            .activity-icon.danger {
                background: #dc3545;
            }
            
            .activity-content {
                flex: 1;
            }
            
            .activity-text {
                font-weight: 500;
                color: #333;
                margin-bottom: 5px;
            }
            
            .activity-meta {
                font-size: 0.9rem;
                color: #666;
            }
        `;
        
        document.head.appendChild(style);
    }

    bindEvents() {
        // Refresh button
        const refreshBtn = document.createElement('button');
        refreshBtn.className = 'btn btn-secondary btn-sm';
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
        refreshBtn.addEventListener('click', () => this.refreshDashboard());
        
        // Add to dashboard header
        const dashboardHeader = document.querySelector('#dashboard h2');
        if (dashboardHeader) {
            dashboardHeader.parentNode.insertBefore(refreshBtn, dashboardHeader.nextSibling);
        }

        // Auto-refresh toggle
        const autoRefreshToggle = document.createElement('button');
        autoRefreshToggle.className = 'btn btn-outline-secondary btn-sm';
        autoRefreshToggle.innerHTML = '<i class="fas fa-clock"></i> Auto-refresh';
        autoRefreshToggle.addEventListener('click', () => this.toggleAutoRefresh());
        
        if (dashboardHeader) {
            dashboardHeader.parentNode.insertBefore(autoRefreshToggle, refreshBtn.nextSibling);
        }
    }

    async refreshDashboard() {
        try {
            // Show loading state
            this.showLoadingState();
            
            // Refresh all dashboard data
            await Promise.all([
                this.refreshStats(),
                this.refreshCharts(),
                this.refreshActivities()
            ]);
            
            // Hide loading state
            this.hideLoadingState();
            
            // Show success message
            this.showMessage('Dashboard refreshed successfully!', 'success');
            
        } catch (error) {
            console.error('Error refreshing dashboard:', error);
            this.showMessage('Error refreshing dashboard', 'error');
            this.hideLoadingState();
        }
    }

    async refreshStats() {
        try {
            const response = await fetch('api/dashboard.php');
            const data = await response.json();
            
            if (data.success) {
                // Update statistics
                document.getElementById('total-projects').textContent = data.stats.total_projects;
                document.getElementById('total-tasks').textContent = data.stats.total_tasks;
                document.getElementById('total-team').textContent = data.stats.total_team;
                document.getElementById('pending-tasks').textContent = data.stats.pending_tasks;
                
                // Animate number changes
                this.animateNumberChange('total-projects', data.stats.total_projects);
                this.animateNumberChange('total-tasks', data.stats.total_tasks);
                this.animateNumberChange('total-team', data.stats.total_team);
                this.animateNumberChange('pending-tasks', data.stats.pending_tasks);
            }
        } catch (error) {
            console.error('Error refreshing stats:', error);
        }
    }

    async refreshCharts() {
        try {
            const response = await fetch('api/dashboard.php?charts=true');
            const data = await response.json();
            
            if (data.success) {
                // Update project progress chart
                this.updateProjectProgressChart(data.charts.project_progress);
                
                // Update task distribution chart
                this.updateTaskDistributionChart(data.charts.task_distribution);
                
                // Update timeline chart
                this.updateTimelineChart(data.charts.timeline);
            }
        } catch (error) {
            console.error('Error refreshing charts:', error);
        }
    }

    async refreshActivities() {
        try {
            const response = await fetch('api/dashboard.php?activities=true');
            const data = await response.json();
            
            if (data.success) {
                this.updateRecentActivities(data.activities);
            }
        } catch (error) {
            console.error('Error refreshing activities:', error);
        }
    }

    updateProjectProgressChart(data) {
        const container = document.getElementById('project-progress-chart');
        if (!container) return;

        // Update chart values
        const chartItems = container.querySelectorAll('.chart-item');
        if (chartItems.length >= 3) {
            // Update active projects
            const activeFill = chartItems[0].querySelector('.chart-fill');
            const activeValue = chartItems[0].querySelector('.chart-value');
            if (activeFill && activeValue) {
                const percentage = data.active > 0 ? (data.active / (data.active + data.completed + data.pending)) * 100 : 0;
                activeFill.style.width = `${percentage}%`;
                activeValue.textContent = data.active || 0;
            }
            
            // Update completed projects
            const completedFill = chartItems[1].querySelector('.chart-fill');
            const completedValue = chartItems[1].querySelector('.chart-value');
            if (completedFill && completedValue) {
                const percentage = data.completed > 0 ? (data.completed / (data.active + data.completed + data.pending)) * 100 : 0;
                completedFill.style.width = `${percentage}%`;
                completedValue.textContent = data.completed || 0;
            }
            
            // Update pending projects
            const pendingFill = chartItems[2].querySelector('.chart-fill');
            const pendingValue = chartItems[2].querySelector('.chart-value');
            if (pendingFill && pendingValue) {
                const percentage = data.pending > 0 ? (data.pending / (data.active + data.completed + data.pending)) * 100 : 0;
                pendingFill.style.width = `${percentage}%`;
                pendingValue.textContent = data.pending || 0;
            }
        }
    }

    updateTaskDistributionChart(data) {
        const container = document.getElementById('task-distribution-chart');
        if (!container) return;

        // Update chart values
        const chartItems = container.querySelectorAll('.chart-item');
        if (chartItems.length >= 3) {
            // Update high priority
            const highValue = chartItems[0].querySelector('.chart-center');
            if (highValue) highValue.textContent = data.high || 0;
            
            // Update medium priority
            const mediumValue = chartItems[1].querySelector('.chart-center');
            if (mediumValue) mediumValue.textContent = data.medium || 0;
            
            // Update low priority
            const lowValue = chartItems[2].querySelector('.chart-center');
            if (lowValue) lowValue.textContent = data.low || 0;
        }
    }

    updateTimelineChart(data) {
        const container = document.getElementById('timeline-chart');
        if (!container) return;

        // Update timeline items
        if (data && data.length > 0) {
            container.innerHTML = data.map(item => `
                <div class="timeline-item">
                    <div class="timeline-marker ${item.status}"></div>
                    <div class="timeline-content">
                        <div class="timeline-date">${item.quarter}</div>
                        <div class="timeline-project">${item.name}</div>
                        <div class="timeline-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${item.progress}%"></div>
                            </div>
                            <span>${item.progress}% Complete</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    }

    updateRecentActivities(activities) {
        const container = document.getElementById('recent-activities');
        if (!container || !activities) return;

        container.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon ${activity.color || 'info'}">
                    <i class="fas fa-${activity.icon || 'info'}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">${activity.action}</div>
                    <div class="activity-meta">
                        by ${activity.user_name} • ${this.formatTimeAgo(activity.created_at)}
                    </div>
                </div>
            </div>
        `).join('');
    }

    animateNumberChange(elementId, newValue) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const currentValue = parseInt(element.textContent) || 0;
        const difference = newValue - currentValue;
        const duration = 1000; // 1 second
        const steps = 60;
        const stepValue = difference / steps;
        const stepDuration = duration / steps;

        let currentStep = 0;
        const interval = setInterval(() => {
            currentStep++;
            const currentValueAnimated = Math.round(currentValue + (stepValue * currentStep));
            element.textContent = currentValueAnimated;

            if (currentStep >= steps) {
                element.textContent = newValue;
                clearInterval(interval);
            }
        }, stepDuration);
    }

    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInSeconds = Math.floor((now - time) / 1000);

        if (diffInSeconds < 60) {
            return 'Just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
    }

    showLoadingState() {
        const dashboard = document.getElementById('dashboard');
        if (dashboard) {
            dashboard.classList.add('loading');
        }
    }

    hideLoadingState() {
        const dashboard = document.getElementById('dashboard');
        if (dashboard) {
            dashboard.classList.remove('loading');
        }
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.zIndex = '10000';
        messageDiv.style.animation = 'slideInRight 0.3s ease';
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 3000);
    }

    startAutoRefresh() {
        this.autoRefreshInterval = setInterval(() => {
            if (this.autoRefreshEnabled) {
                this.refreshDashboard();
            }
        }, 300000); // Refresh every 5 minutes
    }

    toggleAutoRefresh() {
        this.autoRefreshEnabled = !this.autoRefreshEnabled;
        const toggleBtn = document.querySelector('.btn-outline-secondary');
        
        if (toggleBtn) {
            if (this.autoRefreshEnabled) {
                toggleBtn.innerHTML = '<i class="fas fa-pause"></i> Pause auto-refresh';
                toggleBtn.classList.remove('btn-outline-secondary');
                toggleBtn.classList.add('btn-warning');
                this.showMessage('Auto-refresh enabled', 'success');
            } else {
                toggleBtn.innerHTML = '<i class="fas fa-clock"></i> Auto-refresh';
                toggleBtn.classList.remove('btn-warning');
                toggleBtn.classList.add('btn-outline-secondary');
                this.showMessage('Auto-refresh disabled', 'info');
            }
        }
    }

    // Export dashboard data
    exportDashboard(format = 'json') {
        const dashboardData = {
            timestamp: new Date().toISOString(),
            stats: {
                total_projects: document.getElementById('total-projects').textContent,
                total_tasks: document.getElementById('total-tasks').textContent,
                total_team: document.getElementById('total-team').textContent,
                pending_tasks: document.getElementById('pending-tasks').textContent
            }
        };

        if (format === 'json') {
            const dataStr = JSON.stringify(dashboardData, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(dataBlob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `dashboard-${new Date().toISOString().split('T')[0]}.json`;
            link.click();
            
            URL.revokeObjectURL(url);
        } else if (format === 'csv') {
            // Convert to CSV format
            const csvContent = this.convertToCSV(dashboardData);
            const dataBlob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(dataBlob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `dashboard-${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
            
            URL.revokeObjectURL(url);
        }
    }

    convertToCSV(data) {
        const headers = ['Metric', 'Value'];
        const rows = [
            ['Total Projects', data.stats.total_projects],
            ['Total Tasks', data.stats.total_tasks],
            ['Total Team Members', data.stats.total_team],
            ['Pending Tasks', data.stats.pending_tasks],
            ['Export Date', data.timestamp]
        ];

        return [headers, ...rows]
            .map(row => row.map(cell => `"${cell}"`).join(','))
            .join('\n');
    }

    // Cleanup method
    destroy() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new Dashboard();
});

// Add CSS animations for messages
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 0.8rem;
    }
    
    .btn-outline-secondary {
        background: transparent;
        border: 1px solid #6c757d;
        color: #6c757d;
    }
    
    .btn-outline-secondary:hover {
        background: #6c757d;
        color: white;
    }
    
    .btn-warning {
        background: #ffc107;
        border-color: #ffc107;
        color: #212529;
    }
    
    .btn-warning:hover {
        background: #e0a800;
        border-color: #d39e00;
        color: #212529;
    }
    
    .loading .dashboard-grid,
    .loading .dashboard-charts {
        opacity: 0.6;
        pointer-events: none;
    }
    
    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
`;
document.head.appendChild(style);
