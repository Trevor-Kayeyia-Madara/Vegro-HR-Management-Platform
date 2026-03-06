<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Employee;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    // List all payrolls
    public function index()
    {
        return Payroll::with('employee')->get();
    }

    // Create a payroll
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'basic_salary' => 'required|numeric',
            'allowances' => 'numeric|nullable',
            'deductions' => 'numeric|nullable',
            'tax' => 'numeric|nullable'
        ]);

        // Calculate net salary
        $validated['net_salary'] = $validated['basic_salary'] 
            + ($validated['allowances'] ?? 0) 
            - ($validated['deductions'] ?? 0) 
            - ($validated['tax'] ?? 0);

        $payroll = Payroll::create($validated);

        return response()->json($payroll, 201);
    }

    // Show a payroll
    public function show(Payroll $payroll)
    {
        return $payroll->load('employee', 'payslip');
    }

    // Update payroll
    public function update(Request $request, Payroll $payroll)
    {
        $validated = $request->validate([
            'basic_salary' => 'numeric|nullable',
            'allowances' => 'numeric|nullable',
            'deductions' => 'numeric|nullable',
            'tax' => 'numeric|nullable',
        ]);

        // Keep existing values if not provided
        $basic = $validated['basic_salary'] ?? $payroll->basic_salary;
        $allowances = $validated['allowances'] ?? $payroll->allowances;
        $deductions = $validated['deductions'] ?? $payroll->deductions;
        $tax = $validated['tax'] ?? $payroll->tax;

        $payroll->update([
            'basic_salary' => $basic,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'tax' => $tax,
            'net_salary' => $basic + $allowances - $deductions - $tax
        ]);

        return $payroll;
    }

    // Delete payroll
    public function destroy(Payroll $payroll)
    {
        $payroll->delete();
        return response()->noContent();
    }
}