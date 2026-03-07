<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::all();
        return ApiResponse::success(Department::all(), "Departments retrieved successfully");
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name'=>'required|unique:departments']);
        return Department::create($validated);
        return ApiResponse::success(Department::create($validated), "Department created successfully", 201);        
    }

    public function show(Department $department)
    {
        return $department;
        return ApiResponse::success($department, "Department retrieved successfully");
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate(['name'=>'required|unique:departments,name,'.$department->id]);
        $department->update($validated);
        return $department;
        return ApiResponse::success($department, "Department updated successfully");
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return response()->noContent();
        return ApiResponse::success(null, "Department deleted successfully");
    }
}