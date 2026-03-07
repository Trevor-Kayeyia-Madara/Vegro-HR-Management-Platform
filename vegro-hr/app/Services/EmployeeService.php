<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Department;

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

    public function getEmployeeById($id)
    {
        return Employee::with('department')->findOrFail($id);
    }

    public function getEmployeesByDepartment($departmentId)
    {
        return Employee::where('department_id', $departmentId)->get();
    }

    public function searchEmployees($query)
    {
        return Employee::where('name', 'like', "%$query%")
                        ->orWhere('email', 'like', "%$query%")
                        ->get();
    }

    public function getEmployeeCount()
    {
        return Employee::count();
    }

    public function getEmployeesWithPagination($perPage = 15)
    {
        return Employee::with('department')->paginate($perPage);
    }

    public function getEmployeesByRole($role)
    {
        return Employee::where('role', $role)->get();
    }

     public function getDepartmentIdByName($name)
    {
        $department = Department::where('name', $name)->first();
        return $department ? $department->id : null;
    }
    public function getEmployeesByStatus($status)
    {
        return Employee::where('status', $status)->get();
    }

    public function getEmployeesByHireDate($startDate, $endDate)
    {
        return Employee::whereBetween('hire_date', [$startDate, $endDate])->get();
    }

    public function updateEmployeeStatus(Employee $employee, $status)
    {
        $employee->status = $status;
        $employee->save();
        return $employee;
    }

    public function assignEmployeeToDepartment(Employee $employee, $departmentId)
    {
        $employee->department_id = $departmentId;
        $employee->save();
        return $employee;
    }

    public function getEmployeesByManager($managerId)
    {
        return Employee::where('manager_id', $managerId)->get();
    }

    public function getEmployeesWithSalaryAbove($amount)
    {
        return Employee::where('salary', '>', $amount)->get();
    }

    public function getEmployeesWithSalaryBelow($amount)
    {
        return Employee::where('salary', '<', $amount)->get();
    }

    public function getEmployeesByLocation($location)
    {
        return Employee::where('location', $location)->get();
    }

    public  function updateEmployeeSalary(Employee $employee, $salary)
    {
        $employee->salary = $salary;
        $employee->save();
        return $employee;
    }

    public function exportEmployeesToCSV()
    {
        $employees = Employee::all();
        $csvData = "ID,Name,Email,Department,Role,Status\n";
        foreach ($employees as $employee) {
            $csvData .= "{$employee->id},{$employee->name},{$employee->email},{$employee->department->name},{$employee->role},{$employee->status}\n";
        }
        return $csvData;
    }

    public function importEmployeesFromCSV($csvData)
    {
        $lines = explode("\n", $csvData);
        foreach ($lines as $line) {
            $data = str_getcsv($line);
            if (count($data) === 6) {
                Employee::create([
                    'name' => $data[0],
                    'email' => $data[1],
                    'department_id' => $this->getDepartmentIdByName($data[2]),
                    'role' => $data[3],
                    'status' => $data[4],
                ]);
            }
        }
    }
}