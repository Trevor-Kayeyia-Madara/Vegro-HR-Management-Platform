<?php

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Role;
use App\Helpers\CsvHelper;
use Illuminate\Http\UploadedFile;
use App\Services\LeaveService;

class EmployeeService
{
    protected $employeeRepository;
    protected LeaveService $leaveService;

    public function __construct(EmployeeRepository $employeeRepository, LeaveService $leaveService)
    {
        $this->employeeRepository = $employeeRepository;
        $this->leaveService = $leaveService;
    }

    public function getAllEmployees()
    {
        return $this->employeeRepository->getAll();
    }

    public function getEmployeesPaginated($perPage = 15)
    {
        return $this->employeeRepository->getPaginated($perPage);
    }

    public function createEmployee(array $data)
    {
        $roleIds = $this->normalizeRoleIds($data['role_ids'] ?? ($data['role_id'] ?? null));
        // Generate employee_number if not provided
        if (!isset($data['employee_number'])) {
            $data['employee_number'] = 'EMP' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        // Combine first_name and last_name into name
        if (isset($data['first_name']) && isset($data['last_name'])) {
            $data['name'] = $data['first_name'] . ' ' . $data['last_name'];
        }

        // Remove first_name and last_name from data as they're not database columns
        unset($data['first_name'], $data['last_name'], $data['role_id'], $data['role_ids']);

        // Set default position if not provided
        if (!isset($data['position'])) {
            $data['position'] = 'Employee';
        }

        // Set hire_date to today if not provided
        if (!isset($data['hire_date'])) {
            $data['hire_date'] = date('Y-m-d');
        }
        // Set salary to 0 if not provided
        if (!isset($data['salary'])) {
            $data['salary'] = 0;
        }

        $annualDays = isset($data['annual_leave_days']) ? (int) $data['annual_leave_days'] : 21;
        $data['annual_leave_days'] = max($annualDays, 0);
        $data['annual_leave_used'] = isset($data['annual_leave_used']) ? max((int) $data['annual_leave_used'], 0) : 0;
        $data['annual_leave_balance'] = max($data['annual_leave_days'] - $data['annual_leave_used'], 0);

        $employee = $this->employeeRepository->create($data);

        if (!empty($roleIds)) {
            $employee->roles()->sync($roleIds);
        }

        $this->leaveService->initializeLeaveBalancesForEmployee($employee, true);

        return $employee->load(['department', 'roles']);
    }

    public function updateEmployee(Employee $employee, array $data)
    {
        $roleIds = $this->normalizeRoleIds($data['role_ids'] ?? ($data['role_id'] ?? null));
        // Combine first_name and last_name into name if both are provided
        if (isset($data['first_name']) && isset($data['last_name'])) {
            $data['name'] = $data['first_name'] . ' ' . $data['last_name'];
        } elseif (isset($data['first_name'])) {
            // If only first_name is provided, update the first part of name
            $nameParts = explode(' ', $employee->name ?? '');
            $data['name'] = $data['first_name'] . ' ' . ($nameParts[1] ?? '');
        } elseif (isset($data['last_name'])) {
            // If only last_name is provided, update the last part of name
            $nameParts = explode(' ', $employee->name ?? '');
            $data['name'] = ($nameParts[0] ?? '') . ' ' . $data['last_name'];
        }

        // Remove first_name and last_name from data as they're not database columns
        unset($data['first_name'], $data['last_name'], $data['role_id'], $data['role_ids']);

        $updated = $this->employeeRepository->update($employee, $data);

        if (!empty($roleIds)) {
            $updated->roles()->sync($roleIds);
        }

        $this->leaveService->initializeLeaveBalancesForEmployee($updated, true);

        return $updated->load(['department', 'roles']);
    }

    protected function normalizeRoleIds($roleIds): array
    {
        $ids = is_array($roleIds) ? $roleIds : ($roleIds !== null ? [$roleIds] : []);
        return array_values(array_filter($ids, function ($id) {
            return is_numeric($id) && (int) $id > 0;
        }));
    }

    public function deleteEmployee(Employee $employee)
    {
        return $this->employeeRepository->delete($employee);
    }

    public function getEmployeeById($id)
    {
        $employee = $this->employeeRepository->findById($id);
        $this->leaveService->initializeLeaveBalancesForEmployee($employee);
        return $employee->fresh(['department', 'roles', 'leaveBalances']);
    }

    public function getEmployeeByEmail($email)
    {
        $employee = $this->employeeRepository->findByEmail($email);
        if ($employee) {
            $this->leaveService->initializeLeaveBalancesForEmployee($employee);
            return $employee->fresh(['department', 'roles', 'leaveBalances']);
        }
        return $employee;
    }

    public function getEmployeesByDepartment($departmentId)
    {
        return $this->employeeRepository->findByDepartment($departmentId);
    }

    public function exportEmployeesToCSV(): string
    {
        $employees = $this->employeeRepository->getAll();
        $header = [
            'employee_number',
            'name',
            'email',
            'phone',
            'department_id',
            'department_name',
            'position',
            'salary',
            'hire_date',
            'status',
            'role_ids',
            'role_titles',
        ];

        $csv = CsvHelper::row($header);

        foreach ($employees as $employee) {
            $departmentName = $employee->department?->name;
            $roleIds = $employee->roles->pluck('id')->implode('|');
            $roleTitles = $employee->roles->pluck('title')->implode('|');

            $csv .= CsvHelper::row([
                $employee->employee_number,
                $employee->name,
                $employee->email,
                $employee->phone,
                $employee->department_id,
                $departmentName,
                $employee->position,
                $employee->salary,
                CsvHelper::formatDate($employee->hire_date),
                $employee->status,
                $roleIds,
                $roleTitles,
            ]);
        }

        return $csv;
    }

    public function importEmployeesFromCSV(UploadedFile $file, string $mode = 'upsert'): array
    {
        $path = $file->getRealPath();
        $csv = new \SplFileObject($path);
        $csv->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        $header = null;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $rowNumber = 0;

        foreach ($csv as $row) {
            $rowNumber++;
            if ($row === [null] || $row === false) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($value) => CsvHelper::normalizeHeader((string) $value), $row);
                continue;
            }

            $data = [];
            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $data[$key] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $hasContent = collect($data)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
            if (!$hasContent) {
                continue;
            }

            try {
                $employeeData = [];
                $employeeData['employee_number'] = $data['employee_number'] ?? null;
                $employeeData['name'] = $data['name'] ?? null;
                $employeeData['email'] = $data['email'] ?? null;
                $employeeData['phone'] = $data['phone'] ?? null;
                $employeeData['position'] = $data['position'] ?? null;
                $employeeData['salary'] = $data['salary'] !== null && $data['salary'] !== '' ? (float) $data['salary'] : null;
                $employeeData['hire_date'] = CsvHelper::parseDateForStorage($data['hire_date'] ?? null);
                $employeeData['status'] = $data['status'] ?? null;

                if (!$employeeData['name']) {
                    $firstName = $data['first_name'] ?? null;
                    $lastName = $data['last_name'] ?? null;
                    if ($firstName || $lastName) {
                        $employeeData['name'] = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
                    }
                }

                if (empty($employeeData['email'])) {
                    throw new \Exception('Missing email');
                }

                if (empty($employeeData['name'])) {
                    throw new \Exception('Missing name or first_name/last_name');
                }

                if (!empty($data['hire_date']) && !$employeeData['hire_date']) {
                    throw new \Exception('Invalid hire_date. Use DD-MM-YYYY or YYYY-MM-DD');
                }

                $departmentId = $data['department_id'] ?? null;
                if (!$departmentId && !empty($data['department_name'])) {
                    $departmentId = Department::where('name', $data['department_name'])->value('id');
                }

                if ($departmentId) {
                    $departmentId = (int) $departmentId;
                    if (!Department::where('id', $departmentId)->exists()) {
                        throw new \Exception('Department not found');
                    }
                    $employeeData['department_id'] = $departmentId;
                }

                $roleIds = CsvHelper::splitList($data['role_ids'] ?? null);
                $roleTitles = CsvHelper::splitList($data['role_titles'] ?? null);

                if (!empty($roleTitles)) {
                    $roleIds = Role::whereIn('title', $roleTitles)->pluck('id')->all();
                }

                if (!empty($roleIds)) {
                    $employeeData['role_ids'] = array_values(array_filter(array_map('intval', $roleIds)));
                }

                $existing = null;
                if (!empty($employeeData['employee_number'])) {
                    $existing = $this->employeeRepository->getByEmployeeNumber($employeeData['employee_number']);
                }

                if (!$existing && !empty($employeeData['email'])) {
                    $existing = $this->employeeRepository->findByEmail($employeeData['email']);
                }

                if ($existing) {
                    if ($mode === 'skip') {
                        $skipped++;
                        continue;
                    }
                    $this->updateEmployee($existing, $employeeData);
                    $updated++;
                } else {
                    $this->createEmployee($employeeData);
                    $created++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
