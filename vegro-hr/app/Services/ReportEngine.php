<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Builder;

class ReportEngine
{
    protected array $sources = [
        'employees' => [
            'label' => 'Employees',
            'model' => Employee::class,
            'fields' => [
                'id' => 'ID',
                'employee_number' => 'Employee Number',
                'name' => 'Name',
                'email' => 'Email',
                'phone' => 'Phone',
                'department_id' => 'Department ID',
                'position' => 'Position',
                'salary' => 'Salary',
                'hire_date' => 'Hire Date',
                'status' => 'Status',
                'created_at' => 'Created At',
            ],
        ],
        'departments' => [
            'label' => 'Departments',
            'model' => Department::class,
            'fields' => [
                'id' => 'ID',
                'name' => 'Name',
                'description' => 'Description',
                'manager_id' => 'Manager ID',
                'created_at' => 'Created At',
            ],
        ],
        'attendances' => [
            'label' => 'Attendances',
            'model' => Attendance::class,
            'fields' => [
                'id' => 'ID',
                'employee_id' => 'Employee ID',
                'date' => 'Date',
                'status' => 'Status',
                'clock_in' => 'Clock In',
                'clock_out' => 'Clock Out',
                'created_at' => 'Created At',
            ],
        ],
        'leave_requests' => [
            'label' => 'Leave Requests',
            'model' => LeaveRequest::class,
            'fields' => [
                'id' => 'ID',
                'employee_id' => 'Employee ID',
                'type' => 'Type',
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'leave_days' => 'Leave Days',
                'status' => 'Status',
                'approved_by' => 'Approved By',
                'approved_role' => 'Approved Role',
                'created_at' => 'Created At',
            ],
        ],
        'payrolls' => [
            'label' => 'Payrolls',
            'model' => Payroll::class,
            'fields' => [
                'id' => 'ID',
                'employee_id' => 'Employee ID',
                'month' => 'Month',
                'year' => 'Year',
                'basic_salary' => 'Basic Salary',
                'allowances' => 'Allowances',
                'gross_salary' => 'Gross Salary',
                'nssf' => 'NSSF',
                'shif' => 'SHIF',
                'housing_levy' => 'Housing Levy',
                'taxable_income' => 'Taxable Income',
                'paye' => 'PAYE',
                'tax_rate' => 'Tax Rate',
                'personal_relief' => 'Personal Relief',
                'insurance_premium' => 'Insurance Premium',
                'insurance_relief' => 'Insurance Relief',
                'pension_contribution' => 'Pension Contribution',
                'mortgage_interest' => 'Mortgage Interest',
                'deductions' => 'Deductions',
                'tax' => 'Tax',
                'net_salary' => 'Net Salary',
                'created_at' => 'Created At',
            ],
        ],
    ];

    public function metadata(): array
    {
        $sources = [];
        foreach ($this->sources as $key => $config) {
            $fields = [];
            foreach ($config['fields'] as $field => $label) {
                $fields[] = [
                    'key' => $field,
                    'label' => $label,
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
        $allowedFields = array_keys($config['fields']);

        $columns = array_values(array_intersect($payload['columns'] ?? [], $allowedFields));
        if (empty($columns)) {
            $columns = $allowedFields;
        }

        $query = $model::query()->select($columns);
        $this->applyFilters($query, $allowedFields, $payload['filters'] ?? []);
        $this->applySort($query, $allowedFields, $payload['sort'] ?? null);

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
        return $this->sources[$source]['fields'] ?? [];
    }

    public function aggregate(string $source, array $payload, array $chart): array
    {
        if (!isset($this->sources[$source])) {
            return ['labels' => [], 'series' => []];
        }

        $config = $this->sources[$source];
        $model = $config['model'];
        $allowedFields = array_keys($config['fields']);

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
        $this->applyFilters($query, $allowedFields, $payload['filters'] ?? []);

        $select = [$xField];
        $aggregateField = $aggregate === 'count' ? '*' : $yField;
        $alias = 'metric';

        switch ($aggregate) {
            case 'sum':
                $query->selectRaw("$xField, SUM($aggregateField) as $alias");
                break;
            case 'avg':
                $query->selectRaw("$xField, AVG($aggregateField) as $alias");
                break;
            case 'min':
                $query->selectRaw("$xField, MIN($aggregateField) as $alias");
                break;
            case 'max':
                $query->selectRaw("$xField, MAX($aggregateField) as $alias");
                break;
            default:
                $query->selectRaw("$xField, COUNT(*) as $alias");
                break;
        }

        $query->groupBy($xField);

        $sort = $payload['sort'] ?? null;
        if (!empty($sort['field']) && in_array($sort['field'], $allowedFields, true)) {
            $query->orderBy($sort['field'], strtolower($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy($xField);
        }

        $limit = (int) ($payload['limit'] ?? 50);
        $limit = max(1, min($limit, 200));

        $rows = $query->limit($limit)->get();

        return [
            'labels' => $rows->pluck($xField)->values(),
            'series' => $rows->pluck($alias)->map(fn ($value) => (float) $value)->values(),
        ];
    }

    protected function applyFilters(Builder $query, array $allowedFields, array $filters): void
    {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $op = strtolower($filter['op'] ?? '=');
            $value = $filter['value'] ?? null;

            if (!$field || !in_array($field, $allowedFields, true)) {
                continue;
            }

            switch ($op) {
                case '!=':
                case '<>':
                case '>':
                case '>=':
                case '<':
                case '<=':
                case '=':
                    $query->where($field, $op, $value);
                    break;
                case 'like':
                    $query->where($field, 'like', '%' . $value . '%');
                    break;
                case 'in':
                    $values = is_array($value) ? $value : array_map('trim', explode(',', (string) $value));
                    $query->whereIn($field, $values);
                    break;
                case 'between':
                    $values = is_array($value) ? $value : array_map('trim', explode(',', (string) $value));
                    if (count($values) >= 2) {
                        $query->whereBetween($field, [$values[0], $values[1]]);
                    }
                    break;
            }
        }
    }

    protected function applySort(Builder $query, array $allowedFields, ?array $sort): void
    {
        if (!$sort) {
            return;
        }

        $field = $sort['field'] ?? null;
        if ($field && in_array($field, $allowedFields, true)) {
            $direction = strtolower($sort['direction'] ?? 'desc');
            $query->orderBy($field, $direction === 'asc' ? 'asc' : 'desc');
        }
    }
}
