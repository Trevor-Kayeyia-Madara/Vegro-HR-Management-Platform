<?php

namespace App\Repositories;
use App\Models\Payslip;

class PayslipRepository
{
    public function getAllPayslips()
    {
        return Payslip::with('payroll.employee')->get();
    }

    public function getPayslipById($id)
    {
        return Payslip::with('payroll.employee')->findOrFail($id);
    }

    public function createPayslip($data)
    {
        $payslip = Payslip::create($data);
        return $payslip->load('payroll.employee');
    }

    public function updatePayslip($id, $data)
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->update($data);
        return $payslip->load('payroll.employee');
    }

    public function deletePayslip($id)
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->delete();
        return true;
    }

    public function getPayslipsByEmployee($employeeId)
    {
        return Payslip::with('payroll.employee')
            ->whereHas('payroll', function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->get();
    }   

    public function exportPayslipsToCSV()
    {
        $payslips = Payslip::with('payroll.employee')->get();
        $csvData = "Employee Name,Month,Year,Net Salary\n";

        foreach ($payslips as $payslip) {
            $employeeName = $payslip->payroll?->employee?->name ?? '';
            $month = $payslip->payroll?->month ?? '';
            $year = $payslip->payroll?->year ?? '';
            $netSalary = $payslip->payroll?->net_salary ?? '';
            $csvData .= "{$employeeName},{$month},{$year},{$netSalary}\n";
        }

        return $csvData;
    }
}
