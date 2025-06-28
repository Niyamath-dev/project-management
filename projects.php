<?php
$pageTitle = 'Projects';
require_once 'components/header.php';
require_once 'includes/db.php';

$db = new Database();
$message = '';

// Handle project creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_project') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (empty($title)) {
        $message = '<div class="alert alert-danger">Project title is required.</div>';
    } else {
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
            
            $message = '<div class="alert alert-success">Project created successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error creating project.</div>';
        }
    }
}

// Handle project deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $project_id = $_GET['delete'];
    
    // Check if user owns the project
    $db->query('SELECT id FROM projects WHERE id = :id AND created_by = :user_id');
    $db->bind(':id', $project_id);
    $db->bind(':user_id', $currentUser['id']);
    $project = $db->single();
    
    if ($project) {
        $db->query('DELETE FROM projects WHERE id = :id');
        $db->bind(':id', $project_id);
        if ($db->execute()) {
            $message = '<div class="alert alert-success">Project deleted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error deleting project.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">You can only delete projects you own.</div>';
    }
}

// Get user's projects
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
?>

<?php echo $message; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-folder text-primary"></i>
        My Projects
    </h2>
    <button class="btn btn-primary" data-modal="projectModal">
        <i class="fas fa-plus"></i> New Project
    </button>
</div>

<!-- Projects Grid -->
<?php if (empty($projects)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-folder-open fa-4x text-muted mb-4"></i>
            <h4 class="text-muted">No Projects Yet</h4>
            <p class="text-muted mb-4">Create your first project to get started with managing your tasks and team.</p>
            <button class="btn btn-primary btn-lg" data-modal="projectModal">
                <i class="fas fa-plus"></i> Create Your First Project
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($projects as $project): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 searchable-item">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($project->title); ?></h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="project-detail.php?id=<?php echo $project->id; ?>">
                                    <i class="fas fa-eye"></i> View Details
                                </a></li>
                                <?php if ($project->created_by == $currentUser['id']): ?>
                                    <li><a class="dropdown-item" href="edit-project.php?id=<?php echo $project->id; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="?delete=<?php echo $project->id; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this project? This will also delete all associated tasks.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted">
                            <?php 
                            $description = htmlspecialchars($project->description);
                            echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                            ?>
                        </p>
                        
                        <!-- Project Stats -->
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="stat-number text-primary"><?php echo $project->task_count; ?></div>
                                <div class="stat-label">Tasks</div>
                            </div>
                            <div class="col-4">
                                <div class="stat-number text-success"><?php echo $project->completed_tasks; ?></div>
                                <div class="stat-label">Completed</div>
                            </div>
                            <div class="col-4">
                                <div class="stat-number text-info"><?php echo $project->member_count; ?></div>
                                <div class="stat-label">Members</div>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <?php 
                        $progress = $project->task_count > 0 ? ($project->completed_tasks / $project->task_count) * 100 : 0;
                        ?>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $progress; ?>%" 
                                 aria-valuenow="<?php echo $progress; ?>" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Created by <?php echo htmlspecialchars($project->created_by_name); ?>
                            </small>
                            <small class="text-muted">
                                <?php echo date('M j, Y', strtotime($project->created_at)); ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="project-detail.php?id=<?php echo $project->id; ?>" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-arrow-right"></i> View Project
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Create Project Modal -->
<div class="modal" id="projectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary"></i>
                    Create New Project
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="" id="projectForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_project">
                    
                    <div class="form-group">
                        <label for="title" class="form-label">Project Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               placeholder="Enter project title">
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Describe your project..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'components/footer.php'; ?>
