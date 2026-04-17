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

$employeeId = trim($_GET['employee_id'] ?? $_POST['employee_id'] ?? '');
$userIndex = null;
$user = null;
$error = '';
$flash = getFlash();

if ($employeeId !== '') {
    $userIndex = findUserIndexById($db, $employeeId);
    if ($userIndex !== null) {
        $user = $db['users'][$userIndex];
    }
}

if ($user === null
    || ($user['company_id'] ?? 0) !== ($currentAdmin['company_id'] ?? 0)
) {
    setFlash('error', 'Employee not found.');
    redirect('admin_roster.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_employee') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $employeeNumber = trim($_POST['employee_number'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $yearsExperience = (int)($_POST['years_experience'] ?? 0);
    $role = strtolower(trim($_POST['role'] ?? 'employee'));
    $name = trim($_POST['name'] ?? '');
    $hourlyRate = (float)($_POST['hourly_rate'] ?? 0);
    $task = trim($_POST['task'] ?? '');
    
    // NEW: Capture Job Title and Department
    $jobTitle = trim($_POST['job_title'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // NEW: Added $jobTitle and $department to validation
    if ($username === '' || $employeeNumber === '' || $name === '' || $hourlyRate <= 0 || $age <= 0 || $yearsExperience < 0 || $jobTitle === '' || $department === '') {
        $error = 'Please provide valid values for all required fields, including Job Title and Department.';
    } elseif (!in_array($role, ['admin', 'employee'], true)) {
        $error = 'Please select a valid role from the dropdown menu.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address or leave it blank.';
    } else {
        foreach ($db['users'] as $index => $existingUser) {
            if ($index === $userIndex) {
                continue;
            }
            if (strcasecmp($existingUser['username'], $username) === 0) {
                $error = 'That username is already taken by another account.';
                break;
            }
            if ($employeeNumber !== '' && trim((string)$existingUser['employee_number']) !== '' && strcasecmp($existingUser['employee_number'], $employeeNumber) === 0) {
                $error = 'That employee ID number is already in use.';
                break;
            }
        }
    }

    if ($error === '') {
        $db['users'][$userIndex]['username'] = $username;
        $db['users'][$userIndex]['email'] = $email;
        $db['users'][$userIndex]['employee_number'] = $employeeNumber;
        
        // NEW: Save updated Job Title and Department
        $db['users'][$userIndex]['job_title'] = $jobTitle;
        $db['users'][$userIndex]['department'] = $department;
        
        $db['users'][$userIndex]['age'] = $age;
        $db['users'][$userIndex]['years_experience'] = $yearsExperience;
        $db['users'][$userIndex]['role'] = $role;
        $db['users'][$userIndex]['name'] = $name;
        $db['users'][$userIndex]['hourly_rate'] = $hourlyRate;
        $db['users'][$userIndex]['task'] = $task !== '' ? $task : 'No task assigned yet.';
        saveDb($db);

        setFlash('success', 'Employee details updated successfully.');
        redirect('admin_roster.php');
    }
}

$user = $db['users'][$userIndex];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - <?php echo e($user['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container auth-page">
        <div class="card auth-card auth-card-standalone">
            <div class="auth-card-top">
                <div class="auth-badge">Employee Management</div>
                <div class="section-heading">
                    <p class="eyebrow">Edit Employee Profile</p>
                    <h2><?php echo e($user['name']); ?></h2>
                    <p>Update role, pay, task, and profile history details.</p>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="4000"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="action" value="update_employee">
                <input type="hidden" name="employee_id" value="<?php echo e($user['id']); ?>">
                <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo e($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Login Username</label>
                    <input type="text" name="username" value="<?php echo e($user['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>System Access Role</label>
                    <select name="role" required>
                        <option value="employee" <?php echo ($user['role'] === 'employee') ? 'selected' : ''; ?>>Employee</option>
                        <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Job Title / Position</label>
                    <input type="text" name="job_title" value="<?php echo e($user['job_title'] ?? ''); ?>" placeholder="e.g. Software Dev, Cleaner, Manager" required>
                </div>
                
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo e($user['department'] ?? ''); ?>" placeholder="e.g. IT, Maintenance, HR" required>
                </div>

                <div class="form-group">
                    <label>Employee ID Number</label>
                    <input type="text" name="employee_number" value="<?php echo e($user['employee_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" min="16" value="<?php echo e((int)$user['age']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Years of Experience</label>
                    <input type="number" name="years_experience" min="0" value="<?php echo e((int)$user['years_experience']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Employee Email (optional)</label>
                    <input type="email" name="email" value="<?php echo e($user['email'] ?? ''); ?>" placeholder="user@example.com">
                </div>
                <div class="form-group">
                    <label>Hourly Rate ($)</label>
                    <input type="number" step="0.01" min="0.01" name="hourly_rate" value="<?php echo e(number_format((float)$user['hourly_rate'], 2, '.', '')); ?>" required>
                </div>
                <div class="form-group">
                    <label>Current Task</label>
                    <input type="text" name="task" value="<?php echo e($user['task']); ?>" placeholder="No task assigned yet.">
                </div>
                </div>
                <button type="submit" class="auth-submit" style="margin-top: 10px;">Save Changes</button>
            </form>

            <p class="helper-text auth-helper" style="margin-top: 20px;">
                <a href="admin_roster.php" class="text-link">← Back to Employee Roster</a>
            </p>
        </div>
    </div>
    <script src="app.js"></script>
</body>
</html>
