<?php

namespace App\Repositories;
use App\Models\Payroll;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class PayrollRepository
{
    public function getAllPayrolls()
    {
        return Payroll::with('employee')->get();
    }

    public function getPayrollById($id)
    {
        return Payroll::with('employee')->findOrFail($id);
    }

    public function createPayroll($data)
    {
        return Payroll::create($data);
    }

    public function updatePayroll($id, $data)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->update($data);
        return $payroll;
    }

    public function deletePayroll($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->delete();
        return true;
    }

    public function calculatePayroll($employeeId, $hoursWorked, $hourlyRate)
    {
        $employee = Employee::findOrFail($employeeId);
        $grossPay = $hoursWorked * $hourlyRate;
        $tax = $grossPay * 0.16; // Assuming a flat tax rate of 16%
        $netPay = $grossPay - $tax;

        return [
            'employee' => $employee,
            'gross_pay' => $grossPay,
            'tax' => $tax,
            'net_pay' => $netPay,
        ];
    }

    public function getPayrollsByEmployee($employeeId)
    {
        return Payroll::where('employee_id', $employeeId)->get();
    }   

    public function getTotalPayrollCost()
    {
        return Payroll::sum('net_pay');
    }

    public function getPayrollsByDateRange($startDate, $endDate)
    {
        return Payroll::whereBetween('created_at', [$startDate, $endDate])->get();
    }

    public function getAveragePayroll()
    {
        return Payroll::avg('net_pay');
    }

    public function getPayrollsWithEmployeeDetails()
    {
        return Payroll::with('employee')->get();
    }

    public function getPayrollsByDepartment($departmentId)
    {
        return Payroll::whereHas('employee', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })->get();
    }

    public function getPayrollsByMonth($month, $year)
    {
        return Payroll::whereMonth('created_at', $month)
                      ->whereYear('created_at', $year)
                      ->get();
    }

    public function getPayrollsByStatus($status)
    {
        return Payroll::where('status', $status)->get();
    }

    public function getPayrollsWithPayslips()
    {
        return Payroll::with('payslip')->get();
    }
    
    public function getPayrollsWithEmployeeAndPayslip()
    {
        return Payroll::with(['employee', 'payslip'])->get();
    }

    public function getPayrollsWithEmployeeDepartment()
    {
        return Payroll::with('employee.department')->get();
    }
}
