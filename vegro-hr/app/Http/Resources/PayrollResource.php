<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->employee ? new EmployeeResource($this->employee) : null,
            'tax_profile_id' => $this->tax_profile_id,
            'month' => $this->month,
            'year' => $this->year,
            'basic_salary' => $this->basic_salary,
            'allowances' => $this->allowances,
            'gross_salary' => $this->gross_salary,
            'nssf' => $this->nssf,
            'shif' => $this->shif,
            'housing_levy' => $this->housing_levy,
            'taxable_income' => $this->taxable_income,
            'paye' => $this->paye,
            'tax_rate' => $this->tax_rate,
            'personal_relief' => $this->personal_relief,
            'insurance_premium' => $this->insurance_premium,
            'insurance_relief' => $this->insurance_relief,
            'pension_contribution' => $this->pension_contribution,
            'mortgage_interest' => $this->mortgage_interest,
            'deductions' => $this->deductions,
            'tax' => $this->tax,
            'net_salary' => $this->net_salary,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'approver_signature_name' => $this->approver_signature_name,
            'approver_signature_at' => $this->approver_signature_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
