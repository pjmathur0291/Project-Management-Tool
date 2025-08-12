# Dynamic Project Management Tool

A comprehensive, modern web-based project management application built with PHP, MySQL, and JavaScript. This tool provides a dynamic, responsive interface for managing projects, tasks, team members, and generating reports.

## 🚀 Features

### Core Functionality
- **Project Management**: Create, edit, delete, and track project progress
- **Task Management**: Assign tasks, set priorities, track completion status
- **Team Management**: Manage team members with different roles and permissions
- **Real-time Dashboard**: Live statistics and progress tracking
- **Dynamic Content**: Single-page application with smooth transitions
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices

### Advanced Features
- **Modal System**: Dynamic modal creation for forms and dialogs
- **Search Functionality**: Search across projects, tasks, and team members
- **Activity Logging**: Track all system activities and changes
- **Progress Tracking**: Visual progress bars and completion percentages
- **Priority Management**: Set and manage task priorities (Low, Medium, High)
- **Status Management**: Track project and task statuses
- **Export Functionality**: Export dashboard data in JSON/CSV formats

### User Experience
- **Modern UI/UX**: Clean, intuitive interface with smooth animations
- **External CSS**: Organized, maintainable styling system
- **Keyboard Navigation**: Full keyboard support with focus management
- **Accessibility**: ARIA labels and semantic HTML structure
- **Loading States**: Visual feedback during data operations
- **Error Handling**: Comprehensive error messages and validation

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Server**: Apache/Nginx (XAMPP compatible)
- **Icons**: Font Awesome 6.0
- **Styling**: Custom CSS with CSS Grid and Flexbox

## 📁 Project Structure

```
management-tool/
├── index.php                 # Main application entry point
├── config/
│   └── database.php         # Database configuration and initialization
├── includes/
│   └── functions.php        # Helper functions and business logic
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet (external CSS)
│   ├── js/
│   │   ├── app.js          # Main application logic
│   │   ├── modals.js       # Modal system
│   │   └── dashboard.js    # Dashboard functionality
│   └── images/             # Image assets
├── api/                    # REST API endpoints
│   ├── projects.php        # Project CRUD operations
│   ├── tasks.php           # Task CRUD operations
│   ├── team.php            # Team member management
│   ├── dashboard.php       # Dashboard statistics
│   └── reports.php         # Reporting and analytics
└── README.md               # This file
```

## 🚀 Quick Start

### Prerequisites
- XAMPP (or similar local development environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Installation

1. **Clone/Download the Project**
   ```bash
   # If using git
   git clone <repository-url>
   
   # Or download and extract to your XAMPP htdocs folder
   # Place in: /Applications/XAMPP/xamppfiles/htdocs/management-tool/
   ```

2. **Start XAMPP Services**
   - Start Apache and MySQL services in XAMPP Control Panel
   - Ensure both services are running (green status)

3. **Database Setup**
   - The application will automatically create the database and tables on first run
   - Sample data will be inserted automatically
   - Default database name: `project_management`

4. **Access the Application**
   - Open your web browser
   - Navigate to: `http://localhost/management-tool/`
   - The application will load with sample data

### Default Credentials
- **Admin User**: `admin` / `admin123`
- **Manager User**: `john_doe` / `password123`
- **Team Member**: `jane_smith` / `password123`

## 📊 Database Schema

### Tables
- **users**: User accounts and roles
- **projects**: Project information and metadata
- **tasks**: Task assignments and tracking
- **project_members**: Many-to-many relationship between projects and users
- **comments**: Project and task comments
- **activity_log**: System activity tracking

### Key Relationships
- Projects have one manager (user)
- Projects can have multiple members
- Tasks belong to projects and are assigned to users
- All activities are logged with user context

## 🎯 Usage Guide

### Dashboard
- View project statistics and progress
- Monitor task completion rates
- Track team member activities
- Access quick actions for common tasks

### Project Management
1. **Create Project**: Click "New Project" button
2. **Set Details**: Fill in name, description, dates, and assign manager
3. **Track Progress**: Monitor completion percentage and status
4. **Manage Team**: Add/remove team members with specific roles

### Task Management
1. **Create Task**: Click "New Task" button
2. **Assign**: Select project and assignee
3. **Set Priority**: Choose Low, Medium, or High priority
4. **Track Status**: Update task completion status
5. **Monitor Progress**: View estimated vs. actual hours

### Team Management
1. **Add Members**: Click "Add Member" button
2. **Set Roles**: Assign appropriate role (Admin, Manager, Member)
3. **Manage Permissions**: Different roles have different access levels
4. **Track Performance**: Monitor member activity and task completion

## 🔧 Configuration

### Database Configuration
Edit `config/database.php` to modify database settings:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'project_management');
```

### Customization
- **Styling**: Modify `assets/css/style.css` for visual changes
- **Functionality**: Extend `assets/js/app.js` for new features
- **API**: Add new endpoints in the `api/` directory
- **Business Logic**: Modify `includes/functions.php` for core functionality

## 🎨 Customization

### Adding New Features
1. **Frontend**: Add new sections in `index.php`
2. **JavaScript**: Extend the `ProjectManagementApp` class
3. **API**: Create new PHP endpoints in the `api/` directory
4. **Database**: Add new tables and relationships
5. **Styling**: Extend the CSS with new classes and components

### Modifying Existing Features
- **Forms**: Update modal content in JavaScript
- **Validation**: Modify validation rules in functions
- **Styling**: Update CSS classes and properties
- **Logic**: Modify PHP functions for business rules

## 🚀 Deployment

### Production Considerations
1. **Security**: Update database credentials
2. **HTTPS**: Enable SSL/TLS encryption
3. **Backup**: Implement database backup strategy
4. **Monitoring**: Add logging and error tracking
5. **Performance**: Optimize database queries and caching

### Server Requirements
- PHP 7.4+ with PDO extension
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx web server
- SSL certificate (recommended)

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection Error**
   - Verify XAMPP services are running
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is active

2. **Page Not Loading**
   - Check Apache service status
   - Verify file permissions
   - Check error logs in XAMPP

3. **JavaScript Errors**
   - Open browser developer tools
   - Check console for error messages
   - Verify all JS files are loading

4. **Styling Issues**
   - Clear browser cache
   - Verify CSS file path
   - Check for CSS syntax errors

### Debug Mode
Enable error reporting in PHP for development:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🤝 Contributing

### Development Workflow
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Code Standards
- Follow PSR-12 coding standards for PHP
- Use consistent JavaScript ES6+ syntax
- Maintain CSS organization and naming conventions
- Add comments for complex logic
- Include error handling for all operations

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 🙏 Acknowledgments

- Font Awesome for icons
- Modern CSS Grid and Flexbox for layout
- PHP PDO for database operations
- ES6+ JavaScript for modern functionality

## 📞 Support

For support and questions:
- Check the troubleshooting section above
- Review the code comments and documentation
- Open an issue in the project repository
- Contact the development team

---

**Happy Project Managing! 🎉**

This tool is designed to make project management simple, efficient, and enjoyable. With its dynamic interface and comprehensive features, you'll have everything you need to keep your projects on track and your team productive.
