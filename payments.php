<?php
require 'database.php';

if (!isset($_SESSION['role'])) {
    redirect('login.php');
}

$role = $_SESSION['role'];
$currentUserIndex = findUserIndexById($db, $_SESSION['user_id'] ?? null);
$currentUser = $currentUserIndex !== null ? $db['users'][$currentUserIndex] : null;

if ($currentUser === null) {
    session_destroy();
    redirect('login.php');
}

$transactionId = trim($_GET['transaction_id'] ?? '');
$flash = getFlash();
$payments = [];

foreach ($db['payments'] as $payment) {
    if ($role === 'admin') {
        if (($payment['company_id'] ?? 0) === ($currentUser['company_id'] ?? 0)) {
            $payments[] = $payment;
        }
    } elseif ((string)$payment['employee_id'] === (string)$currentUser['id']) {
        $payments[] = $payment;
    }
}

usort($payments, static function ($left, $right) {
    return (int)$right['created_at'] <=> (int)$left['created_at'];
});

$totalPaid = 0;
foreach ($payments as $payment) {
    $totalPaid += (float)$payment['amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="navbar">
            <?php if ($role === 'admin'): ?>
                <div class="nav-brand">
                    <h2>Admin Panel</h2>
                </div>
            <?php else: ?>
                <div class="nav-brand">
                    <h2><?php echo e($currentUser['name']); ?></h2>
                </div>
            <?php endif; ?>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-panel">
                <?php if ($role === 'admin'): ?>
                    <div class="nav-links">
                        <a href="admin_roster.php">Employee Roster</a>
                        <a href="admin_payroll.php">Payroll Manager</a>
                        <a href="payments.php" class="active-link">Payments</a>
                        <a href="admin_register.php">Register Employee</a>
                    </div>
                <?php else: ?>
                    <div class="nav-links">
                        <a href="employee_dashboard.php">Dashboard</a>
                        <a href="payments.php" class="active-link">Payment History</a>
                    </div>
                <?php endif; ?>
                <a href="database.php?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>

        <div class="card">
            <div class="section-heading">
                <p class="eyebrow">Payments</p>
                <h2><?php echo $role === 'admin' ? 'Approved Payment Ledger' : 'Your Payment History'; ?></h2>
                <p>
                    <?php echo $role === 'admin'
                        ? 'Each payroll approval is recorded here with the transaction ID and the payment mode used.'
                        : 'Every approved payment appears here with its transaction reference and payout method.'; ?>
                </p>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label"><?php echo $role === 'admin' ? 'Total Paid Out' : 'Total Received'; ?></span>
                    <strong>$<?php echo number_format($totalPaid, 2); ?></strong>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Transactions</span>
                    <strong><?php echo count($payments); ?></strong>
                </div>
            </div>
            <?php if (empty($payments)): ?>
                <div class="empty-state">No payments have been recorded yet.</div>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Transaction ID</th>
                        <?php if ($role === 'admin'): ?><th>Employee</th><?php endif; ?>
                        <th>Hours Paid</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Approved By</th>
                        <th>Date</th>
                    </tr>
                    <?php foreach ($payments as $payment): ?>
                        <tr class="<?php echo $transactionId !== '' && $transactionId === $payment['transaction_id'] ? 'highlight-row' : ''; ?>">
                            <td>
                                <b><?php echo e($payment['transaction_id']); ?></b>
                                <div class="table-subtext">Rate: $<?php echo number_format((float)$payment['hourly_rate'], 2); ?>/hr</div>
                            </td>
                            <?php if ($role === 'admin'): ?>
                                <td><?php echo e($payment['employee_name']); ?></td>
                            <?php endif; ?>
                            <td><?php echo number_format((float)$payment['hours_paid'], 2); ?> hrs</td>
                            <td>$<?php echo number_format((float)$payment['amount'], 2); ?></td>
                            <td><?php echo e($payment['payment_mode']); ?></td>
                            <td><?php echo e($payment['approved_by']); ?></td>
                            <td><?php echo e(date('M d, Y H:i', (int)$payment['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
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
