# Vegro HR — Employee Management & Payroll System

## Overview

Vegro HR is a modern Employee Management and Payroll system built with Laravel, designed for small to medium businesses.

This system allows you to:

- Create and manage employee records
- Track departments and roles
- Handle payroll generation and payslips
- Manage attendance and leave requests
- Integrate roles and permissions for secure access
- Log activity with MongoDB for audit purposes

This README covers the current Phase 2, which includes full database architecture and backend modules.

## Tech Stack

- **Backend:** PHP 8, Laravel 10
- **Database:** MySQL (core HR data), MongoDB (activity logs)
- **Authentication & Authorization:** Laravel Breeze / Spatie Permissions
- **Frontend (Phase 3 upcoming):** Blade + Tailwind CSS / Livewire (planned)

## Phase 2 — Core Modules & Database

### Tables & Relationships

**Departments**

- Fields: `id`, `name`, `manager_id`
- Relationships: 1 department → many employees

**Employees**

- Fields: `id`, `employee_number`, `user_id`, `name`, `email`, `phone`, `department_id`, `position`, `salary`, `hire_date`, `status`
- Relationships: belongs to department, has many payrolls, attendance, leave requests

**Payrolls & Payslips**

- Payroll: calculates `net_salary` automatically
- Payslip: linked to payroll, stores PDF path

**Attendance**

- Tracks clock in/out and status (present, absent, late)

**Leave Requests**

- Tracks leave type, start/end dates, status, approved by user

**Users / Roles & Permissions**

- Roles via Spatie package
- Users assigned roles with permissions

**Activity Logs (MongoDB)**

- Stores user actions, timestamps, and metadata for auditing

### Module Features (Phase 2)

- Full MCR (Model + Controller + Resource) structure
- CRUD operations ready for:
  - Departments
  - Employees
  - Payrolls & Payslips
  - Attendance
  - Leave Requests
- Relationships defined and validated
- Net salary calculation and payslip generation logic implemented

## Installation

```bash
# Clone the repository
git clone https://github.com/Trevor-Kayeyia-Madara/vegro.git
cd vegro

# Install dependencies
composer install
npm install
npm run dev

# Configure .env file
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=vegro_hr
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed initial data (optional)
php artisan db:seed

# Serve the app
php artisan serve
```

## API Endpoints (Phase 2)

| Module       | Endpoint             | Method | Description                  |
|--------------|-------------------|--------|------------------------------|
| Departments  | /departments       | GET    | List all departments         |
| Departments  | /departments       | POST   | Create a department          |
| Employees    | /employees         | GET    | List all employees           |
| Payrolls     | /payrolls          | POST   | Create payroll               |
| Payslips     | /payslips          | POST   | Generate payslip             |
| Attendance   | /attendances       | POST   | Record attendance            |
| Leave        | /leave-requests    | POST   | Submit leave request         |

## Next Steps (Phase 3)

- Build modern UI dashboard using Tailwind CSS and Blade
- Implement Livewire components for real-time updates
- Add PDF generation for payslips
- Integrate reporting and analytics dashboards

## Contributing

1. Fork the repo
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -m "feat: description"`)
4. Push to branch (`git push origin feature/your-feature`)
5. Open a Pull Request

## License

MIT License — free for personal and commercial use.