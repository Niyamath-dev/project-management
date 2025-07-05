<?php
$pageTitle = 'Dashboard';
require_once 'components/header.php';
require_once 'includes/db.php';

$db = new Database();

// Get dashboard statistics
$stats = [
    'total_projects' => 0,
    'total_tasks' => 0,
    'completed_tasks' => 0,
    'pending_tasks' => 0,
    'overdue_tasks' => 0
];

// Get total projects for current user
$db->query('SELECT COUNT(*) as count FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$stats['total_projects'] = $result ? $result->count : 0;

// Get total tasks
$db->query('SELECT COUNT(*) as count FROM tasks WHERE project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$stats['total_tasks'] = $result ? $result->count : 0;

// Get completed tasks
$db->query('SELECT COUNT(*) as count FROM tasks WHERE status = "completed" AND project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$stats['completed_tasks'] = $result ? $result->count : 0;

// Get pending tasks
$stats['pending_tasks'] = $stats['total_tasks'] - $stats['completed_tasks'];

// Get overdue tasks
$db->query('SELECT COUNT(*) as count FROM tasks WHERE due_date < CURDATE() AND status != "completed" AND project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$stats['overdue_tasks'] = $result ? $result->count : 0;

// Get recent projects
$db->query('SELECT p.*, u.username as created_by_name FROM projects p 
           LEFT JOIN users u ON p.created_by = u.id 
           WHERE p.created_by = :user_id OR p.id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)
           ORDER BY p.created_at DESC LIMIT 5');
$db->bind(':user_id', $currentUser['id']);
$recent_projects = $db->resultset();

// Get recent tasks
$db->query('SELECT t.*, p.title as project_title, u.username as assigned_to_name 
           FROM tasks t 
           LEFT JOIN projects p ON t.project_id = p.id 
           LEFT JOIN users u ON t.assigned_to = u.id 
           WHERE t.project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))
           ORDER BY t.created_at DESC LIMIT 10');
$db->bind(':user_id', $currentUser['id']);
$recent_tasks = $db->resultset();
?>

<!-- Dashboard Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
        <div class="stat-label">Total Projects</div>
    </div>
    <div class="stat-card success">
        <div class="stat-number"><?php echo $stats['completed_tasks']; ?></div>
        <div class="stat-label">Completed Tasks</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-number"><?php echo $stats['pending_tasks']; ?></div>
        <div class="stat-label">Pending Tasks</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-number"><?php echo $stats['overdue_tasks']; ?></div>
        <div class="stat-label">Overdue Tasks</div>
    </div>
</div>

<!-- Welcome Message -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">
            <i class="fas fa-hand-wave text-warning"></i>
            Welcome back, <?php echo htmlspecialchars($currentUser['username']); ?>!
        </h5>
        <p class="card-text">Here's what's happening with your projects today.</p>
    </div>
</div>

<div class="row">
    <!-- Recent Projects -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-folder text-primary"></i>
                    Recent Projects
                </h6>
                <a href="projects.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_projects)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No projects yet. <a href="projects.php">Create your first project</a></p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_projects as $project): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($project->title); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <?php echo htmlspecialchars(substr($project->description, 0, 100)); ?>
                                            <?php if (strlen($project->description) > 100) echo '...'; ?>
                                        </p>
                                        <small class="text-muted">
                                            Created by <?php echo htmlspecialchars($project->created_by_name); ?> 
                                            on <?php echo date('M j, Y', strtotime($project->created_at)); ?>
                                        </small>
                                    </div>
                                    <a href="project-detail.php?id=<?php echo $project->id; ?>" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Tasks -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-tasks text-success"></i>
                    Recent Tasks
                </h6>
                <a href="tasks.php" class="btn btn-sm btn-success">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_tasks)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No tasks yet. <a href="tasks.php">Create your first task</a></p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_tasks as $task): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($task->title); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            Project: <?php echo htmlspecialchars($task->project_title); ?>
                                        </p>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge badge-<?php echo str_replace('_', '-', $task->status); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $task->status)); ?>
                                            </span>
                                            <span class="badge badge-<?php echo $task->priority; ?>">
                                                <?php echo ucfirst($task->priority); ?>
                                            </span>
                                            <?php if ($task->due_date): ?>
                                                <small class="text-muted">
                                                    Due: <?php echo date('M j', strtotime($task->due_date)); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
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

<!-- Quick Actions -->
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-bolt text-warning"></i>
            Quick Actions
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="text-center">
                    <?php if ($auth->hasPermission('create_project')): ?>
                        <a href="projects.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-plus-circle"></i><br>
                            <small>New Project</small>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary w-100" disabled title="Owner access required">
                            <i class="fas fa-plus-circle"></i><br>
                            <small>New Project</small>
                        </button>
                        <small class="text-muted d-block mt-2">Owner access required</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="text-center">
                    <a href="tasks.php" class="btn btn-outline-success w-100">
                        <i class="fas fa-plus"></i><br>
                        <small>New Task</small>
                    </a>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="text-center">
                    <?php if ($auth->hasPermission('invite_team')): ?>
                        <a href="team.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-user-plus"></i><br>
                            <small>Invite Team</small>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary w-100" disabled title="Owner access required">
                            <i class="fas fa-user-plus"></i><br>
                            <small>Invite Team</small>
                        </button>
                        <small class="text-muted d-block mt-2">Owner access required</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="text-center">
                    <a href="reports.php" class="btn btn-outline-warning w-100">
                        <i class="fas fa-chart-bar"></i><br>
                        <small>View Reports</small>
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($auth->isCurrentUserMember()): ?>
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i>
            <strong>Member Access:</strong> Some features require owner permissions. Create your first project to become an owner and unlock all features.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'components/footer.php'; ?>
