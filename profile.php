<?php
$pageTitle = 'Profile';
require_once 'components/header.php';
require_once 'includes/db.php';

$db = new Database();
$message = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_profile_pic') {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['profile_pic']['type'];
        $file_size = $_FILES['profile_pic']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $message = '<div class="alert alert-danger">Only JPEG, PNG, and GIF images are allowed.</div>';
        } elseif ($file_size > $max_size) {
            $message = '<div class="alert alert-danger">File size must be less than 5MB.</div>';
        } else {
            $upload_dir = 'uploads/profile_pics/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $currentUser['id'] . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                try {
                    $db->query('SELECT profile_pic FROM users WHERE id = :id');
                    $db->bind(':id', $currentUser['id']);
                    $old_user = $db->single();
                    if ($old_user && $old_user->profile_pic && file_exists($old_user->profile_pic)) {
                        unlink($old_user->profile_pic);
                    }
                } catch (Exception $e) {
                    // Column doesn't exist yet, skip cleanup
                }
                
                // Update database
                try {
                    $db->query('UPDATE users SET profile_pic = :profile_pic WHERE id = :id');
                    $db->bind(':profile_pic', $upload_path);
                    $db->bind(':id', $currentUser['id']);
                    
                    if ($db->execute()) {
                        $message = '<div class="alert alert-success">Profile picture updated successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error updating profile picture in database.</div>';
                    }
                } catch (Exception $e) {
                    $message = '<div class="alert alert-danger">Database error: Profile picture column may not exist. Please run the database migration.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Error uploading file.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-danger">Please select a file to upload.</div>';
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email)) {
        $message = '<div class="alert alert-danger">Username and email are required.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Please enter a valid email address.</div>';
    } else {
        // Check if email is already taken by another user
        $db->query('SELECT id FROM users WHERE email = :email AND id != :user_id');
        $db->bind(':email', $email);
        $db->bind(':user_id', $currentUser['id']);
        $existing_user = $db->single();
        
        if ($existing_user) {
            $message = '<div class="alert alert-danger">Email address is already taken.</div>';
        } else {
            $update_password = false;
            $hashed_password = '';
            
            // Check if password update is requested
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $message = '<div class="alert alert-danger">Current password is required to change password.</div>';
                } elseif (strlen($new_password) < 6) {
                    $message = '<div class="alert alert-danger">New password must be at least 6 characters long.</div>';
                } elseif ($new_password !== $confirm_password) {
                    $message = '<div class="alert alert-danger">New passwords do not match.</div>';
                } else {
                    // Verify current password
                    $db->query('SELECT password FROM users WHERE id = :id');
                    $db->bind(':id', $currentUser['id']);
                    $user = $db->single();
                    
                    if (!password_verify($current_password, $user->password)) {
                        $message = '<div class="alert alert-danger">Current password is incorrect.</div>';
                    } else {
                        $update_password = true;
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    }
                }
            }
            
            // Update profile if no errors
            if (empty($message)) {
                if ($update_password) {
                    $db->query('UPDATE users SET username = :username, email = :email, password = :password WHERE id = :id');
                    $db->bind(':password', $hashed_password);
                } else {
                    $db->query('UPDATE users SET username = :username, email = :email WHERE id = :id');
                }
                
                $db->bind(':username', $username);
                $db->bind(':email', $email);
                $db->bind(':id', $currentUser['id']);
                
                if ($db->execute()) {
                    // Update session data
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $currentUser['username'] = $username;
                    $currentUser['email'] = $email;
                    
                    $message = '<div class="alert alert-success">Profile updated successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error updating profile.</div>';
                }
            }
        }
    }
}

// Get user statistics
$db->query('SELECT COUNT(*) as count FROM projects WHERE created_by = :user_id');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$projects_created = $result ? $result->count : 0;

$db->query('SELECT COUNT(*) as count FROM tasks WHERE assigned_to = :user_id');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$tasks_assigned = $result ? $result->count : 0;

$db->query('SELECT COUNT(*) as count FROM tasks WHERE assigned_to = :user_id AND status = "completed"');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$tasks_completed = $result ? $result->count : 0;

