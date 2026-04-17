<?php
require 'database.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('login.php');
}

$currentAdminIndex = findUserIndexById($db, $_SESSION['user_id'] ?? null);
$currentAdmin = $currentAdminIndex !== null ? $db['users'][$currentAdminIndex] : null;

if ($currentAdmin === null) {
    session_destroy();
    redirect('login.php');
}

// Handle Task Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'assign_task') {
        $employeeId = $_POST['employee_id'] ?? '';
        $task = trim($_POST['task'] ?? '');
        $userIndex = findUserIndexById($db, $employeeId);

        if ($userIndex !== null
            && $db['users'][$userIndex]['role'] !== 'admin'
            && ($db['users'][$userIndex]['company_id'] ?? 0) === ($currentAdmin['company_id'] ?? 0)
        ) {
            $db['users'][$userIndex]['task'] = $task !== '' ? $task : 'No task assigned yet.';
            saveDb($db);
            setFlash('success', 'Task updated successfully.');
        } else {
            setFlash('error', 'Unable to update the selected employee.');
        }
    } elseif (($_POST['action'] ?? '') === 'delete_employee') {
        $employeeId = $_POST['employee_id'] ?? '';
        $userIndex = findUserIndexById($db, $employeeId);

        if ($userIndex !== null
            && ($db['users'][$userIndex]['company_id'] ?? 0) === ($currentAdmin['company_id'] ?? 0)
            && $db['users'][$userIndex]['role'] === 'employee'
        ) {
            array_splice($db['users'], $userIndex, 1);
            saveDb($db);
            setFlash('success', 'Employee deleted successfully.');
        } elseif ($userIndex !== null && $db['users'][$userIndex]['role'] !== 'employee') {
            setFlash('error', 'Only employee accounts can be deleted from this screen.');
        } else {
            setFlash('error', 'Unable to find the employee to delete.');
        }
    }

    redirect('admin_roster.php');
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Roster</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="navbar">
            <div class="nav-brand">
                <h2>Admin Panel</h2>
            </div>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-panel">
                <div class="nav-links">
                    <a href="admin_roster.php" class="active-link">Employee Roster</a>
                    <a href="admin_payroll.php">Payroll Manager</a>
                    <a href="payments.php">Payments</a>
                    <a href="admin_register.php">Register Employee</a>
                </div>
                <a href="database.php?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>

        <div class="card">
            <div class="section-heading">
                <p class="eyebrow">Operations</p>
                <h2>Employee Roster & Task Management</h2>
                <p>Monitor who is currently working and keep task assignments current. Use View to open employee profile, reset credentials, or edit/delete.</p>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="4000"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="roster-table">
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Timer</th>
                        <th>Worked Hours</th>
                        <th>Task</th>
                        <th>Actions</th>
                    </tr>
                <?php foreach ($db['users'] as $u): if ($u['role'] !== 'admin' && ($u['company_id'] ?? 0) === ($currentAdmin['company_id'] ?? 0)): ?>
                <?php
                    $liveSeconds = workedSecondsForUser($u);
                    $baseWorkedHours = (float)$u['pending_hours']
                        + ((float)$u['approved_pay'] / max(1, (float)$u['hourly_rate']));
                    $workedHours = $baseWorkedHours + ($liveSeconds / 3600);
                    $statusClass = $u['status'] === 'working' ? 'bg-green' : ($u['status'] === 'paused' ? 'bg-amber' : 'bg-gray');
                    $statusLabel = $u['status'] === 'working'
                        ? 'ACTIVE'
                        : ($u['status'] === 'paused' ? 'ON BREAK (Paused)' : 'INACTIVE');
                ?>
                <tr>
                    <td data-label="Name">
                        <b><?php echo e($u['name']); ?></b>
                        <div class="table-subtext">@<?php echo e($u['username']); ?></div>
                        <?php if (trim((string)$u['email']) !== ''): ?>
                            
                        <?php endif; ?>
                    </td>
                    <td data-label="Status">
                        <span class="badge <?php echo e($statusClass); ?>">
                            <?php echo e($statusLabel); ?>
                        </span>
                    </td>
                    <td data-label="Timer">
                        <?php if (in_array($u['status'], ['working', 'paused'], true) && !empty($u['clock_in_time'])): ?>
                            <span
                                class="live-timer"
                                data-start-time="<?php echo e((int)$u['clock_in_time']); ?>"
                                data-break-start="<?php echo e((int)($u['break_started_at'] ?? 0)); ?>"
                                data-break-seconds="<?php echo e((int)($u['accumulated_break_seconds'] ?? 0)); ?>"
                                data-paused="<?php echo $u['status'] === 'paused' ? 'true' : 'false'; ?>"
                                data-target="timer"
                            >
                                <?php echo e(formatDuration($liveSeconds)); ?>
                            </span>
                            <div class="table-subtext">
                                <?php echo $u['status'] === 'paused' ? 'Paused shift' : 'Since ' . e(date('M d, Y H:i', (int)$u['clock_in_time'])); ?>
                            </div>
                        <?php else: ?>
                            <span class="muted-label">Not on shift</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Worked Hours">
                        <b
                            class="worked-hours"
                            data-target="worked-hours"
                            data-base-hours="<?php echo e(number_format($baseWorkedHours, 4, '.', '')); ?>"
                            data-start-time="<?php echo e((int)$u['clock_in_time']); ?>"
                            data-break-start="<?php echo e((int)($u['break_started_at'] ?? 0)); ?>"
                            data-break-seconds="<?php echo e((int)($u['accumulated_break_seconds'] ?? 0)); ?>"
                            data-paused="<?php echo $u['status'] === 'paused' ? 'true' : 'false'; ?>"
                            data-label-prefix=""
                        >
                            <?php echo number_format($workedHours, 2); ?> hrs
                        </b>
                         </td>
                    <td class="task-cell" data-label="Task">
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="assign_task">
                            <input type="hidden" name="employee_id" value="<?php echo e($u['id']); ?>">
                            <input type="text" name="task" value="<?php echo e($u['task']); ?>" required placeholder="Enter task...">
                            <button type="submit" class="action-button">Assign</button>
                        </form>
                    </td>
                    <td data-label="Actions">
                        <a href="admin_view_employee.php?employee_id=<?php echo e($u['id']); ?>" class="view-button">View</a>
                    </td>
                </tr>
                <?php endif; endforeach; ?>
            </table>
            </div>
        </div>
    </div>

    <script src="app.js"></script>
</body>
</html>
