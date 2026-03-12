<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Payroll extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'tax_profile_id',
        'month',
        'year',
        'basic_salary',
        'allowances',
        'gross_salary',
        'nssf',
        'shif',
        'housing_levy',
        'taxable_income',
        'paye',
        'tax_rate',
        'personal_relief',
        'insurance_premium',
        'insurance_relief',
        'pension_contribution',
        'mortgage_interest',
        'deductions',
        'tax',
        'net_salary'
    ];

    // Relationship: Payroll belongs to Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function taxProfile()
    {
        return $this->belongsTo(TaxProfile::class);
    }

    // Relationship: Payroll has one Payslip
    public function payslip()
    {
        return $this->hasOne(Payslip::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
