<?php

namespace App\Http\Controllers;
use App\Services\PayslipService;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    protected $payslipService;

    public function __construct(PayslipService $payslipService)
    {
        $this->payslipService = $payslipService;
    }

    public function index()
    {
        return response()->json($this->payslipService->getAllPayslips());
    }

    public function show($id)
    {
        return response()->json($this->payslipService->getPayslipById($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'amount' => 'required|numeric',
            'pay_date' => 'required|date',
        ]);

        return response()->json($this->payslipService->createPayslip($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'employee_id' => 'integer',
            'amount' => 'numeric',
            'pay_date' => 'date',
        ]);

        return response()->json($this->payslipService->updatePayslip($id, $data));
    }

    public function destroy($id)
    {
        $this->payslipService->deletePayslip($id);
        return response()->json(null, 204);
    }

    public function exportToCSV()
    {
        return $this->payslipService->exportPayslipsToCSV();
    }
}