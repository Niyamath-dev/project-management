<?php
$pageTitle = 'Project Details';
require_once 'components/header.php';
require_once 'includes/db.php';

$db = new Database();
$message = '';

// Get project ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: projects.php');
    exit();
}

$project_id = $_GET['id'];

// Get project details
$db->query('SELECT p.*, u.username as created_by_name,
           (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
           (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = "completed") as completed_tasks,
           (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = "in_progress") as in_progress_tasks,
           (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = "todo") as todo_tasks,
           (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count
           FROM projects p 
           LEFT JOIN users u ON p.created_by = u.id 
           WHERE p.id = :project_id AND (p.created_by = :user_id OR p.id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))');
$db->bind(':project_id', $project_id);
$db->bind(':user_id', $currentUser['id']);
$project = $db->single();

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Get project tasks
$db->query('SELECT t.*, u.username as assigned_to_name 
           FROM tasks t 
           LEFT JOIN users u ON t.assigned_to = u.id 
           WHERE t.project_id = :project_id 
           ORDER BY t.created_at DESC');
$db->bind(':project_id', $project_id);
$tasks = $db->resultset();

// Get project members
$db->query('SELECT pm.*, u.username, u.email 
           FROM project_members pm 
           JOIN users u ON pm.user_id = u.id 
           WHERE pm.project_id = :project_id 
           ORDER BY pm.role DESC, u.username ASC');
$db->bind(':project_id', $project_id);
$members = $db->resultset();

// Calculate progress
$progress = $project->task_count > 0 ? ($project->completed_tasks / $project->task_count) * 100 : 0;
?>

<!-- Project Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($project->title); ?></li>
            </ol>
        </nav>
        <h1 class="h2 mb-2"><?php echo htmlspecialchars($project->title); ?></h1>
        <p class="text-muted mb-0">
            Created by <?php echo htmlspecialchars($project->created_by_name); ?> on 
            <?php echo date('F j, Y', strtotime($project->created_at)); ?>
        </p>
    </div>
    <div class="btn-group">
            <?php if ($project->created_by == $currentUser['id']): ?>
            <button class="btn btn-outline-primary" data-modal="editProjectModal">
                <i class="fas fa-edit"></i> Edit Project
            </button>
            <button class="btn btn-outline-secondary" data-modal="inviteMemberModal">
                <i class="fas fa-user-plus"></i> Invite Member
            </button>
            <?php endif; ?>
        <?php if ($project->created_by == $currentUser['id']): ?>
            <button class="btn btn-primary" data-modal="taskModal">
                <i class="fas fa-plus"></i> New Task
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Project Stats -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $project->task_count; ?></h4>
                        <p class="mb-0">Total Tasks</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-tasks fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $project->completed_tasks; ?></h4>
                        <p class="mb-0">Completed</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $project->in_progress_tasks; ?></h4>
                        <p class="mb-0">In Progress</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $project->member_count; ?></h4>
                        <p class="mb-0">Team Members</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Bar -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Project Progress</h6>
            <span class="text-muted"><?php echo round($progress, 1); ?>%</span>
        </div>
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success" role="progressbar" 
                 style="width: <?php echo $progress; ?>%" 
                 aria-valuenow="<?php echo $progress; ?>" 
                 aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</div>

<!-- Project Description -->
<?php if (!empty($project->description)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Project Description</h5>
    </div>
    <div class="card-body">
        <p class="mb-0"><?php echo nl2br(htmlspecialchars($project->description)); ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Tasks and Team Tabs -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="projectTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" 
                        type="button" role="tab" aria-controls="tasks" aria-selected="true">
                    <i class="fas fa-tasks"></i> Tasks (<?php echo $project->task_count; ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="team-tab" data-bs-toggle="tab" data-bs-target="#team" 
                        type="button" role="tab" aria-controls="team" aria-selected="false">
                    <i class="fas fa-users"></i> Team (<?php echo $project->member_count; ?>)
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="projectTabsContent">
            <!-- Tasks Tab -->
            <div class="tab-pane fade show active" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
                <?php if (empty($tasks)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Tasks Yet</h5>
                        <p class="text-muted mb-3">Create your first task to get started.</p>
                        <?php if ($project->created_by == $currentUser['id']): ?>
                            <button class="btn btn-primary" data-modal="taskModal">
                                <i class="fas fa-plus"></i> Create First Task
                            </button>
                        <?php else: ?>
                            <p class="text-muted">Only the project owner can create tasks.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Assigned To</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($task->title); ?></strong>
                                            <?php if (!empty($task->description)): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($task->description, 0, 100)); ?><?php echo strlen($task->description) > 100 ? '...' : ''; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch ($task->status) {
                                                case 'completed':
                                                    $statusClass = 'success';
                                                    break;
                                                case 'in_progress':
                                                    $statusClass = 'warning';
                                                    break;
                                                default:
                                                    $statusClass = 'secondary';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $task->status)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $priorityClass = '';
                                            switch ($task->priority) {
                                                case 'high':
                                                    $priorityClass = 'warning';
                                                    break;
                                                case 'medium':
                                                    $priorityClass = 'info';
                                                    break;
                                                default:
                                                    $priorityClass = 'light text-dark';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $priorityClass; ?>">
                                                <?php echo ucfirst($task->priority); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $task->assigned_to_name ? htmlspecialchars($task->assigned_to_name) : '<span class="text-muted">Unassigned</span>'; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($task->due_date) {
                                                $due_date = new DateTime($task->due_date);
                                                $now = new DateTime();
                                                $is_overdue = $due_date < $now && $task->status != 'completed';
                                                echo '<span class="' . ($is_overdue ? 'text-danger' : '') . '">';
                                                echo $due_date->format('M j, Y');
                                                echo '</span>';
                                            } else {
                                                echo '<span class="text-muted">No due date</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary btn-sm" title="Edit Task" onclick="editTask(<?php echo $task->id; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($project->created_by == $currentUser['id']): ?>
                                                    <button class="btn btn-outline-danger btn-sm" title="Delete Task" onclick="deleteTask(<?php echo $task->id; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Team Tab -->
            <div class="tab-pane fade" id="team" role="tabpanel" aria-labelledby="team-tab">
                <?php if (empty($members)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Team Members</h5>
                        <p class="text-muted mb-3">Add team members to collaborate on this project.</p>
                        <p class="text-muted">Team members are managed through the Team section.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($members as $member): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="avatar-circle bg-primary text-white mb-3 mx-auto">
                                            <?php echo strtoupper(substr($member->username, 0, 2)); ?>
                                        </div>
                                        <h6 class="card-title"><?php echo htmlspecialchars($member->username); ?></h6>
                                        <p class="card-text text-muted small"><?php echo htmlspecialchars($member->email); ?></p>
                                        <span class="badge bg-<?php echo $member->role == 'owner' ? 'primary' : 'secondary'; ?>">
                                            <?php echo ucfirst($member->role); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div class="modal" id="taskModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary"></i>
                    Create New Task
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="tasks.php" id="taskForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_task">
                    <input type="hidden" name="project_id" value="<?php echo $project->id; ?>">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="task_title" class="form-label">Task Title *</label>
                                <input type="text" class="form-control" id="task_title" name="title" required 
                                       placeholder="Enter task title">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-control" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="task_description" class="form-label">Description</label>
                        <textarea class="form-control" id="task_description" name="description" rows="3" 
                                  placeholder="Describe the task..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="assigned_to" class="form-label">Assign To</label>
                                <select class="form-control" id="assigned_to" name="assigned_to">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?php echo $member->user_id; ?>">
                                            <?php echo htmlspecialchars($member->username); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date">
                            </div>
                        </div>
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

<!-- Add Member Modal -->
<div class="modal" id="addMemberModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus text-primary"></i>
                    Add Team Member
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addMemberForm">
                <div class="modal-body">
                    <input type="hidden" id="member_project_id" name="project_id" value="<?php echo $project->id; ?>">
                    <input type="hidden" name="action" value="add_member">
                    
                    <div class="form-group">
                        <label for="member_email" class="form-label">User Email *</label>
                        <input type="email" class="form-control" id="member_email" name="email" required 
                               placeholder="Enter user email address">
                        <small class="form-text text-muted">Enter the email address of the user you want to add to this project.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="member_role" class="form-label">Role</label>
                        <select class="form-control" id="member_role" name="role">
                            <option value="member">Member</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Invite Member Modal -->
<div class="modal" id="inviteMemberModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus text-primary"></i>
                    Invite Team Member
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="inviteMemberForm">
                <div class="modal-body">
                    <input type="hidden" name="project_id" value="<?php echo $project->id; ?>">
                    
                    <div class="form-group mb-3">
                        <label for="invite_email" class="form-label">User Email *</label>
                        <input type="email" class="form-control" id="invite_email" name="email" required 
                               placeholder="Enter user's email address">
                        <small class="form-text text-muted">Enter the email address of the user you want to invite.</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="invite_role" class="form-label">Role</label>
                        <select class="form-control" id="invite_role" name="role">
                            <option value="member">Member</option>
                            <option value="owner">Owner</option>
                        </select>
                        <small class="form-text text-muted">Owners can manage project settings and team members.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal" id="editProjectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-primary"></i>
                    Edit Project
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editProjectForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_project_id" value="<?php echo $project->id; ?>">
                    
                    <div class="form-group">
                        <label for="edit_project_title" class="form-label">Project Title *</label>
                        <input type="text" class="form-control" id="edit_project_title" name="title" required 
                               value="<?php echo htmlspecialchars($project->title); ?>" placeholder="Enter project title">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_project_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_project_description" name="description" rows="4" 
                                  placeholder="Describe your project..."><?php echo htmlspecialchars($project->description); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Edit Task Modal -->
<div class="modal" id="editTaskModal">
    <div class="modal-dialog modal-lg">
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
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="edit_title" class="form-label">Task Title *</label>
                                <input type="text" class="form-control" id="edit_title" name="title" required 
                                       placeholder="Enter task title">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_priority" class="form-label">Priority</label>
                                <select class="form-control" id="edit_priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" 
                                  placeholder="Describe the task..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_assigned_to" class="form-label">Assign To</label>
                                <select class="form-control" id="edit_assigned_to" name="assigned_to">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?php echo $member->user_id; ?>">
                                            <?php echo htmlspecialchars($member->username); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="edit_due_date" name="due_date">
                            </div>
                        </div>
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
// Handle invite member form submission
document.getElementById('inviteMemberForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    try {
        const response = await fetch('api/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(formData).toString()
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Member invited successfully!', 'success');
            document.getElementById('inviteMemberModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            e.target.reset();
            // Reload page to show new member
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(result.message || 'Error inviting member', 'error');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send Invitation';
    }
});
</script>

<style>
.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>

<?php require_once 'components/footer.php'; ?>
