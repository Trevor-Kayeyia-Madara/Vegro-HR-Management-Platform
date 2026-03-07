<?php

namespace App\Services;

use App\Models\Employee;

class EmployeeService
{
    public function getAllEmployees()
    {
        return Employee::with('department')->get();
    }

    public function createEmployee(array $data)
    {
        return Employee::create($data);
    }

    public function updateEmployee(Employee $employee, array $data)
    {
        $employee->update($data);
        return $employee;
    }

    public function deleteEmployee(Employee $employee)
    {
        return $employee->delete();
    }
}