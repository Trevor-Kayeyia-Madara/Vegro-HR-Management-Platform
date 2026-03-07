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

    public function store(Request $request)
    {
        return $this->employeeService->createEmployee($request->all());
    }
}