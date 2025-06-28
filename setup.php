<?php
require_once 'includes/config.php';

// Check if database setup is needed
$setup_needed = true;
$error_message = '';
$success_message = '';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $database_exists = $stmt->fetch() !== false;
    
    if ($database_exists) {
        // Check if tables exist
        $pdo->exec("USE " . DB_NAME);
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required_tables = ['users', 'projects', 'tasks', 'project_members', 'comments'];
        $missing_tables = array_diff($required_tables, $tables);
        
        if (empty($missing_tables)) {
            $setup_needed = false;
        }
    }
} catch (PDOException $e) {
    $error_message = "Database connection failed: " . $e->getMessage();
}

// Handle setup request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['setup_database'])) {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute SQL file
        $sql = file_get_contents('database.sql');
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        $success_message = "Database setup completed successfully! You can now <a href='register.php'>register</a> or <a href='login.php'>login</a>.";
        $setup_needed = false;
        
    } catch (PDOException $e) {
        $error_message = "Setup failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .setup-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
        }
        
        .setup-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .setup-body {
            padding: 30px;
        }
        
        .status-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .status-card.success {
            border-color: #28a745;
            background-color: #f8fff9;
        }
        
        .status-card.warning {
            border-color: #ffc107;
            background-color: #fffdf5;
        }
        
        .status-card.error {
            border-color: #dc3545;
            background-color: #fff5f5;
        }
        
        .btn-setup {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: transform 0.2s ease;
        }
        
        .btn-setup:hover {
            transform: translateY(-1px);
            color: white;
        }
        
        .requirements-list {
            list-style: none;
            padding: 0;
        }
        
        .requirements-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .requirements-list li:last-child {
            border-bottom: none;
        }
        
        .check-icon {
            color: #28a745;
            margin-right: 10px;
        }
        
        .warning-icon {
            color: #ffc107;
            margin-right: 10px;
        }
        
        .error-icon {
            color: #dc3545;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <i class="fas fa-cogs fa-3x mb-3"></i>
            <h2><?php echo APP_NAME; ?> Setup</h2>
            <p class="mb-0">Project Management System Installation</p>
        </div>
        
        <div class="setup-body">
            <?php if ($error_message): ?>
                <div class="status-card error">
                    <h5><i class="fas fa-exclamation-triangle error-icon"></i>Setup Error</h5>
                    <p class="mb-0"><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="status-card success">
                    <h5><i class="fas fa-check-circle check-icon"></i>Setup Complete!</h5>
                    <p class="mb-0"><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!$setup_needed && !$success_message): ?>
                <div class="status-card success">
                    <h5><i class="fas fa-check-circle check-icon"></i>Already Set Up</h5>
                    <p class="mb-3">Your database is already configured and ready to use.</p>
                    <div class="d-flex gap-2">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- System Requirements Check -->
            <div class="status-card">
                <h5><i class="fas fa-list-check check-icon"></i>System Requirements</h5>
                <ul class="requirements-list">
                    <li>
                        <i class="fas fa-check check-icon"></i>
                        PHP <?php echo PHP_VERSION; ?> (Required: 7.4+)
                    </li>
                    <li>
                        <?php if (extension_loaded('pdo_mysql')): ?>
                            <i class="fas fa-check check-icon"></i>
                            PDO MySQL Extension (Available)
                        <?php else: ?>
                            <i class="fas fa-times error-icon"></i>
                            PDO MySQL Extension (Missing)
                        <?php endif; ?>
                    </li>
                    <li>
                        <?php if (extension_loaded('mysqli')): ?>
                            <i class="fas fa-check check-icon"></i>
                            MySQLi Extension (Available)
                        <?php else: ?>
                            <i class="fas fa-exclamation-triangle warning-icon"></i>
                            MySQLi Extension (Recommended)
                        <?php endif; ?>
                    </li>
                    <li>
                        <i class="fas fa-check check-icon"></i>
                        Database: <?php echo DB_NAME; ?> on <?php echo DB_HOST; ?>
                    </li>
                </ul>
            </div>
            
            <?php if ($setup_needed): ?>
                <div class="status-card warning">
                    <h5><i class="fas fa-exclamation-triangle warning-icon"></i>Database Setup Required</h5>
                    <p>The database needs to be initialized with the required tables and structure.</p>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <h6>What will be created:</h6>
                            <ul>
                                <li>Database: <code><?php echo DB_NAME; ?></code></li>
                                <li>Tables: users, projects, tasks, project_members, comments</li>
                                <li>Initial database structure and relationships</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Make sure your MySQL server is running and the database credentials in <code>includes/config.php</code> are correct.
                        </div>
                        
                        <button type="submit" name="setup_database" class="btn btn-setup">
                            <i class="fas fa-database"></i> Initialize Database
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Quick Start Guide -->
            <div class="status-card">
                <h5><i class="fas fa-rocket check-icon"></i>Quick Start Guide</h5>
                <ol>
                    <li>Complete the database setup above</li>
                    <li>Register a new user account</li>
                    <li>Create your first project</li>
                    <li>Add tasks to your project</li>
                    <li>Invite team members to collaborate</li>
                </ol>
            </div>
            
            <!-- Configuration Info -->
            <div class="status-card">
                <h5><i class="fas fa-cog check-icon"></i>Configuration</h5>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Database Host:</strong><br>
                        <code><?php echo DB_HOST; ?></code>
                    </div>
                    <div class="col-md-6">
                        <strong>Database Name:</strong><br>
                        <code><?php echo DB_NAME; ?></code>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        To change these settings, edit <code>includes/config.php</code>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
