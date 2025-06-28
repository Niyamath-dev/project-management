<?php
$pageTitle = 'Tasks';
require_once 'components/header.php';
require_once 'includes/db.php';

$db = new Database();
$message = '';

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 'task_created') {
    $message = '<div class="alert alert-success">Task created successfully!</div>';
}

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_task') {
    $title = trim($_POST['title']);
    $project_id = intval($_POST['project_id']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'] ?: null;
    $assigned_to = intval($_POST['assigned_to']) ?: null;
    
    if (empty($title) || $project_id <= 0) {
        $message = '<div class="alert alert-danger">Task title and project are required.</div>';
    } else {
        // Check if user has access to project
        $db->query('SELECT id FROM projects WHERE id = :project_id AND (created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))');
        $db->bind(':project_id', $project_id);
        $db->bind(':user_id', $currentUser['id']);
        $project = $db->single();
        
        if (!$project) {
            $message = '<div class="alert alert-danger">Access denied to project.</div>';
        } else {
            // Create task
            $db->query('INSERT INTO tasks (project_id, title, description, status, priority, due_date, assigned_to, created_at) VALUES (:project_id, :title, :description, "todo", :priority, :due_date, :assigned_to, NOW())');
            $db->bind(':project_id', $project_id);
            $db->bind(':title', $title);
            $db->bind(':description', $description);
            $db->bind(':priority', $priority);
            $db->bind(':due_date', $due_date);
            $db->bind(':assigned_to', $assigned_to);
            
            if ($db->execute()) {
                // Redirect to prevent duplicate submission on page refresh
                header('Location: tasks.php?success=task_created');
                exit();
            } else {
                $message = '<div class="alert alert-danger">Error creating task.</div>';
            }
        }
    }
}

// Get all projects user has access to
$db->query('SELECT id, title FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id) ORDER BY title');
$db->bind(':user_id', $currentUser['id']);
$projects = $db->resultset();

// Get all users for task assignment
$db->query('SELECT id, username FROM users ORDER BY username');
$users = $db->resultset();

// Get tasks based on filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$project_filter = isset($_GET['project']) ? intval($_GET['project']) : 0;

$query = 'SELECT t.*, p.title as project_title, u.username as assigned_to_name 
          FROM tasks t 
          LEFT JOIN projects p ON t.project_id = p.id 
          LEFT JOIN users u ON t.assigned_to = u.id 
          WHERE t.project_id IN (
              SELECT id FROM projects 
              WHERE created_by = :user_id 
              OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)
          )';

if ($status_filter) {
    $query .= ' AND t.status = :status';
}
if ($project_filter) {
    $query .= ' AND t.project_id = :project_id';
}

$query .= ' ORDER BY t.due_date ASC, t.created_at DESC';

$db->query($query);
$db->bind(':user_id', $currentUser['id']);
if ($status_filter) {
    $db->bind(':status', $status_filter);
}
if ($project_filter) {
    $db->bind(':project_id', $project_filter);
}

$tasks = $db->resultset();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-tasks text-primary"></i>
        Tasks
    </h2>
    <button class="btn btn-primary" data-modal="taskModal">
        <i class="fas fa-plus"></i> New Task
    </button>
</div>

<!-- Success/Error Messages -->
<?php if ($message): ?>
    <?php echo $message; ?>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Project</label>
                <select name="project" class="form-select" onchange="this.form.submit()">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project->id; ?>" <?php echo $project_filter == $project->id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($project->title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="todo" <?php echo $status_filter === 'todo' ? 'selected' : ''; ?>>To Do</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <a href="tasks.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Tasks List -->
<?php if (empty($tasks)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-tasks fa-4x text-muted mb-4"></i>
            <h4 class="text-muted">No Tasks Found</h4>
            <p class="text-muted mb-4">Create your first task to start managing your work.</p>
            <button class="btn btn-primary btn-lg" data-modal="taskModal">
                <i class="fas fa-plus"></i> Create Your First Task
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Project</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Due Date</th>
                    <th>Assigned To</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr class="searchable-item">
                        <td>
                            <strong><?php echo htmlspecialchars($task->title); ?></strong>
                            <?php if ($task->description): ?>
                                <br>
                                <small class="text-muted">
                                    <?php 
                                    $desc = htmlspecialchars($task->description);
                                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                                    ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($task->project_title); ?></td>
                        <td>
                            <select class="form-select form-select-sm task-status-select" 
                                    data-task-id="<?php echo $task->id; ?>"
                                    data-original-value="<?php echo $task->status; ?>">
                                <option value="todo" <?php echo $task->status === 'todo' ? 'selected' : ''; ?>>To Do</option>
                                <option value="in_progress" <?php echo $task->status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $task->status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $task->priority; ?>">
                                <?php echo ucfirst($task->priority); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $task->due_date ? date('M j, Y', strtotime($task->due_date)) : '-'; ?>
                        </td>
                        <td>
                            <?php echo $task->assigned_to_name ? htmlspecialchars($task->assigned_to_name) : 'Unassigned'; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editTask(<?php echo $task->id; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteTask(<?php echo $task->id; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Create Task Modal -->
<div class="modal" id="taskModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary"></i>
                    Create New Task
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" id="taskForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_task">
                    
                    <div class="form-group mb-3">
                        <label for="project_id" class="form-label">Project *</label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project->id; ?>">
                                    <?php echo htmlspecialchars($project->title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="title" class="form-label">Task Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="assigned_to" class="form-label">Assign To</label>
                        <select class="form-select" id="assigned_to" name="assigned_to">
                            <option value="">Unassigned</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->id; ?>">
                                    <?php echo htmlspecialchars($user->username); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal" id="editTaskModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-primary"></i>
                    Edit Task
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editTaskForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_task_id" name="task_id">
                    
                    <div class="form-group mb-3">
                        <label for="edit_title" class="form-label">Task Title *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_priority" class="form-label">Priority</label>
                                <select class="form-select" id="edit_priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="edit_due_date" name="due_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_assigned_to" class="form-label">Assign To</label>
                        <select class="form-select" id="edit_assigned_to" name="assigned_to">
                            <option value="">Unassigned</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->id; ?>">
                                    <?php echo htmlspecialchars($user->username); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Task status update
document.addEventListener('DOMContentLoaded', function() {
    // Handle status changes
    document.querySelectorAll('.task-status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const newStatus = this.value;
            const originalValue = this.dataset.originalValue;
            
            fetch('api/tasks.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    task_id: taskId,
                    action: 'update_status',
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.dataset.originalValue = newStatus;
                    showAlert('Task status updated successfully!', 'success');
                    // Update UI immediately instead of reloading
                    // No need to reload the page
                } else {
                    this.value = originalValue;
                    showAlert(data.message || 'Error updating task status', 'danger');
                }
            })
            .catch(error => {
                this.value = originalValue;
                showAlert('Error updating task status', 'danger');
                console.error('Error:', error);
            });
        });
    });
});

