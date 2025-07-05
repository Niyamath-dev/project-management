<?php
$pageTitle = 'Team';
require_once 'components/header.php';
require_once 'includes/db.php';

$db = new Database();
$message = '';

// Handle team member invitation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'invite_member') {
    $project_id = intval($_POST['project_id']);
    $user_email = trim($_POST['user_email']);
    $role = $_POST['role'] ?? 'member';
    
    if (empty($user_email) || $project_id <= 0) {
        $message = '<div class="alert alert-danger">Email and project are required.</div>';
    } else {
        // Check if user exists
        $db->query('SELECT id FROM users WHERE email = :email');
        $db->bind(':email', $user_email);
        $user = $db->single();
        
        if (!$user) {
            $message = '<div class="alert alert-danger">User with this email does not exist.</div>';
        } else {
            // Check if user owns the project
            $db->query('SELECT id FROM projects WHERE id = :id AND created_by = :user_id');
            $db->bind(':id', $project_id);
            $db->bind(':user_id', $currentUser['id']);
            $project = $db->single();
            
            if (!$project) {
                $message = '<div class="alert alert-danger">You can only invite members to projects you own.</div>';
            } else {
                // Check if user is already a member
                $db->query('SELECT project_id FROM project_members WHERE project_id = :project_id AND user_id = :user_id');
                $db->bind(':project_id', $project_id);
                $db->bind(':user_id', $user->id);
                $existing = $db->single();
                
                if ($existing) {
                    $message = '<div class="alert alert-warning">User is already a member of this project.</div>';
                } else {
                    // Add member to project
                    $db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (:project_id, :user_id, :role)');
                    $db->bind(':project_id', $project_id);
                    $db->bind(':user_id', $user->id);
                    $db->bind(':role', $role);
                    
                    if ($db->execute()) {
                        $message = '<div class="alert alert-success">Team member invited successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error inviting team member.</div>';
                    }
                }
            }
        }
    }
}

// Handle member removal
if (isset($_GET['remove']) && isset($_GET['project'])) {
    $member_id = intval($_GET['remove']);
    $project_id = intval($_GET['project']);
    
    // Check if user owns the project
    $db->query('SELECT id FROM projects WHERE id = :id AND created_by = :user_id');
    $db->bind(':id', $project_id);
    $db->bind(':user_id', $currentUser['id']);
    $project = $db->single();
    
    if ($project) {
        $db->query('DELETE FROM project_members WHERE project_id = :project_id AND user_id = :user_id');
        $db->bind(':project_id', $project_id);
        $db->bind(':user_id', $member_id);
        
        if ($db->execute()) {
            $message = '<div class="alert alert-success">Team member removed successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error removing team member.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">You can only remove members from projects you own.</div>';
    }
}

// Get user's projects for invitation dropdown
$db->query('SELECT id, title FROM projects WHERE created_by = :user_id ORDER BY title');
$db->bind(':user_id', $currentUser['id']);
$owned_projects = $db->resultset();

// Get all team members across all projects
$db->query('SELECT pm.*, p.title as project_title, u.username, u.email, p.created_by as project_owner
           FROM project_members pm
           JOIN projects p ON pm.project_id = p.id
           JOIN users u ON pm.user_id = u.id
           WHERE p.created_by = :user_id OR p.id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)
           ORDER BY p.title, u.username');
$db->bind(':user_id', $currentUser['id']);
$team_members = $db->resultset();

// Group members by project
$projects_with_members = [];
foreach ($team_members as $member) {
    $projects_with_members[$member->project_title][] = $member;
}
?>

<?php echo $message; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-users text-primary"></i>
        Team Management
    </h2>
    <?php if (!empty($owned_projects)): ?>
        <button class="btn btn-primary" data-modal="inviteModal">
            <i class="fas fa-user-plus"></i> Invite Member
        </button>
    <?php endif; ?>
</div>

<!-- Team Overview -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_unique(array_column($team_members, 'user_id'))); ?></div>
            <div class="stat-label">Total Team Members</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-number"><?php echo count($owned_projects); ?></div>
            <div class="stat-label">Projects You Own</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-number"><?php echo count(array_unique(array_column($team_members, 'project_id'))); ?></div>
            <div class="stat-label">Projects with Teams</div>
        </div>
    </div>
