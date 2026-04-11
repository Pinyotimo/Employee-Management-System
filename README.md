# Employee-Management-System

A lightweight employee management system that supports admin-controlled employee registration, task assignment, payroll, and clock tracking.

## Features

- Admin-managed employee creation and account setup
- Admin-only sign-up creates a new company admin account
- Tenant-aware company support: admins only manage employees in their own company
- Employee profile history stored with:
  - Employee ID number
  - Age
  - Years of experience
- Clock-in/clock-out workflow with task assignment enforcement
- Pause/resume break support for active shifts
- Password reset support using secure reset tokens
- Admin override reset and employee deletion support
- Company-scoped payroll and payment records for tenant isolation
- Responsive admin roster with a dedicated employee view page

## Pages

- `index.php` — landing page
- `login.php` — sign in for admins and employees
- `signup.php` — create a new admin account only
- `admin_register.php` — admin creates new employee and admin accounts
- `admin_roster.php` — admin roster, task assignment, and employee view access
- `admin_view_employee.php` — detailed employee profile, edit, reset password, and delete
- `admin_edit_employee.php` — update employee profile, pay, role, and task
- `admin_reset_password.php` — admin override reset page
- `forgot_password.php` — request employee password reset link
- `reset_password.php` — complete password reset with token
- `employee_dashboard.php` — employee clock and assignment dashboard
- `payments.php` — payment history and payroll records
- `database.php` / `database.json` — data storage and helper functions

## Notes

- Employees cannot clock in unless an admin assigns a task.
- Profile and password reset actions are centralized in the employee view page.
- The system uses password hashing for secure credential storage.
- Each installation can support multiple companies via company-scoped admin accounts and employee records.
