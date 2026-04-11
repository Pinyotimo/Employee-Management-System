<?php
require 'database.php';

if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin_roster.php' : 'employee_dashboard.php');
}

$error = '';
$flash = getFlash();
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$userIndex = null;
$user = null;

if ($token !== '') {
    $userIndex = findUserIndexByResetToken($db, $token);
    if ($userIndex !== null) {
        $user = $db['users'][$userIndex];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($userIndex === null || $user === null) {
        $error = 'Invalid or expired password reset link.';
    } elseif ($password === '' || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } else {
        $db['users'][$userIndex]['password'] = password_hash($password, PASSWORD_DEFAULT);
        clearPasswordResetToken($db, $userIndex);
        saveDb($db);

        setFlash('success', 'Your password has been updated successfully. You may now log in.');
        redirect('login.php');
    }
}

if ($token !== '' && $userIndex === null) {
    $error = 'Invalid or expired password reset link.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container auth-page">
        <div class="card auth-card auth-card-standalone">
            <div class="auth-card-top">
                <div class="auth-badge">Secure Update</div>
                <div class="section-heading">
                    <p class="eyebrow">Reset Password</p>
                    <h2>Choose a new password</h2>
                    <p>Use a strong password and avoid reuse of old credentials.</p>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($userIndex !== null && $user !== null): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo e($user['username']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" minlength="6" required>
                    </div>
                    <button type="submit" class="auth-submit">Update Password</button>
                </form>
            <?php else: ?>
                <div class="alert alert-info">Please request a new password reset link if this one has expired.</div>
            <?php endif; ?>

            <p class="helper-text auth-helper">
                <a href="login.php" class="text-link">Back to login</a>
            </p>
        </div>
    </div>
</body>
</html>
