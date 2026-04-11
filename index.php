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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Project | Modern Team Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Custom Landing Page Enhancements */
        .landing-shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .landing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color, #2563eb);
            letter-spacing: -0.02em;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
            align-items: center;
            margin-top: 60px;
            padding: 60px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
        }

        .landing-copy h1 {
            font-size: 3rem;
            line-height: 1.1;
            margin: 15px 0 25px;
            color: #0f172a;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
        }

        .hero-stats strong {
            display: block;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .hero-stats span {
            font-size: 0.85rem;
            color: #64748b;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-top: 40px;
        }

        .feature-card {
            transition: transform 0.2s ease;
            padding: 30px;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .icon-box {
            font-size: 2rem;
            margin-bottom: 15px;
            display: block;
        }

        @media (max-width: 900px) {
            .hero-grid {
                grid-template-columns: 1fr;
                padding: 40px 20px;
            }
            .landing-copy h1 { font-size: 2.2rem; }
            .hero-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container landing-shell">
        <header class="landing-header">
            <div class="brand">Modern HR Management System</div>
            <nav class="hero-nav">
                <a href="login.php" class="landing-btn landing-btn-tertiary">Login</a>
                <a href="signup.php" class="landing-btn landing-btn-primary">Sign Up</a>
            </nav>
        </header>

        <section class="landing-hero card hero-grid">
            <div class="landing-copy">
                <p class="eyebrow">Enterprise Solutions for Small Teams</p>
                <h1>Manage your payroll, people, and progress.</h1>
                <p class="auth-lead" style="font-size: 1.2rem; color: #475569;">
                    The all-in-one dashboard to track departments, specific job titles, work hours, and payroll with absolute tenant-safe security.
                </p>
                <div class="landing-actions">
                    <a href="signup.php" class="landing-btn landing-btn-primary">Get Started as Admin</a>
                    <a href="login.php" class="landing-btn landing-btn-secondary">Staff Login</a>
                </div>
                
                <div class="hero-stats">
                    <div>
                        <strong>Multi-Role</strong>
                        <span>Software, HR, Janitorial—organize any role.</span>
                    </div>
                    <div>
                        <strong>Departmental</strong>
                        <span>Group employees into logical cost centers.</span>
                    </div>
                    <div>
                        <strong>One-Click Pay</strong>
                        <span>Approve hours and generate payroll instantly.</span>
                    </div>
                </div>
            </div>

            <div class="landing-panel">
                <div class="feature-card" style="background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                    <span class="icon-box">🏢</span>
                    <h3 style="margin-bottom: 10px;">Why this system?</h3>
                    <p style="font-size: 0.95rem; line-height: 1.6; color: #64748b;">
                        We built this for businesses that outgrew spreadsheets but aren't ready for complex enterprise software. Simple, fast, and secure.
                    </p>
                </div>
                <div class="feature-card slim-card" style="margin-top: 20px; background: #eff6ff; border: 1px solid #bfdbfe;">
                    <strong>✨ New Feature</strong>
                    <p style="font-size: 0.85rem; margin: 0;">Detailed job titles and department tracking now live.</p>
                </div>
            </div>
        </section>

        <section class="feature-section" id="features">
            <div class="section-heading" style="text-align: center;">
                <p class="eyebrow">Platform Capabilities</p>
                <h2 style="font-size: 2.2rem;">Everything your team needs.</h2>
            </div>
            
            <div class="feature-grid">
                <article class="feature-card card">
                    <span class="icon-box">🔐</span>
                    <strong>Role-Based Access</strong>
                    <p>Admins manage the company; employees manage their time. Zero overlap, total security.</p>
                </article>
                
                <article class="feature-card card">
                    <span class="icon-box">📊</span>
                    <strong>Departmental View</strong>
                    <p>Sort and view your roster by Department or Job Title to see exactly how your team is allocated.</p>
                </article>
                
                <article class="feature-card card">
                    <span class="icon-box">⏱️</span>
                    <strong>Live Time Tracking</strong>
                    <p>Clock-in and clock-out with automated break tracking. No more manual timecard entry.</p>
                </article>
                
                <article class="feature-card card">
                    <span class="icon-box">💰</span>
                    <strong>Payroll Records</strong>
                    <p>Transparent history for employees and detailed management tools for admins.</p>
                </article>
            </div>
        </section>

        <footer class="landing-footer">
            &copy; <?php echo date('Y'); ?> EmployeeProject. Built for efficiency.
        </footer>
    </div>
</body>
</html>