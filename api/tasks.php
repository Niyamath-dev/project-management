<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

$auth = new Auth();
$db = new Database();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$currentUser = $auth->getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        createTask();
        break;
    case 'PUT':
        updateTask();
        break;
    case 'DELETE':
        deleteTask();
        break;
    case 'GET':
        getTasks();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function createTask() {
    global $db, $currentUser;
    
    $title = trim($_POST['title'] ?? '');
    $project_id = intval($_POST['project_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'todo';
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = $_POST['due_date'] ?? null;
    $assigned_to = intval($_POST['assigned_to'] ?? 0);
    
    if (empty($title) || $project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Title and project are required']);
        return;
    }
    
    // Check if user is the project owner
    $db->query('SELECT id, created_by FROM projects WHERE id = :project_id');
    $db->bind(':project_id', $project_id);
    $project = $db->single();
    
    if (!$project || $project->created_by != $currentUser['id']) {
        echo json_encode(['success' => false, 'message' => 'Only project owners can create tasks']);
        return;
    }
    
    try {
        $db->query('INSERT INTO tasks (project_id, title, description, status, priority, due_date, assigned_to, created_at) VALUES (:project_id, :title, :description, :status, :priority, :due_date, :assigned_to, NOW())');
        $db->bind(':project_id', $project_id);
        $db->bind(':title', $title);
        $db->bind(':description', $description);
        $db->bind(':status', $status);
        $db->bind(':priority', $priority);
        $db->bind(':due_date', $due_date);
        $db->bind(':assigned_to', $assigned_to > 0 ? $assigned_to : null);
        
        if ($db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Task created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating task']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function updateTask() {
    global $db, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $task_id = intval($input['task_id'] ?? 0);
    $action = $input['action'] ?? '';
    
    // Debug logging
    error_log("Task update - Input: " . json_encode($input));
    error_log("Task update - task_id: " . $task_id);
    error_log("Task update - action: " . $action);
    
    if ($task_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID', 'debug' => ['task_id' => $task_id, 'input' => $input]]);
        return;
    }
    
    // Check if user has access to the project (owner or member)
    $db->query('SELECT t.id, p.created_by FROM tasks t 
               JOIN projects p ON t.project_id = p.id 
               WHERE t.id = :task_id');
    $db->bind(':task_id', $task_id);
    $task = $db->single();
    
    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        return;
    }
    
    // Check if user is project owner or member
    $db->query('SELECT COUNT(*) as has_access FROM projects p 
               LEFT JOIN project_members pm ON p.id = pm.project_id 
               WHERE p.id = (SELECT project_id FROM tasks WHERE id = :task_id) 
               AND (p.created_by = :user_id OR pm.user_id = :user_id)');
    $db->bind(':task_id', $task_id);
    $db->bind(':user_id', $currentUser['id']);
    $access = $db->single();
    
    if (!$access || $access->has_access == 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied to modify tasks']);
        return;
    }
    
    try {
        if ($action === 'update_status') {
            $status = $input['status'] ?? 'todo';
            $db->query('UPDATE tasks SET status = :status, updated_at = NOW() WHERE id = :task_id');
            $db->bind(':status', $status);
            $db->bind(':task_id', $task_id);
            $db->execute();
            echo json_encode(['success' => true, 'message' => 'Task status updated']);
        } elseif ($action === 'update_task') {
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $priority = $input['priority'] ?? 'medium';
            $due_date = $input['due_date'] ?? null;
            $assigned_to = intval($input['assigned_to'] ?? 0);
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                return;
            }
            
            $db->query('UPDATE tasks SET title = :title, description = :description, priority = :priority, due_date = :due_date, assigned_to = :assigned_to, updated_at = NOW() WHERE id = :task_id');
            $db->bind(':title', $title);
            $db->bind(':description', $description);
            $db->bind(':priority', $priority);
            $db->bind(':due_date', $due_date);
            $db->bind(':assigned_to', $assigned_to > 0 ? $assigned_to : null);
            $db->bind(':task_id', $task_id);
            $db->execute();
            echo json_encode(['success' => true, 'message' => 'Task updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function deleteTask() {
    global $db, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $task_id = intval($input['task_id'] ?? 0);
    
    if ($task_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        return;
    }
    
    // Check if user is the project owner
    $db->query('SELECT t.id, p.created_by FROM tasks t 
               JOIN projects p ON t.project_id = p.id 
               WHERE t.id = :task_id');
    $db->bind(':task_id', $task_id);
    $task = $db->single();
    
    if (!$task || $task->created_by != $currentUser['id']) {
        echo json_encode(['success' => false, 'message' => 'Only project owners can delete tasks']);
        return;
    }
    
    try {
        $db->query('DELETE FROM tasks WHERE id = :task_id');
        $db->bind(':task_id', $task_id);
        if ($db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Task deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting task']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function getTasks() {
    global $db, $currentUser;
    
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    
    try {
        if ($task_id > 0) {
            // Get specific task
            $db->query('SELECT t.*, p.title as project_title, u.username as assigned_to_name 
                       FROM tasks t 
                       LEFT JOIN projects p ON t.project_id = p.id 
                       LEFT JOIN users u ON t.assigned_to = u.id 
                       WHERE t.id = :task_id AND t.project_id IN (
                           SELECT id FROM projects 
                           WHERE created_by = :user_id 
                           OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)
                       )');
            $db->bind(':task_id', $task_id);
            $db->bind(':user_id', $currentUser['id']);
        } elseif ($project_id > 0) {
            // Get tasks for a specific project
            $db->query('SELECT t.*, u.username as assigned_to_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.project_id = :project_id ORDER BY t.due_date ASC, t.created_at DESC');
            $db->bind(':project_id', $project_id);
        } else {
            // Get all tasks for user projects
            $db->query('SELECT t.*, p.title as project_title, u.username as assigned_to_name 
                       FROM tasks t 
                       LEFT JOIN projects p ON t.project_id = p.id 
                       LEFT JOIN users u ON t.assigned_to = u.id 
                       WHERE t.project_id IN (
                           SELECT id FROM projects 
                           WHERE created_by = :user_id 
                           OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)
                       ) 
                       ORDER BY t.due_date ASC, t.created_at DESC');
            $db->bind(':user_id', $currentUser['id']);
        }
        
        $tasks = $db->resultset();
        echo json_encode(['success' => true, 'tasks' => $tasks]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}
?>
