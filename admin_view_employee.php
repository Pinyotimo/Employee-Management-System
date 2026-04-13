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

$employeeId = trim($_GET['employee_id'] ?? '');
$userIndex = findUserIndexById($db, $employeeId);

if ($userIndex === null
    || $db['users'][$userIndex]['role'] !== 'employee'
    || ($db['users'][$userIndex]['company_id'] ?? 0) !== ($currentAdmin['company_id'] ?? 0)
) {
    setFlash('error', 'Employee not found.');
    redirect('admin_roster.php');
}

$user = $db['users'][$userIndex];
$flash = getFlash();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_employee') {
    array_splice($db['users'], $userIndex, 1);
    saveDb($db);
    setFlash('success', 'Employee deleted successfully.');
    redirect('admin_roster.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employee - <?php echo e($user['name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Local layout styles for the profile data grid */
        .profile-container {
            max-width: 780px;
            margin: 0 auto;
            padding: 28px 28px 24px;
            border-radius: 28px;
            background: rgba(201, 210, 184, 0.96);
            box-shadow: 0 30px 70px rgba(15, 76, 129, 0.1);
            border: 1px solid rgba(15, 76, 129, 0.08);
        }
        .profile-section {
            margin-bottom: 24px;
            padding-bottom: 22px;
            border-bottom: 1px solid rgba(15, 76, 129, 0.12);
        }
        .profile-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .profile-section h3 {
            font-size: 1.1rem;
            margin-bottom: 14px;
            color: #1e293b;
            font-weight: 700;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 18px;
        }
        .info-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 18px;
            border-radius: 20px;
            border: 1px solid rgba(15, 76, 129, 0.08);
            background: #fff;
            box-shadow: 0 14px 28px rgba(15, 76, 129, 0.04);
        }
        .info-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted-text, #64748b);
            font-weight: 700;
        }
        .info-value {
            font-size: 1rem;
            color: var(--text-color, #0f172a);
            font-weight: 600;
            line-height: 1.5;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            background-color: #f1f5f9;
            color: #475569;
            width: fit-content;
        }
        .status-badge.active { background-color: #4ebe75; color: #166534; }
        .status-badge.paused { background-color: #fef3c7; color: #92400e; }
        .status-badge.idle { background-color: #fee2e2; color: #991b1b; }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-top: 24px;
        }
        .action-grid .view-button {
            width: 80%;
            justify-content: center;
            padding: 14px 18px;
            border-radius: 16px;
            font-size: 0.98rem;
        }
        .btn-danger {
            background-color: #8d0909;
            color: white;
            border: none;
            padding: 14px 18px;
            border-radius: 16px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            display:flex;
           
            text-align: center;
            justify-content: center;
            transition: background 0.2s ease;
            font-size: 0.95rem;
        }
        .btn-danger:hover { background-color: #dc2626; }
        .auth-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2563eb, #0f172a);
            color: white;
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            box-shadow: 0 12px 26px rgba(15, 76, 129, 0.18);
        }
        .auth-card-top {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: flex-start;
            justify-content: space-between;
        }
        .section-heading h2 {
            font-size: 2rem;
            margin: 0;
            line-height: 1.1;
        }
        .section-heading p {
            color: var(--muted-text, #64748b);
            max-width: 760px;
            font-size: 1rem;
            line-height: 1.7;
            margin-top: 10px;
        }
        .helper-text {
            margin-top: 26px;
        }
        @media (max-width: 860px) {
            .profile-container {
                padding: 24px 20px 22px;
            }
            .section-heading h2 {
                font-size: 1.8rem;
            }
            .profile-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }
        }
        @media (max-width: 760px) {
            .profile-container {
                max-width: 100%;
                padding: 22px 16px 20px;
            }
            .profile-section {
                padding: 18px 0;
            }
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .action-grid {
                grid-template-columns: 1fr;
            }
            .auth-card-top {
                flex-direction: column;
                align-items: stretch;
            }
            .section-heading h2 {
                font-size: 1.7rem;
            }
            .section-heading p {
                font-size: 0.96rem;
            }
            .profile-container {
                border-radius: 24px;
            }
            .auth-badge {
                padding: 8px 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container auth-page">
        <div class="card auth-card auth-card-standalone profile-container">
            <div class="auth-card-top">
                <div class="auth-badge">Employee Profile</div>
                <div class="section-heading">
                    <p class="eyebrow"><?php echo e($user['department'] ?? 'Department Unassigned'); ?></p>
                    <h2><?php echo e($user['name']); ?></h2>
                    <p><?php echo e($user['job_title'] ?? 'Role Unassigned'); ?></p>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="profile-section">
                <h3>Work Details</h3>
                <div class="profile-grid">
                    <div class="info-group">
                        <span class="info-label">Employee ID</span>
                        <span class="info-value" style="font-family: monospace;"><?php echo e($user['employee_number']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?php echo e($user['username']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">System Access</span>
                        <span class="info-value" style="text-transform: capitalize;"><?php echo e($user['role']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Hourly Rate</span>
                        <span class="info-value">$<?php echo number_format((float)$user['hourly_rate'], 2); ?> / hr</span>
                    </div>
                </div>
            </div>

            <div class="profile-section">
                <h3>Personal Info</h3>
                <div class="profile-grid">
                    <div class="info-group">
                        <span class="info-label">Age</span>
                        <span class="info-value"><?php echo e((int)$user['age']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Experience</span>
                        <span class="info-value"><?php echo e((int)$user['years_experience']); ?> Years</span>
                    </div>
                    <?php if (trim((string)($user['email'] ?? '')) !== ''): ?>
                    <div class="info-group" style="grid-column: 1 / -1;">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><?php echo e($user['email']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-section">
                <h3>Current Status</h3>
                <div class="profile-grid">
                    <div class="info-group" style="grid-column: 1 / -1;">
                        <span class="info-label">Active Task</span>
                        <span class="info-value"><?php echo e($user['task'] ?? 'No task assigned'); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">State</span>
                        <?php
                            $rawStatus = strtolower(trim((string)($user['status'] ?? 'idle')));
                            $statusClass = $rawStatus === 'working' ? 'active' : ($rawStatus === 'paused' ? 'paused' : 'idle');
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo e(ucwords($rawStatus)); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Pending Hours</span>
                        <span class="info-value"><?php echo number_format((float)$user['pending_hours'], 2); ?> hrs</span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Approved Pay</span>
                        <span class="info-value" style="color: #16a34a; font-weight: 700;">$<?php echo number_format((float)$user['approved_pay'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="action-grid">
                <a href="admin_edit_employee.php?employee_id=<?php echo e($user['id']); ?>" class="view-button">Edit Profile</a>
                <a href="admin_reset_password.php?employee_id=<?php echo e($user['id']); ?>" class="view-button">Reset Password</a>
            </div>

            <form method="POST" onsubmit="return confirm('Are you sure you want to completely delete this employee? This action cannot be undone.');" style="margin-top: 12px;">
                <input type="hidden" name="action" value="delete_employee">
                <button type="submit" class="btn-danger">Delete Employee</button>
            </form>

            <p class="helper-text auth-helper" style="margin-top: 24px;">
                <a href="admin_roster.php" class="text-link">← Back to Employee Roster</a>
            </p>
        </div>
    </div>
</body>
</html>