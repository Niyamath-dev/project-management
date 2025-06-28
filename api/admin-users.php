<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = new Database();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isCurrentUserAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access required']);
    exit();
}

// Handle POST request for creating user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    parse_str(file_get_contents('php://input'), $input);
    
    if (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    $username = trim($input['username']);
    $email = trim($input['email']);
    $password = $input['password'];
    $role = $input['role'] ?? 'user';
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    // Check if email already exists
    if ($auth->emailExists($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    
    try {
        // Create user
        if ($auth->register($username, $email, $password)) {
            // Update role if not default
            if ($role !== 'user') {
                $db->query('UPDATE users SET role = :role WHERE email = :email');
                $db->bind(':role', $role);
                $db->bind(':email', $email);
                $db->execute();
            }
            
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error creating user']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()]);
    }
    exit();
}

// Handle PUT request for updating user
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $input);
    
    if (!isset($input['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing user ID']);
        exit();
    }

    $user_id = intval($input['user_id']);
    
    try {
        // Update user details
        $updates = [];
        $params = [];
        
        if (isset($input['username']) && !empty($input['username'])) {
            $updates[] = 'username = :username';
            $params[':username'] = trim($input['username']);
        }
        
        if (isset($input['email']) && !empty($input['email'])) {
            // Check if email already exists for another user
            $db->query('SELECT id FROM users WHERE email = :email AND id != :user_id');
            $db->bind(':email', trim($input['email']));
            $db->bind(':user_id', $user_id);
            
            if ($db->single()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit();
            }
            
            $updates[] = 'email = :email';
            $params[':email'] = trim($input['email']);
        }
        
        if (isset($input['role'])) {
            $updates[] = 'role = :role';
            $params[':role'] = $input['role'];
        }
        
        if (isset($input['password']) && !empty($input['password'])) {
            $updates[] = 'password = :password';
            $params[':password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        
        if (!empty($updates)) {
            $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $db->query($sql);
            $db->bind(':id', $user_id);
            foreach ($params as $key => $value) {
                $db->bind($key, $value);
            }
            
            if ($db->execute()) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error updating user']);
            }
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes made']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
    }
    exit();
}

// Handle other HTTP methods
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
