<?php
$pageTitle = 'User Management';
require_once 'components/header.php';

// Only admins can access this page
if (!$auth->isCurrentUserAdmin()) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$message = '';

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    // Don't allow deleting yourself
    if ($user_id != $currentUser['id']) {
        $db->query('DELETE FROM users WHERE id = :id');
        $db->bind(':id', $user_id);
        
        if ($db->execute()) {
            $message = '<div class="alert alert-success">User deleted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error deleting user.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">You cannot delete your own account.</div>';
    }
}

// Get all users
$users = $auth->getAllUsers();
?>

<?php echo $message; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">User Management</h1>
        <button class="btn btn-primary" data-modal="addUserModal">
            <i class="fas fa-user-plus"></i> Add New User
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($user->username); ?>
                                    <?php if ($user->id == $currentUser['id']): ?>
                                        <span class="badge bg-info ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user->email); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user->role === 'admin' ? 'primary' : 'secondary'; ?>">
                                        <?php echo ucfirst($user->role); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user->created_at)); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editUser(<?php echo $user->id; ?>, '<?php echo htmlspecialchars($user->username); ?>', '<?php echo htmlspecialchars($user->email); ?>', '<?php echo $user->role; ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($user->id !== $currentUser['id']): ?>
                                        <a href="?delete=<?php echo $user->id; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal" id="editUserModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit text-primary"></i>
                    Edit User
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="form-group mb-3">
                        <label for="edit_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-control" id="edit_role" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus text-primary"></i>
                    Add New User
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-control" id="role" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit user function
function editUser(userId, username, email, role) {
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_password').value = '';
    
    // Show modal
    document.getElementById('editUserModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Handle edit user form submission
document.getElementById('editUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    try {
        const response = await fetch('api/admin-users.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(formData).toString()
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('User updated successfully!', 'success');
            document.getElementById('editUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reload page to show updated user
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(result.message || 'Error updating user', 'error');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-save"></i> Update User';
    }
});

// Handle add user form submission
document.getElementById('addUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    try {
        const response = await fetch('api/admin-users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(formData).toString()
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('User added successfully!', 'success');
            document.getElementById('addUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            e.target.reset();
            // Reload page to show new user
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(result.message || 'Error adding user', 'error');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-plus"></i> Add User';
    }
});
</script>

<?php require_once 'components/footer.php'; ?>
