# ProjectHub - Project Management Web Application

A comprehensive project management web application built with PHP, MySQL, HTML, CSS, and JavaScript. Features user authentication, project creation, task management, team collaboration, and reporting capabilities.

## Features

### 🔐 User Authentication
- User registration and login
- Secure password hashing
- Session management
- Profile management

### 📁 Project Management
- Create, edit, and delete projects
- Project overview with statistics
- Project member management
- Project progress tracking

### ✅ Task Management
- Create and assign tasks
- Task status tracking (Todo, In Progress, Completed)
- Priority levels (Low, Medium, High)
- Due date management
- Task filtering and search

### 👥 Team Collaboration
- Invite team members to projects
- Role-based access (Owner, Member)
- Team member management
- Project sharing

### 📊 Reports & Analytics
- Project completion rates
- Task statistics
- Priority distribution
- Activity tracking
- Printable reports

### 📅 Calendar View
- Visual calendar with due dates
- Upcoming tasks overview
- Overdue task tracking
- Monthly navigation

### 🎨 Modern UI/UX
- Responsive design
- Bootstrap-based interface
- Interactive dashboard
- Mobile-friendly layout

## Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx (XAMPP recommended for development)
- **Extensions**: PDO MySQL, MySQLi (recommended)

## Installation

### 1. Download/Clone the Project
```bash
git clone [repository-url]
# or download and extract the ZIP file
```

### 2. Setup Web Server
- Place the project folder in your web server directory
  - XAMPP: `C:/xampp/htdocs/project-managemnt/`
  - WAMP: `C:/wamp64/www/project-managemnt/`
  - Linux: `/var/www/html/project-managemnt/`

### 3. Configure Database
1. Start your MySQL server
2. Edit `includes/config.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'project_management');
   ```

### 4. Initialize Database
1. Open your browser and navigate to: `http://localhost/project-managemnt/setup.php`
2. Click "Initialize Database" to create the required tables
3. Wait for the success message

### 5. Start Using the Application
1. Navigate to: `http://localhost/project-managemnt/`
2. Click "Register" to create your first account
3. Login and start creating projects!

## File Structure

```
project-managemnt/
├── api/                    # API endpoints
│   ├── projects.php       # Project CRUD operations
│   └── tasks.php          # Task CRUD operations
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── app.js         # JavaScript functionality
├── components/            # Reusable components
│   ├── header.php         # Page header with navigation
│   └── footer.php         # Page footer
├── includes/              # Core functionality
│   ├── config.php         # Configuration settings
│   ├── db.php             # Database connection class
│   └── auth.php           # Authentication class
├── calendar.php           # Calendar view
├── database.sql           # Database schema
├── index.php              # Dashboard (home page)
├── login.php              # User login
├── logout.php             # User logout
├── profile.php            # User profile management
├── projects.php           # Project management
├── register.php           # User registration
├── reports.php            # Analytics and reports
├── setup.php              # Database setup utility
├── tasks.php              # Task management
├── team.php               # Team collaboration
└── README.md              # This file
```

## Usage Guide

### Getting Started
1. **Register**: Create your account with username, email, and password
2. **Login**: Access the dashboard with your credentials
3. **Create Project**: Start by creating your first project
4. **Add Tasks**: Break down your project into manageable tasks
5. **Invite Team**: Collaborate by inviting team members

### Managing Projects
- **Create**: Use the "New Project" button on the projects page
- **View**: Click on any project card to see details
- **Edit**: Project owners can edit project information
- **Delete**: Remove projects you no longer need (owners only)

### Task Management
- **Create**: Add tasks to any project you have access to
- **Assign**: Assign tasks to team members
- **Track**: Update task status as work progresses
- **Filter**: Use filters to find specific tasks quickly

### Team Collaboration
- **Invite**: Add team members by email address
- **Roles**: Assign Owner or Member roles
- **Permissions**: Owners can manage project settings and members

### Reports & Analytics
- **Overview**: View completion rates and statistics
- **Project Performance**: Analyze individual project progress
- **Activity**: Track recent task creation and updates
- **Export**: Print reports for offline use

## API Endpoints

### Projects API (`api/projects.php`)
- `GET`: Retrieve user's projects
- `POST`: Create new project
- `PUT`: Update existing project
- `DELETE`: Remove project

### Tasks API (`api/tasks.php`)
- `GET`: Retrieve tasks (all or by project)
- `POST`: Create new task
- `PUT`: Update task or change status
- `DELETE`: Remove task

## Database Schema

### Users Table
- `id`: Primary key
- `username`: User display name
- `email`: Login email (unique)
- `password`: Hashed password
- `created_at`: Registration timestamp

### Projects Table
- `id`: Primary key
- `title`: Project name
- `description`: Project details
- `created_by`: Owner user ID
- `created_at`: Creation timestamp
- `updated_at`: Last modification

### Tasks Table
- `id`: Primary key
- `project_id`: Associated project
- `title`: Task name
- `description`: Task details
- `status`: todo/in_progress/completed
- `priority`: low/medium/high
- `due_date`: Optional deadline
- `assigned_to`: Optional assignee
- `created_at`: Creation timestamp
- `updated_at`: Last modification

### Project Members Table
- `project_id`: Project reference
- `user_id`: User reference
- `role`: owner/member
- `joined_at`: Membership timestamp

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` function
- **SQL Injection Protection**: PDO prepared statements
- **Session Management**: Secure session handling
- **Access Control**: Role-based permissions
- **Input Validation**: Server-side validation for all forms

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers (responsive design)

## Troubleshooting

### Database Connection Issues
1. Verify MySQL server is running
2. Check database credentials in `includes/config.php`
3. Ensure database user has proper permissions
4. Run `setup.php` to initialize database

### Permission Errors
1. Check file permissions on web server
2. Ensure web server can read/write to project directory
3. Verify PHP extensions are loaded

### Login Issues
1. Clear browser cache and cookies
2. Verify user account exists in database
3. Check for JavaScript errors in browser console

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support, please:
1. Check this README for common solutions
2. Review the troubleshooting section
3. Check browser console for JavaScript errors
4. Verify database connection and setup

## Version History

- **v1.0.0**: Initial release with core functionality
  - User authentication
  - Project and task management
  - Team collaboration
  - Reports and calendar views
  - Responsive design

---

**ProjectHub** - Streamline your project management workflow with an intuitive, feature-rich web application.
