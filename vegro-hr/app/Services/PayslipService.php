<?php

namespace App\Services;
use App\Repositories\PayslipRepository;
use App\Models\Payroll;
use Carbon\Carbon;

class PayslipService
{
    protected $payslipRepository;

    public function __construct(PayslipRepository $payslipRepository)
    {
        $this->payslipRepository = $payslipRepository;
    }

    public function getAllPayslips()
    {
        return $this->payslipRepository->getAllPayslips();
    }

    public function getPayslipsPaginated($perPage = 15)
    {
        return $this->payslipRepository->getPayslipsPaginated($perPage);
    }

    public function getPayslipById($id)
    {
        return $this->payslipRepository->getPayslipById($id);
    }

    public function createPayslip($data)
    {
        $payrollId = $data['payroll_id'] ?? null;
        if (!$payrollId) {
            throw new \InvalidArgumentException('Payroll ID is required.');
        }

        $payroll = Payroll::with('employee')->findOrFail($payrollId);
        if ($payroll->payslip) {
            return $this->syncPayslipForPayroll($payroll->load('payslip', 'employee'));
        }
        $payload = $this->buildPayslipPayload($payroll, $data);
        return $this->payslipRepository->createPayslip($payload);
    }

    public function syncPayslipForPayroll(Payroll $payroll)
    {
        $payslip = $payroll->payslip;
        if (!$payslip || $payslip->status !== 'draft') {
            return $payslip;
        }
        $payload = $this->buildPayslipPayload($payroll, [
            'pdf_path' => $payslip->pdf_path,
            'status' => $payslip->status,
            'generated_at' => $payslip->generated_at ?? now(),
        ]);

        return $this->payslipRepository->updatePayslip($payslip->id, $payload);
    }

    public function updatePayslip($id, $data)
    {
        return $this->payslipRepository->updatePayslip($id, $data);
    }

    public function approvePayslip($id, $userId)
    {
        $payslip = $this->payslipRepository->getPayslipById($id);
        if ($payslip->status === 'issued') {
            return $payslip;
        }
        $payload = [
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ];
        return $this->payslipRepository->approvePayslip($id, $payload);
    }

    public function issuePayslip($id)
    {
        $payslip = $this->payslipRepository->getPayslipById($id);
        if ($payslip->status !== 'approved') {
            return $payslip;
        }
        $payload = [
            'status' => 'issued',
            'issued_at' => now(),
        ];
        return $this->payslipRepository->issuePayslip($id, $payload);
    }

    public function deletePayslip($id)
    {
        return $this->payslipRepository->deletePayslip($id);
    }

    public function getPayslipsByEmployee($employeeId)
    {
        return $this->payslipRepository->getPayslipsByEmployee($employeeId);
    }  

    public function getPayslipsByEmployeePaginated($employeeId, $perPage = 15)
    {
        return $this->payslipRepository->getPayslipsByEmployeePaginated($employeeId, $perPage);
    }
    
    public function exportPayslipsToCSV()
    {
        return $this->payslipRepository->exportPayslipsToCSV();
    }

    protected function buildPayslipPayload(Payroll $payroll, array $overrides = []): array
    {
        $employee = $payroll->employee;
        $month = (int) $payroll->month;
        $year = (int) $payroll->year;

        $periodStart = ($month && $year)
            ? Carbon::create($year, $month, 1)->startOfMonth()->toDateString()
            : null;
        $periodEnd = ($month && $year)
            ? Carbon::create($year, $month, 1)->endOfMonth()->toDateString()
            : null;

        $basicSalary = (float) ($payroll->basic_salary ?? 0);
        $allowances = (float) ($payroll->allowances ?? 0);
        $grossSalary = (float) ($payroll->gross_salary ?? 0);
        $grossSalary = $grossSalary > 0 ? $grossSalary : $basicSalary + $allowances;

        $deductionValues = [
            'nssf' => (float) ($payroll->nssf ?? 0),
            'shif' => (float) ($payroll->shif ?? 0),
            'housing_levy' => (float) ($payroll->housing_levy ?? 0),
            'paye' => (float) ($payroll->paye ?? 0),
            'tax' => (float) ($payroll->tax ?? 0),
            'other_deductions' => (float) ($payroll->deductions ?? 0),
            'pension_contribution' => (float) ($payroll->pension_contribution ?? 0),
            'mortgage_interest' => (float) ($payroll->mortgage_interest ?? 0),
            'insurance_premium' => (float) ($payroll->insurance_premium ?? 0),
        ];

        $totalDeductions = array_sum($deductionValues);

        $earnings = [
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'gross_salary' => $grossSalary,
        ];

        $reliefs = [
            'personal_relief' => (float) ($payroll->personal_relief ?? 0),
            'insurance_relief' => (float) ($payroll->insurance_relief ?? 0),
        ];

        return array_merge([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee?->id,
            'employee_name' => $employee?->name,
            'employee_email' => $employee?->email,
            'employee_number' => $employee?->employee_number,
            'pay_period_start' => $periodStart,
            'pay_period_end' => $periodEnd,
            'gross_pay' => $grossSalary,
            'total_deductions' => $totalDeductions,
            'net_pay' => (float) ($payroll->net_salary ?? 0),
            'earnings_breakdown' => $earnings,
            'deductions_breakdown' => [
                'statutory' => $deductionValues,
                'reliefs' => $reliefs,
                'tax_rate' => (float) ($payroll->tax_rate ?? 0),
                'taxable_income' => (float) ($payroll->taxable_income ?? 0),
            ],
            'status' => $overrides['status'] ?? 'draft',
            'pdf_path' => $overrides['pdf_path'] ?? null,
            'generated_at' => $overrides['generated_at'] ?? now(),
        ], $overrides);
    }
}
