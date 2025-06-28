<?php
$pageTitle = 'Calendar';
require_once 'components/header.php';
require_once 'includes/db.php';

$db = new Database();

// Get tasks with due dates for calendar display
$db->query('SELECT t.*, p.title as project_title 
           FROM tasks t 
           LEFT JOIN projects p ON t.project_id = p.id 
           WHERE t.due_date IS NOT NULL 
           AND t.project_id IN (
               SELECT id FROM projects 
               WHERE created_by = :user_id 
               OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)
           )
           ORDER BY t.due_date ASC');
$db->bind(':user_id', $currentUser['id']);
$tasks_with_dates = $db->resultset();

// Get current month and year
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Calculate previous and next month
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get first day of month and number of days
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$start_day = date('w', $first_day); // 0 = Sunday

// Group tasks by date
$tasks_by_date = [];
foreach ($tasks_with_dates as $task) {
    $date = date('Y-m-d', strtotime($task->due_date));
    $tasks_by_date[$date][] = $task;
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-calendar text-primary"></i>
        Calendar
    </h2>
    <div class="btn-group">
        <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-outline-primary">
            <i class="fas fa-chevron-left"></i> Previous
        </a>
        <a href="calendar.php" class="btn btn-primary">Today</a>
        <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-outline-primary">
            Next <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>

<!-- Calendar Header -->
<div class="card">
    <div class="card-header text-center">
        <h4 class="mb-0"><?php echo date('F Y', $first_day); ?></h4>
    </div>
    <div class="card-body p-0">
        <!-- Calendar Grid -->
        <div class="table-responsive">
            <table class="table table-bordered mb-0" style="table-layout: fixed;">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center py-3">Sunday</th>
                        <th class="text-center py-3">Monday</th>
                        <th class="text-center py-3">Tuesday</th>
                        <th class="text-center py-3">Wednesday</th>
                        <th class="text-center py-3">Thursday</th>
                        <th class="text-center py-3">Friday</th>
                        <th class="text-center py-3">Saturday</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $day = 1;
                    $weeks = ceil(($days_in_month + $start_day) / 7);
                    
                    for ($week = 0; $week < $weeks; $week++):
                    ?>
                        <tr>
                            <?php for ($day_of_week = 0; $day_of_week < 7; $day_of_week++): ?>
                                <td class="p-2" style="height: 120px; vertical-align: top;">
                                    <?php
                                    $cell_day = $day - $start_day + $day_of_week;
                                    if ($cell_day >= 1 && $cell_day <= $days_in_month):
                                        $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $cell_day);
                                        $is_today = $current_date === date('Y-m-d');
                                    ?>
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <span class="fw-bold <?php echo $is_today ? 'text-primary' : ''; ?>">
                                                <?php echo $cell_day; ?>
                                            </span>
                                            <?php if ($is_today): ?>
                                                <span class="badge bg-primary">Today</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (isset($tasks_by_date[$current_date])): ?>
                                            <?php foreach ($tasks_by_date[$current_date] as $task): ?>
                                                <div class="mb-1">
                                                    <div class="badge bg-<?php echo $task->status === 'completed' ? 'success' : ($task->priority === 'high' ? 'danger' : 'warning'); ?> text-wrap w-100 text-start" 
                                                         style="font-size: 10px;">
                                                        <?php echo htmlspecialchars(substr($task->title, 0, 20)); ?>
                                                        <?php if (strlen($task->title) > 20) echo '...'; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php
                    $day += 7;
                    endfor;
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Upcoming Tasks -->
<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-clock text-warning"></i>
                    Upcoming Tasks
                </h6>
            </div>
            <div class="card-body">
                <?php
                $upcoming_tasks = array_filter($tasks_with_dates, function($task) {
                    return strtotime($task->due_date) >= strtotime('today');
                });
                $upcoming_tasks = array_slice($upcoming_tasks, 0, 5);
                ?>
                
                <?php if (empty($upcoming_tasks)): ?>
                    <p class="text-muted text-center py-3">No upcoming tasks with due dates.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcoming_tasks as $task): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($task->title); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            Project: <?php echo htmlspecialchars($task->project_title); ?>
                                        </p>
                                        <small class="text-muted">
                                            Due: <?php echo date('M j, Y', strtotime($task->due_date)); ?>
                                        </small>
                                    </div>
                                    <span class="badge badge-<?php echo $task->priority; ?>">
                                        <?php echo ucfirst($task->priority); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    Overdue Tasks
                </h6>
            </div>
            <div class="card-body">
                <?php
                $overdue_tasks = array_filter($tasks_with_dates, function($task) {
                    return strtotime($task->due_date) < strtotime('today') && $task->status !== 'completed';
                });
                $overdue_tasks = array_slice($overdue_tasks, 0, 5);
                ?>
                
                <?php if (empty($overdue_tasks)): ?>
                    <p class="text-success text-center py-3">
                        <i class="fas fa-check-circle"></i>
                        No overdue tasks!
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($overdue_tasks as $task): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 text-danger"><?php echo htmlspecialchars($task->title); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            Project: <?php echo htmlspecialchars($task->project_title); ?>
                                        </p>
                                        <small class="text-danger">
                                            Overdue by <?php echo abs(floor((strtotime('today') - strtotime($task->due_date)) / 86400)); ?> days
                                        </small>
                                    </div>
                                    <span class="badge badge-<?php echo $task->priority; ?>">
                                        <?php echo ucfirst($task->priority); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'components/footer.php'; ?>
