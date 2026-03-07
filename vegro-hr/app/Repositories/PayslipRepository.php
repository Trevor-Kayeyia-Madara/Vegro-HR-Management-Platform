<?php

namespace App\Repositories;
use App\Models\Payslip;
use App\Models\Employee;

class PayslipRepository
{
    public function getAllPayslips()
    {
        return Payslip::with('employee')->get();
    }

    public function getPayslipById($id)
    {
        return Payslip::with('employee')->findOrFail($id);
    }

    public function createPayslip($data)
    {
        return Payslip::create($data);
    }

    public function updatePayslip($id, $data)
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->update($data);
        return $payslip;
    }

    public function deletePayslip($id)
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->delete();
        return true;
    }

    public function getPayslipsByEmployee($employeeId)
    {
        return Payslip::where('employee_id', $employeeId)->get();
    }   

    public function exportPayslipsToCSV()
    {
        $payslips = Payslip::with('employee')->get();
        $csvData = "Employee Name,Month,Year,Net Salary\n";

        foreach ($payslips as $payslip) {
            $csvData .= "{$payslip->employee->name},{$payslip->month},{$payslip->year},{$payslip->net_salary}\n";
        }

        return $csvData;
    }
}