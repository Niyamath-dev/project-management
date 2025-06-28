// ProjectHub JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the application
    initializeApp();
});

function initializeApp() {
    // Initialize modals
    initializeModals();
    
    // Initialize forms
    initializeForms();
    
    // Initialize sidebar toggle for mobile
    initializeSidebar();
    
    // Initialize tooltips and other UI elements
    initializeUI();
}

// Modal functionality
function initializeModals() {
    // Get all modal triggers
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.modal .close, [data-dismiss="modal"]');

    // Open modal
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Close modal
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });

    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
}

// Form handling
function initializeForms() {
    // Project form
    const projectForm = document.getElementById('projectForm');
    if (projectForm) {
        projectForm.addEventListener('submit', handleProjectSubmit);
    }

    // Task form - only handle if it's an API form, not regular POST form
    const taskForm = document.getElementById('taskForm');
    if (taskForm && taskForm.getAttribute('method') !== 'POST') {
        taskForm.addEventListener('submit', handleTaskSubmit);
    }

    // Edit task form
    const editTaskForm = document.getElementById('editTaskForm');
    if (editTaskForm) {
        editTaskForm.addEventListener('submit', handleEditTaskSubmit);
    }

    // Edit project form
    const editProjectForm = document.getElementById('editProjectForm');
    if (editProjectForm) {
        editProjectForm.addEventListener('submit', handleEditProjectSubmit);
    }

    // Add member form
    const addMemberForm = document.getElementById('addMemberForm');
    if (addMemberForm) {
        addMemberForm.addEventListener('submit', handleAddMemberSubmit);
    }

    // Task status updates
    const statusSelects = document.querySelectorAll('.task-status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', handleStatusChange);
    });
}

// Handle edit task form submission
async function handleEditTaskSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const taskId = document.getElementById('edit_task_id').value;
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    try {
        const response = await fetch('api/tasks.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_task',
                task_id: taskId,
                title: formData.get('title'),
                description: formData.get('description'),
                priority: formData.get('priority'),
                due_date: formData.get('due_date'),
                assigned_to: formData.get('assigned_to')
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Task updated successfully!', 'success');
            closeModal('editTaskModal');
            // Reload page to show updated task
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(result.message || 'Error updating task', 'error');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-save"></i> Update Task';
    }
}

// Handle edit project form submission
async function handleEditProjectSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const projectId = document.getElementById('edit_project_id').value;
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    try {
        const response = await fetch('api/projects.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_project',
                project_id: projectId,
                title: formData.get('title'),
                description: formData.get('description')
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Project updated successfully!', 'success');
            closeModal('editProjectModal');
            // Reload page to show updated project
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(result.message || 'Error updating project', 'error');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-save"></i> Update Project';
    }
}

// Handle add member form submission
async function handleAddMemberSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const projectId = document.getElementById('member_project_id').value;
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    try {
        const response = await fetch('api/projects.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Team member added successfully!', 'success');
            closeModal('addMemberModal');
            e.target.reset();
            // Reload page to show new member
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(result.message || 'Error adding team member', 'error');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-user-plus"></i> Add Member';
    }
}

// Sidebar toggle for mobile
function initializeSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
}

// UI initialization
function initializeUI() {
    // Set active navigation
    setActiveNavigation();
    
    // Initialize date pickers
    initializeDatePickers();
    
    // Initialize search functionality
    initializeSearch();
}

// Set active navigation based on current page
function setActiveNavigation() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.sidebar-menu a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Date picker initialization
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        input.setAttribute('min', today);
    });
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }
}

// Project form submission
async function handleProjectSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.textContent = 'Creating...';
    
    try {
        const response = await fetch('api/projects.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Project created successfully!', 'success');
            e.target.reset();
            closeModal('projectModal');
            // Reload projects or update UI
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(result.message || 'Error creating project', 'error');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Create Project';
    }
}

// Task form submission
async function handleTaskSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.textContent = 'Creating...';
    
    try {
        const response = await fetch('api/tasks.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Task created successfully!', 'success');
            e.target.reset();
            closeModal('taskModal');
            // Reload tasks or update UI
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(result.message || 'Error creating task', 'error');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Create Task';
    }
}

// Handle task status changes
async function handleStatusChange(e) {
    const taskId = e.target.getAttribute('data-task-id');
    const newStatus = e.target.value;
    
    try {
        const response = await fetch('api/tasks.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_status',
                task_id: taskId,
                status: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Task status updated!', 'success');
            // Update UI without reload
            updateTaskStatusUI(taskId, newStatus);
        } else {
            showNotification(result.message || 'Error updating status', 'error');
            // Revert select value
            e.target.value = e.target.getAttribute('data-original-value');
        }
    } catch (error) {
        showNotification('Network error occurred', 'error');
        e.target.value = e.target.getAttribute('data-original-value');
    }
}

// Update task status in UI
function updateTaskStatusUI(taskId, status) {
    const taskRow = document.querySelector(`tr[data-task-id="${taskId}"]`);
    if (taskRow) {
        const statusBadge = taskRow.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.className = `badge badge-${status.replace('_', '-')}`;
            statusBadge.textContent = status.replace('_', ' ').toUpperCase();
        }
    }
}

// Search functionality
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    const searchableItems = document.querySelectorAll('.searchable-item');
    
    searchableItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Utility functions
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function hideModal(modalId) {
    closeModal(modalId);
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        max-width: 400px;
        animation: slideIn 0.3s ease;
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        removeNotification(notification);
    }, 5000);
    
    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        removeNotification(notification);
    });
}

function removeNotification(notification) {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Delete confirmation
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Edit task function
async function editTask(taskId) {
    try {
        const response = await fetch(`api/tasks.php?task_id=${taskId}`);
        const result = await response.json();
        
        if (result.success && result.tasks && result.tasks.length > 0) {
            const task = result.tasks[0];
            
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
            showNotification('Error loading task data', 'error');
        }
    } catch (error) {
        showNotification('Error loading task data', 'error');
        console.error('Error:', error);
    }
}

// Delete task function
async function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        try {
            const response = await fetch('api/tasks.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    task_id: taskId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Task deleted successfully!', 'success');
                // Refresh page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(result.message || 'Error deleting task', 'error');
            }
        } catch (error) {
            showNotification('Error deleting task', 'error');
            console.error('Error:', error);
        }
    }
}


// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
