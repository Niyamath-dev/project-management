<?php
$pageTitle = 'Reports';
require_once 'components/header.php';
require_once 'includes/db.php';

$db = new Database();

// Get overall statistics
$stats = [];

// Total projects
$db->query('SELECT COUNT(*) as count FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$stats['total_projects'] = $result ? $result->count : 0;

// Total tasks
$db->query('SELECT COUNT(*) as count FROM tasks WHERE project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$stats['total_tasks'] = $result ? $result->count : 0;

// Completed tasks
$db->query('SELECT COUNT(*) as count FROM tasks WHERE status = "completed" AND project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$stats['completed_tasks'] = $result ? $result->count : 0;

// In progress tasks
$db->query('SELECT COUNT(*) as count FROM tasks WHERE status = "in_progress" AND project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$stats['in_progress_tasks'] = $result ? $result->count : 0;

// Overdue tasks
$db->query('SELECT COUNT(*) as count FROM tasks WHERE due_date < CURDATE() AND status != "completed" AND project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))');
$db->bind(':user_id', $currentUser['id']);
$result = $db->single();
$stats['overdue_tasks'] = $result ? $result->count : 0;

// Calculate completion rate
$completion_rate = $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 1) : 0;

// Get project performance data
$db->query('SELECT p.title, p.id,
           COUNT(t.id) as total_tasks,
           SUM(CASE WHEN t.status = "completed" THEN 1 ELSE 0 END) as completed_tasks,
           SUM(CASE WHEN t.status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tasks,
           SUM(CASE WHEN t.due_date < CURDATE() AND t.status != "completed" THEN 1 ELSE 0 END) as overdue_tasks
           FROM projects p
           LEFT JOIN tasks t ON p.id = t.project_id
           WHERE p.created_by = :user_id OR p.id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)
           GROUP BY p.id, p.title
           ORDER BY p.title');
$db->bind(':user_id', $currentUser['id']);
$project_stats = $db->resultset();

// Get task priority distribution
$db->query('SELECT priority, COUNT(*) as count 
           FROM tasks 
           WHERE project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))
           GROUP BY priority');
$db->bind(':user_id', $currentUser['id']);
$priority_stats = $db->resultset();

// Get recent activity (last 30 days)
$db->query('SELECT DATE(t.created_at) as date, COUNT(*) as tasks_created
           FROM tasks t
           WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
           AND t.project_id IN (SELECT id FROM projects WHERE created_by = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id))
           GROUP BY DATE(t.created_at)
           ORDER BY date DESC
           LIMIT 10');
$db->bind(':user_id', $currentUser['id']);
$activity_stats = $db->resultset();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-chart-bar text-primary"></i>
        Reports & Analytics
    </h2>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print"></i> Print Report
    </button>
</div>

<!-- Overview Stats -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
        <div class="stat-label">Total Projects</div>
    </div>
    <div class="stat-card success">
        <div class="stat-number"><?php echo $stats['completed_tasks']; ?></div>
        <div class="stat-label">Completed Tasks</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-number"><?php echo $stats['in_progress_tasks']; ?></div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-number"><?php echo $stats['overdue_tasks']; ?></div>
        <div class="stat-label">Overdue Tasks</div>
    </div>
</div>

<!-- Completion Rate -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-percentage text-success"></i>
                    Overall Completion Rate
                </h6>
            </div>
            <div class="card-body text-center">
                <div class="display-4 text-success mb-3"><?php echo $completion_rate; ?>%</div>
                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?php echo $completion_rate; ?>%" 
                         aria-valuenow="<?php echo $completion_rate; ?>" 
                         aria-valuemin="0" aria-valuemax="100">
                        <?php echo $completion_rate; ?>%
                    </div>
                </div>
                <p class="text-muted">
                    <?php echo $stats['completed_tasks']; ?> out of <?php echo $stats['total_tasks']; ?> tasks completed
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-flag text-warning"></i>
                    Task Priority Distribution
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($priority_stats)): ?>
                    <p class="text-muted text-center">No tasks to analyze</p>
                <?php else: ?>
                    <?php foreach ($priority_stats as $priority): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge badge-<?php echo $priority->priority; ?> me-2">
                                <?php echo ucfirst($priority->priority); ?> Priority
                            </span>
                            <span class="fw-bold"><?php echo $priority->count; ?> tasks</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <?php 
                            $percentage = $stats['total_tasks'] > 0 ? ($priority->count / $stats['total_tasks']) * 100 : 0;
                            $color = $priority->priority === 'high' ? 'danger' : ($priority->priority === 'medium' ? 'warning' : 'info');
                            ?>
                            <div class="progress-bar bg-<?php echo $color; ?>" role="progressbar" 
                                 style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Project Performance -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-folder-open text-primary"></i>
            Project Performance
        </h6>
    </div>
    <div class="card-body">
        <?php if (empty($project_stats)): ?>
            <p class="text-muted text-center py-4">No projects to analyze</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Total Tasks</th>
                            <th>Completed</th>
                            <th>In Progress</th>
                            <th>Overdue</th>
                            <th>Completion Rate</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($project_stats as $project): ?>
                            <?php 
                            $project_completion = $project->total_tasks > 0 ? 
                                round(($project->completed_tasks / $project->total_tasks) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($project->title); ?></strong>
                                </td>
                                <td><?php echo $project->total_tasks; ?></td>
                                <td>
                                    <span class="badge bg-success"><?php echo $project->completed_tasks; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-warning"><?php echo $project->in_progress_tasks; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-danger"><?php echo $project->overdue_tasks; ?></span>
                                </td>
                                <td><?php echo $project_completion; ?>%</td>
                                <td>
                                    <div class="progress" style="height: 20px; width: 100px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $project_completion; ?>%">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-activity text-info"></i>
            Recent Activity (Last 30 Days)
        </h6>
    </div>
    <div class="card-body">
        <?php if (empty($activity_stats)): ?>
            <p class="text-muted text-center py-4">No recent activity</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Tasks Created</th>
                            <th>Activity Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activity_stats as $activity): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($activity->date)); ?></td>
                                <td><?php echo $activity->tasks_created; ?></td>
                                <td>
                                    <div class="progress" style="height: 15px; width: 100px;">
                                        <?php 
                                        $max_activity = max(array_column($activity_stats, 'tasks_created'));
                                        $activity_percentage = $max_activity > 0 ? ($activity->tasks_created / $max_activity) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo $activity_percentage; ?>%">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .sidebar, .topbar, .btn, .dropdown {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once 'components/footer.php'; ?>
