<?php
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('login.php');
}

$currentAdminIndex = findUserIndexById($db, $_SESSION['user_id'] ?? null);
$currentAdmin = $currentAdminIndex !== null ? $db['users'][$currentAdminIndex] : null;

if ($currentAdmin === null) {
    session_destroy();
    redirect('login.php');
}

$error = '';
$flash = getFlash();
$user = null;
$userIndex = null;
$employeeId = trim($_GET['employee_id'] ?? $_POST['employee_id'] ?? '');

if ($employeeId !== '') {
    $userIndex = findUserIndexById($db, $employeeId);
    if ($userIndex !== null) {
        $user = $db['users'][$userIndex];
    }
}

if ($user !== null && ($user['company_id'] ?? 0) !== ($currentAdmin['company_id'] ?? 0)) {
    $user = null;
    $userIndex = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($userIndex === null || $user === null) {
        $error = 'Unable to locate the selected employee account.';
    } elseif ($password === '' || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } else {
        $db['users'][$userIndex]['password'] = password_hash($password, PASSWORD_DEFAULT);
        clearPasswordResetToken($db, $userIndex);
        saveDb($db);

        setFlash('success', 'Password has been reset for ' . e($db['users'][$userIndex]['name']) . '.');
        redirect('admin_roster.php');
    }
}

if ($userIndex === null || $user === null) {
    setFlash('error', 'No employee selected or the account does not exist.');
    redirect('admin_roster.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container auth-page">
        <div class="card auth-card auth-card-standalone">
            <div class="auth-card-top">
                <div class="auth-badge">Admin Override</div>
                <div class="section-heading">
                    <p class="eyebrow">Reset Employee Password</p>
                    <h2>Set a new password</h2>
                    <p>This action allows the administrator to update login credentials directly.</p>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="4000"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="employee_id" value="<?php echo e($user['id']); ?>">
                <div class="form-group">
                    <label>Employee</label>
                    <input type="text" value="<?php echo e($user['name']); ?> (@<?php echo e($user['username']); ?>)" disabled>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" minlength="6" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" minlength="6" required>
                </div>
                <button type="submit" class="auth-submit">Reset Password</button>
            </form>

            <p class="helper-text auth-helper">
                <a href="admin_roster.php" class="text-link">Back to roster</a>
            </p>
        </div>
    </div>
    <script src="app.js"></script>
</body>
</html>
