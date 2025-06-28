<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'project_management');

// Application configuration
define('APP_NAME', 'ProjectHub');
define('APP_URL', 'http://localhost/project-managemnt/');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('UTC');
?>
