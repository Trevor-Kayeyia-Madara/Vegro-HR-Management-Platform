<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AtsApplication;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ReportEngine
{
    protected array $sources = [
        'employees' => [
            'label' => 'Employees',
            'model' => Employee::class,
            'base' => 'employees',
            'joins' => [
                'departments' => [
                    'type' => 'left',
                    'table' => 'departments',
                    'first' => 'employees.department_id',
                    'second' => 'departments.id',
                    'company' => true,
                ],
            ],
            'fields' => [
                'id' => ['label' => 'ID', 'expr' => 'employees.id'],
                'employee_number' => ['label' => 'Employee Number', 'expr' => 'employees.employee_number'],
                'name' => ['label' => 'Name', 'expr' => 'employees.name'],
                'email' => ['label' => 'Email', 'expr' => 'employees.email'],
                'phone' => ['label' => 'Phone', 'expr' => 'employees.phone'],
                'department_id' => ['label' => 'Department ID', 'expr' => 'employees.department_id'],
                'department_name' => ['label' => 'Department', 'expr' => 'departments.name', 'joins' => ['departments']],
                'position' => ['label' => 'Position', 'expr' => 'employees.position'],
                'salary' => ['label' => 'Salary', 'expr' => 'employees.salary'],
                'hire_date' => ['label' => 'Hire Date', 'expr' => 'employees.hire_date'],
                'status' => ['label' => 'Status', 'expr' => 'employees.status'],
                'annual_leave_days' => ['label' => 'Annual Leave Days', 'expr' => 'employees.annual_leave_days'],
                'annual_leave_used' => ['label' => 'Annual Leave Used', 'expr' => 'employees.annual_leave_used'],
                'annual_leave_balance' => ['label' => 'Annual Leave Balance', 'expr' => 'employees.annual_leave_balance'],
                'created_at' => ['label' => 'Created At', 'expr' => 'employees.created_at'],
            ],
        ],
        'departments' => [
            'label' => 'Departments',
            'model' => Department::class,
            'base' => 'departments',
            'joins' => [
                'managers' => [
                    'type' => 'left',
                    'table' => 'users',
                    'first' => 'departments.manager_id',
                    'second' => 'users.id',
                    'company' => true,
                ],
            ],
            'fields' => [
                'id' => ['label' => 'ID', 'expr' => 'departments.id'],
                'name' => ['label' => 'Name', 'expr' => 'departments.name'],
                'description' => ['label' => 'Description', 'expr' => 'departments.description'],
                'manager_id' => ['label' => 'Manager ID', 'expr' => 'departments.manager_id'],
                'manager_name' => ['label' => 'Manager', 'expr' => 'users.name', 'joins' => ['managers']],
                'created_at' => ['label' => 'Created At', 'expr' => 'departments.created_at'],
            ],
        ],
        'attendances' => [
            'label' => 'Attendances',
            'model' => Attendance::class,
            'base' => 'attendances',
            'joins' => [
                'employees' => [
                    'type' => 'left',
                    'table' => 'employees',
                    'first' => 'attendances.employee_id',
                    'second' => 'employees.id',
                    'company' => true,
                ],
                'departments' => [
                    'type' => 'left',
                    'table' => 'departments',
                    'first' => 'employees.department_id',
                    'second' => 'departments.id',
                    'company' => true,
                ],
            ],
            'fields' => [
                'id' => ['label' => 'ID', 'expr' => 'attendances.id'],
                'employee_id' => ['label' => 'Employee ID', 'expr' => 'attendances.employee_id'],
                'employee_name' => ['label' => 'Employee', 'expr' => 'employees.name', 'joins' => ['employees']],
                'department_name' => ['label' => 'Department', 'expr' => 'departments.name', 'joins' => ['employees', 'departments']],
                'date' => ['label' => 'Date', 'expr' => 'attendances.date'],
                'status' => ['label' => 'Status', 'expr' => 'attendances.status'],
                'clock_in' => ['label' => 'Clock In', 'expr' => 'attendances.clock_in'],
                'clock_out' => ['label' => 'Clock Out', 'expr' => 'attendances.clock_out'],
                'created_at' => ['label' => 'Created At', 'expr' => 'attendances.created_at'],
            ],
        ],
        'leave_requests' => [
            'label' => 'Leave Requests',
            'model' => LeaveRequest::class,
            'base' => 'leave_requests',
            'joins' => [
                'employees' => [
                    'type' => 'left',
                    'table' => 'employees',
                    'first' => 'leave_requests.employee_id',
                    'second' => 'employees.id',
                    'company' => true,
                ],
                'approvers' => [
                    'type' => 'left',
                    'table' => 'users',
                    'first' => 'leave_requests.approved_by',
                    'second' => 'users.id',
                    'company' => true,
                ],
                'departments' => [
                    'type' => 'left',
                    'table' => 'departments',
                    'first' => 'employees.department_id',
                    'second' => 'departments.id',
                    'company' => true,
                ],
            ],
            'fields' => [
                'id' => ['label' => 'ID', 'expr' => 'leave_requests.id'],
                'employee_id' => ['label' => 'Employee ID', 'expr' => 'leave_requests.employee_id'],
                'employee_name' => ['label' => 'Employee', 'expr' => 'employees.name', 'joins' => ['employees']],
                'department_name' => ['label' => 'Department', 'expr' => 'departments.name', 'joins' => ['employees', 'departments']],
                'type' => ['label' => 'Type', 'expr' => 'leave_requests.type'],
                'start_date' => ['label' => 'Start Date', 'expr' => 'leave_requests.start_date'],
                'end_date' => ['label' => 'End Date', 'expr' => 'leave_requests.end_date'],
                'leave_days' => ['label' => 'Leave Days', 'expr' => 'leave_requests.leave_days'],
                'status' => ['label' => 'Status', 'expr' => 'leave_requests.status'],
                'approved_by' => ['label' => 'Approved By ID', 'expr' => 'leave_requests.approved_by'],
                'approved_by_name' => ['label' => 'Approved By', 'expr' => 'users.name', 'joins' => ['approvers']],
                'approved_role' => ['label' => 'Approved Role', 'expr' => 'leave_requests.approved_role'],
                'created_at' => ['label' => 'Created At', 'expr' => 'leave_requests.created_at'],
            ],
        ],
        'leaves_by_status' => [
            'label' => 'Leaves by Status',
            'model' => LeaveRequest::class,
            'base' => 'leave_requests',
            'group_by' => ['leave_requests.status'],
            'fields' => [
                'status' => ['label' => 'Status', 'expr' => 'leave_requests.status'],
                'requests_count' => ['label' => 'Requests Count', 'expr' => 'COUNT(*)'],
                'leave_days_total' => ['label' => 'Total Leave Days', 'expr' => 'COALESCE(SUM(leave_requests.leave_days), 0)'],
                'annual_leave_days_total' => ['label' => 'Annual Leave Days', 'expr' => "COALESCE(SUM(CASE WHEN leave_requests.type = 'annual' THEN leave_requests.leave_days ELSE 0 END), 0)"],
            ],
        ],
        'employee_leave_summary' => [
            'label' => 'Employee Leave Summary',
            'model' => Employee::class,
            'base' => 'employees',
            'joins' => [
                'departments' => [
                    'type' => 'left',
                    'table' => 'departments',
                    'first' => 'employees.department_id',
                    'second' => 'departments.id',
                    'company' => true,
                ],
                'leave_requests' => [
                    'type' => 'left',
                    'table' => 'leave_requests',
                    'first' => 'employees.id',
                    'second' => 'leave_requests.employee_id',
                    'company' => true,
                ],
            ],
            'group_by' => [
                'employees.id',
                'employees.name',
                'employees.status',
                'departments.name',
                'employees.annual_leave_days',
                'employees.annual_leave_used',
                'employees.annual_leave_balance',
            ],
            'fields' => [
                'employee_id' => ['label' => 'Employee ID', 'expr' => 'employees.id'],
                'employee_name' => ['label' => 'Employee', 'expr' => 'employees.name'],
                'department_name' => ['label' => 'Department', 'expr' => 'departments.name', 'joins' => ['departments']],
                'employee_status' => ['label' => 'Employee Status', 'expr' => 'employees.status'],
                'annual_leave_days' => ['label' => 'Annual Leave Days', 'expr' => 'employees.annual_leave_days'],
                'annual_leave_used' => ['label' => 'Annual Leave Used', 'expr' => 'employees.annual_leave_used'],
                'annual_leave_balance' => ['label' => 'Annual Leave Balance', 'expr' => 'employees.annual_leave_balance'],
                'approved_leave_days_total' => [
                    'label' => 'Approved Leave Days (Total)',
                    'expr' => "COALESCE(SUM(CASE WHEN leave_requests.status = 'approved' THEN leave_requests.leave_days ELSE 0 END), 0)",
                    'joins' => ['leave_requests'],
                ],
                'approved_annual_leave_days' => [
                    'label' => 'Approved Annual Leave Days',
                    'expr' => "COALESCE(SUM(CASE WHEN leave_requests.status = 'approved' AND leave_requests.type = 'annual' THEN leave_requests.leave_days ELSE 0 END), 0)",
                    'joins' => ['leave_requests'],
                ],
                'pending_leave_days_total' => [
                    'label' => 'Pending Leave Days (Total)',
                    'expr' => "COALESCE(SUM(CASE WHEN leave_requests.status = 'pending' THEN leave_requests.leave_days ELSE 0 END), 0)",
                    'joins' => ['leave_requests'],
                ],
                'leave_requests_total' => [
                    'label' => 'Leave Requests (Total)',
                    'expr' => 'COUNT(leave_requests.id)',
                    'joins' => ['leave_requests'],
                ],
            ],
        ],
        'payroll_department_summary' => [
            'label' => 'Payroll Department Summary',
            'model' => Payroll::class,
            'base' => 'payrolls',
            'joins' => [
                'employees' => [
                    'type' => 'left',
                    'table' => 'employees',
                    'first' => 'payrolls.employee_id',
                    'second' => 'employees.id',
                    'company' => true,
                ],
                'departments' => [
                    'type' => 'left',
                    'table' => 'departments',
                    'first' => 'employees.department_id',
                    'second' => 'departments.id',
                    'company' => true,
                ],
            ],
            'group_by' => [
                'payrolls.year',
                'payrolls.month',
                'departments.name',
            ],
            'fields' => [
                'year' => ['label' => 'Year', 'expr' => 'payrolls.year'],
                'month' => ['label' => 'Month', 'expr' => 'payrolls.month'],
                'department_name' => ['label' => 'Department', 'expr' => 'departments.name', 'joins' => ['employees', 'departments']],
                'employees_count' => ['label' => 'Employees Count', 'expr' => 'COUNT(DISTINCT payrolls.employee_id)'],
                'gross_salary_total' => ['label' => 'Gross Salary Total', 'expr' => 'COALESCE(SUM(payrolls.gross_salary), 0)'],
                'tax_total' => ['label' => 'Tax Total', 'expr' => 'COALESCE(SUM(payrolls.tax), 0)'],
                'deductions_total' => ['label' => 'Deductions Total', 'expr' => 'COALESCE(SUM(payrolls.deductions), 0)'],
                'net_salary_total' => ['label' => 'Net Salary Total', 'expr' => 'COALESCE(SUM(payrolls.net_salary), 0)'],
            ],
        ],
        'attendance_department_summary' => [
            'label' => 'Attendance Department Summary',
            'model' => Attendance::class,
            'base' => 'attendances',
            'joins' => [
                'employees' => [
                    'type' => 'left',
                    'table' => 'employees',
                    'first' => 'attendances.employee_id',
                    'second' => 'employees.id',
                    'company' => true,
                ],
                'departments' => [
                    'type' => 'left',
                    'table' => 'departments',
                    'first' => 'employees.department_id',
                    'second' => 'departments.id',
                    'company' => true,
                ],
            ],
            'group_by' => [
                'attendances.date',
                'departments.name',
                'attendances.status',
            ],
            'fields' => [
                'date' => ['label' => 'Date', 'expr' => 'attendances.date'],
                'department_name' => ['label' => 'Department', 'expr' => 'departments.name', 'joins' => ['employees', 'departments']],
                'attendance_status' => ['label' => 'Attendance Status', 'expr' => 'attendances.status'],
                'records_count' => ['label' => 'Records Count', 'expr' => 'COUNT(*)'],
                'employees_count' => ['label' => 'Employees Count', 'expr' => 'COUNT(DISTINCT attendances.employee_id)'],
            ],
        ],
        'recruitment_funnel_summary' => [
            'label' => 'Recruitment Funnel Summary',
            'model' => AtsApplication::class,
            'base' => 'ats_applications',
            'joins' => [
                'jobs' => [
                    'type' => 'left',
                    'table' => 'ats_job_postings',
                    'first' => 'ats_applications.job_posting_id',
                    'second' => 'ats_job_postings.id',
                    'company' => true,
                ],
                'departments' => [
                    'type' => 'left',
                    'table' => 'departments',
                    'first' => 'ats_job_postings.department_id',
                    'second' => 'departments.id',
                    'company' => true,
                ],
            ],
            'group_by' => [
                'ats_applications.stage',
                'ats_job_postings.title',
                'departments.name',
            ],
            'fields' => [
                'stage' => ['label' => 'Stage', 'expr' => 'ats_applications.stage'],
                'job_title' => ['label' => 'Job', 'expr' => 'ats_job_postings.title', 'joins' => ['jobs']],
                'department_name' => ['label' => 'Department', 'expr' => 'departments.name', 'joins' => ['jobs', 'departments']],
                'applications_count' => ['label' => 'Applications Count', 'expr' => 'COUNT(*)'],
                'candidates_count' => ['label' => 'Candidates Count', 'expr' => 'COUNT(DISTINCT ats_applications.candidate_id)'],
                'average_rating' => ['label' => 'Average Rating', 'expr' => 'COALESCE(AVG(ats_applications.rating), 0)'],
            ],
        ],
        'payrolls' => [
            'label' => 'Payrolls',
            'model' => Payroll::class,
            'base' => 'payrolls',
            'joins' => [
                'employees' => [
                    'type' => 'left',
                    'table' => 'employees',
                    'first' => 'payrolls.employee_id',
                    'second' => 'employees.id',
                    'company' => true,
                ],
                'departments' => [
                    'type' => 'left',
                    'table' => 'departments',
                    'first' => 'employees.department_id',
                    'second' => 'departments.id',
                    'company' => true,
                ],
            ],
            'fields' => [
                'id' => ['label' => 'ID', 'expr' => 'payrolls.id'],
                'employee_id' => ['label' => 'Employee ID', 'expr' => 'payrolls.employee_id'],
                'employee_name' => ['label' => 'Employee', 'expr' => 'employees.name', 'joins' => ['employees']],
                'department_name' => ['label' => 'Department', 'expr' => 'departments.name', 'joins' => ['employees', 'departments']],
                'month' => ['label' => 'Month', 'expr' => 'payrolls.month'],
                'year' => ['label' => 'Year', 'expr' => 'payrolls.year'],
                'basic_salary' => ['label' => 'Basic Salary', 'expr' => 'payrolls.basic_salary'],
                'allowances' => ['label' => 'Allowances', 'expr' => 'payrolls.allowances'],
                'gross_salary' => ['label' => 'Gross Salary', 'expr' => 'payrolls.gross_salary'],
                'nssf' => ['label' => 'NSSF', 'expr' => 'payrolls.nssf'],
                'shif' => ['label' => 'SHIF', 'expr' => 'payrolls.shif'],
                'housing_levy' => ['label' => 'Housing Levy', 'expr' => 'payrolls.housing_levy'],
                'taxable_income' => ['label' => 'Taxable Income', 'expr' => 'payrolls.taxable_income'],
                'paye' => ['label' => 'PAYE', 'expr' => 'payrolls.paye'],
                'tax_rate' => ['label' => 'Tax Rate', 'expr' => 'payrolls.tax_rate'],
                'personal_relief' => ['label' => 'Personal Relief', 'expr' => 'payrolls.personal_relief'],
                'insurance_premium' => ['label' => 'Insurance Premium', 'expr' => 'payrolls.insurance_premium'],
                'insurance_relief' => ['label' => 'Insurance Relief', 'expr' => 'payrolls.insurance_relief'],
                'pension_contribution' => ['label' => 'Pension Contribution', 'expr' => 'payrolls.pension_contribution'],
                'mortgage_interest' => ['label' => 'Mortgage Interest', 'expr' => 'payrolls.mortgage_interest'],
                'deductions' => ['label' => 'Deductions', 'expr' => 'payrolls.deductions'],
                'tax' => ['label' => 'Tax', 'expr' => 'payrolls.tax'],
                'net_salary' => ['label' => 'Net Salary', 'expr' => 'payrolls.net_salary'],
                'created_at' => ['label' => 'Created At', 'expr' => 'payrolls.created_at'],
            ],
        ],
    ];

    public function metadata(): array
    {
        $sources = [];
        foreach ($this->sources as $key => $config) {
            $fields = [];
            foreach (($config['fields'] ?? []) as $fieldKey => $field) {
                $fields[] = [
                    'key' => $fieldKey,
                    'label' => (string) ($field['label'] ?? $fieldKey),
                ];
            }
            $sources[] = [
                'key' => $key,
                'label' => $config['label'],
                'fields' => $fields,
            ];
        }

        return $sources;
    }

    public function run(string $source, array $payload): array
    {
        if (!isset($this->sources[$source])) {
            return ['columns' => [], 'rows' => []];
        }

        $config = $this->sources[$source];
        $model = $config['model'];
        $allowedFields = array_keys($config['fields'] ?? []);

        $columns = array_values(array_intersect($payload['columns'] ?? [], $allowedFields));
        if (empty($columns)) {
            $columns = $allowedFields;
        }

        $query = $model::query();
        $this->applyRequiredJoins($query, $config, $columns, $payload);
        $this->applyFilters($query, $config, $payload['filters'] ?? []);
        $this->applyGroupBy($query, $config['group_by'] ?? []);
        $this->applySelect($query, $config, $columns);
        $this->applySort($query, $config, $payload['sort'] ?? null);

        $limit = (int) ($payload['limit'] ?? 500);
        $limit = max(1, min($limit, 5000));

        $rows = $query->limit($limit)->get();

        return [
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    public function getFields(string $source): array
    {
        if (!isset($this->sources[$source])) {
            return [];
        }

        $fields = [];
        foreach ($this->sources[$source]['fields'] as $key => $meta) {
            $fields[$key] = (string) ($meta['label'] ?? $key);
        }

        return $fields;
    }

    public function aggregate(string $source, array $payload, array $chart): array
    {
        if (!isset($this->sources[$source])) {
            return ['labels' => [], 'series' => []];
        }

        $config = $this->sources[$source];
        $model = $config['model'];
        $allowedFields = array_keys($config['fields'] ?? []);

        $xField = $chart['x_field'] ?? null;
        $yField = $chart['y_field'] ?? null;
        $aggregate = strtolower($chart['aggregate'] ?? 'count');

        if (!$xField || !in_array($xField, $allowedFields, true)) {
            return ['labels' => [], 'series' => []];
        }

        if ($aggregate !== 'count' && (!$yField || !in_array($yField, $allowedFields, true))) {
            return ['labels' => [], 'series' => []];
        }

        $query = $model::query();
        $this->applyRequiredJoins($query, $config, [$xField, $yField], $payload);
        $this->applyFilters($query, $config, $payload['filters'] ?? []);

        $xExpr = $this->getFieldExpr($config, $xField);
        $yExpr = $aggregate === 'count' ? '*' : $this->getFieldExpr($config, $yField);

        $alias = 'metric';
        switch ($aggregate) {
            case 'sum':
                $query->selectRaw("$xExpr as label, SUM($yExpr) as $alias");
                break;
            case 'avg':
                $query->selectRaw("$xExpr as label, AVG($yExpr) as $alias");
                break;
            case 'min':
                $query->selectRaw("$xExpr as label, MIN($yExpr) as $alias");
                break;
            case 'max':
                $query->selectRaw("$xExpr as label, MAX($yExpr) as $alias");
                break;
            default:
                $query->selectRaw("$xExpr as label, COUNT(*) as $alias");
                break;
        }

        $query->groupByRaw($xExpr);

        $sort = $payload['sort'] ?? null;
        if ($sort) {
            $this->applySort($query, $config, $sort);
        } else {
            $query->orderByRaw("$xExpr asc");
        }

        $limit = (int) ($payload['limit'] ?? 50);
        $limit = max(1, min($limit, 200));

        $rows = $query->limit($limit)->get();

        return [
            'labels' => $rows->pluck('label')->values(),
            'series' => $rows->pluck($alias)->map(fn($value) => (float) $value)->values(),
        ];
    }

    protected function applyFilters(Builder $query, array $config, array $filters): void
    {
        $allowedFields = array_keys($config['fields'] ?? []);

        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $op = strtolower($filter['op'] ?? '=');
            $value = $filter['value'] ?? null;

            if (!$field || !in_array($field, $allowedFields, true)) {
                continue;
            }

            $expr = $this->getFieldExpr($config, $field);

            switch ($op) {
                case '!=':
                case '<>':
                case '>':
                case '>=':
                case '<':
                case '<=':
                case '=':
                    $query->whereRaw("$expr $op ?", [$value]);
                    break;
                case 'like':
                    $query->whereRaw("$expr like ?", ['%' . $value . '%']);
                    break;
                case 'in':
                    $values = is_array($value) ? $value : array_map('trim', explode(',', (string) $value));
                    $query->whereIn(DB::raw($expr), $values);
                    break;
                case 'between':
                    $values = is_array($value) ? $value : array_map('trim', explode(',', (string) $value));
                    if (count($values) >= 2) {
                        $query->whereBetween(DB::raw($expr), [$values[0], $values[1]]);
                    }
                    break;
            }
        }
    }

    protected function applySort(Builder $query, array $config, ?array $sort): void
    {
        if (!$sort) {
            return;
        }

        $field = $sort['field'] ?? null;
        $allowedFields = array_keys($config['fields'] ?? []);

        if ($field && in_array($field, $allowedFields, true)) {
            $direction = strtolower($sort['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
            $expr = $this->getFieldExpr($config, $field);
            $query->orderByRaw("$expr $direction");
        }
    }

    protected function getCompanyId(): ?int
    {
        $companyId = App::has('company_id') ? App::get('company_id') : null;
        return $companyId ? (int) $companyId : null;
    }

    protected function getFieldExpr(array $config, ?string $field): string
    {
        if (!$field) {
            return "''";
        }

        $meta = $config['fields'][$field] ?? null;
        return (string) ($meta['expr'] ?? $field);
    }

    protected function applyRequiredJoins(Builder $query, array $config, array $columns, array $payload): void
    {
        $required = [];

        foreach ($columns as $column) {
            if (!$column || empty($config['fields'][$column]['joins'])) {
                continue;
            }
            foreach ($config['fields'][$column]['joins'] as $joinName) {
                $required[$joinName] = true;
            }
        }

        foreach (($payload['filters'] ?? []) as $filter) {
            $field = $filter['field'] ?? null;
            if (!$field || empty($config['fields'][$field]['joins'])) {
                continue;
            }
            foreach ($config['fields'][$field]['joins'] as $joinName) {
                $required[$joinName] = true;
            }
        }

        $sortField = $payload['sort']['field'] ?? null;
        if ($sortField && !empty($config['fields'][$sortField]['joins'])) {
            foreach ($config['fields'][$sortField]['joins'] as $joinName) {
                $required[$joinName] = true;
            }
        }

        $joins = $config['joins'] ?? [];
        if (empty($required) || empty($joins)) {
            return;
        }

        $companyId = $this->getCompanyId();

        foreach (array_keys($required) as $name) {
            $join = $joins[$name] ?? null;
            if (!$join) {
                continue;
            }

            $type = strtolower((string) ($join['type'] ?? 'left'));
            $table = (string) ($join['table'] ?? '');
            $first = (string) ($join['first'] ?? '');
            $second = (string) ($join['second'] ?? '');
            $scopeCompany = (bool) ($join['company'] ?? false);

            if ($table === '' || $first === '' || $second === '') {
                continue;
            }

            $joinFn = function ($q) use ($table, $first, $second, $companyId, $scopeCompany) {
                $q->on($first, '=', $second);
                if ($companyId && $scopeCompany) {
                    $q->where($table . '.company_id', '=', $companyId);
                }
            };

            if ($type === 'inner') {
                $query->join($table, $joinFn);
            } else {
                $query->leftJoin($table, $joinFn);
            }
        }
    }

    protected function applySelect(Builder $query, array $config, array $columns): void
    {
        $selects = [];
        foreach ($columns as $field) {
            $expr = $this->getFieldExpr($config, $field);
            $selects[] = "$expr as $field";
        }

        if (!empty($selects)) {
            $query->selectRaw(implode(', ', $selects));
        }
    }

    protected function applyGroupBy(Builder $query, array $groupBy): void
    {
        $groups = array_values(array_filter(array_map(fn ($value) => trim((string) $value), $groupBy)));
        foreach ($groups as $expr) {
            $query->groupByRaw($expr);
        }
    }
}
