<?php

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Models\Employee;

class EmployeeService
{
    protected $employeeRepository;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
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
        $roleIds = $data['role_ids'] ?? ($data['role_id'] ?? null);
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

        $employee = $this->employeeRepository->create($data);

        if ($roleIds) {
            $ids = is_array($roleIds) ? $roleIds : [$roleIds];
            $employee->roles()->sync($ids);
        }

        return $employee->load(['department', 'roles']);
    }

    public function updateEmployee(Employee $employee, array $data)
    {
        $roleIds = $data['role_ids'] ?? ($data['role_id'] ?? null);
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

        if ($roleIds) {
            $ids = is_array($roleIds) ? $roleIds : [$roleIds];
            $updated->roles()->sync($ids);
        }

        return $updated->load(['department', 'roles']);
    }

    public function deleteEmployee(Employee $employee)
    {
        return $this->employeeRepository->delete($employee);
    }

    public function getEmployeeById($id)
    {
        return $this->employeeRepository->findById($id);
    }

    public function getEmployeeByEmail($email)
    {
        return $this->employeeRepository->findByEmail($email);
    }

    public function getEmployeesByDepartment($departmentId)
    {
        return $this->employeeRepository->findByDepartment($departmentId);
    }
}
