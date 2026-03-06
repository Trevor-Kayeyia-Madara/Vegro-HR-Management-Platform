<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\Payroll;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    // List all payslips
    public function index()
    {
        return Payslip::with('payroll.employee')->get();
    }

    // Create payslip
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'pdf_path' => 'required|string'
        ]);

        $validated['generated_at'] = now();

        return Payslip::create($validated);
    }

    // Show payslip
    public function show(Payslip $payslip)
    {
        return $payslip->load('payroll.employee');
    }

    // Update payslip (maybe replace PDF)
    public function update(Request $request, Payslip $payslip)
    {
        $validated = $request->validate([
            'pdf_path' => 'required|string'
        ]);

        $validated['generated_at'] = now();

        $payslip->update($validated);
        return $payslip;
    }

    // Delete payslip
    public function destroy(Payslip $payslip)
    {
        $payslip->delete();
        return response()->noContent();
    }
}