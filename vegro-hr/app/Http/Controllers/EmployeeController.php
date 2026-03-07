<?php

namespace App\Http\Controllers;

use App\Services\EmployeeService;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index()
    {
        return $this->employeeService->getAllEmployees();
    }

    public function show($id)
    {
        return $this->employeeService->getEmployeeById($id);
    }

    public function store(Request $request)
    {
        return $this->employeeService->createEmployee($request->all());
    }
    
   public function update(Request $request, $id)
    {
        $employee = $this->employeeService->getEmployeeById($id);
        return $this->employeeService->updateEmployee($employee, $request->all());
    }

    public function destroy($id)
    {
        $employee = $this->employeeService->getEmployeeById($id);
        return $this->employeeService->deleteEmployee($employee);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        return $this->employeeService->searchEmployees($query);
    }

    public function count()
    {
        return $this->employeeService->getEmployeeCount();
    }

    public function paginate(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        return $this->employeeService->getEmployeesWithPagination($perPage);
    }

    public function byDepartment($departmentId)
    {
        return $this->employeeService->getEmployeesByDepartment($departmentId);
    }

    public function byRole($role)
    {
        return $this->employeeService->getEmployeesByRole($role);
    }

    public function byStatus($status)
    {
        return $this->employeeService->getEmployeesByStatus($status);
    }
    
    public function departmentIdByName($name)
    {
        return $this->employeeService->getDepartmentIdByName($name);
    }

    public function export()
    {
        return $this->employeeService->exportEmployeesToCSV();
    }

    public function import(Request $request)
    {
        $file = $request->file('file');
        return $this->employeeService->importEmployeesFromCSV($file);
    }
}