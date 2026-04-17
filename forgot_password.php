<?php
require 'database.php';

if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin_roster.php' : 'employee_dashboard.php');
}

$error = '';
$flash = getFlash();
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'forgot_password') {
    $username = trim($_POST['username'] ?? '');
    $role = trim($_POST['role'] ?? 'employee');

    if ($username === '' || !in_array($role, ['admin', 'employee'], true)) {
        $error = 'Please enter your username and select your role.';
    } else {
        $user = findUserByUsername($db, $username);

        if ($user === null || $user['role'] !== $role) {
            $error = 'No account matches that username and role.';
        } elseif (trim((string)$user['email']) === '') {
            $error = 'No email address is on file for that account. Please contact the administrator to reset your password.';
        } else {
            $userIndex = findUserIndexById($db, $user['id']);
            if ($userIndex === null) {
                $error = 'Unable to process password reset for this account.';
            } else {
                $token = generatePasswordResetToken($db, $userIndex);
                saveDb($db);

                $resetLink = 'reset_password.php?token=' . urlencode($token);
                setFlash('success', 'A password reset link has been generated. In a production system, this link would be emailed to the address on file.');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container auth-page">
        <div class="card auth-card auth-card-standalone">
            <div class="auth-card-top">
                <div class="auth-badge">Reset Access</div>
                <div class="section-heading">
                    <p class="eyebrow">Password Recovery</p>
                    <h2>Forgot your password?</h2>
                    <p>Enter your login details and we will generate a reset link.</p>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="4000"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="action" value="forgot_password">
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
                <button type="submit" class="auth-submit">Request Reset Link</button>
            </form>

            <?php if ($resetLink): ?>
                <div class="alert alert-success">
                    <p><strong>Reset link:</strong></p>
                    <p><a href="<?php echo e($resetLink); ?>"><?php echo e($resetLink); ?></a></p>
                    <p>This is the generated password reset token link for this demo.</p>
                </div>
            <?php endif; ?>

            <p class="helper-text auth-helper">
                <a href="login.php" class="text-link">Back to login</a>
            </p>
        </div>
    </div>
    <script src="app.js"></script>
</body>
</html>
