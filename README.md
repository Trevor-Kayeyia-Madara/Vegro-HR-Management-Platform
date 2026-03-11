# Vegro HR

Modern HR platform for employee management, payroll, attendance, and access control. Built as a portfolio-grade system with clean domain modeling, API-ready modules, and room for a rich front-end experience.

**Badges**
![License](https://img.shields.io/badge/License-MIT-black)
![Backend](https://img.shields.io/badge/Backend-Laravel%2010-red)
![PHP](https://img.shields.io/badge/PHP-8-blue)
![DB](https://img.shields.io/badge/DB-MySQL%20%2B%20MongoDB-teal)

**Highlights**
- End-to-end HR core: employees, departments, attendance, leave, payroll, payslips
- Role-based access with audit-friendly activity logs
- Laravel-first architecture with clear module boundaries
- Designed for Phase 3 UI expansion and analytics

**Project Layout**
- `vegro-hr/` Laravel backend and core modules
- `vegro-hr/vegro-hr-frontend/` UI workspace (in progress)

**Tech Stack**
- Backend: PHP 8, Laravel 10
- Database: MySQL (core HR), MongoDB (activity logs)
- Auth: Laravel Breeze + Spatie Permissions
- UI (Phase 3): Blade + Tailwind CSS / Livewire

**Core Modules**
- Departments: structure, managers, assignments
- Employees: profiles, positions, salary, status
- Attendance: clock-in/out, status tracking
- Leave Requests: approval workflows
- Payroll + Payslips: net salary calculation and records
- Activity Logs: audit trail for critical actions

**Demo**
- Live demo: `TBD`
- Walkthrough video: `TBD`

**Screenshots**
- Coming soon. I will add UI previews here.

**API Snapshot**
| Module | Endpoint | Method | Description |
|---|---|---|---|
| Departments | `/departments` | GET | List all departments |
| Departments | `/departments` | POST | Create a department |
| Employees | `/employees` | GET | List all employees |
| Payrolls | `/payrolls` | POST | Create payroll |
| Payslips | `/payslips` | POST | Generate payslip |
| Attendance | `/attendances` | POST | Record attendance |
| Leave | `/leave-requests` | POST | Submit leave request |

**Quick Start**
```bash
git clone https://github.com/Trevor-Kayeyia-Madara/vegro.git
cd vegro/vegro-hr
composer install
npm install
npm run dev
php artisan migrate
php artisan db:seed
php artisan serve
```

**Environment**
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vegro_hr
DB_USERNAME=root
DB_PASSWORD=
```

**Roadmap**
- Phase 3 UI: modern dashboard, real-time Livewire components
- PDF payslips and exportable reports
- Analytics and workforce insights

**Notes**
- Backend implementation details live in `vegro-hr/README.md`.
- This repo is intended as a portfolio project; contributions are welcome.

**About the Author**
Hi, I’m Trevor Madara — a software engineer focused on building practical, well-structured products. I enjoy working on clean architecture, robust APIs, and polished user experiences. Connect with me on LinkedIn and see more of my work on GitHub.

- LinkedIn: `https://www.linkedin.com/in/trevor-madara/`
- GitHub: `https://github.com/Trevor-Kayeyia-Madara`

**License**
MIT License — free for personal and commercial use.
