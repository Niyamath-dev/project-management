<?php
$pageTitle = 'Settings';
require_once 'components/header.php';
require_once 'includes/db.php';

$db = new Database();
$message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_settings') {
    $email = trim($_POST['email']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    
    // Validate current password
    $db->query('SELECT password FROM users WHERE id = :user_id');
    $db->bind(':user_id', $currentUser['id']);
    $user = $db->single();
    
    if (!password_verify($current_password, $user->password)) {
        $message = '<div class="alert alert-danger">Current password is incorrect.</div>';
    } else if (!empty($new_password) && $new_password != $confirm_password) {
        $message = '<div class="alert alert-danger">New passwords do not match.</div>';
    } else {
        // Update settings
        try {
            if (!empty($new_password)) {
                // Update password if provided
                $db->query('UPDATE users SET email = :email, password = :password, notifications = :notifications WHERE id = :user_id');
                $db->bind(':password', password_hash($new_password, PASSWORD_DEFAULT));
            } else {
                // Update without changing password
                $db->query('UPDATE users SET email = :email, notifications = :notifications WHERE id = :user_id');
            }
        } catch (Exception $e) {
            // Fallback for databases without notifications column
            if (!empty($new_password)) {
                $db->query('UPDATE users SET email = :email, password = :password WHERE id = :user_id');
                $db->bind(':password', password_hash($new_password, PASSWORD_DEFAULT));
            } else {
                $db->query('UPDATE users SET email = :email WHERE id = :user_id');
            }
        }
        
        $db->bind(':email', $email);
        $db->bind(':notifications', $notifications);
        $db->bind(':user_id', $currentUser['id']);
        
        if ($db->execute()) {
            // Update session data
            $_SESSION['user']['email'] = $email;
            $message = '<div class="alert alert-success">Settings updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating settings.</div>';
        }
    }
}

// Get current user settings
try {
    $db->query('SELECT id, username, email, role,
               COALESCE(notifications, 1) as notifications, 
               profile_pic, created_at, updated_at 
               FROM users WHERE id = :user_id');
    $db->bind(':user_id', $currentUser['id']);
    $user = $db->single();
} catch (Exception $e) {
    // Fallback for databases without role/notifications/updated_at columns
    $db->query('SELECT id, username, email, created_at FROM users WHERE id = :user_id');
    $db->bind(':user_id', $currentUser['id']);
    $user = $db->single();
    // Add default values for missing columns
    $user->role = 'user';
    $user->notifications = 1;
    $user->profile_pic = null;
    $user->updated_at = null;
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>
            <i class="fas fa-cog text-primary"></i>
            Account Settings
        </h2>
        <p class="text-muted mb-0">Manage your account settings and preferences</p>
    </div>
</div>

<?php echo $message; ?>

<!-- Settings Form -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-cog"></i>
                    General Settings
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <!-- Email -->
                    <div class="form-group mb-4">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($user->email); ?>">
                        <small class="form-text text-muted">This email will be used for notifications and account recovery.</small>
                    </div>
                    
                    <!-- Password Change -->
                    <div class="form-group mb-4">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <small class="form-text text-muted">Required to save any changes.</small>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="8">
                                <small class="form-text text-muted">Leave blank to keep current password.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="8">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preferences -->
                    <div class="form-group mb-4">
                        <label class="form-label d-block">Preferences</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="notifications" name="notifications" value="1"
                                   <?php echo $user->notifications ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notifications">
                                Receive email notifications for project updates and tasks
                            </label>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="profile.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Account Actions -->
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
                        <h6 class="mb-1">Delete Account</h6>
                        <p class="text-muted mb-0">Permanently delete your account and all associated data.</p>
                    </div>
                    <button class="btn btn-outline-danger" onclick="confirmDeleteAccount()">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone and will delete all your projects and tasks.')) {
        window.location.href = 'users.php?delete=' + <?php echo $currentUser['id']; ?>;
    }
}
</script>

<style>
.form-label {
    font-weight: 600;
    color: #495057;
}

.card-header {
    border-bottom: 2px solid #e9ecef;
}

.form-check-label {
    font-weight: normal;
}
</style>

<?php require_once 'components/footer.php'; ?>