// Edit task function
function editTask(taskId) {
    // Get task data
    fetch(`api/tasks.php?task_id=${taskId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.tasks && data.tasks.length > 0) {
            const task = data.tasks[0];
            
            // Populate edit form
            document.getElementById('edit_task_id').value = task.id;
            document.getElementById('edit_title').value = task.title;
            document.getElementById('edit_description').value = task.description || '';
            document.getElementById('edit_priority').value = task.priority;
            document.getElementById('edit_due_date').value = task.due_date || '';
            document.getElementById('edit_assigned_to').value = task.assigned_to || '';
            
            // Show modal
            showModal('editTaskModal');
        } else {
            showAlert('Error loading task data', 'danger');
        }
    })
    .catch(error => {
        showAlert('Error loading task data', 'danger');
        console.error('Error:', error);
    });
}

// Delete task function
function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        fetch('api/tasks.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                task_id: taskId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Task deleted successfully!', 'success');
                // Refresh page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert(data.message || 'Error deleting task', 'danger');
            }
        })
        .catch(error => {
            showAlert('Error deleting task', 'danger');
            console.error('Error:', error);
        });
    }
}

// Handle edit task form submission
document.getElementById('editTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const taskData = {
        task_id: form.querySelector('#edit_task_id').value,
        action: 'update_task',
        title: form.querySelector('#edit_title').value,
        description: form.querySelector('#edit_description').value,
        priority: form.querySelector('#edit_priority').value,
        due_date: form.querySelector('#edit_due_date').value,
        assigned_to: form.querySelector('#edit_assigned_to').value
    };
    
    fetch('api/tasks.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(taskData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideModal('editTaskModal');
            showAlert('Task updated successfully!', 'success');
            
            // Update the UI immediately instead of reloading
            const taskId = taskData.task_id;
            const taskRow = document.querySelector(`[data-task-id="${taskId}"]`).closest('tr');
            
            // Update task title
            const titleCell = taskRow.querySelector('td:first-child strong');
            titleCell.textContent = taskData.title;
            
            // Update priority badge
            const priorityCell = taskRow.querySelector('td:nth-child(4) .badge');
            priorityCell.className = `badge badge-${taskData.priority}`;
            priorityCell.textContent = taskData.priority.charAt(0).toUpperCase() + taskData.priority.slice(1);
            
            // Update due date
            const dueDateCell = taskRow.querySelector('td:nth-child(5)');
            if (taskData.due_date) {
                const date = new Date(taskData.due_date);
                dueDateCell.textContent = date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
            } else {
                dueDateCell.textContent = '-';
            }

            // Update assigned user
            const assignedCell = taskRow.querySelector('td:nth-child(6)');
            const assignedSelect = document.getElementById('edit_assigned_to');
            const selectedOption = assignedSelect.options[assignedSelect.selectedIndex];
            assignedCell.textContent = selectedOption.value ? selectedOption.text : 'Unassigned';
            
            // Handle description display - add if doesn't exist, update if exists
            let descCell = taskRow.querySelector('td:first-child small');
            if (taskData.description && taskData.description.trim()) {
                if (!descCell) {
                    // Create description element if it doesn't exist
                    const titleCell = taskRow.querySelector('td:first-child');
                    const br = document.createElement('br');
                    const small = document.createElement('small');
                    small.className = 'text-muted';
                    titleCell.appendChild(br);
                    titleCell.appendChild(small);
                    descCell = small;
                }
                const desc = taskData.description;
                descCell.textContent = desc.length > 50 ? desc.substring(0, 50) + '...' : desc;
            } else if (descCell) {
                // Remove description if empty
                const br = descCell.previousElementSibling;
                if (br && br.tagName === 'BR') br.remove();
                descCell.remove();
            }
        } else {
            showAlert(data.message || 'Error updating task', 'danger');
        }
    })
    .catch(error => {
        showAlert('Error updating task', 'danger');
        console.error('Error:', error);
    });
});

// Helper functions
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the page content
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<style>
.badge-low { 
    background-color: #6c757d;
    color: #fff;
}
.badge-medium { 
    background-color: #0dcaf0;
    color: #000;
}
.badge-high { 
    background-color: #fd7e14;
    color: #fff;
}
.badge {
    padding: 0.35em 0.65em;
    font-size: 0.85em;
    font-weight: 500;
    display: inline-block;
}
</style>

<?php require_once 'components/footer.php'; ?>
