<?php
require 'database.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    redirect('login.php');
}

// Find current user data
$currentUser = null;
$currentUserIndex = findUserIndexById($db, $_SESSION['user_id']);
if ($currentUserIndex !== null) {
    $currentUser = $db['users'][$currentUserIndex];
}

if ($currentUser === null) {
    session_destroy();
    redirect('login.php');
}

$hasAssignedTask = trim((string)$currentUser['task']) !== '' && trim((string)$currentUser['task']) !== 'No task assigned yet.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'clock_in') {
        $employeeTask = trim((string)$db['users'][$currentUserIndex]['task']);
        $canClockIn = $employeeTask !== '' && $employeeTask !== 'No task assigned yet.';

        if (!$canClockIn) {
            setFlash('error', 'You cannot clock in until an admin assigns you a task.');
        } elseif ($db['users'][$currentUserIndex]['status'] === 'idle') {
            $db['users'][$currentUserIndex]['status'] = 'working';
            $db['users'][$currentUserIndex]['clock_in_time'] = time();
            $db['users'][$currentUserIndex]['break_started_at'] = null;
            $db['users'][$currentUserIndex]['accumulated_break_seconds'] = 0;
            saveDb($db);
            setFlash('success', 'Clock-in recorded successfully.');
        }
    } elseif (($_POST['action'] ?? '') === 'pause_shift') {
        if ($db['users'][$currentUserIndex]['status'] === 'working') {
            $db['users'][$currentUserIndex]['status'] = 'paused';
            $db['users'][$currentUserIndex]['break_started_at'] = time();
            saveDb($db);
            setFlash('success', 'Your shift has been paused for break.');
        } else {
            setFlash('error', 'You can only pause an active shift.');
        }
    } elseif (($_POST['action'] ?? '') === 'resume_shift') {
        if ($db['users'][$currentUserIndex]['status'] === 'paused' && !empty($db['users'][$currentUserIndex]['break_started_at'])) {
            $db['users'][$currentUserIndex]['accumulated_break_seconds'] += max(
                0,
                time() - (int)$db['users'][$currentUserIndex]['break_started_at']
            );
            $db['users'][$currentUserIndex]['break_started_at'] = null;
            $db['users'][$currentUserIndex]['status'] = 'working';
            saveDb($db);
            setFlash('success', 'Your shift has resumed.');
        } else {
            setFlash('error', 'You are not currently on break.');
        }
    } elseif (($_POST['action'] ?? '') === 'clock_out') {
        if (
            in_array($db['users'][$currentUserIndex]['status'], ['working', 'paused'], true)
            && !empty($db['users'][$currentUserIndex]['clock_in_time'])
        ) {
            $secondsWorked = workedSecondsForUser($db['users'][$currentUserIndex]);
            $hoursWorked = round($secondsWorked / 3600, 2);

            $db['users'][$currentUserIndex]['pending_hours'] += $hoursWorked;
            $db['users'][$currentUserIndex]['status'] = 'idle';
            $db['users'][$currentUserIndex]['clock_in_time'] = null;
            $db['users'][$currentUserIndex]['break_started_at'] = null;
            $db['users'][$currentUserIndex]['accumulated_break_seconds'] = 0;
            saveDb($db);
            setFlash('success', 'Clock-out recorded successfully.');
        } else {
            setFlash('error', 'You are not currently clocked in.');
        }
    }

    redirect('employee_dashboard.php');
}

$currentUser = $db['users'][$currentUserIndex];
$flash = getFlash();
$hasAssignedTask = trim((string)$currentUser['task']) !== '' && trim((string)$currentUser['task']) !== 'No task assigned yet.';
$currentShiftSeconds = workedSecondsForUser($currentUser);
$isPaused = $currentUser['status'] === 'paused';
$breakSeconds = 0;

if ($isPaused && !empty($currentUser['break_started_at'])) {
    $breakSeconds = max(0, time() - (int)$currentUser['break_started_at']);
}

