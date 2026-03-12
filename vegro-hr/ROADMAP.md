# Vegro HR SaaS Roadmap Tracking

Last reviewed: 2026-03-12

Legend:
- Done: Implemented in repository and referenced below.
- In progress: Partially implemented or present but incomplete.
- Not started: No code found in repository.
- External: Exists outside the repo (hosting, marketing, operations).

## Phase Summary

Phase | Status | Notes
--- | --- | ---
0 - Core Setup | Done | Core HR modules, RBAC, payroll, tax profiles, dashboards present.
1 - Core HR SaaS Foundation | Done | Multi-tenant scaffolding, CSV import/export, report builder, audit logs implemented.
2 - Public Demo & Landing Page | In progress | Landing, pricing, contact pages present; demo form not wired; hosting/marketing external.
3 - Multi-Tenant SaaS Infrastructure | In progress | Company onboarding, domain-based tenants, environment gating present; subdomain routing and environment separation not fully covered.
4 - Advanced HR Features & Analytics | Not started | Custom fields, user-defined dashboards, dynamic report builder not found.
5 - Integration & Extensibility | Not started | Webhooks and external integrations not found.
6 - Business Suite Expansion | Not started | No modules beyond HR found.
7 - Investor & Market Readiness | External | Deck, testimonials, marketing assets not in repo.
8 - Scaling & Cloud Deployment | External | CI/CD, backups, monitoring not in repo.
9 - Continuous Improvement | Ongoing | Process item, not tracked in code.

## Phase 0 - Core Setup (Done)

- Backend HR core: employees, departments, payroll, attendance, leave.
  - Evidence: app/Http/Controllers/*Controller.php, app/Models/*.php, database/migrations/2026_03_06_*.
- RBAC and permissions.
  - Evidence: app/Http/Controllers/RoleController.php, app/Models/Role.php, app/Models/Permission.php, database/migrations/2026_03_10_190000_create_permissions_table.php.
- Payroll with tax profiles.
  - Evidence: app/Models/TaxProfile.php, database/migrations/2026_03_10_150000_create_tax_profiles_table.php.
- Dashboards (admin, HR, finance, employee, manager, director).
  - Evidence: vegro-hr-frontend/src/router/index.js, vegro-hr-frontend/src/pages/*/Dashboard.vue.

## Phase 1 - Core HR SaaS Foundation (Done)

- Multi-tenant ready (company_id and scoping).
  - Done: companies, company_domains, company_settings, subscriptions, plans tables.
  - Evidence: database/migrations/2026_03_12_090000_create_companies_table.php through 090600_create_activity_logs_table.php.
  - Done: global company scope and middleware enforcement.
  - Evidence: app/Models/Concerns/BelongsToCompany.php, app/Http/Middleware/EnsureCompanyContext.php, app/Http/Middleware/ResolveCompanyFromDomain.php.
- CSV import/export for employees, payroll, attendance.
  - Done: import/export endpoints and UI wired.
  - Evidence: app/Http/Controllers/EmployeeController.php, app/Http/Controllers/PayrollController.php,
    app/Http/Controllers/AttendanceController.php, vegro-hr-frontend/src/pages/Employees/Employees.vue,
    vegro-hr-frontend/src/pages/Payroll/Payroll.vue, vegro-hr-frontend/src/pages/Attendance/Attendance.vue.
- User-defined report builder.
  - Done: report definitions, run endpoint, and frontend builder.
  - Evidence: app/Http/Controllers/ReportController.php, app/Models/ReportDefinition.php,
    database/migrations/2026_03_12_120000_create_report_definitions_table.php,
    vegro-hr-frontend/src/pages/Reports/Reports.vue.
- Audit logs per company.
  - Done: activity_logs table + service, role and permission audit tables.
  - Evidence: app/Services/ActivityLogService.php, app/Http/Controllers/ActivityLogController.php,
    database/migrations/2026_03_10_130000_create_role_assignment_audits_table.php,
    database/migrations/2026_03_10_190200_create_permission_assignment_audits_table.php,
    database/migrations/2026_03_12_090600_create_activity_logs_table.php.

## Phase 2 - Public Demo & Landing Page (In Progress)

- Landing, pricing, contact pages.
  - Done in frontend.
  - Evidence: vegro-hr-frontend/src/pages/Landing/Landing.vue, Pricing/Pricing.vue, Contact/Contact.vue.
- Book a Demo form.
  - Present, but no backend integration or external embed found.
  - Evidence: vegro-hr-frontend/src/pages/Contact/Contact.vue.
- Demo environment.
  - Partial: company environment flag and demo reset tooling exist.
  - Evidence: database/migrations/2026_03_12_090000_create_companies_table.php,
    app/Console/Commands/ResetDemoData.php, app/Http/Middleware/EnsureCompanyEnvironment.php.
- Hosting and marketing.
  - External: not tracked in repo.

## Phase 3 - Multi-Tenant SaaS Infrastructure (In Progress)

- Super Admin can create companies and seed defaults.
  - Done: CompanyController + onboarding service.
  - Evidence: app/Http/Controllers/CompanyController.php, app/Services/CompanyOnboardingService.php.
- Company-specific domains.
  - Done: domain mapping by host.
  - Evidence: app/Models/CompanyDomain.php, app/Http/Middleware/ResolveCompanyFromDomain.php.
- Separate demo, staging, live environments.
  - Partial: environment field and middleware gate exist, but no deployment separation tracked.
  - Evidence: database/migrations/2026_03_12_090000_create_companies_table.php, app/Http/Middleware/EnsureCompanyEnvironment.php.
- Tenant isolation.
  - Done: global scope applied to core models; company_id auto-assignment.
  - Evidence: app/Models/Concerns/BelongsToCompany.php plus model usage.

## Phase 4 - Advanced HR Features & Analytics (Not Started)

- User-defined dashboards per company.
  - Not found.
- Custom fields and user-defined tables.
  - Not found.
- Advanced payroll rules (multiple tax profiles, recurring deductions).
  - Partial: tax profiles exist; advanced rules not found.
- Dynamic reports and exports (CSV/PDF).
  - Not found beyond payslip and leave CSV.

## Phase 5 - Integration & Extensibility (Not Started)

- Public API coverage for all core HR features.
  - Partial: API controllers exist but no explicit full coverage audit.
- Webhooks.
  - Not found.
- External integrations (accounting, email, analytics).
  - Not found.

## Phase 6 - Business Suite Expansion (Not Started)

- ERP/CRM/Inventory/PM/BI modules.
  - Not found.

## Phase 7 - Investor & Market Readiness (External)

- Deck, testimonials, video walkthroughs.
  - Not tracked in repo.

## Phase 8 - Scaling & Cloud Deployment (External)

- Cloud deploy, backups, monitoring, CI/CD.
  - Not tracked in repo.

## Phase 9 - Continuous Improvement (Ongoing)

- Feedback loop and incremental improvements.
  - Ongoing, not tracked here.

## Suggested Next Tracking Actions

- Add CSV import/export tasks for employees, payroll, attendance.
- Define report builder scope and add a minimal MVP spec.
- Add webhook scaffolding and event list.
- Document deployment environments and hosting in a separate ops doc.
