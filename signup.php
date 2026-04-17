<?php
require 'database.php';

if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin_roster.php' : 'employee_dashboard.php');
}

$error = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'signup') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $companyName = trim($_POST['company_name'] ?? '');

    $role = 'admin';
    $employeeNumber = '';
    $age = 0;
    $experienceYears = 0;
    $hourlyRate = 0;
    $companyId = random_int(1000000000, 2147483647);

    if ($name === '' || $username === '' || $password === '' || $companyName === '') {
        $error = 'Please complete the name, username, password, and company name fields.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address or leave it blank.';
    } elseif (findUserByUsername($db, $username) !== null) {
        $error = 'That username is already in use. Please choose a different one.';
    }

    if ($error === '') {
        $db['users'][] = [
            'id' => random_int(1000000000, 2147483647),
            'username' => $username,
            'email' => $email,
            'employee_number' => $employeeNumber,
            'age' => $age,
            'years_experience' => $experienceYears,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'role' => $role,
            'company_id' => $companyId,
            'company_name' => $companyName,
            'hourly_rate' => $hourlyRate,
            'task' => 'No task assigned yet.',
            'status' => 'idle',
            'clock_in_time' => null,
            'break_started_at' => null,
            'accumulated_break_seconds' => 0,
            'pending_hours' => 0,
            'approved_pay' => 0,
            'password_reset_token_hash' => '',
            'password_reset_expires_at' => 0
        ];

        saveDb($db);
        setFlash('success', 'Your admin account has been created. Please log in.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Employee Project</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container auth-page">
        <div class="card auth-card auth-card-standalone">
            <div class="auth-card-top">
                <div class="auth-badge">Create Account</div>
                <div class="section-heading">
                    <p class="eyebrow">Sign Up</p>
                    <h2>Register to manage employees</h2>
                    <p>Create your own admin account. Admins can then add employees from the dashboard.</p>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="4000"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="action" value="signup">
                <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Login Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group form-group-full">
                    <label>Company Name</label>
                    <input type="text" name="company_name" required>
                </div>
                <div class="alert alert-warning">
                    Admin sign-up only. Once logged in, admins can create employee accounts.
                </div>
                <div class="form-group">
                    <label>Employee Email (optional)</label>
                    <input type="email" name="email" placeholder="user@example.com">
                </div>
                <div class="form-group">
                    <label>Login Password</label>
                    <input type="password" name="password" minlength="6" required>
                </div>
                </div>
                <button type="submit" class="auth-submit">Sign Up</button>
            </form>

            <p class="helper-text auth-helper">
                Already have an account? <a href="login.php" class="text-link">Sign in here</a>
            </p>
        </div>
    </div>
    <script src="app.js"></script>
</body>
</html>
