<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\AuditsModelChanges;

class Payroll extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use AuditsModelChanges;

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
        'net_salary',
        'status',
        'approved_by',
        'approved_at',
        'approver_signature_name',
        'approver_signature_at',
        'approver_signature_ip',
        'approver_signature_user_agent',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'approver_signature_at' => 'datetime',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
