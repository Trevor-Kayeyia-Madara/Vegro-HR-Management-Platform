<?php

namespace App\Repositories;
use App\Models\Payslip;

class PayslipRepository
{
    public function getAllPayslips()
    {
        return Payslip::with(['payroll.employee', 'employee'])->get();
    }

    public function getPayslipsPaginated($perPage = 15)
    {
        return Payslip::with(['payroll.employee', 'employee'])->paginate($perPage);
    }

    public function getPayslipById($id)
    {
        return Payslip::with(['payroll.employee', 'employee'])->findOrFail($id);
    }

    public function createPayslip($data)
    {
        $payslip = Payslip::create($data);
        return $payslip->load(['payroll.employee', 'employee']);
    }

    public function updatePayslip($id, $data)
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->update($data);
        return $payslip->load(['payroll.employee', 'employee']);
    }

    public function approvePayslip($id, $data)
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->update($data);
        return $payslip->load(['payroll.employee', 'employee']);
    }

    public function issuePayslip($id, $data)
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->update($data);
        return $payslip->load(['payroll.employee', 'employee']);
    }

    public function deletePayslip($id)
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->delete();
        return true;
    }

    public function getPayslipsByEmployee($employeeId)
    {
        return Payslip::with(['payroll.employee', 'employee'])
            ->where('employee_id', $employeeId)
            ->get();
    }   

    public function getPayslipsByEmployeePaginated($employeeId, $perPage = 15)
    {
        return Payslip::with(['payroll.employee', 'employee'])
            ->where('employee_id', $employeeId)
            ->paginate($perPage);
    }

    public function exportPayslipsToCSV()
    {
        $payslips = Payslip::with(['payroll.employee', 'employee'])->get();
        $csvData = "Employee Name,Employee Number,Period Start,Period End,Gross Pay,Total Deductions,Net Pay,Status\n";

        foreach ($payslips as $payslip) {
            $employeeName = $payslip->employee_name ?? $payslip->payroll?->employee?->name ?? '';
            $employeeNumber = $payslip->employee_number ?? $payslip->payroll?->employee?->employee_number ?? '';
            $periodStart = $payslip->pay_period_start ?? '';
            $periodEnd = $payslip->pay_period_end ?? '';
            $grossPay = $payslip->gross_pay ?? '';
            $totalDeductions = $payslip->total_deductions ?? '';
            $netPay = $payslip->net_pay ?? $payslip->payroll?->net_salary ?? '';
            $status = $payslip->status ?? '';
            $csvData .= "{$employeeName},{$employeeNumber},{$periodStart},{$periodEnd},{$grossPay},{$totalDeductions},{$netPay},{$status}\n";
        }

        return $csvData;
    }
}
