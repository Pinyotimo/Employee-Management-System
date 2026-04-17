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

// Handle Payroll Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_pay') {
    $employeeId = $_POST['employee_id'] ?? '';
    $paymentMode = trim($_POST['payment_mode'] ?? '');
    $userIndex = findUserIndexById($db, $employeeId);

    if ($userIndex !== null
        && ($db['users'][$userIndex]['company_id'] ?? 0) === ($currentAdmin['company_id'] ?? 0)
        && $db['users'][$userIndex]['role'] !== 'admin'
    ) {
        if (!in_array($paymentMode, paymentModes(), true)) {
            setFlash('error', 'Please choose a valid payment mode.');
            redirect('admin_payroll.php');
        }

        $pay = $db['users'][$userIndex]['pending_hours'] * $db['users'][$userIndex]['hourly_rate'];

        if ($pay > 0) {
            $hoursPaid = (float)$db['users'][$userIndex]['pending_hours'];
            $transactionId = generateTransactionId();

            $db['users'][$userIndex]['approved_pay'] += $pay;
            $db['users'][$userIndex]['pending_hours'] = 0;
            $db['payments'][] = [
                'id' => uniqid('payment_', true),
                'transaction_id' => $transactionId,
                'employee_id' => $db['users'][$userIndex]['id'],
                'employee_name' => $db['users'][$userIndex]['name'],
                'company_id' => $db['users'][$userIndex]['company_id'] ?? 0,
                'hours_paid' => $hoursPaid,
                'hourly_rate' => (float)$db['users'][$userIndex]['hourly_rate'],
                'amount' => $pay,
                'payment_mode' => $paymentMode,
                'created_at' => time(),
                'approved_by' => (string)($_SESSION['name'] ?? 'Administrator')
            ];
            saveDb($db);
            setFlash('success', "Payment approved successfully. Transaction ID: {$transactionId}");
            redirect('payments.php?transaction_id=' . urlencode($transactionId));
        } else {
            setFlash('error', 'This employee has no pending hours to approve.');
        }
    } else {
        setFlash('error', 'Unable to find the selected employee.');
    }

    redirect('admin_payroll.php');
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Manager</title>
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
                    <a href="admin_payroll.php" class="active-link">Payroll Manager</a>
                    <a href="payments.php">Payments</a>
                    <a href="admin_register.php">Register Employee</a>
                </div>
                <a href="database.php?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>

        <div class="card">
            <div class="section-heading">
                <p class="eyebrow">Finance</p>
                <h2>Payroll Approvals</h2>
                <p>Review unpaid hours and approve the amount to move into confirmed earnings.</p>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="4000"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <div class="table-responsive">
            <table>
                <tr>
                    <th>Employee Name</th>
                    <th>Hourly Rate</th>
                    <th>Pending Hrs (Unpaid)</th>
                    <th>Total Approved Pay</th>
                    <th>Payroll Action</th>
                </tr>
                <?php foreach ($db['users'] as $u): if ($u['role'] !== 'admin' && ($u['company_id'] ?? 0) === ($currentAdmin['company_id'] ?? 0)): ?>
                <tr>
                    <td data-label="Employee Name"><b><?php echo e($u['name']); ?></b></td>
                    <td data-label="Hourly Rate">$<?php echo number_format((float)$u['hourly_rate'], 2); ?>/hr</td>
                    <td data-label="Pending Hours"><b><?php echo number_format((float)$u['pending_hours'], 2); ?> hrs</b></td>
                    <td data-label="Approved Pay"><b style="color: green;">$<?php echo number_format((float)$u['approved_pay'], 2); ?></b></td>
                    <td data-label="Payroll Action">
                        <form method="POST" class="payroll-form">
                            <input type="hidden" name="action" value="approve_pay">
                            <input type="hidden" name="employee_id" value="<?php echo e($u['id']); ?>">
                            <select name="payment_mode" <?php echo $u['pending_hours'] <= 0 ? 'disabled' : ''; ?>>
                                <?php foreach (paymentModes() as $mode): ?>
                                    <option value="<?php echo e($mode); ?>"><?php echo e($mode); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button
                                type="submit"
                                class="btn-success compact-button"
                                <?php echo $u['pending_hours'] <= 0 ? 'disabled' : ''; ?>
                            >
                                Approve Pay
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endif; endforeach; ?>
            </table>
            </div>
        </div>
    </div>
    <script src="app.js"></script>
</body>
</html>
