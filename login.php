<?php
require 'database.php';

if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin_roster.php' : 'employee_dashboard.php');
}

$error = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if (!in_array($role, ['admin', 'employee'], true)) {
        $error = "Please choose whether you are signing in as an admin or employee.";
    } else {
        foreach ($db['users'] as $user) {
            if (
                strcasecmp($user['username'], $username) === 0
                && $user['role'] === $role
                && passwordMatches($user, $password)
            ) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                redirect($user['role'] === 'admin' ? 'admin_roster.php' : 'employee_dashboard.php');
            }
        }

        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container auth-page">
        <div class="card auth-card auth-card-standalone">
            <div class="auth-card-top">
                <div class="auth-badge">Secure Access</div>
                <div class="section-heading">
                    <p class="eyebrow">Employee Management System</p>
                    <h2>Sign In</h2>
                    <p>Please enter your credentials to access the system.</p>
                </div>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="4000"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <form method="POST" class="auth-form">
                <input type="hidden" name="action" value="login">
                <div class="demo-credentials">
                    <strong>Demo admin login:</strong>
                    <span>Username: <code>admin</code> | Password: <code>admin123</code></span>
                </div>
                <div class="form-group">
                    <label>Sign In As</label>
                    <select name="role" required>
                        <option value="">Choose account type</option>
                        <option value="admin">Admin</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="auth-submit">Login</button>
            </form>
            <p class="helper-text auth-helper">
                <a href="signup.php" class="text-link">Create an account</a>
            </p>
            <p class="helper-text auth-helper">
                <a href="forgot_password.php" class="text-link">Forgot password?</a>
            </p>
            <p class="helper-text auth-helper">
                <a href="index.php" class="text-link">Back Home</a>
            </p>
        </div>
    </div>
    <script src="app.js"></script>
</body>
</html>
