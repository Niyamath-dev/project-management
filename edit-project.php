<?php
$pageTitle = 'Edit Project';
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

// Get project details and verify ownership
$db->query('SELECT p.*, u.username as created_by_name
           FROM projects p 
           LEFT JOIN users u ON p.created_by = u.id 
           WHERE p.id = :project_id AND p.created_by = :user_id');
$db->bind(':project_id', $project_id);
$db->bind(':user_id', $currentUser['id']);
$project = $db->single();

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Handle project update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_project') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (empty($title)) {
        $message = '<div class="alert alert-danger">Project title is required.</div>';
    } else {
        // Update project
        $db->query('UPDATE projects SET title = :title, description = :description, updated_at = NOW() WHERE id = :id');
        $db->bind(':title', $title);
        $db->bind(':description', $description);
        $db->bind(':id', $project_id);
        
        if ($db->execute()) {
            $message = '<div class="alert alert-success">Project updated successfully!</div>';
            // Refresh project data
            $db->query('SELECT p.*, u.username as created_by_name
                       FROM projects p 
                       LEFT JOIN users u ON p.created_by = u.id 
                       WHERE p.id = :project_id');
            $db->bind(':project_id', $project_id);
            $project = $db->single();
        } else {
            $message = '<div class="alert alert-danger">Error updating project.</div>';
        }
    }
}
?>

<?php echo $message; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                <li class="breadcrumb-item"><a href="project-detail.php?id=<?php echo $project->id; ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
        <h1 class="h2 mb-2">
            <i class="fas fa-edit text-primary"></i>
            Edit Project
        </h1>
        <p class="text-muted mb-0">
            Update your project details and settings
        </p>
    </div>
    <div class="btn-group">
        <a href="project-detail.php?id=<?php echo $project->id; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Project
        </a>
    </div>
</div>

<!-- Edit Project Form -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-folder text-primary"></i>
                    Project Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="editProjectForm">
                    <input type="hidden" name="action" value="update_project">
                    
                    <div class="form-group mb-4">
                        <label for="title" class="form-label">Project Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($project->title); ?>" 
                               placeholder="Enter project title">
                        <small class="form-text text-muted">Choose a clear, descriptive name for your project.</small>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="6" 
                                  placeholder="Describe your project goals, scope, and key details..."><?php echo htmlspecialchars($project->description); ?></textarea>
                        <small class="form-text text-muted">Provide a detailed description to help team members understand the project objectives.</small>
                    </div>
                    
                    <!-- Project Metadata -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Created By</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($project->created_by_name); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Created Date</label>
                                <input type="text" class="form-control" value="<?php echo date('F j, Y', strtotime($project->created_at)); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($project->updated_at)): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Last Updated</label>
                                <input type="text" class="form-control" value="<?php echo date('F j, Y g:i A', strtotime($project->updated_at)); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="project-detail.php?id=<?php echo $project->id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Project Actions -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0 text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Danger Zone
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Delete Project</h6>
                        <p class="text-muted mb-0">Permanently delete this project and all associated tasks. This action cannot be undone.</p>
                    </div>
                    <a href="projects.php?delete=<?php echo $project->id; ?>" 
                       class="btn btn-outline-danger"
                       onclick="return confirm('Are you sure you want to delete this project? This will permanently delete the project and all associated tasks. This action cannot be undone.')">
                        <i class="fas fa-trash"></i> Delete Project
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-label {
    font-weight: 600;
    color: #495057;
}

.card-header {
    border-bottom: 2px solid #e9ecef;
}

.btn-group .btn {
    border-radius: 0.375rem;
}

.btn-group .btn:not(:last-child) {
    margin-right: 0.5rem;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.breadcrumb {
    background-color: transparent;
    padding: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: ">";
    color: #6c757d;
}
</style>

<?php require_once 'components/footer.php'; ?>
