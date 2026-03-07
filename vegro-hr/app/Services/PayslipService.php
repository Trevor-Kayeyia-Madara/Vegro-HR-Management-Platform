<?php

namespace App\Services;
use App\Repositories\PayslipRepository;

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

    public function getPayslipById($id)
    {
        return $this->payslipRepository->getPayslipById($id);
    }

    public function createPayslip($data)
    {
        return $this->payslipRepository->createPayslip($data);
    }

    public function updatePayslip($id, $data)
    {
        return $this->payslipRepository->updatePayslip($id, $data);
    }

    public function deletePayslip($id)
    {
        return $this->payslipRepository->deletePayslip($id);
    }

    public function getPayslipsByEmployee($employeeId)
    {
        return $this->payslipRepository->getPayslipsByEmployee($employeeId);
    }   
}