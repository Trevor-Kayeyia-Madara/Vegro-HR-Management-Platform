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

    public function createEmployee(array $data)
    {
        return $this->employeeRepository->create($data);
    }

    public function updateEmployee(Employee $employee, array $data)
    {
        return $this->employeeRepository->update($employee, $data);
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