</div>

<!-- Team Members by Project -->
<?php if (empty($projects_with_members)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-users fa-4x text-muted mb-4"></i>
            <h4 class="text-muted">No Team Members Yet</h4>
            <p class="text-muted mb-4">Start collaborating by inviting team members to your projects.</p>
            <?php if (!empty($owned_projects)): ?>
                <button class="btn btn-primary btn-lg" data-modal="inviteModal">
                    <i class="fas fa-user-plus"></i> Invite Your First Team Member
                </button>
            <?php else: ?>
                <p class="text-muted">Create a project first to start inviting team members.</p>
                <a href="projects.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus"></i> Create Project
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($projects_with_members as $project_title => $members): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-folder text-primary"></i>
                    <?php echo htmlspecialchars($project_title); ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($members); ?> members</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php echo $auth->getUserAvatar($member->user_id, $member->username, 32, 'me-2'); ?>
                                            <div>
                                                <?php echo htmlspecialchars($member->username); ?>
                                                <?php if ($member->user_id == $currentUser['id']): ?>
                                                    <span class="badge bg-info ms-1">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($member->email); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $member->role === 'owner' ? 'primary' : 'secondary'; ?>">
                                            <?php echo ucfirst($member->role); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($member->joined_at)); ?></td>
                                    <td>
                                        <?php if ($member->project_owner == $currentUser['id'] && $member->user_id != $currentUser['id']): ?>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="editMember(<?php echo $member->user_id; ?>, '<?php echo htmlspecialchars($member->username); ?>', '<?php echo htmlspecialchars($member->email); ?>', '<?php echo $member->role; ?>', <?php echo $member->project_id; ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="?remove=<?php echo $member->user_id; ?>&project=<?php echo $member->project_id; ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to remove this member?')">
                                                    <i class="fas fa-user-minus"></i> Remove
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Invite Member Modal -->
<?php if (!empty($owned_projects)): ?>
<div class="modal" id="inviteModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus text-primary"></i>
                    Invite Team Member
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="invite_member">
                    
                    <div class="form-group mb-3">
                        <label for="project_id" class="form-label">Project *</label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="">Select Project</option>
                            <?php foreach ($owned_projects as $project): ?>
                                <option value="<?php echo $project->id; ?>">
                                    <?php echo htmlspecialchars($project->title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="user_email" class="form-label">User Email *</label>
                        <input type="email" class="form-control" id="user_email" name="user_email" 
                               placeholder="Enter user's email address" required>
                        <div class="form-text">The user must already have an account in the system.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="member">Member</option>
                            <option value="owner">Owner</option>
                        </select>
                        <div class="form-text">Owners can manage project settings and invite/remove members.</div>
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
<?php endif; ?>

<!-- Edit Member Modal -->
<div class="modal" id="editMemberModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit text-primary"></i>
                    Edit Team Member
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editMemberForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_member_id" name="member_id">
                    <input type="hidden" id="edit_project_id" name="project_id">
                    
                    <div class="form-group mb-3">
                        <label for="edit_member_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="edit_member_username" name="username" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_member_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_member_email" name="email" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_member_role" class="form-label">Project Role</label>
                        <select class="form-control" id="edit_member_role" name="role">
                            <option value="member">Member</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_member_password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="edit_member_password" name="password">
                        <small class="form-text text-muted">Only fill this if you want to change the user's password.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit member function
function editMember(userId, username, email, role, projectId) {
    document.getElementById('edit_member_id').value = userId;
    document.getElementById('edit_project_id').value = projectId;
    document.getElementById('edit_member_username').value = username;
    document.getElementById('edit_member_email').value = email;
    document.getElementById('edit_member_role').value = role;
    document.getElementById('edit_member_password').value = '';
    
    // Show modal
    document.getElementById('editMemberModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Handle edit member form submission
document.getElementById('editMemberForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    try {
        const response = await fetch('api/users.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(formData).toString()
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Member updated successfully!', 'success');
            document.getElementById('editMemberModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reload page to show updated member
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(result.message || 'Error updating member', 'error');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-save"></i> Update Member';
    }
});
</script>

<?php require_once 'components/footer.php'; ?>
