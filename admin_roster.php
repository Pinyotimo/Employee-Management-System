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
                <div class="alert alert-<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <div class="table-responsive">
                <table>
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
                    <td>
                        <b><?php echo e($u['name']); ?></b>
                        <div class="table-subtext">@<?php echo e($u['username']); ?></div>
                        <?php if (trim((string)$u['email']) !== ''): ?>
                            
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo e($statusClass); ?>">
                            <?php echo e($statusLabel); ?>
                        </span>
                    </td>
                    <td>
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
                    <td>
                        <b
                            class="worked-hours"
                            data-target="worked-hours"
                            data-base-hours="<?php echo e(number_format($baseWorkedHours, 4, '.', '')); ?>"
                            data-start-time="<?php echo e((int)$u['clock_in_time']); ?>"
                            data-break-start="<?php echo e((int)($u['break_started_at'] ?? 0)); ?>"
                            data-break-seconds="<?php echo e((int)($u['accumulated_break_seconds'] ?? 0)); ?>"
                            data-paused="<?php echo $u['status'] === 'paused' ? 'true' : 'false'; ?>"
                        >
                            <?php echo number_format($workedHours, 2); ?> hrs
                        </b>
                         </td>
                    <td class="task-cell">
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="assign_task">
                            <input type="hidden" name="employee_id" value="<?php echo e($u['id']); ?>">
                            <input type="text" name="task" value="<?php echo e($u['task']); ?>" required placeholder="Enter task...">
                            <button type="submit" style="padding: 5px;">Assign</button>
                        </form>
                    </td>
                    <td>
                        <a href="admin_view_employee.php?employee_id=<?php echo e($u['id']); ?>" class="view-button">View</a>
                    </td>
                </tr>
                <?php endif; endforeach; ?>
            </table>
            </div>
        </div>
    </div>

    <script>
        const navToggle = document.querySelector('.nav-toggle');
        const navPanel = document.querySelector('.nav-panel');
        const timerNodes = document.querySelectorAll('[data-target="timer"]');
        const workedHourNodes = document.querySelectorAll('[data-target="worked-hours"]');

        function formatDuration(totalSeconds) {
            const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            return `${hours}:${minutes}:${seconds}`;
        }

        if (navToggle && navPanel) {
            navToggle.addEventListener('click', () => {
                const expanded = navToggle.getAttribute('aria-expanded') === 'true';
                navToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                navPanel.classList.toggle('is-open');
            });
        }

        function updateLiveTimers() {
            const now = Math.floor(Date.now() / 1000);
            timerNodes.forEach((node) => {
                const startTime = Number(node.dataset.startTime || 0);
                const breakStart = Number(node.dataset.breakStart || 0);
                const baseBreakSeconds = Number(node.dataset.breakSeconds || 0);
                const isPaused = node.dataset.paused === 'true';
                const currentBreakSeconds = isPaused && breakStart > 0 ? Math.max(0, now - breakStart) : 0;
                const elapsed = Math.max(0, now - startTime - baseBreakSeconds - currentBreakSeconds);
                node.textContent = formatDuration(elapsed);
            });

            workedHourNodes.forEach((node) => {
                const baseHours = Number(node.dataset.baseHours || 0);
                const startTime = Number(node.dataset.startTime || 0);
                const breakStart = Number(node.dataset.breakStart || 0);
                const baseBreakSeconds = Number(node.dataset.breakSeconds || 0);
                const isPaused = node.dataset.paused === 'true';
                const currentBreakSeconds = isPaused && breakStart > 0 ? Math.max(0, now - breakStart) : 0;
                const extraHours = startTime > 0
                    ? Math.max(0, now - startTime - baseBreakSeconds - currentBreakSeconds) / 3600
                    : 0;
                node.textContent = `${(baseHours + extraHours).toFixed(2)} hrs`;
            });
        }

        if (timerNodes.length > 0 || workedHourNodes.length > 0) {
            updateLiveTimers();
            setInterval(updateLiveTimers, 1000);
        }
    </script>
</body>
</html>
