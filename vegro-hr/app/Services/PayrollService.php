<?php

namespace App\Services;

use App\Repositories\PayrollRepository;

class PayrollService
{
    protected $payrollRepository;

    public function __construct(PayrollRepository $payrollRepository)
    {
        $this->payrollRepository = $payrollRepository;
    }

    public function getAllPayrolls()
    {
        return $this->payrollRepository->getAllPayrolls();
    }

    public function getPayrollById($id)
    {
        return $this->payrollRepository->getPayrollById($id);
    }

    public function createPayroll($data)
    {
        return $this->payrollRepository->createPayroll($data);
    }

    public function updatePayroll($id, $data)
    {
        return $this->payrollRepository->updatePayroll($id, $data);
    }

    public function deletePayroll($id)
    {
        return $this->payrollRepository->deletePayroll($id);
    }

    public function calculatePayroll($employeeId, $hoursWorked, $hourlyRate)
    {
        return $this->payrollRepository->calculatePayroll($employeeId, $hoursWorked, $hourlyRate);
    }

    public function getPayrollsByEmployee($employeeId)
    {
        return $this->payrollRepository->getPayrollsByEmployee($employeeId);
    }   

    public function getTotalPayrollCost()
    {
        return $this->payrollRepository->getTotalPayrollCost();
    }
}