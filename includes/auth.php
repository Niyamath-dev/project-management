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
}
?>
