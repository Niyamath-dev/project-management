<?php
require_once 'db.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    // Register user
    public function register($username, $email, $password) {
        $this->db->query('INSERT INTO users (username, email, password, created_at) VALUES(:username, :email, :password, NOW())');
        
        // Bind values
        $this->db->bind(':username', $username);
        $this->db->bind(':email', $email);
        $this->db->bind(':password', password_hash($password, PASSWORD_DEFAULT));

        // Execute
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    // Login user
    public function login($email, $password) {
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        
        $row = $this->db->single();

        if($row) {
            $hashed_password = $row->password;
            if(password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $row->id;
                $_SESSION['username'] = $row->username;
                $_SESSION['email'] = $row->email;
                $_SESSION['user_role'] = $row->role;
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // Logout user
    public function logout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['email']);
        unset($_SESSION['user_role']);
        session_destroy();
        return true;
    }

    // Check if user is logged in
    public function isLoggedIn() {
        if(isset($_SESSION['user_id'])) {
            return true;
        } else {
            return false;
        }
    }

    // Get current user
    public function getCurrentUser() {
        if($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['user_role'] ?? 'user'
            ];
        }
        return null;
    }

    // Check if email exists
    public function emailExists($email) {
        $this->db->query('SELECT id FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        $this->db->single();
        
        if($this->db->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    // Get user by ID
    public function getUserById($id) {
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Get all users
    public function getAllUsers() {
        $this->db->query('SELECT id, username, email, role, created_at FROM users ORDER BY username');
        return $this->db->resultset();
    }

    // Check if user is admin
    public function isAdmin($userId = null) {
        if ($userId === null && $this->isLoggedIn()) {
            return $_SESSION['user_role'] === 'admin';
        } elseif ($userId !== null) {
            $this->db->query('SELECT role FROM users WHERE id = :id');
            $this->db->bind(':id', $userId);
            $user = $this->db->single();
            return $user && $user->role === 'admin';
        }
        return false;
    }

    // Check if current user is admin (helper method)
    public function isCurrentUserAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }

    // Check if current user is a regular member (not admin and doesn't own projects)
    public function isCurrentUserMember() {
        return $this->isLoggedIn() && 
               ($_SESSION['user_role'] !== 'admin') && 
               !$this->isCurrentUserProjectOwner();
    }

    // Check if user has permission for specific action
    public function hasPermission($action) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // System admins have all permissions
        if ($this->isCurrentUserAdmin()) {
            return true;
        }

        // Check if user owns any projects (project owners get full access)
        $this->db->query('SELECT COUNT(*) as count FROM projects WHERE created_by = :user_id');
        $this->db->bind(':user_id', $_SESSION['user_id']);
        $result = $this->db->single();
        $isProjectOwner = $result && $result->count > 0;

        // Project owners have full access (except system admin functions)
        if ($isProjectOwner) {
            $adminOnlyActions = ['manage_users'];
            return !in_array($action, $adminOnlyActions);
        }

        // Regular members (users who don't own projects) have restricted access
        $restrictedActions = [
            'create_project',
            'invite_team',
            'view_all_reports',
            'manage_users'
        ];

        // Members cannot perform restricted actions
        if (in_array($action, $restrictedActions)) {
            return false;
        }

        return true;
    }

    // Check if current user is a project owner
    public function isCurrentUserProjectOwner() {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $this->db->query('SELECT COUNT(*) as count FROM projects WHERE created_by = :user_id');
        $this->db->bind(':user_id', $_SESSION['user_id']);
        $result = $this->db->single();
        return $result && $result->count > 0;
    }

    // Get user avatar HTML
    public function getUserAvatar($userId, $username = null, $size = 40, $classes = '') {
        $this->db->query('SELECT profile_pic, username FROM users WHERE id = :id');
        $this->db->bind(':id', $userId);
        $user = $this->db->single();
        
        if (!$user) {
            return $this->getDefaultAvatar($username ?: 'User', $size, $classes);
        }
        
        $displayName = $username ?: $user->username;
        
        if (!empty($user->profile_pic) && file_exists($user->profile_pic)) {
            return '<img src="' . htmlspecialchars($user->profile_pic) . '" 
                         alt="' . htmlspecialchars($displayName) . '" 
                         class="rounded-circle ' . $classes . '" 
                         style="width: ' . $size . 'px; height: ' . $size . 'px; object-fit: cover;">';
        } else {
            return $this->getDefaultAvatar($displayName, $size, $classes);
        }
    }

    // Get default avatar (initials)
    public function getDefaultAvatar($username, $size = 40, $classes = '') {
        $initials = strtoupper(substr($username, 0, 2));
        return '<div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center ' . $classes . '" 
                     style="width: ' . $size . 'px; height: ' . $size . 'px; font-size: ' . ($size * 0.4) . 'px; color: white;">
                    ' . $initials . '
                </div>';
    }

    // Get current user avatar
    public function getCurrentUserAvatar($size = 40, $classes = '') {
        if (!$this->isLoggedIn()) {
            return $this->getDefaultAvatar('User', $size, $classes);
        }
        
        return $this->getUserAvatar($_SESSION['user_id'], $_SESSION['username'], $size, $classes);
    }
}
?>
