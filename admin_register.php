<?php
require 'database.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_employee') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rate = (float)($_POST['rate'] ?? 0);

    if ($name === '' || $username === '' || $password === '' || $rate <= 0) {
        setFlash('error', 'Please complete all fields and use a valid hourly rate.');
        redirect('admin_register.php');
    }

    if (strlen($password) < 6) {
        setFlash('error', 'Password must be at least 6 characters long.');
        redirect('admin_register.php');
    }

    if (findUserByUsername($db, $username) !== null) {
        setFlash('error', 'That username is already in use. Please choose a different one.');
        redirect('admin_register.php');
    }

    $db['users'][] = [
        'id' => random_int(1000000000, 2147483647),
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'name' => $name,
        'role' => 'employee',
        'hourly_rate' => $rate,
        'task' => 'No task assigned yet.',
        'status' => 'idle',
        'clock_in_time' => null,
        'break_started_at' => null,
        'accumulated_break_seconds' => 0,
        'pending_hours' => 0,
        'approved_pay' => 0
    ];

    saveDb($db);
    setFlash('success', 'Employee account created successfully.');
    redirect('admin_roster.php');
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Employee</title>
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
                    <a href="admin_roster.php">Employee Roster</a>
                    <a href="admin_payroll.php">Payroll Manager</a>
                    <a href="payments.php">Payments</a>
                    <a href="admin_register.php" class="active-link">Register Employee</a>
                </div>
                <a href="database.php?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>

        <div class="card form-card">
            <div class="section-heading">
                <p class="eyebrow">Administration</p>
                <h2>Register New Employee</h2>
                <p>Create a fresh employee login and assign their base hourly rate.</p>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="add_employee">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Login Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Login Password</label>
                    <input type="password" name="password" minlength="6" required>
                </div>
                <div class="form-group">
                    <label>Hourly Rate ($)</label>
                    <input type="number" step="0.01" min="0.01" name="rate" required>
                </div>
                <button type="submit">Create Employee Account</button>
            </form>
        </div>
    </div>

    <script>
        const navToggle = document.querySelector('.nav-toggle');
        const navPanel = document.querySelector('.nav-panel');

        if (navToggle && navPanel) {
            navToggle.addEventListener('click', () => {
                const expanded = navToggle.getAttribute('aria-expanded') === 'true';
                navToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                navPanel.classList.toggle('is-open');
            });
        }
    </script>
</body>
</html>
