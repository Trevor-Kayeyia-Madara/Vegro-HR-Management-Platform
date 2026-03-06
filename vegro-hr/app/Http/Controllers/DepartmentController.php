<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name'=>'required|unique:departments']);
        return Department::create($validated);
    }

    public function show(Department $department)
    {
        return $department;
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate(['name'=>'required|unique:departments,name,'.$department->id]);
        $department->update($validated);
        return $department;
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return response()->noContent();
    }
}