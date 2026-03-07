<?php

namespace App\Services;

use App\Models\Payroll;

class PayrollService
{
    public function calculateNetSalary($basic, $allowances, $deductions, $tax)
    {
        return $basic + $allowances - $deductions - $tax;
    }

    public function createPayroll(array $data)
    {
        $data['net_salary'] = $this->calculateNetSalary(
            $data['basic_salary'],
            $data['allowances'] ?? 0,
            $data['deductions'] ?? 0,
            $data['tax'] ?? 0
        );

        return Payroll::create($data);
    }

    public function getAllPayrolls()
    {
        return Payroll::with('employee')->get();
    }   
    public function updatePayroll(Payroll $payroll, array $data)
    {
        $basic = $data['basic_salary'] ?? $payroll->basic_salary;
        $allowances = $data['allowances'] ?? $payroll->allowances;
        $deductions = $data['deductions'] ?? $payroll->deductions;
        $tax = $data['tax'] ?? $payroll->tax;

        $data['net_salary'] = $this->calculateNetSalary(
            $basic,
            $allowances,
            $deductions,
            $tax
        );

        $payroll->update($data);

        return $payroll;
    }
}