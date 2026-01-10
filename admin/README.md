# Admin Panel - TravelApp

## Installation

1. **Database Setup**
   - Open phpMyAdmin or MySQL command line
   - Import the `database.sql` file to create the database and tables
   - Or run the SQL commands manually

2. **Database Configuration**
   - Edit `config.php` and update database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'travelapp_admin');
     ```

3. **Default Login Credentials**
   - Username: `admin`
   - Password: `admin123`

## Features

- ✅ Secure Login/Logout
- ✅ Change Password
- ✅ Dashboard with Statistics
- ✅ Activity Logging
- ✅ Responsive Design
- ✅ Modern UI with Sidebar Menu

## File Structure

```
admin/
├── config.php              # Database and session configuration
├── login.php               # Login page
├── logout.php              # Logout handler
├── dashboard.php           # Main dashboard
├── change-password.php     # Change password page
├── admin.css               # Admin panel styles
├── database.sql            # Database schema
├── includes/
│   ├── sidebar.php        # Sidebar navigation
│   └── header.php         # Top header
└── README.md              # This file
```

## Security Features

- Password hashing using PHP `password_hash()`
- Session management
- SQL injection protection (prepared statements)
- Activity logging
- Account status checking

## Access

Navigate to: `http://localhost/all_project/micro-adventures/admin/login.php`

