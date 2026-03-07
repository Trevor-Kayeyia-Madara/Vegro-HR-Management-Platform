<?php

namespace App\Http\Controllers;

use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    // List all payrolls
    public function index()
    {
        return $this->payrollService->getAllPayrolls();
    }

    public function update(Request $request, $payroll)
    {
        $validated = $request->validate([
            'employee_id' => 'sometimes|required|exists:employees,id',
            'amount' => 'sometimes|required|numeric',
            'pay_date' => 'sometimes|required|date',
        ]);

        return $this->payrollService->updatePayroll($payroll, $validated);
    }
}