$totalWorkedHours = (float)$currentUser['pending_hours']
    + ((float)$currentUser['approved_pay'] / max(1, (float)$currentUser['hourly_rate']))
    + ($currentShiftSeconds / 3600);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="navbar">
            <div class="nav-brand">
                <h2>Welcome, <?php echo e($currentUser['name']); ?></h2>
            </div>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-panel">
                <div class="nav-links">
                    <a href="payments.php">Payment History</a>
                </div>
                <a href="database.php?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="4000"><?php echo e($flash['message']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="section-heading">
                <p class="eyebrow">Work Queue</p>
                <h2>Your Current Assignment</h2>
            </div>
            <p class="task-panel">
                <?php echo e($currentUser['task']); ?>
            </p>
        </div>

        <div class="card">
            <div class="section-heading">
                <p class="eyebrow">Employee History</p>
                <h2>Your Profile Details</h2>
            </div>
            <p><strong>ID Number:</strong> <?php echo e($currentUser['employee_number']); ?></p>
            <p><strong>Age:</strong> <?php echo e((int)$currentUser['age']); ?></p>
            <p><strong>Years of Experience:</strong> <?php echo e((int)$currentUser['years_experience']); ?> years</p>
            <p><strong>Hourly Rate:</strong> $<?php echo number_format((float)$currentUser['hourly_rate'], 2); ?> / hr</p>
            <p><strong>Role:</strong> <?php echo e(ucfirst($currentUser['role'])); ?></p>
        </div>

        <div class="card center-card">
            <h2>Time Clock</h2>
            <?php if ($currentUser['status'] === 'idle'): ?>
                <p class="helper-text">Total worked hours: <?php echo number_format($totalWorkedHours, 2); ?> hrs</p>
                <?php if (!$hasAssignedTask): ?>
                    <div class="alert alert-warning">Clock-in is disabled until an admin assigns you a task.</div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="clock_in">
                    <button type="submit" class="btn-success" <?php echo !$hasAssignedTask ? 'disabled' : ''; ?>>CLOCK IN (Start Work)</button>
                </form>
            <?php else: ?>
                <h3 class="status-text"><?php echo $isPaused ? 'Shift paused for break' : 'Currently on the clock...'; ?></h3>
                <p class="timer-readout">
                    <span
                        class="live-timer"
                        data-start-time="<?php echo e((int)$currentUser['clock_in_time']); ?>"
                        data-break-start="<?php echo e((int)($currentUser['break_started_at'] ?? 0)); ?>"
                        data-break-seconds="<?php echo e((int)($currentUser['accumulated_break_seconds'] ?? 0)); ?>"
                        data-paused="<?php echo $isPaused ? 'true' : 'false'; ?>"
                        data-target="timer"
                    >
                        <?php echo e(formatDuration($currentShiftSeconds)); ?>
                    </span>
                </p>
                <?php if ($isPaused): ?>
                <p
                    class="helper-text"
                    data-target="break-timer"
                    data-break-start="<?php echo e((int)($currentUser['break_started_at'] ?? 0)); ?>"
                    data-label-prefix="Current break: "
                >
                    Current break: <?php echo e(formatDuration($breakSeconds)); ?>
                </p>
                <?php endif; ?>
                <p
                    class="helper-text total-hours-label"
                    data-target="worked-hours"
                    data-base-hours="<?php echo e(number_format($totalWorkedHours - ($currentShiftSeconds / 3600), 4, '.', '')); ?>"
                    data-start-time="<?php echo e((int)$currentUser['clock_in_time']); ?>"
                    data-break-start="<?php echo e((int)($currentUser['break_started_at'] ?? 0)); ?>"
                    data-break-seconds="<?php echo e((int)($currentUser['accumulated_break_seconds'] ?? 0)); ?>"
                    data-paused="<?php echo $isPaused ? 'true' : 'false'; ?>"
                    data-label-prefix="Total worked hours: "
                >
                    Total worked hours: <?php echo number_format($totalWorkedHours, 2); ?> hrs
                </p>
                <div class="action-row">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $isPaused ? 'resume_shift' : 'pause_shift'; ?>">
                        <button type="submit" class="btn-secondary">
                            <?php echo $isPaused ? 'RESUME SHIFT' : 'PAUSE FOR BREAK'; ?>
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="clock_out">
                        <button type="submit" class="btn-warning">CLOCK OUT (End Day)</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Your Earnings</h2>
            <p><b>Pending Hours Awaiting Approval:</b> <?php echo number_format((float)$currentUser['pending_hours'], 2); ?> hrs</p>
            <p><b>Total Approved Earnings:</b> <span class="earnings-total">$<?php echo number_format((float)$currentUser['approved_pay'], 2); ?></span></p>
        </div>
    </div>

    <script src="app.js"></script>
</body>
</html>