$db->query('SELECT COUNT(*) as count FROM project_members WHERE user_id = :user_id');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$projects_member = $result ? $result->count : 0;

// Get user info
$db->query('SELECT * FROM users WHERE id = :id');
$db->bind(':id', $currentUser['id']);
$user_info = $db->single();
?>

<?php echo $message; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-user text-primary"></i>
        My Profile
    </h2>
</div>

<div class="row">
    <!-- Profile Information -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-edit text-primary"></i>
                    Edit Profile
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Change Password (Optional)</h6>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-text mb-3">
                        Leave password fields empty if you don't want to change your password.
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Profile Stats -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-pie text-success"></i>
                    Your Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <?php if (!empty($user_info->profile_pic) && file_exists($user_info->profile_pic)): ?>
                        <img src="<?php echo htmlspecialchars($user_info->profile_pic); ?>" 
                             alt="Profile Picture" 
                             class="rounded-circle profile-pic" 
                             style="width: 80px; height: 80px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                    <?php endif; ?>
                    <h5 class="mt-3 mb-1"><?php echo htmlspecialchars($currentUser['username']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                    
                    <!-- Profile Picture Upload -->
                    <button class="btn btn-sm btn-outline-primary" onclick="showModal('profilePicModal')">
                        <i class="fas fa-camera"></i> Change Photo
                    </button>
                </div>
                
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="stat-number text-primary"><?php echo $projects_created; ?></div>
                        <div class="stat-label">Projects Created</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-number text-info"><?php echo $projects_member; ?></div>
                        <div class="stat-label">Projects Joined</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-number text-warning"><?php echo $tasks_assigned; ?></div>
                        <div class="stat-label">Tasks Assigned</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-number text-success"><?php echo $tasks_completed; ?></div>
                        <div class="stat-label">Tasks Completed</div>
                    </div>
                </div>
                
                <?php if ($tasks_assigned > 0): ?>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Completion Rate</span>
                            <span class="small"><?php echo round(($tasks_completed / $tasks_assigned) * 100, 1); ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo ($tasks_completed / $tasks_assigned) * 100; ?>%"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Account Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle text-info"></i>
                    Account Information
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Member Since:</strong><br>
                    <span class="text-muted"><?php echo date('F j, Y', strtotime($user_info->created_at)); ?></span>
                </div>
                <div class="mb-3">
                    <strong>User ID:</strong><br>
                    <span class="text-muted">#<?php echo $currentUser['id']; ?></span>
                </div>
                <div class="mb-3">
                    <strong>Account Status:</strong><br>
                    <span class="badge bg-success">Active</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Clear password fields if new password is empty
document.getElementById('new_password').addEventListener('input', function() {
    if (this.value === '') {
        document.getElementById('current_password').value = '';
        document.getElementById('confirm_password').value = '';
    }
});
</script>

<!-- Profile Picture Upload Modal -->
<div class="modal" id="profilePicModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-camera text-primary"></i>
                    Change Profile Picture
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="upload_profile_pic">
                    
                    <div class="form-group">
                        <label for="profile_pic" class="form-label">Select Image</label>
                        <input type="file" class="form-control" id="profile_pic" name="profile_pic" 
                               accept="image/jpeg,image/png,image/gif" required>
                        <small class="form-text text-muted">
                            Allowed formats: JPEG, PNG, GIF. Maximum size: 5MB.
                        </small>
                    </div>
                    
                    <!-- Image Preview -->
                    <div class="mt-3 text-center d-none" id="imagePreview">
                        <img src="" alt="Preview" style="max-width: 200px; max-height: 200px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Photo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show modal function
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

// Image preview
document.getElementById('profile_pic').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    const file = e.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.querySelector('img').src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(file);
    } else {
        preview.classList.add('d-none');
    }
});

// Close modal when clicking close button or outside
document.querySelectorAll('.modal .btn-close, .modal .btn-secondary').forEach(button => {
    button.addEventListener('click', function() {
        const modal = this.closest('.modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
});

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
});
</script>

<?php require_once 'components/footer.php'; ?>
