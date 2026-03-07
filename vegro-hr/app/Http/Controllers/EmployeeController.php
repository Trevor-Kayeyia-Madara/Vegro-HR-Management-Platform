<?php

namespace App\Http\Controllers;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Resources\EmployeeResource;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }
    public function store(Request $request)
    {
        return ApiResponse::success($this->employeeService->createEmployee($request->all()), "Employee created successfully", 201);
    }

    public function index()
    {
        return ApiResponse::success(EmployeeResource::collection($this->employeeService->getAllEmployees()));   
    }

    public function show($id)
    {
        return ApiResponse::success(new EmployeeResource($this->employeeService->getEmployeeById($id)));
    }

    public function update(Request $request, $id)
    {
        return ApiResponse::success($this->employeeService->updateEmployee($id, $request->all()));
    }

    public function destroy($id)
    {
        return ApiResponse::success($this->employeeService->deleteEmployee($id));
    }

    public function getEmployeeByEmail($email)
    {
        return ApiResponse::success($this->employeeService->getEmployeeByEmail($email));
    }

    public function getEmployeesByDepartment($departmentId)
    {
        return ApiResponse::success(EmployeeResource::collection($this->employeeService->getEmployeesByDepartment($departmentId)));
    }
}