<?php

namespace App\Repositories;

use App\Models\Employee;

class EmployeeRepository
{
    public function getAll()
    {
        return Employee::with(['department', 'roles', 'leaveBalances'])->get();
    }

    public function findById($id)
    {
        return Employee::with(['department', 'roles', 'leaveBalances'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Employee::create($data);
    }

    public function update(Employee $employee, array $data)
    {
        $employee->update($data);
        return $employee;
    }

    public function delete(Employee $employee)
    {
        return $employee->delete();
    }

    public function search($query)
    {
        return Employee::where('name', 'like', "%$query%")
            ->orWhere('email', 'like', "%$query%")
            ->with(['department', 'roles', 'leaveBalances'])
            ->get();
    }

    public function getByDepartment($departmentId)
    {
        return Employee::where('department_id', $departmentId)->with(['department', 'roles', 'leaveBalances'])->get();
    }

    public function getPaginated($perPage = 15)
    {
        return Employee::with(['department', 'roles', 'leaveBalances'])->paginate($perPage);
    }

    public function getByEmail($email)
    {
        return Employee::with(['department', 'roles', 'leaveBalances'])->where('email', $email)->first();
    }

    public function getByEmployeeNumber($employeeNumber)
    {
        return Employee::where('employee_number', $employeeNumber)->first();
    }

    public function getActiveEmployees()
    {
        return Employee::where('status', 'active')->with(['department', 'roles', 'leaveBalances'])->get();
    }

    public function findByDepartment($departmentId)
    {
        return Employee::where('department_id', $departmentId)->with(['department', 'roles', 'leaveBalances'])->get();
    }

    public function findByEmail($email)
    {
        return Employee::with(['department', 'roles', 'leaveBalances'])->where('email', $email)->first();
    }

    public function findByStatus($status)
    {
        return Employee::where('status',$status)->first();
    }

}
