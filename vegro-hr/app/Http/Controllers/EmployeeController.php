<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index() { return Employee::all(); }
    public function store(Request $request) {
        $validated = $request->validate([
            'employee_number'=>'required|unique:employees',
            'name'=>'required',
            'email'=>'required|email|unique:employees',
            'department_id'=>'nullable|exists:departments,id'
        ]);
        return Employee::create($validated);
    }
    public function show(Employee $employee){ return $employee; }
    public function update(Request $request, Employee $employee){
        $validated = $request->validate([
            'employee_number'=>'required|unique:employees,employee_number,'.$employee->id,
            'email'=>'required|email|unique:employees,email,'.$employee->id
        ]);
        $employee->update($validated);
        return $employee;
    }
    public function destroy(Employee $employee){ $employee->delete(); return response()->noContent(); }
}