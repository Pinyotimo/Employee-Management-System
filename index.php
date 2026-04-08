<?php
require 'database.php';

if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin_roster.php' : 'employee_dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Project</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container landing-shell">
        <section class="landing-hero card">
            <div class="landing-copy">
                <p class="eyebrow">Employee Project</p>
                <h1>Workforce access, time tracking, and payroll in one place.</h1>
                <p class="auth-lead">
                    Sign in securely to manage employee activity, approve payments, and keep daily work moving smoothly.
                </p>
                <div class="landing-actions">
                    <a href="login.php" class="landing-btn landing-btn-primary">Go To Login</a>
                    <a href="#features" class="landing-btn landing-btn-secondary">Explore Features</a>
                </div>
            </div>
            <div class="landing-panel">
                <div class="auth-feature-list" id="features">
                    <div class="auth-feature">
                        <strong>Role-based sign-in</strong>
                        <span>Choose whether you are entering as admin or employee before access is granted.</span>
                    </div>
                    <div class="auth-feature">
                        <strong>Live time visibility</strong>
                        <span>Track active work sessions, pauses, and approved earnings from a single workflow.</span>
                    </div>
                    <div class="auth-feature">
                        <strong>Payment records</strong>
                        <span>Keep payroll approvals organized with transaction IDs and payment modes in one ledger.</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
