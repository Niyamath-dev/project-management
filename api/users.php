<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = new Database();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Handle POST request for inviting member
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    parse_str(file_get_contents('php://input'), $input);
    
    if (!isset($input['project_id']) || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    $project_id = intval($input['project_id']);
    $email = trim($input['email']);
    $role = $input['role'] ?? 'member';
    
    // Check if current user owns the project
    $db->query('SELECT id FROM projects WHERE id = :project_id AND created_by = :user_id');
    $db->bind(':project_id', $project_id);
    $db->bind(':user_id', $auth->getCurrentUser()['id']);
    
    if (!$db->single()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only invite members to projects you own']);
        exit();
    }
    
    // Check if user exists
    $db->query('SELECT id FROM users WHERE email = :email');
    $db->bind(':email', $email);
    $user = $db->single();
    
    if (!$user) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User with this email does not exist']);
        exit();
    }
    
    // Check if user is already a member
    $db->query('SELECT project_id FROM project_members WHERE project_id = :project_id AND user_id = :user_id');
    $db->bind(':project_id', $project_id);
    $db->bind(':user_id', $user->id);
    $existing = $db->single();
    
    if ($existing) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User is already a member of this project']);
        exit();
    }
    
    try {
        // Add member to project
        $db->query('INSERT INTO project_members (project_id, user_id, role, joined_at) VALUES (:project_id, :user_id, :role, NOW())');
        $db->bind(':project_id', $project_id);
        $db->bind(':user_id', $user->id);
        $db->bind(':role', $role);
        
        if ($db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Member invited successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error inviting member']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error inviting member: ' . $e->getMessage()]);
    }
    exit();
}

// Handle PUT request for updating user
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $input);
    
    if (!isset($input['member_id']) || !isset($input['project_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    $member_id = intval($input['member_id']);
    $project_id = intval($input['project_id']);
    
    // Check if current user owns the project
    $db->query('SELECT id FROM projects WHERE id = :project_id AND created_by = :user_id');
    $db->bind(':project_id', $project_id);
    $db->bind(':user_id', $auth->getCurrentUser()['id']);
    
    if (!$db->single()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only update members of projects you own']);
        exit();
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Update user details
        $updates = [];
        $params = [];
        
        if (isset($input['username']) && !empty($input['username'])) {
            $updates[] = 'username = :username';
            $params[':username'] = $input['username'];
        }
        
        if (isset($input['email']) && !empty($input['email'])) {
            $updates[] = 'email = :email';
            $params[':email'] = $input['email'];
        }
        
        if (isset($input['password']) && !empty($input['password'])) {
            $updates[] = 'password = :password';
            $params[':password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        
        if (!empty($updates)) {
            $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $db->query($sql);
            $db->bind(':id', $member_id);
            foreach ($params as $key => $value) {
                $db->bind($key, $value);
            }
            $db->execute();
        }
        
        // Update project role if provided
        if (isset($input['role'])) {
            $db->query('UPDATE project_members SET role = :role 
                       WHERE project_id = :project_id AND user_id = :user_id');
            $db->bind(':role', $input['role']);
            $db->bind(':project_id', $project_id);
            $db->bind(':user_id', $member_id);
            $db->execute();
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Member updated successfully']);
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating member: ' . $e->getMessage()]);
    }
    exit();
}

// Handle other HTTP methods
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
