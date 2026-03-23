<?php

namespace App\Repositories;
use App\Models\Payslip;
use App\Helpers\CsvHelper;

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
        $csvData = CsvHelper::row([
            'employee_name',
            'employee_number',
            'period_start',
            'period_end',
            'gross_pay',
            'total_deductions',
            'net_pay',
            'status',
        ]);

        foreach ($payslips as $payslip) {
            $employeeName = $payslip->employee_name ?? $payslip->payroll?->employee?->name ?? '';
            $employeeNumber = $payslip->employee_number ?? $payslip->payroll?->employee?->employee_number ?? '';
            $periodStart = CsvHelper::formatDate($payslip->pay_period_start);
            $periodEnd = CsvHelper::formatDate($payslip->pay_period_end);
            $grossPay = $payslip->gross_pay ?? '';
            $totalDeductions = $payslip->total_deductions ?? '';
            $netPay = $payslip->net_pay ?? $payslip->payroll?->net_salary ?? '';
            $status = $payslip->status ?? '';
            $csvData .= CsvHelper::row([
                $employeeName,
                $employeeNumber,
                $periodStart,
                $periodEnd,
                $grossPay,
                $totalDeductions,
                $netPay,
                $status,
            ]);
        }

        return $csvData;
    }
}
