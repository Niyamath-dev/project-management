<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

$auth = new Auth();
$db = new Database();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$currentUser = $auth->getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        createProject();
        break;
    case 'PUT':
        updateProject();
        break;
    case 'DELETE':
        deleteProject();
        break;
    case 'GET':
        getProjects();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function createProject() {
    global $db, $currentUser;
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Project title is required']);
        return;
    }
    
    try {
        // Create project
        $db->query('INSERT INTO projects (title, description, created_by, created_at) VALUES (:title, :description, :created_by, NOW())');
        $db->bind(':title', $title);
        $db->bind(':description', $description);
        $db->bind(':created_by', $currentUser['id']);
        
        if ($db->execute()) {
            $project_id = $db->lastInsertId();
            
            // Add creator as project owner
            $db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (:project_id, :user_id, "owner")');
            $db->bind(':project_id', $project_id);
            $db->bind(':user_id', $currentUser['id']);
            $db->execute();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Project created successfully',
                'project_id' => $project_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating project']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function updateProject() {
    global $db, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $project_id = $input['project_id'] ?? 0;
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Project title is required']);
        return;
    }
    
    // Check if user owns the project
    $db->query('SELECT id FROM projects WHERE id = :id AND created_by = :user_id');
    $db->bind(':id', $project_id);
    $db->bind(':user_id', $currentUser['id']);
    $project = $db->single();
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or access denied']);
        return;
    }
    
    try {
        $db->query('UPDATE projects SET title = :title, description = :description, updated_at = NOW() WHERE id = :id');
        $db->bind(':title', $title);
        $db->bind(':description', $description);
        $db->bind(':id', $project_id);
        
        if ($db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating project']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function deleteProject() {
    global $db, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $project_id = $input['project_id'] ?? 0;
    
    // Check if user owns the project
    $db->query('SELECT id FROM projects WHERE id = :id AND created_by = :user_id');
    $db->bind(':id', $project_id);
    $db->bind(':user_id', $currentUser['id']);
    $project = $db->single();
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or access denied']);
        return;
    }
    
    try {
        $db->query('DELETE FROM projects WHERE id = :id');
        $db->bind(':id', $project_id);
        
        if ($db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting project']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function getProjects() {
    global $db, $currentUser;
    
    try {
        $db->query('SELECT p.*, u.username as created_by_name,
                   (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
                   (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = "completed") as completed_tasks,
                   (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count
                   FROM projects p 
                   LEFT JOIN users u ON p.created_by = u.id 
                   WHERE p.created_by = :user_id OR p.id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)
                   ORDER BY p.created_at DESC');
        $db->bind(':user_id', $currentUser['id']);
        $projects = $db->resultset();
        
        echo json_encode(['success' => true, 'projects' => $projects]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}
?>
